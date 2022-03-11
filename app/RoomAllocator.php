<?php
class RoomAllocator {
	public static function page() {
		$user = new User();
		$ballot = new BallotMaker();
		if (isset($_POST['submit_allocations'])){
			if (isset($_POST['confirm_allocations'])){
				$group = new Group($user->getGroup(), $user->getBallot());
				$errors = 0;
				$room_crsid = array();
				foreach($group->getRooms() as $room_id => $room_name){
					if (!$errors){
						$temp_room = "select_".$room_id;
						if (isset($_POST[$temp_room]) && $_POST[$temp_room] != ""){
							foreach($room_crsid as $crsid) if ($crsid == $_POST[$temp_room]) $errors = 1;
							if (!$errors) $room_crsid[$room_id] = $_POST[$temp_room];
						}
						else $errors = 1;
					}
				}
				if (!$errors){
					foreach($room_crsid as $temp_room => $crsid){
						if (!$errors){
							$query = "UPDATE `users` SET `room` = ".$temp_room." WHERE `crsid` = '".$crsid."'";
							$result = Database::getInstance()->query($query);
							if (!$result) $errors = 1;
						}
					}
					if (!$errors){
						HTML::HTMLsuccess("Rooms successfully allocated to each user");
						$user = new User();
					}
					else HTML::HTMLerror("An error occured allocating rooms to each user, please contact jcr.website@fitz.cam.ac.uk");
				}
				else HTML::HTMLerror("Please select one member per room without repeats");
			}
			else HTML::HTMLerror("Please confirm these are the room allocations you all want");
		}
		if (isset($_POST['submit_room'])){
			if (isset($_POST['confirm_choice'])){
				if (isset($_POST['select_room']) && count($_POST['select_room']) == $_POST['group_size']){
					if ($user->getCRSID() == $ballot->getProxy()){
						$query = "SELECT `group_id` FROM `".$ballot->getName()."` WHERE `order` = ".$ballot->getPosition();
						$result = Database::getInstance()->query($query);
						if (!$result) throw new Exception("Unable to retrieve current group");
						else $group = new Group($result->fetch_assoc()['group_id'], $ballot->getName());
					}
					else $group = new Group($user->getGroup(), $user->getBallot());
					if ($group->allocateRooms($_POST['select_room'])){
						HTML::HTMLsuccess("Rooms allocated successfully");
						$ballot = new BallotMaker();
						$user = new User();
					}
					else HTML::HTMLerror("An error occured allocating you rooms, please contact jcr.website@fitz.cam.ac.uk");
				}
				else HTML::HTMLerror("You must select as many rooms as there are members in your group");
			}
			else HTML::HTMLerror("You must confirm your choices before submitting them");
		}
		if (isset($_POST['submit_house'])){
			if (isset($_POST['confirm_choice'])){
				if (isset($_POST['select_house'])){
					if ($user->getCRSID() == $ballot->getProxy()){
						$query = "SELECT `group_id` FROM `".$ballot->getName()."` WHERE `order` = ".$ballot->getPosition();
						$result = Database::getInstance()->query($query);
						if (!$result) throw new Exception("Unable to retrieve current group");
						else $group = new Group($result->fetch_assoc()['group_id'], $ballot->getName());
					}
					else $group = new Group($user->getGroup(), $user->getBallot());
					if ($group->allocateHouse($_POST['select_house'])){
						HTML::HTMLsuccess("House allocated successfully");
						$ballot = new BallotMaker();
						$user = new User();
					}
					else HTML::HTMLerror("An error occured allocating your house, please contact jcr.website@fitz.cam.ac.uk");
				}
				else HTML::HTMLerror("You must select a house");
			}
			else HTML::HTMLerror("You must confirm your choices before submitting them");
		}
		$open = 0;
		if ($user->getBallot() == "housing_ballot" && $ballot->getStage() > 2) $open = 1;
		elseif ($user->getBallot() == "room_ballot" && $ballot->getStage() > 5) $open = 1;
		if (!$open) HTML::HTMLerror("Your Ballot has not yet been drawn, therefore room allocation is currently closed");
		else {
			$proxy = 0;
			if ($user->getCRSID() == $ballot->getProxy()){
				$proxy = 1;
				$query = "SELECT `group_id` FROM `".$ballot->getName()."` WHERE `order` = ".$ballot->getPosition();
				$result = Database::getInstance()->query($query);
				if (!$result) throw new Exception("Unable to retrieve current group");
				else $group = new Group($result->fetch_assoc()['group_id'], $ballot->getName());
			}
			else $group = new Group($user->getGroup(), $user->getBallot());
			if ($user->getBallot() != $ballot->getName()) $position = $group->getOrder() + 1;
			else $position = $ballot->getPosition();
			if ($group->getOrder() == $position){
  				if(($group->getAdmin() == $user->getCRSID() || $proxy) && $user->getBallot() == "room_ballot"){
  					$query = "SELECT `id` FROM `houses` WHERE `house` = 0";
  					$result = Database::getInstance()->query($query);
  					if ($result){?>
  	<div class="container">
  	<p>Please select <?= $group->getSize()?> room(s) for <strong><?= $group->getName() ?></strong>, from the following:</p>
  	<form action = "" method = "POST">
  	<input type = "hidden" name = "group_size" value = "<?= $group->getSize()?>">
  	<table class="table table-condensed table-bordered table-hover">
  	<thead>
  	<tr>
  	<td>Blocks:</td>
  	<td>Floors:</td>
  	<td>Rooms:</td>
  	</tr>
  	</thead>
  						<?while (($row = $result->fetch_assoc()) != null){
  							$house = new House($row['id']);
  							$available_floors = array();
  							if ($house->getRooms() != null){
  								foreach($house->getFloors_Rooms() as $floor => $floors){
  									$available_rooms = array();
  									foreach($floors as $rooms){
  										$room = new Room($rooms);
  										if ($room->isAvailable()) array_push($available_rooms, $room->getID().",".$room->getName());
  									}
  									if(!empty($available_rooms)){
  										$available_floors[$floor] = $available_rooms;
  										unset($available_rooms);
  									}
  								}
  								if (!empty($available_floors)){
  									echo('<tr><td rowspan = "'.count($available_floors).'">'.$house->getName().'</td>');
  									$first_floor = 1;
  									foreach($available_floors as $floor => $floors){
  										if ($first_floor) $first_floor = 0;
  										else echo('<tr>');
  										echo('<td>'.$house->getFloor($floor).'</td><td>');
  										$first_room = 1;
  										foreach($floors as $name){
  											$ID_name = array();
  											$ID_name = explode(",", $name);
  											if ($first_room) $first_room = 0;
  											else echo(" || ");
  											echo('<input type = "checkbox" name = "select_room[]" value = "'.$ID_name[0].'"> '.$ID_name[1]);
  										}
  										echo("</td></tr>");
  									}
  								}
  							}
  						}?>
  	</table>
  	<p>Please confirm you have selected the above house for your group <input type = "checkbox" name = "confirm_choice" value = "confirm"></p>
  	<input type = "submit" name = "submit_room" value = "Submit your choice">
  	</form>
  	</div>
  					<?}
  					else throw new Exception("Unable to retrieve available blocks");
				}
				elseif(($group->getAdmin() == $user->getCRSID() || $proxy) && $user->getBallot() == "housing_ballot"){
  					$query = "SELECT `name`, `id` FROM `houses` WHERE `house` = 1 AND `available` = 1 AND `size` = ".$group->getSize();
  					$result = Database::getInstance()->query($query);
  					if ($result){?>
  	<div class="container">
  	<p>Please select from the following houses for <strong><?= $group->getName() ?></strong>:</p>
  	<form action = "" method = "POST">
  	<table class="table table-condensed table-bordered table-hover">
  	<thead>
  	<tr>
  	<td>Houses:</td>
  	<td>Select:</td>
  	</tr>
  	</thead>
  						<?while(($row = $result->fetch_assoc()) != null){?>
  	<tr>
  	<td><?= $row['name'] ?></td>
  	<td><input type = "radio" name = "select_house" value = "<?= $row['id'] ?>"></td>
  	</tr>
  						<?}?>
  	</table>
  	<p>Please confirm you have selected the above house for your group <input type = "checkbox" name = "confirm_choice" value = "confirm"></p>
  	<input type = "submit" name = "submit_house" value = "Submit your choice">
  	</form>
  	</div>
  					<?}
  					else throw new Exception("Unable to retrieve available houses");
				}
  				else HTML::HTMLsuccess("It is your turn in the ballot! Please get your group admin to select your rooms!");
  			}
  			elseif ($group->getOrder() > $position){
  				$remaining_places = $group->getOrder() - $position;
  				HTML::HTMLerror("It is not yet your turn in the ballot, there are currently ".$remaining_places." groups ahead of you who need to choose");
  			}
  			else{
  				if ($user->getRoom() != null){
  					$room = new Room($user->getRoom());
  					$house = new House($room->getHouse())?>
   <div class="container">
   <p>You have been allocated <strong><?= $room->getName() ?></strong>: <?= $house->getName()?>, <?= $house->getFloor($room->getFloor()) ?></p>
   <p>Your rent will be <?= $room->getPrice()?> pounds/week</p>
   					<? if ($group->getSize() > 1){?>
   <hr>
   <p>The rest of your group has been allocated the following rooms:</p>
   <table class="table table-condensed table-bordered table-hover">
   <thead>
   <tr>
   <td>Group Members</td>
   <td>Room</td>
   </tr>
   </thead>
   						<? foreach ($group->getMembers() as $member){
  							if ($member != $user->getCRSID()){
  								$temp_user = new User($member);
  								$temp_room = new Room($temp_user->getRoom());?>
   <tr>
   <td><?= $temp_user->getName() ?></td>
   <td><?= $temp_room->getName() ?></td>
   </tr>
  							<?}
   						}?>
   </table>
   					<?}?>
   </div>
  				<?}
  				elseif ($group->getRooms() != null){
  					if ($group->getAdmin() == $user->getCRSID()){?>
   <div class="container">
   <p>Please assign specific rooms to specific group members:</p>
   <form action = "" method = "POST">
   <table class="table table-condensed table-bordered table-hover">
   <thead>
   <tr>
   <td>Rooms:</td>
   <td>Group Members:</td>
   </tr>
   </thead>
   						<?foreach($group->getRooms() as $room_id => $room_name){?>
   <tr>
   <td><?= $room_name ?></td>
   <td><select name = "select_<?= $room_id ?>">
   <option value = "">Please select</option>
   							<?foreach($group->getMembers() as $member){
   								$user = new User($member);
   								echo('<option value = "'.$member.'">'.$user->getName().'</option>');
   							}?>
   </select></td>
   </tr>
   						<?}?>
   </table>
   <p>Please confirm the above room allocations <input type = "checkbox" name = "confirm_allocations" value = "confirm"></p>
   <input type = "submit" name = "submit_allocations" value = "Submit Room Allocations">
   </form>
   </div>
  					<?}
 					else HTML::HTMLsuccess("Your rooms have been chosen for your group by your group admin, please now get your group admin to allocate each room to a member of the group!"); 				
  				}
  				else HTML::HTMLerror("Sorry, there are no houses left of equal size to your group to be allocated, when all remaining houses have been allocated you will be automatically added to the room ballot");
			}
  		}
	}
}