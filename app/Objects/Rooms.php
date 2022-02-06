<?php
require_once "Houses.php";

Class Room {
	
	protected $id = NULL;
	protected $name = NULL;
	protected $price = NULL;
	protected $available = NULL;
	protected $access = NULL;
	protected $house = NULL;
	protected $floor = NULL;
	
	public function __construct($id = null){
		if ($id != null){
			$this->id = $id;
			$query = "SELECT * FROM `rooms` WHERE `id` = '".$this->id."'";
			$result = Database::getInstance()->query($query);
			if ($result) {
				$row = $result->fetch_assoc();
				$this->name = $row['name'];
				$this->price = $row['price'];
				$this->available = $row['available'];
				if ($row['access'] != null) $this->access = explode(",", $row['access']);
				$this->house = $row['house'];
				$this->floor = $row['floor'];
			}
			else throw new Exception("Unable to retrieve room data");
		}
	}
	
	public function getID(){
		return $this->id;
	}
	
	public function getName(){
		return $this->name;
	}
	
	public function getPrice(){
		return $this->price;
	}
	
	public function isAvailable(){
		return $this->available;
	}
	
	public function getAccess(){
		return $this->access;
	}
	
	public function getHouse(){
		return $this->house;
	}
	
	public function getFloor(){
		return $this->floor;
	}
	
	public function update_name($name){
		$query = "UPDATE `rooms` SET `name` = '".$name."' WHERE `id` = ".$this->id;
		$result = Database::getInstance()->query($query);
		if ($result) return true;
		else return false;
	}
	
	public function update_floor($floor){
		$query = "UPDATE `rooms` SET `floor` = '".$floor."' WHERE `id` = ".$this->id;
		$result = Database::getInstance()->query($query);
		if ($result) return true;
		else return false;
	}
	
	public function update_rent($rent){
		$query = "UPDATE `rooms` SET `price` = '".$rent."' WHERE `id` = ".$this->id;
		$result = Database::getInstance()->query($query);
		if ($result) return true;
		else return false;
	}
	
	public function update_availability($available){
		$query = "UPDATE `rooms` SET `available` = ".$available." WHERE `id` = ".$this->id;
		$result = Database::getInstance()->query($query);
		if ($result) return true;
		else return false;
	}
	
	public function delete(){
		$house = new House($this->house);
		if ($house->remove($this->id)){
			$query = "DELETE FROM `rooms` WHERE `id` = ".$this->id;
			$result = Database::getInstance()->query($query);
			if ($result) return true;
			else return false;
		}
		else return false;
	}
	
	public function add($name, $price, $house, $floor, $available = null){
		Database::getInstance()->query("begin");
		$query = "INSERT INTO `rooms` (`name`, `price`, `house`, `floor`";
		if ($available != null) $query .= ", `available`) VALUES ('".$name."', '".$price."', '".$house."', '".$floor."', '".$available."')";
		else $query .= ") VALUES ('".$name."', '".$price."', '".$house."', '".$floor."')";
		$result = Database::getInstance()->query($query);
		if ($result) {
			$query = "SELECT `id` FROM `rooms` WHERE `name` = '".$name."' AND `house` = ".$house;
			$result = Database::getInstance()->query($query);
			if ($result){
				$temp_house = new House($house);
				$new_size = $temp_house->getSize() + 1;
				if ($temp_house->getRooms() != null){
					$new_rooms = $temp_house->getRooms();
					array_push($new_rooms, $result->fetch_assoc()['id']);
					$str_rooms = implode(",", $new_rooms);
				}
				else $str_rooms = $result->fetch_assoc()['id'];
				$new_rooms = array();
				$query = "UPDATE `houses` SET `size` = ".$new_size.", `rooms` = '".$str_rooms."' WHERE `id` = ".$house;
				$result = Database::getInstance()->query($query);
				if ($result){
					Database::getInstance()->query("commit");
					return true;
				}
				else {
					Database::getInstance()->query("rollback");
					return false;
				}
			}
			else {
				Database::getInstance()->query("rollback");
				return false;
			}
		}
		else {
			Database::getInstance()->query("rollback");
			return false;
		}
	}
	
	public function giveAccess($access){
		if ($this->access == null) $str_access = $access;
		else {
			array_push($this->access, $access);
			$str_access = "'".implode(",", $this->access)."'";
		}
		$query = "UPDATE `rooms` SET `access` = ".$str_access." WHERE `id` = ".$this->id;
		$result = Database::getInstance()->query($query);
		if ($result) return true;
		else false;
	}
	
	public function takeAccess($access){
		$new_access = array();
		foreach($this->access as $old_access) if ($old_access != $access) array_push($new_access, $old_access);
		if (empty($new_access)) $str_access = "NULL";
		else $str_access = "'".implode(",", $new_access)."'";
		$query = "UPDATE `rooms` SET `access` = ".$str_access." WHERE `id` = ".$this->id;
		$result = Database::getInstance()->query($query);
		if ($result) return true;
		else false;
	}
}
