<?php
require_once "Objects/Database.php";
require_once "Objects/User.php";
require_once "Objects/BallotMaker.php";
require_once "Objects/HTML.php";
require_once "Objects/AccessArrangement.php";

class ControlPanel {
	public static function page() {
		$user = new User();
		$ballot = new BallotMaker();
		if(!$user->isadmin()) {
			HTML::HTMLerror("You do not have admin permission");
			return;
		}
		else{
			if (isset($_POST['push_ballot'])){
				if (isset($_POST['year']) && $_POST['year'] != ""){
					if (HTML::Integerchecker($_POST['year'])){
						if ($ballot->PushBallot($_POST['year'])){
							HTML::HTMLsuccess("Ballot pushed to next stage");
							$ballot = new BallotMaker();
						}
						else HTML::HTMLerror("Error creating new ballot, please email jcr.website@fitz.cam.ac.uk");
					}
					else HTML::HTMLerror("Please enter a valid year");
				}
				elseif ($ballot->PushBallot()){
					HTML::HTMLsuccess("Ballot pushed to next stage");
					$ballot = new BallotMaker();
				}
				else HTML::HTMLerror("Error pushing ballot to next stage, please email jcr.website@fitz.cam.ac.uk");
			}
			if (isset($_POST['submit_admin'])){
				$selected = 0;
				if (isset($_POST['name']) && isset($_POST['crsid'])  && $_POST['name'] != "" && $_POST['crsid'] != ""){
					$selected = 1;
					if (HTML::Stringchecker($_POST['name']) && HTML::Stringchecker($_POST['crsid'])){
						$query = "INSERT INTO `admin` (`name`, `crsid`) VALUES ('".$_POST['name']."', '".$_POST['crsid']."')";
						$result = Database::getInstance()->query($query);
						if ($result) HTML::HTMLsuccess("New admin added successfully");
						else HTML::HTMLerror("Error adding admin, please email jcr.website@fitz.cam.ac.uk");
					}
					else HTML::HTMLerror("Sorry, you have entered an invalid character or word");
				}
				if (isset($_POST['remove_admin'])){
					$errors = 0;
					foreach ($_POST['remove_admin'] as $remove){
						$query = "DELETE FROM `admin` WHERE `crsid` = '".$remove."'";
						$result = Database::getInstance()->query($query);
						if (!$result) $errors = 1;
					}
					if ($errors) HTML::HTMLerror("Error removing admin, please email jcr.webiste@fitz.cam.ac.uk");
					else HTML::HTMLsuccess("Admin removed successfully");
				}
				elseif (!$selected) HTML::HTMLerror("Please select an admin to remove or provide all details to add one");
			}
			if (isset($_POST['submit_access'])){
				if (isset($_POST['access_name']) && $_POST['access_name'] != ""){
					if (HTML::Stringchecker($_POST['access_name'])){
						$query = "INSERT INTO `access` (`name`) VALUES ('".$_POST['access_name']."')";
						$result = Database::getInstance()->query($query);
						if ($result) HTML::HTMLsuccess("New access arrangement added successfully");
						else HTML::HTMLerror("Error adding access arrangement, please email jcr.website@fitz.cam.ac.uk");
					}
					else HTML::HTMLerror("Sorry, you have entered an invalid character or word");
				}
				else HTML::HTMLerror("You must enter an access arrangement name to add one");
			}
			if (isset($_POST['submit_remove'])){
				if (isset($_POST['select_access']) && $_POST['select_access'] != ""){
					$errors = 0;
					foreach ($_POST['select_access'] as $arrangement){
						if (!$errors){
							if ($arrangement != ""){
								$temp_arrangement = new Access($arrangement);
								if (!$temp_arrangement->remove()) $errors = 1;
							}
							else $errors = 1;
						}
					}
					if (!$errors) HTML::HTMLsuccess("Successfully removed access arrangement(s)");
					else HTML::HTMLerror("Error occured removing access arrangement(s), please email jcr.website@fitz.cam.ac.uk");
				}
				else HTML::HTMLerror("You must select an access arrangement to remove it");
			}
			if (isset($_POST['submit_give'])){
				$errors = 0;
				if (!isset($_POST['give_user']) || $_POST['give_user'] == "") $errors = 1;
				if (!isset($_POST['select_access']))$errors = 1;
				if (!$errors){
					foreach ($_POST['select_access'] as $arrangement){
						if (!$errors){
							if ($arrangement != ""){
								$temp_arrangement = new Access($arrangement);
								if (!$temp_arrangement->add($_POST['give_user'])) $errors = 1;
							}
							else $errors = 1;
						}
					}
					if (!$errors) HTML::HTMLsuccess("Successfully gave user access arrangement(s)");
					else HTML::HTMLerror("Error occured giving user access arrangement(s), please email jcr.website@fitz.cam.ac.uk");
				}
				else HTML::HTMLerror("Please select an access arrangement and a user to give it");
			}
			if (isset($_POST['submit_take'])){
				if (isset($_POST['remove_user']) && $_POST['remove_user'] != "") {
					$errors = 0;
					foreach ($_POST['remove_user'] as $remove_user){
						if (!$errors){
							if ($remove_user != ""){
								$user_id = array();
								$user_id = explode(",", $remove_user);
								$temp_arrangement = new Access($user_id[1]);
								if (!$temp_arrangement->take($user_id[0])) $errors = 1;
							}
							else $errors = 1;
						}
					}
					if (!$errors) HTML::HTMLsuccess("Successfully taken away access arrangement(s) from user");
					else HTML::HTMLerror("Error occured taking access arrangement(s) from user, please email jcr.website@fitz.cam.ac.uk");
				}
				else HTML::HTMLerror("Please select a user within an access arrangement to take it from them");
			}
			if (isset($_POST['draw_seed'])){
				if ($ballot->drawSeed()){
					HTML::HTMLsuccess("Successfully updated balloting seed");
					$ballot = new BallotMaker();
				}
				else HTML::HTMLerror("Error updating balloting seed, please email jcr.website@fitz.cam.ac.uk");
			}
			if (isset($_POST['draw_order'])){
				if ($ballot->drawOrder()){
					HTML::HTMLsuccess("Successfully updated balloting order");
					$ballot = new BallotMaker();
				}
				else HTML::HTMLerror("Error updating balloting order, please email jcr.website@fitz.cam.ac.uk");
			}
			if (isset($_POST['pull_ballot'])){
				$new_stage = $ballot->getStage() - 1;
				$query = "UPDATE `ballot_log` SET `stage` = ".$new_stage." WHERE `year` = ".$ballot->getYear();
				$result = Database::getInstance()->query($query);
				if ($result) {
					HTML::HTMLsuccess("Ballot pulled back to previous stage");
					$ballot = new BallotMaker();
				}
				else HTML::HTMLerror("Error pulling ballot to previous stage, please email jcr.website@fitz.cam.ac.uk");
			}
			if (isset($_POST['submit_key_value'])){
				if (!isset($_POST['value']) || $_POST['value'] == "") HTML::HTMLerror("Please enter a value to add/change a key-value pair");
				elseif (!HTML::Stringchecker($_POST['value'])) HTML::HTMLerror("Sorry, you have entered an invalid character or word in your value");
				else {
					$selected = 0;
					if (isset($_POST['new_key']) && $_POST['new_key'] != "") $selected = 1;
					if (isset($_POST['old_key']) && $_POST['old_key'] != "") $selected += 2;
					if ($selected == 1){
						if (HTML::Stringchecker($_POST['new_key'])){
							$query = "INSERT INTO `key_value` VALUES ('".$_POST['new_key']."', '".$_POST['value']."')";
							$result = Database::getInstance()->query($query);
							if ($result) HTML::HTMLsuccess("successfully added new key-value pair");
							else HTML::HTMLerror("Error occured adding new key-value pair, please contact jcr.website@fitz.cam.ac.uk");
						}
						else HTML::HTMLerror("Sorry, you have entered an invalid character or word in your key");
					}
					elseif ($selected == 2){
						$query = "UPDATE `key_value` SET `value` = '".$_POST['value']."' WHERE `key` = '".$_POST['old_key']."'";
						$result = Database::getInstance()->query($query);
						if ($result) HTML::HTMLsuccess("successfully changed key-value pair");
						else HTML::HTMLerror("Error occured changing key-value pair, please contact jcr.website@fitz.cam.ac.uk");
					}
					else HTML::HTMLerror("Please select either one existing key-value pair or enter a new key");
				}
			}
			if (isset($_POST['prompt'])){
				$query = "SELECT `group_id` FROM `".$ballot->getName()."` WHERE `order` = ".$ballot->getPosition();
				$result = Database::getInstance()->query($query);
				if ($result){
					$temp_group = new Group($result->fetch_assoc()['group_id'], $ballot->getName());
					HTML::sendEmail($temp_group->getAdmin(), "URGENT: Please complete the ballot asap");
					HTML::HTMLsuccess("Successfully sent prompt email");
				}
				else HTML::HTMLerror("An error occured sending prompt, pleas contact jcr.website@fitz.cam.ac.uk");
			}
			if (isset($_POST['proxy'])){
				Database::getInstance()->query("begin");
				$errors = 0;
				$query = "SELECT `group_id` FROM `".$ballot->getName()."` WHERE `order` = ".$ballot->getPosition();
				$result = Database::getInstance()->query($query);
				if ($result){
					$temp_group = new Group($result->fetch_assoc()['group_id'], $ballot->getName());
					$temp_user = new User($temp_group->getAdmin());
					$query = "UPDATE `ballot_log` SET `proxy` = '".$temp_user->getProxy()."' WHERE `year` = ".$ballot->getYear();
					$result = Database::getInstance()->query($query);
					if ($result){
						HTML::sendEmail($temp_user->getProxy(), "URGENT: You have been given proxy access");
						HTML::sendEmail($temp_user->getCRSID(), "URGENT: Your proxy has been given access");
					}
					else $errors = 1;
				}
				else $errors = 1;
				if ($errors){
					Database::getInstance()->query("rollback");
					HTML::HTMLerror("An error occured giving proxy room allocation access, pleas contact jcr.website@fitz.cam.ac.uk");
				}
				else {
					Database::getInstance()->query("commit");
					HTML::HTMLsuccess("Successfully given proxy access to room allocation");
				}
			}
			if (isset($_POST['skip'])){
				$errors = 0;
				$query = "UPDATE `ballot_log` SET `proxy` = NULL WHERE `year` = ".$ballot->getYear();
				$result = Database::getInstance()->query($query);
				if ($result){
					$query = "SELECT `group_id` FROM `".$ballot->getName()."` WHERE `order` = ".$ballot->getPosition();
					$result = Database::getInstance()->query($query);
					if ($result){
						$temp_group = new Group($result->fetch_assoc()['group_id'], $ballot->getName());
						$query = "SELECT `order` FROM `".$ballot->getName()."` ORDER BY `order` desc";
						$result = Database::getInstance()->query($query);
						if ($result){
							if ($temp_group->newPosition($row = $result->fetch_assoc()['order'])){
								HTML::sendEmail($temp_group->getAdmin(), "URGENT: Pushed to end of ballot");
								$query = "SELECT `group_id` FROM `".$ballot->getName()."` WHERE `order` = ".$ballot->getPosition();
								$result = Database::getInstance()->query($query);
								if ($result){
									$temp_group = new Group($result->fetch_assoc()['group_id'], $ballot->getName());
									HTML::sendEmail($temp_group->getAdmin(), "Your turn in the ballot!");
								}
								else $errors = 1;
							}
							else $errors = 1;
						}
						else $errors = 1;
					}
					else $errors = 1;
				}
				else $errors = 1;
				if ($errors){
					Database::getInstance()->query("rollback");
					HTML::HTMLerror("An error occured skipping the current group in the ballot, pleas contact jcr.website@fitz.cam.ac.uk");
				}
				else {
					Database::getInstance()->query("commit");
					HTML::HTMLsuccess("Successfully skipped the current group in the ballot");
				}
			}
			?>
	<div class = "container">
	<h2>Control the Ballot</h2>
	<form method="POST">
	<?		if ($ballot->getStage() != 6){?>
	<p> Current Balloting Stage: <strong><?= $ballot->getStageName()?></strong></p>
	<p>Push Ballot to the next stage: <input type = "submit" name = "push_ballot" value = "<?= $ballot->getStageInstruction() ?>"></p>
<?		}
		else {?>
	<p>Current Balloting Stage: <strong><?= $ballot->getStageName()?></strong></p>
	<p>Create new ballot:</p>
	<p>Year: <input type = "text" name = "year" maxlength = "4"></p>
	<p><input type = "submit" name = "push_ballot" value = "Open the Housing Ballot"></p> 
<? 		}
		if ($ballot->getStage() == 1 || $ballot->getStage() == 4) {?>
	<p>A ballot requires a seed before it can be drawn, therefore to redraw a ballot the seed must also first be redrawn.</p>
	<p><input type = "submit" name = "draw_seed" value = "Draw new seed for ballot"> <input type = "submit" name = "draw_order" value = "Draw new ballot order"></p>
	<p><input type = "submit" name = "pull_ballot" value = "Emergency unlock ballot"></p> 
<? 		}
		if ($ballot->getStage() == 2 || $ballot->getStage() == 5) {?>
	<p>Send a prompt to the group admin, currently at the top of the ballot, to allocate rooms for themselves:
	<input type = "submit" name = "prompt" value = "Send Prompt"></p>
	<p>Open up the ballot to the current group's proxy:
	<input type = "submit" name = "proxy" value = "Open up to Proxy"></p> 
	<p>Move past the current group, thus pushing them to the end of the ballot:
	<input type = "submit" name = "skip" value = "Skip Group"></p>  
<? 		}?>

	<hr>
	<h2>Manage Admin Access</h2>
	<table class="table table-condensed table-bordered table-hover">
 	<thead>
  	<tr>
  	<td>Admin</td>
  	<td>CRSID</td>
  	<td>Remove</td>
  	</tr>
  	</thead>
<?		$query = "SELECT * FROM `admin`";
		$result = Database::getInstance()->query($query);
		if ($result){
			while(($row = $result->fetch_assoc())!= false){?>
	<tr><td class="col-md-4"><?= $row['name']?></td>
	<td class="col-md-4"><?= $row['crsid']?></td>
	<td class="col-md-1"><input type = "checkbox" name = "remove_admin[]" value = "<?= $row['crsid']?>"></td></tr>
<?			}
		}
		else throw new Exception("Unable to retrieve admin data");
?>
	</table>
	<p>Add a new admin:</p>
	<p>Enter Name: <input type = "text" name = "name" maxlength = "255"></p>
	<p>Enter CRSID: <input type = "text" name = "crsid" maxlength = "7"></p>
	<p>To remove an admin, they must be selected from the table above <input type = "submit" name = "submit_admin" value = "Add/Remove Admin"></p>
	<hr>
	<h2>Mangage and Assign Access Arrangements</h2>
	<table class="table table-condensed table-bordered table-hover">
 	<thead>
  	<tr>
  	<td>Access Arrangement</td>
  	<td>Users</td>
  	</tr>
  	</thead>
<?		$query = "SELECT `id` FROM `access`";
		$result = Database::getInstance()->query($query);
		if ($result){
			while(($row = $result->fetch_assoc())!= false){
				$arrangement = new Access($row['id']);?>
	<tr><td class="col-md-4"><input type = "checkbox" name = "select_access[]" value = "<?= $row['id']?>"> <?= $arrangement->getName();?></td>
<?				if ($arrangement->getUsers() != null){
					echo("<td>");
					$first_user = 1;
					foreach($arrangement->getUsers() as $crsid){
						if ($first_user) $first_user = 0;
						else echo("<br>");
						$temp_user = new User($crsid);
						echo('<input type = "checkbox" name = "remove_user[]" value = "'.$crsid.','.$arrangement->getID().'"> '.$temp_user->getName());
					}
					echo("</td>");
				}
				else echo("<td></td>");
	?>
	</tr>
<?			}
		}
?>
	</table>
	<p>To add a new access arrangement, enter a new name here: <input type = "text" name = "access_name" maxlength = "255">
	<input type = "submit" name = "submit_access" value = "Add Access Arrangement"></p>
	<p>To remove an access arrangement, select it above and press here: <input type = "submit" name = "submit_remove" value = "Remove Access Arrangement"></p>
	<p>To assign an access arrangement to a user, select it above and choose a user to give it to:
	<select name="give_user">
 	<option value="">Please select</option>
 		<?$query = "SELECT `crsid`, `name` FROM `users`";
 		$result = Database::getInstance()->query($query);
 		if ($result) while (($row = $result->fetch_assoc())!=false) echo('<option value="'.$row['crsid'].'">'.$row['name'].'</option>');
 		else throw new Exception("Failed to get list of users");?>
 	</select>
	<input type = "submit" name = "submit_give" value = "Give Access Arrangement"></p>
	<p>To take away an access arrangement from a user, select the user within the respective access arrangement above and press here <input type = "submit" name = "submit_take" value = "Take away Access Arrangement">
	<hr>
	<h2>Manage Key-Value Pairs</h2>
	<table class="table table-condensed table-bordered table-hover">
	<thead>
  	<tr>
  	<td>Select</td>
  	<td>Key</td>
  	<td>Value</td>
  	</tr>
  	</thead>
<?		$query = "SELECT * FROM `key_value`";
		$result = Database::getInstance()->query($query);
		if ($result){
			while(($row = $result->fetch_assoc()) != false){?>
	<tr>
	<td><input type = "radio" name = "old_key" value = "<?= $row['key'] ?>"></td>
	<td><?= $row['key'] ?></td>
	<td><?= $row['value'] ?></td>
	</tr>
			<?}
		}
		else throw new Exception("Failed to retrieve ballot data");?>
	</table>
	<p>Either select a key-value pair above, or enter a new key here: <input type = "text" name = "new_key" maxlength = "20"></p>
	<p>Enter a new value here: <input type = "text" name = "value" maxlength = "255">
	<input type = "submit" name = "submit_key_value" value = "Add/Change Key-Value Pair"></p>
	</form>
	</div>
<?	}
	}
}