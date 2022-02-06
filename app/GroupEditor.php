<?php
require_once "Objects/Database.php";
require_once "Objects/User.php";
require_once "Objects/BallotMaker.php";
require_once "Objects/HTML.php";
require_once "Objects/Group.php";

class GroupEditor {
	public static function page() {
		$user = new User;
		if (!$user->isuser()) HTML::HTMLerror("You are not yet registered in the ballot");
		else {
			$group = new Group($user->getGroup(), $user->getBallot());
			$ballot = new BallotMaker();
			if (isset($_POST['submit_lock'])){
				if ($user->lock()) HTML::HTMLsuccess("You have locked in with whom you are balloting");
				else HTML::HTMLerror("An error has occured locking in with whom you are balloting");
			}
			if (isset($_POST['submit_unlock'])){
				if ($user->unlock()) HTML::HTMLsuccess("You have unlocked with whom you are balloting");
				else HTML::HTMLerror("An error has occured unlocking with whom you are balloting");
			}
			if (isset($_POST['submit_remove'])){
				if (isset($_POST['members']) || isset($_POST['requesting'])){
					$errors = 0;
					if (isset($_POST['members'])) if (!$group->remove_members($_POST['members'], $user->getBallot(), $user)) $errors = 1;
					if (isset($_POST['requesting'])) if (!$group->remove_requesting($_POST['requesting'], $user->getBallot())) $errors = 1;
					if (!$errors){
						HTML::HTMLsuccess("You have removed a member or request from your group");
						$user = new User();
						$group = new Group($user->getGroup(), $user->getBallot());
					}
					else HTML::HTMLerror("An error has occured removing a member or request from your group");
				}
				else HTML::HTMLerror("A group member or request must be selected before it can be removed");
			}
			if (isset($_POST['submit_leave'])){
				if ($group->remove_members($user->getCRSID())){
					HTML::HTMLsuccess("You have left your balloting group and are now balloting alone");
					$user = new User();
					$group = new Group($user->getGroup(), $user->getBallot());
				}
				else HTML::HTMLerror("An error has occured leaving your balloting group, please email jcr.website@fitz.cam.ac.uk");
			}
			if (isset($_POST['submit_proxy'])){
				if (isset($_POST['proxy']) && $_POST['proxy'] != ""){
					if ($user->setProxy($_POST['proxy'])){
						HTML::HTMLsuccess("You have set or changed your proxy");
						$user = new User();
					}
					else HTML::HTMLerror("An error has setting or changing your proxy, please email jcr.website@fitz.cam.ac.uk");
				}
				else HTML::HTMLerror("Please select someone to be your proxy");
			}
			if (isset($_POST['submit_join'])){
				if (isset($_POST['group_select'])){
					$selected = 0;
					foreach($_POST['group_select'] as $select) $selected += 1;
					if ($selected == 1){
						if ($group->join($user, $_POST['group_select'][0])){
							HTML::HTMLsuccess("You have joined/changed/declined balloting group");
							$user = new User();
							$group = new Group($user->getGroup(), $user->getBallot());
						}
						else HTML::HTMLerror("An error has occured joining a balloting group, please email jcr.website@fitz.cam.ac.uk");
					}
					else HTML::HTMLerror("You can only select one group to join");
				}
				else HTML::HTMLerror("A group must be selected before you can join or decline it");
			}
			if (isset($_POST['submit_decline'])){
				if (isset($_POST['group_select'])){
					if ($user->decline($_POST['group_select'])){
						HTML::HTMLsuccess("You have joined/changed/declined balloting group");
						$user = new User();
						$group = new Group($user->getGroup(), $user->getBallot());
					}
					else HTML::HTMLerror("An error has occured joining/changing/declining balloting group, please email jcr.website@fitz.cam.ac.uk");
				}
				else HTML::HTMLerror("A group must be selected before you can decline it");
			}
			if ($user->getCRSID() == $group->getAdmin()){
				if (isset($_POST['submit_request'])){
					if (isset($_POST['requests'])){
						$requests = array();
						$requests = $_POST['requests'];
						$limit = $group->getLimit();
						foreach ($requests as $request) $limit += 1;
						if ($limit <= HTML::getValue("GroupLimit")){
							if ($group->send_requests($requests)){
								HTML::HTMLsuccess("You have sent out requests");
								$user = new User();
								$group = new Group($user->getGroup(), $user->getBallot());
							}
							else HTML::HTMLerror("An error has occured sending out requests, please email jcr.website@fitz.cam.ac.uk");
						}
						else HTML::HTMLerror("You can only request as many members that brings your group size up to the maximum of ".HTML::getValue("GroupLimit"));
					}
					else HTML::HTMLerror("A group must be selected before you can join it");
				}
			}
			$searching = 0;
			$group_admin = 0;
			if($user->isSearching()){
				if ($user->getBallot() == "housing_ballot" && $ballot->getStage() == 0) $searching = 1;
				elseif ($user->getBallot() == "room_ballot" && $ballot->getStage() < 4) $searching = 1;
			} 
			if($user->getCRSID() == $group->getAdmin() && $searching) $group_admin = 1;?> 
<div class = "container">
<form method = "POST">
<h2>Your Details</h2>
<p> Hello, <?= $user->getName()?></p>
	 		<?if ($user->getProxy() != NULL && $user->getProxy() != ""){
 				$proxy = new User($user->getProxy());
 				echo("<p>Your proxy is <strong>".$proxy->getName()."</strong></p>");
 			}
 			else echo("<p>You do not currently have a proxy</p>");
 			if ($searching){?>
<p> If you would like to set or change your proxy:
<select name="proxy">
<option value="">Please select</option>
	 			<?$query = "SELECT `crsid`, `name` FROM `users` WHERE searching = 1 AND `room_ballot` = ";
	 			if ($user->getBallot() == "room_ballot") $query .= 1;
	 			else $query .= 0;
	 			$query .= " AND `crsid` NOT IN ('".$user->getCRSID();
 				if ($user->getProxy() != NULL && $user->getProxy() != "") $query .= "', '".$user->getProxy();
 				$query .= "')";
 				$result = Database::getInstance()->query($query);
 				if ($result) while (($row = $result->fetch_assoc())!=false) echo('<option value="'.$row['crsid'].'">'.$row['name'].'</option>');
 				else throw new Exception("Failed to get list of potential proxies");?>
</select>
<input type = "submit" name = "submit_proxy" value = "Update Proxy"/></p>
			<?}?>
<h2>Your Balloting Group</h2>
<p> You are currently balloting <?
			if ($group->getSize() == 1) echo("<strong>alone</strong>. You are classed as within <strong>".$group->getPriorityName()."</strong>");
 			else { ?>
with 			<?if ($user->getCRSID() == $group->getAdmin()) echo("<strong>Your Own Group</strong>, ");
	 			else echo("<strong>".$group->getName()."</strong>, ");
 				echo("classed as within <strong>".$group->getPriorityName()."</strong></p>");
 			}
 			if ($group->getLimit() > 1){?> 
<table class="table table-condensed table-bordered table-hover">
<thead>
<tr>
<td>Your Group:</td>
				<?if ($group_admin) echo("<td>Remove</td>");  ?>
</tr>
</thead>
<tr>
<td <?if ($group_admin) echo('colspan = "2"'); ?>>
<strong>Members</strong></td></tr>
 				<?$members = array();
 				if($group->getSize() == 1) echo("<tr><td>".$user->getName()."</td><td>");
 				else {
 					$members = $group->getMembers();
 					$first_member = 1;
 					foreach($members as $member){
 						$temp_user = new User($member);
 						echo("<tr><td>".$temp_user->getName()."</td>");
 						if ($group_admin){
 							if ($first_member){
 								$first_member = 0;
 								echo("<td></td>");
 							}
 							else echo('<td><input type = "checkbox" name = "members[]" value = "'.$member.'"></td>');
 						}
 						echo("</tr>");
 					}
 				}
 				if ($group->getRequesting() != null){?>
<tr>
<td <?if ($group_admin) echo('colspan = "2"'); ?>>
<strong>Requests</strong></td></tr>
 					<?foreach($group->getRequesting() as $requesting){
 						$temp_user = new User($requesting);
 						echo("<tr><td>".$temp_user->getName()."</td>");
 						if ($group_admin) echo('<td><input type = "checkbox" name = "requesting[]" value = "'.$requesting.'"></td>');
 						echo("</tr>");
 					}
 	 			}?>	
</table>	
 				<?if ($group_admin) echo('<p><input type = "submit" name = "submit_remove" value = "Remove selected members/requests from group"></p>');
 				if ($group->getSize() > 1 && $searching) echo('<input type = "submit" name = "submit_leave" value = "Leave Group and Ballot Alone"></p>');
 			}
 			if ($searching){?>
<p><input type = "submit" name = "submit_lock" value = "Lock in with whom you are balloting"></p>
<h2>Groups Requesting You to Join Them</h2>
<p>You have
 				<?if ($user->getRequests() == NULL || $user->getRequests() == "") echo("<strong>no requests</strong> to join a balloting group</p>");
 				else{
 					echo("<strong>at least one request</strong> to join a balloting group:</p>")?>
<table class="table table-condensed table-bordered table-hover">
<thead>
<tr>
<td>Group and Current Members</td>
<td>Others Requested</td>
<td>Select</td>
</tr>
</thead>
					<?$group_ids = array();
					$group_ids = explode(",", $user->getRequests());
					foreach($group_ids as $group_id){
						$temp_group = new Group($group_id, $user->getBallot());?>
<tr>	
<td class="col-md-4"><?
   						$names = array();
   						foreach($temp_group->getMembers() as $member){
   							$temp_user = new User($member);
   							array_push($names, $temp_user->getName());
   						}
   						echo(implode("<br>", $names));?>
</td>
<td class="col-md-4"><?
   						if ($temp_group->getRequesting() != NULL){
   							$requesting_names = array();
   							foreach($temp_group->getRequesting() as $crsid){
  								$temp_user = new User($crsid);
   								array_push($requesting_names, $temp_user->getName());
   							}
   							echo(implode("<br>", $requesting_names));
   						}?>
</td>
<td class="col-md-1" style ="text-align: left;"><input type = "checkbox" name = "group_select[]" value = "<?= $group_id?>"></td>
</tr>
					<?}?>
</table>
<p><input type="submit" name="submit_join" value="Join Selected Group" /> <input type="submit" name="submit_decline" value="Decline Selected Group" /></p>
				<?}
				if ($user->getCRSID() == $group->getAdmin() && $group->getLimit() < HTML::getValue("GroupLimit")){?>
<h2>Make Requests</h2>
<p>You can request others to join your group:</p>
<table class="table table-condensed table-bordered table-hover">
<thead>
<tr>
<td>Students open to requests</td>
<td>Select</td>
</tr>
</thead>
					<?$query = "SELECT `crsid`, `name` FROM `users` WHERE searching = 1 AND `room_ballot` = ";
					if ($user->getBallot() == "room_ballot") $query .= 1;
					else $query .= 0;
					$query .= " AND`crsid` NOT IN ('".$group->getAdmin();
					if ($group->getSize() > 1) $query .= "', '".implode("', '", $group->getMembers());
					if ($group->getRequesting() != NULL) $query .= "', '".implode("', '", $group->getRequesting());
					$query .= "')";
					$result = Database::getInstance()->query($query);
					if ($result){
  						while (($row = $result->fetch_assoc())!=false){?>
<tr>
<td class="col-md-8"><?= $row['name']?>
</td>
<td class="col-md-1" style ="text-align: left;"><input type = "checkbox" name = "requests[]" value = "<?echo($row['crsid'])?>"></td>
</tr>
						<?}
					}
					else throw new Exception("Failed to get list of searching users");?>
</table>
<input type="submit" name="submit_request" value="Request Selected to Join Group" />
				<?}
 			}
 			else{
 				$show = 0;
 				if ($user->getBallot() == "housing_ballot" && $ballot->getStage() == 0) $show = 1;
 				elseif ($user->getBallot() == "room_ballot" && $ballot->getStage() < 4) $show = 1;
 				if ($show){?>
<p> You have locked in with whom you are balloting. Although, if balloting within a group, the remaining members may still not be locked in. Therfore, it is still possible
for your balloting group to change. If you wish to leave or alter your group you can unlock yourself below.</p>
<input type = "submit" name = "submit_unlock" value = "Unlock with whom you are balloting">
				<?}
			}?>
 	</form>
	</div>
		<?}
	}
}