<?php

Class House {
	
	protected $id = NULL;
	protected $name = NULL;
	protected $rooms = NULL;
	protected $size = NULL;
	protected $house = NULL;
	protected $description = NULL;
	protected $images = NULL;
	
	public function __construct($id = null){
		if ($id != null) {
			$this->id = $id;
			$query = "SELECT * FROM `houses` WHERE `id` = ".$this->id;
			$result = Database::getInstance()->query($query);
			if ($result) {
				$row = $result->fetch_assoc();
				$this->name = $row['name'];
				if ($row['rooms'] != null) $this->rooms = explode(",", $row['rooms']);
				$this->size = $row['size'];
				$this->house = $row['house'];
				if ($row['description'] != null) $this->description = $row['description'];
				if ($row['images'] != null) $this->images = explode(",", $row['images']);
			}
			else throw new Exception("Unable to retrieve house data");
		}
	}
	
	public function getID(){
		return $this->id;
	}
	
	public function getName(){
		return $this->name;
	}
	
	public function getRooms(){
		return $this->rooms;
	}
	
	public function getFloors_Rooms(){
		if ($this->rooms != null){
			$query = "SELECT `floor`, `id` FROM `rooms` WHERE `id` IN ('".implode("', '",$this->rooms)."') ORDER BY `floor`, `name`";
			$result = Database::getInstance()->query($query);
			if ($result){
				$floors = array();
				$house = array();
				$current_floor = 10;
				while (($row = $result->fetch_assoc()) != null){
					if ($current_floor == $row['floor']) array_push($floors, $row['id']);
					else {
						if (!empty($floors)){
							$house[$current_floor] = $floors;
							unset($floors);
							$floors = array();
						}
						array_push($floors, $row['id']);
						$current_floor = $row['floor'];
					}
				}
				if (empty($floors)) {
					if (empty($house)) return null;
					else return $house;
				}
				else $house[$current_floor] = $floors;
				return $house;
			}
			else throw new Exception("Unable to retrieve floor data from rooms");
		}
		else return null;
	}
	
	public function getSize(){
		return $this->size;
	}
	
	public function isHouse(){
		return $this->house;
	}
	
	public function getDescription(){
		return $this->description;
	}
	
	public function getImages(){
		return $this->images;
	}
	
	public function getFloor($floor){
		switch ($floor){
			case 0:
				return "Basement";
			case 1:
				return "Ground Floor";
			case 2:
				return "First Floor";
			case 3:
				return "Second Floor";
			case 4:
				return "Third Floor";
			case 5:
				return "Attic";
			default:
				throw new Exception("Failed to get floor");
		}
		
	}
	
	public function update_name($name){
		$query = "UPDATE `houses` SET `name` = '".$name."' WHERE `id` = ".$this->id;
		$result = Database::getInstance()->query($query);
		if ($result) return true;
		else return false;
	}
	
	public function update_house($house){
		$query = "UPDATE `houses` SET `house` = '".$house."' WHERE `id` = ".$this->id;
		$result = Database::getInstance()->query($query);
		if ($result) return true;
		else return false;
	}
	
	public function update_description($description){
		$query = "UPDATE `houses` SET `description` = '".$description."' WHERE `id` = ".$this->id;
		$result = Database::getInstance()->query($query);
		if ($result) return true;
		else return false;
	}
	
	public function update_images($image){
		if ($this->images != null){
			$new_images = array();
			array_push($this->images, $image);
			$str_images = implode(",", $this->images);
		}
		else $str_images = $image;
		$query = "UPDATE `houses` SET `images` = '".$str_images."' WHERE `id` = ".$this->id;
		$result = Database::getInstance()->query($query);
		if ($result) return true;
		else return false;
	}
	
	public function add($name, $house, $description = null){
		$query = "INSERT INTO `houses` (`name`, `house`";
		if ($description != NULL) $query .= ", `description`) VALUES ('".$name."', '".$house."', '".$description."')";
		else $query .= ") VALUES ('".$name."', '".$house."')";
		$result = Database::getInstance()->query($query);
		if ($result) return true;
		else return false;
	}
	
	public function remove($room){
		$new_rooms = array();
		foreach ($this->rooms as $rooms) if ($rooms != $room) array_push($new_rooms, $rooms);
		if (empty($new_rooms)) $str_rooms = "NULL";
		else $str_rooms = "'".implode(",", $new_rooms)."'";
		$this->size -= 1; 
		$query = "UPDATE `houses` SET `rooms` = ".$str_rooms.", `size` = ".$this->size." WHERE `id` = ".$this->id;
		$result = Database::getInstance()->query($query);
		if ($result) return true;
		else return false;
	}
	
	public function delete(){
		Database::getInstance()->query("begin");
		if ($this->rooms != null){
			$query = "DELETE FROM `rooms` WHERE `id` IN (".implode(", ", $this->rooms).")";
			$result = Database::getInstance()->query($query);
			if (!$result){
				Database::getInstance()->query("rollback");
				return false;
			}
		}
		$query = "DELETE FROM `houses` WHERE `id` = ".$this->id;
		$result = Database::getInstance()->query($query);
		if ($result){
			Database::getInstance()->query("commit");
			return true;
		}
		else{
			Database::getInstance()->query("rollback");
			return false;
		}
	}
}