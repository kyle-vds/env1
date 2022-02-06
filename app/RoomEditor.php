<?php
require_once "Objects/Database.php";
require_once "Objects/User.php";
require_once "Objects/Houses.php";
require_once "Objects/HTML.php";
require_once "Objects/Rooms.php";
require_once "Objects/AccessArrangement.php";

class RoomEditor {
	public static function page() {
		if (isset($_POST['submit_house'])){
			if (isset($_POST['select_house'])){
				Database::getInstance()->query("begin");
				$things = 0;
				$errors = 0;
				$house = new House($_POST['select_house']);
				if (isset($_POST['house_name']) && $_POST['house_name'] != ""){
					$things = 1;
					if (HTML::Stringchecker($_POST['house_name'])) if (!$house->update_name($_POST['house_name'])) $errors = 1;
					else HTML::HTMLerror("Sorry, you have entered an invalid character or word");
				}
				if (isset($_POST['is_house']) && $_POST['is_house'] != ""){
					$things = 1;
					if (!$house->update_house($_POST['is_house'])) $errors = 1;
				}
				if (isset($_POST['house_description']) && $_POST['house_description'] != ""){
					$things = 1;
					if (!$house->update_description($_POST['house_description'])) $errors = 1;
				}
				if ($things){
					if ($errors){
						Database::getInstance()->query("rollback");
						HTML::HTMLerror("An error occured updating the house, please contact jcr.websit@fitz.cam.ac.uk");
					}
					else{
						Database::getInstance()->query("commit");
						HTML::HTMLsuccess("House updated successfully");
					}
				}
				else HTML::HTMLerror("You must either enter a new description, name or change whether it is a house or block to change one");
			}
			elseif (isset($_POST['house_name']) && $_POST['house_name'] != ""){
				if (HTML::Stringchecker($_POST['house_name'])){
					if (isset($_POST['is_house']) && $_POST['is_house'] != ""){
						$house = new House();
						if (isset($_POST['house_description']) && $_POST['house_description'] != ""){
							if (HTML::Stringchecker($_POST['house_description'])){
								if ($house->add($_POST['house_name'], $_POST['is_house'], $_POST['house_description'])) return HTML::HTMLsuccess("House successfully added");
								else HTML::HTMLerror("An error occcured adding the house, please contact jcr.website@fitz.cam.ac.uk");
							}
							else HTML::HTMLerror("Sorry, you have entered an invalid character or word");
						}
						elseif ($house->add($_POST['house_name'], $_POST['is_house'])) HTML::HTMLsuccess("House successfully added");
						else HTML::HTMLerror("An error occcured adding the house, please contact jcr.website@fitz.cam.ac.uk");
					}
					else HTML::HTMLerror("You must select whether it is a house or block to add a new one");
				}
				else HTML::HTMLerror("Sorry, you have entered an invalid character or word");
			}
			else HTML::HTMLerror("Please either enter a new house/block name or select one from the table below");
		}
		if (isset($_POST['remove_house'])){
			if (isset($_POST['select_house'])){
				$house = new House($_POST['select_house']);
				if ($house->delete()) HTML::HTMLsuccess("House successfully removed");
				else HTML::HTMLerror("An error occcured removing the house, please contact jcr.website@fitz.cam.ac.uk");
			}
			else HTML::HTMLerror("You need to select the house from the table to remove it");
		}
		if (isset($_POST['submit_room'])){
			if (isset($_POST['select_room'])){
				Database::getInstance()->query("begin");
				$things = 0;
				$errors = 0;
				$room = new Room($_POST['select_room']);
				if (isset($_POST['room_name']) && $_POST['room_name'] != ""){
					$things = 1;
					if (HTML::Stringchecker($_POST['room_name'])) if (!$room->update_name($_POST['house_name'])) $errors = 1;
					else HTML::HTMLerror("Sorry, you have entered an invalid character or word");
				}
				if (isset($_POST['room_rent']) && $_POST['room_rent'] != ""){
					$things = 1;
					if (HTML::Integerchecker($_POST['room_rent'])) if (!$room->update_rent($_POST['house_rent'])) $errors = 1;
					else HTML::HTMLerror("Sorry, your rent is not a number");
				}
				if (isset($_POST['floor']) && $_POST['floor'] != ""){
					$things = 1;
					if (!$room->update_floor($_POST['floor'])) $errors = 1;
				}
				if (isset($_POST['available']) && $_POST['available'] != ""){
					$things = 1;
					if (!$room->update_availability($_POST['available'])) $errors = 1;
				}
				if ($things){
					if ($errors){
						Database::getInstance()->query("rollback");
						HTML::HTMLerror("An error occured updating the room, please contact jcr.websit@fitz.cam.ac.uk");
					}
					else{
						Database::getInstance()->query("commit");
						HTML::HTMLsuccess("Room updated successfully");
					}
				}
				else HTML::HTMLerror("You must either enter a new name, rent or availability to change a room");
			}
			elseif (isset($_POST['select_house'])){
				$errors = 0;
				if (!isset($_POST['room_name']) || $_POST['room_name'] == "") $errors = 1;
				if (!isset($_POST['room_rent']) || $_POST['room_rent'] == "") $errors = 1;
				if (!isset($_POST['floor']) || $_POST['floor'] == "") $errors = 1;
				if ($errors) HTML::HTMLerror("You must enter at least a room name, floor and rent to add a new room");
				else{
					if (!HTML::Stringchecker($_POST['room_name'])) $errors = 1;
					if (!HTML::Integerchecker($_POST['room_rent'])) $errors = 1;
					if ($errors) HTML::HTMLerror("Sorry, you have entered an invalid character or word");
					else {
						$room = new Room();
						if (isset($_POST['available']) && $_POST['available'] != ""){
							if ($room->add($_POST['room_name'], $_POST['room_rent'], $_POST['select_house'], $_POST['floor'], $_POST['available'])) HTML::HTMLsuccess("Room successfully added");
							else HTML::HTMLerror("An error occcured adding the room, please contact jcr.website@fitz.cam.ac.uk");
						}
						elseif ($room->add($_POST['room_name'], $_POST['room_rent'], $_POST['select_house'], $_POST['floor'])) HTML::HTMLsuccess("Room successfully added");
						else HTML::HTMLerror("An error occcured adding the room, please contact jcr.website@fitz.cam.ac.uk");
					}
				}
			}
			else HTML::HTMLerror("You must select a house before a room can be added to it");
		}
		if (isset($_POST['remove_room'])){
			if (isset($_POST['select_room'])){
				$room = new Room($_POST['select_room']);
				if ($room->delete()) HTML::HTMLsuccess("Room successfully removed");
				else HTML::HTMLerror("An error occcured removing the room, please contact jcr.website@fitz.cam.ac.uk");
			}
			else HTML::HTMLerror("You need to select the room from the table to remove it");
		}
		if (isset($_POST['submit_access'])){
			if (isset($_POST['select_room'])){
				if (isset($_POST['add_access']) && $_POST['add_access'] != ""){
					$room = new Room($_POST['select_room']);
					$errors = 0;
					if ($room->getAccess() != null) foreach ($room->getAccess() as $access) if ($access == $_POST['add_access']) $errors = 1;
					if ($errors) HTML::HTMLerror("That room already has the access arrangement you are trying to add");
					elseif ($room->giveAccess($_POST['add_access'])) HTML::HTMLsuccess("Successfully added access arrangment to room");
					else HTML::HTMLerror("An error occured adding the access arrangement to the room, please contact jcr.website@fitz.cam.ac.uk");
				}
			}
			elseif (isset($_POST['select_access'])){
				$access_pair = explode(",", $_POST['select_access']);
				$room = new Room($access_pair[1]);
				if ($room->takeAccess($access_pair[0])) HTML::HTMLsuccess("Successfully removed access arrangment from room");
				else HTML::HTMLerror("An error occured removing the access arrangement from the room, please contact jcr.website@fitz.cam.ac.uk");
			}
			else HTML::HTMLerror("Please select either a room to add an access arrangement to or an access arrangement to take away from a room");
		}
		$user = new User();
		if(!$user->isadmin()) {
			HTML::HTMLerror("You do not have admin permission");
			return;
		}
  	else {?>
  <div class = "container">
  <form action = "" method = "POST">
  <h2>Manage Houses or Blocks</h2>
  <p>To add or update a house/block select one from those below or enter a new name for one here: <input type = "text" name = "house_name" maxlength = "255"></p>
  <p>Is it a house/block:
  <select name="is_house">
		<option value="">Please select</option>
		<option value="1">House</option>
		<option value="0">Block</option>
  </select></p>
  <p>Description (in HTML, see Page Editor): </p>
  <textarea name = "house_description" cols = "170"></textarea>
  <p><input type = "submit" name = "submit_house" value = "Update House/Block"></p>
  <p>To remove a house instead, select it below and press here instead <input type = "submit" name = "remove_house" value = "Remove House/Block"></p>
  <hr>
  <h2>Manage Rooms</h2>
  <p>To add or update a room, select one from those below or enter a new name for one here: <input type = "text" name = "room_name" maxlength = "255"></p>
  <p>If adding a new room, please select a house below as well to add it to.</p>
  <p>Floor:
  <select name = "floor">
  	<option value="">Please select</option>
  	<option value="0">Basement</option>
  	<option value="1">Ground Floor</option>
  	<option value="2">First Floor</option>
  	<option value="3">Second Floor</option>
  	<option value="4">Third Floor</option>
  	<option value="5">Attic</option>
  </select>
  <p>Rent price: <input type = "text" name = "room_rent" maxlength = "6"></p>
  <p>Available:
  <select name="available">
		<option value="">Please select</option>
		<option value="1">Yes</option>
		<option value="0">No</option>
  </select>
  <input type = "submit" name = "submit_room" value = "Update Room"></p>
  <p>To remove a room instead, select it below and press here instead <input type = "submit" name = "remove_room" value = "Remove Room"></p>
  <hr>
  <h2>Assign Access Arrangements to Rooms</h2>
  <p>To add an access arrangement to a room, select a room below and an access arangement from the following:
  <select name="add_access">
		<option value="">Please select</option>
<?		$query = "SELECT * FROM `access`";
		$result = Database::getInstance()->query($query);
		if ($result) while(($row = $result->fetch_assoc()) != false) echo('<option value="'.$row['id'].'">'.$row['name'].'</option>');
		else throw new Exception("Unable to retrieve access arrangements");
?>
  </select></p>
  <p>To remove an access arrangement, select it from the table below. <input type = "submit" name = "submit_access" value = "Update Access Arrangements"></p>
  <hr>
  <h2>Houses, Blocks, Rooms and Access Arrangements</h2>
  <table class="table table-condensed table-bordered table-hover">
  <thead>
  <tr>
  <td>House or Block</td>
  <td>Name</td>
  <td>Description</td>
  <td>Floor</td>
  <td>Rooms</td>
  <td>Rents</td>
  <td>Availability</td>
  <td>Access Arrangements</td>
  </tr>
  </thead><?
  		$query = "SELECT `id` FROM `houses`";
  		$result = Database::getInstance()->query($query);
  		if ($result){
  			while(($row = $result->fetch_assoc()) != false){
  				$house = new House($row['id']);
  				if ($house->getSize() > 1) $rows = $house->getSize();
  				else $rows = 1;
  				echo("<tr>");
  				if ($house->isHouse()) echo('<td rowspan = "'.$rows.'">House</td>');
  				else echo('<td rowspan = "'.$rows.'">Block</td>');
  				echo('<td rowspan = "'.$rows.'"><input type = "radio" name = "select_house" value = "'.$house->getID().'"> '.$house->getName().'</td>');
  				if ($house->getDescription() != null) echo('<td rowspan = "'.$rows.'">'.HTML::insertValues($house->getDescription()).'</td>');
  				else echo('<td rowspan = "'.$rows.'"></td>');
  				$first_room = 1;
  				if ($house->getSize() > 0){
  					foreach($house->getFloors_Rooms() as $floor => $floors){
  						foreach($floors as $rooms){
  							if ($first_room) $first_room = 0;
  							else echo("<tr>");
  							echo('<td>'.$house->getFloor($floor).'</td>');
  							$room = new Room($rooms);
  							echo('<td><input type = "radio" name = "select_room" value = "'.$room->getID().'"> '.$room->getName().'</td>');
  							echo("<td>".$room->getPrice()."</td>");
  							if ($room->isAvailable()) echo("<td>Yes</td>");
  							else echo("<td>No</td>");
  							echo("<td>");
  							$first_access = 1;
  							if($room->getAccess() != null){
  								foreach($room->getAccess() as $arrangements){
  									if ($first_access) $first_access = 0;
  									else echo("<br>");
  									$arrangement = new Access($arrangements);
  									echo('<input type = "radio" name = "select_access" value = "'.$arrangement->getID().','.$room->getID().'">'.$arrangement->getName());
  								}
  							}
  							echo("</td></tr>");
  						}
  					}
  				}
  				else echo("<td></td><td></td><td></td><td></td><td></td></tr>");
  			}
  		}
  		else throw new Exception("Unable to retrieve house data");
  ?>
  </table>
  </form>
  </div>
  	<?}
	}
}