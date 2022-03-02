<?php
require_once "Objects/Database.php";
require_once "Objects/User.php";
require_once "Objects/BallotMaker.php";
require_once "Objects/HTML.php";
require_once "Objects/Group.php";

class BallotEditor {
	public static function page(){
		$user = new User();
		if (isset($_POST['submit_switch'])){
			if (isset($_POST['current_ballot'])){
				if ($_POST['current_ballot'] == "room_ballot") $ballot = new BallotMaker(0);
				else $ballot = new BallotMaker(1);
			}
			else throw new Exception("Unable to switch ballot");
		}
		else $ballot = new BallotMaker();
		if(!$user->isadmin()) {
			HTML::HTMLerror("You do not have admin permission");
			return;
		}
		elseif ($ballot->getStage() == 6){
			HTML::HTMLerror("The previous ballot has been closed. The ballot editor is only available when a ballot is currently in process");
			return;
		}
		else {
			if ($ballot->getStage() == 0) $ballot->showRemainingGroups();
			if (isset($_POST['submit_remove'])){
				$selected = 0;
				if (isset($_POST['select_user'])) $selected = 1;
				if (isset($_POST['select_group'])) $selected += 2;
				if ($selected == 1){
					$errors = 0;
					foreach ($_POST['select_user'] as $selected_user){
						if (!$errors){
							if ($selected_user == "") $errors = 1;
							else{
								$temp_user = new User($selected_user);
								$temp_group = new Group($temp_user->getGroup(), $temp_user->getBallot());
								if (!$temp_user->destroy_user()) $errors = 1;
							}
						}
					}
					if (!$errors) HTML::HTMLsuccess("Selected users removed from ballot");
					else HTML::HTMLerror("An error occured removing a user from the ballot, please contact jcr.website@fitz.cam.ac.uk");
				}
				elseif ($selected == 2){
					$errors = 0;
					foreach($_POST['select_group'] as $selected_group){
						if (!$errors){
							if ($selected_group == "") $errors = 1;
							else {
								$group = new Group($selected_group, $ballot->getName());
								$remove_members = $group->getMembers();
								array_shift($remove_members);
								if (!$group->remove_members($remove_members)) $errors = 1;
							}
						}
					}
					if (!$errors) HTML::HTMLsuccess("Selected group removed from ballot");
					else HTML::HTMLerror("Failed to remove group from ballot");
				}
				else HTML::HTMLerror("Please select either users or groups to be removed from the ballot at a time");
			}
			if (isset($_POST['submit_position'])){
				$errors = 0;
				if (!isset($_POST['new_position']) || $_POST['new_position'] == ""){
					$errors = 1;
					HTML::HTMLerror("You must enter a new position for the group to recieve");
				}
				elseif (HTML::Integerchecker($_POST['new_position'])){
					$errors = 1;
					HTML::HTMLerror("");
				}
				if (!isset($_POST['select_group']) || $_POST['select_group'] == "" || count($_POST['select_group']) != 1){
					$errors = 1;
					HTML::HTMLerror("You must select one group to change its position");
				}
				if (!$errors){
					$group = new Group($_POST['select_group'][0]);
					if ($_POST['new_position'] == $group->getOrder()) HTML::HTMLerror("A different position needs to be entered for it to be changed");
					else{
						if ($group->newPosition($_POST['new_position'])) HTML::HTMLsuccess("Position of group adjusted");
						else HTML::HTMLerror("Failed to alter group's position");
					}
				}
			}
			if (isset($_POST['submit_swap'])){
				if(isset($_POST['select_user'])){
					$user_room = array();
					$user_1 = null;
					foreach($_POST['select_user'] as $selected_user){
						if ($user_1 == null) $user_1 = $selected_user;
						else $user_2 = $selected_user;
						$temp_user = new User($selected_user);
						$user_room[$temp_user->getCRSID()] = $temp_user->getRoom();
					}
					if (count($user_room) != 2) HTML::HTMLerror("You can only select two users from the table below to swap their position");
					else{
						Database::getInstance()->query("begin");
						$query = "UPDATE `users` SET `room` = ".$user_room[$user_1]." WHERE `crsid` = '".$user_2."'";
						$result = Database::getInstance()->query($query);
						if ($result){
							$query = "UPDATE `users` SET `room` = ".$user_room[$user_2]." WHERE `crsid` = '".$user_1."'";
							$result = Database::getInstance()->query($query);
							if ($result){
								Database::getInstance()->query("commit");
								HTML::HTMLsuccess("Successfully swapped users rooms");
							}
							else {
								Database::getInstance()->query("rollback");
								HTML::HTMLerror("An error occured swapping the users rooms, please contact jcr.website@fitz.cam.ac.uk");
							}
						}
						else {
							Database::getInstance()->query("rollback");
							HTML::HTMLerror("An error occured swapping the users rooms, please contact jcr.website@fitz.cam.ac.uk");
						}
					}
				}
				else HTML::HTMLerror("You must select two users from the table below to swap their rooms");
			}
			?>
	<div class = "container">
	<form method="POST">
	<input type = "submit" name = "submit_switch" value = "Switch to the other ballot"><input type = "hidden" name = "current_ballot" value = "<?= $ballot->getName() ?>">
	<p>To remove any users or groups from the ballot, select them in the table below and press:
	<input type = "submit" name = "submit_remove" value = "Remove"></p>
		<?if ($ballot->getStage() == 1 || $ballot->getStage() == 4){?>
	<p>To change a group's position in the ballot, select them from the table below and enter their new position: 
	<input type = "text" name = "new_position" maxlength = "3">
	<input type = "submit" name = "submit_postion" value = "Update balloting position"></p>
<?		}?>
		<?if ($ballot->getStage() == 2 || $ballot->getStage() == 5){?>
	<p>To swap two users rooms, select them both from the table below: 
	<input type = "submit" name = "submit_swap" value = "Swap Rooms"></p>
<?		}?>
	<p>Ballot Seed: <strong>
<?		if ($ballot->getSeed() == NULL || $ballot->getSeed() == "") echo("TBC");
		else echo($ballot->getSeed());?>
  </strong></p>
  <table class="table table-condensed table-bordered table-hover">
  <thead>
  <tr>
  <td>Position</td>
  <td>Select group</td>
  <td>Groups</td>
  <td>House/Block</td>
  <td>Room</td>
  </tr>
  </thead>
  		<?foreach($ballot->getBallotPriorities() as $ballotPriority => $criteria){
  				$query = "SELECT `group_id` FROM ".$ballot->getName()." WHERE ".$ballot->getCriteriaCol().$criteria." ORDER BY `order`";
  				$result = Database::getInstance()->query($query);
  				if ($result){?>
  <tr><td colspan="5"><h3><?= $ballotPriority ?></h3></td></tr>
  					<?while (($row = $result->fetch_assoc())!= false){
  						$group = new Group($row['group_id'], $ballot->getName());?>
  <tr>
  <td rowspan="<?= $group->getSize()?>">
  						<?if ($group->getOrder() == NULL || $group->getOrder() == "") echo("TBC");
  						else echo($group->getOrder());?>
  </td>
  						<?if ($group->getSize() > 1){?>
  <td rowspan="<?= $group->getSize()?>"><input type = "checkbox" name = "select_group[]" value = "<?=$group->getID()?>"></td>
  						<?
  						}
  						else echo("<td></td>");
  						$names = array();
  						$first_member = 1;
  						foreach($group->getMembers() as $crsid){
  							if ($first_member) $first_member = 0;
  							else echo("</tr>");
  							$temp_user = new User($crsid);?>
  <td><input type = "checkbox" name = "select_user[]" value = "<?=$crsid?>"> <?= $temp_user->getName()?></td>
  							<?if ($temp_user->getRoom() == NULL || $temp_user->getRoom() == "") echo("<td>TBC</td><td>TBC</td>");
  							else{
  								$room = new Room($temp_user->getRoom());
  								$house = new House($room->getHouse());?>
  <td><?= $house->getName() ?></td>
  <td><?= $room->getName() ?></td>
  							<?}?>
  </tr>
  						<?}
          			}
       			}
       			else throw new Exception("Failed to retrieve ballot data");
  		}?>
   </table>
   </form>
   </div>  		
<?  }
	}
}