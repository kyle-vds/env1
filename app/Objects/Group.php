<?php

require_once "Database.php";
require_once "User.php";

class Group {
	
	protected $group_id = NULL;
	protected $admin = NULL;
	protected $members = NULL;
	protected $priority = NULL;
	protected $size = 0;
	protected $requesting = NULL;
	protected $limit = 0;
	protected $ballot = NULL;
	protected $order = NULL;
	protected $rooms = NULL;
	protected $house = NULL;
	
	public function __construct($group_id, $ballot_name){
		$this->group_id = $group_id;
		$this->ballot = $ballot_name;
		$query = "SELECT * FROM ".$this->ballot." WHERE group_id = '".$this->group_id."'";
		$result = Database::getInstance()->query($query);
		if ($result){
			$row = $result->fetch_assoc();
			$this->size = $row['size'];
			$this->limit = $this->size;
			if (!empty($row['requesting']) && $row['requesting'] != ""){
				$this->requesting = explode(",", $row['requesting']);
				foreach ($this->requesting as $requesting) $this->limit += 1;
			}
			$members = array();
			$members = explode(",", $row['crsids']);
			$this->admin = $members[0];
			$this->members = $members;
			$this->priority = $row['priority'];
			$this->order = $row['order'];
			if ($this->ballot == "housing_ballot") $this->house = $row['house'];
			else $this->rooms = explode(",", $row['rooms']);
		}
		else throw new Exception("Failed to get specifc user group data");
	}
	
	public function getAdmin(){
		return $this->admin;
	}
	
	public function getID(){
		return $this->group_id;
	}
	
	public function getMembers(){
		return $this->members;
	}
	
	public function getName(){
		$user = new User($this->admin);
		$admin_name = $user->getName();
		if ($this->size > 1){
			if (strripos($admin_name, "s") == strlen($admin_name) - 1) return $admin_name."' Group";
			else return $admin_name."'s Group";
		}
		else return $admin_name;
	}
	
	public function getPriority(){
		return $this->priority;
	}
	
	public function getSize(){
		return $this->size;
	}
	
	public function getRequesting(){
		return $this->requesting;
	}
	
	public function getLimit(){
		return $this->limit;
	}
	
	public function getOrder(){
		return $this->order;
	}
	
	public function getRooms(){
		if ($this->ballot == "housing_ballot") $query = "SELECT `id`, `name` FROM `rooms` WHERE `house` = ".$this->house;
		else $query = "SELECT `id`, `name` FROM `rooms` WHERE `id` IN ('".implode("', '", $this->rooms)."')";
		$result = Database::getInstance()->query($query);
		if ($result){
			$rooms = array();
			while (($row = $result->fetch_assoc()) != null) $rooms[$row['id']] = $row['name'];
			if (!empty($rooms)) return $rooms;
			else return null;
		}
		throw new Exception("Unable to retrieve house's rooms");
	}
	
	public function getPriorityName(){
		switch ($this->priority){
			case "FIRSTYEAR":
				return "First Year";
			case "SECONDYEAR":
				return "Second Year";
			case "THIRDYEAR":
				return "Third Year";
			case "THIRDYEARABROAD":
				return "Third Year currently abroad";
			default:
				throw new Exception("Failed to get group priority");
		}
	}
	
	public function getPriorityWeight($priority){
		switch ($priority){
			case "FIRSTYEAR":
				return 3;
			case "SECONDYEAR":
				return 1;
			case "THIRDYEAR":
				return 2;
			case "THIRDYEARABROAD":
				return 1;
			default:
				throw new Exception("Failed to get priority weight");
		}
	}
	
	public function getPriorityString($weight){
		switch ($weight){
			case 3:
				return "FIRSTYEAR";
			case 2:
				return "THIRDYEAR";
			case 1:
				return "SECONDYEAR";
			default:
				throw new Exception("Failed to get priority string");
		}
	}
	
	public function updatePriority(){
		$query = "SELECT `priority` FROM `users` WHERE `crsid` IN ('".implode("', '", $this->members)."')";
		$result = Database::getInstance()->query($query);
		if ($result){
			$maximum = 0;
			while (($row = $result->fetch_assoc())!= false){
				if ($this->getPriorityWeight($row['priority']) > $maximum){
					$maximum = $this->getPriorityWeight($row['priority']);
					$str_priority = $row['priority'];
				}
			}
			if ($this->getPriorityString($maximum) == $this->priority) return true;
			else {
				$query = "UPDATE `".$this->ballot."` SET `priority` = '".$str_priority."' WHERE `group_id` = ".$this->group_id;
				$result = Database::getInstance()->query($query);
				return ($result);
			}
		}
		else return false;
	}
	
	public function remove($user = null){
		if ($this->size == 1) {
			if ($this->requesting != null) if (!$this->remove_requesting($this->requesting)) return false;
			$query = "DELETE FROM `".$this->ballot."` WHERE `group_id` = ".$this->group_id;
			$result = Database::getInstance()->query($query);
			if ($result) return true;
			else return false;
		}
		else{
			if ($user != null){
				Database::getInstance()->query("begin");
				$new_crsids = array();
				$new_size = $this->size - 1;
				foreach ($this->members as $current_member) if ($current_member != $user) array_push($new_crsids, $current_member);
				$query = "UPDATE `".$this->ballot."` SET `crsids` = '".implode(",", $new_crsids)."', `size` = '".$new_size."' WHERE `group_id` = ".$this->group_id;
				$result = Database::getInstance()->query($query);
				if ($result){
					if (!$this->updatePriority()){
						Database::getInstance()->query("rollback");
						return false;
					}
					else{
						Database::getInstance()->query("commit");
						return true;
					}
				}
				else{
					Database::getInstance()->query("rollback");
					return false;
				}
			}
			else return false;
		}
	}
	
	public function join($user, $group_id){
		if ($this->remove($user->getCRSID())){
			if ($user->decline(array($group_id))){
				Database::getInstance()->query("begin");
				$group = new Group($group_id, $this->ballot);
				$new_size = $group->size + 1;
				array_push($group->members, $user->getCRSID());
				$query = "UPDATE `".$this->ballot."` SET `crsids` = '".implode(",", $group->members)."', `size` = ".$new_size." WHERE `group_id` = ".$group_id;
				$result = Database::getInstance()->query($query);
				if ($result){
					if ($group->updatePriority()){
						$query = "UPDATE `users` SET `group_id` = ".$group_id." WHERE `crsid` = '".$user->getCRSID()."'"; 
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
					else{
						Database::getInstance()->query("rollback");
						return false;
					}
				}
				else{
					Database::getInstance()->query("rollback");
					return false;
				}
			}
			else return false;
		}
		else return false;
	}

	public function send_requests($requests){
		Database::getInstance()->query("begin");
		$old_requests = array();
		foreach($requests as $request){
			$query = "SELECT `requests` FROM `users` WHERE `crsid` = '".$request."'";
			$result = Database::getInstance()->query($query);
			if ($result){
				$row = $result->fetch_assoc();
				if ($row['requests'] == NULL || $row['requests'] == "") $query = "UPDATE `users` SET `requests` = '".$this->group_id."' WHERE `crsid` = '".$request."'";
				else {
					$old_requests = explode(",",$row['requests']);
					array_push($old_requests, $this->group_id);
					$query = "UPDATE `users` SET `requests` = '".implode(",",$old_requests)."' WHERE `crsid` = '".$request."'";
				}
				$result = Database::getInstance()->query($query);
				if (!$result){
					Database::getInstance()->query("rollback");
					return false;
				}
			}
			else{
				Database::getInstance()->query("rollback");
				return false;
			}
		}
		if ($this->requesting == NULL || $this->requesting[0] == "") $requesting = implode(",",$requests);
		else $requesting = implode(",", array_merge($this->requesting, $requests));
		$query = "UPDATE `".$this->ballot."` SET `requesting` = '".$requesting."' WHERE `group_id` = ".$this->group_id;
		$result = Database::getInstance()->query($query);
		if (!$result){
			Database::getInstance()->query("rollback");
			return false;
		}
		else{
			Database::getInstance()->query("commit");
			return true;
		}
	}
	
	public function remove_members($members){
		Database::getInstance()->query("begin");
		$new_crsids = array();
		$new_size = $this->size;
		if (is_array($members)){
			foreach ($this->members as $current_member) {
				$remove = 0;
				foreach ($members as $remove_member) {
					if ($current_member == $remove_member) {
						$remove = 1;
						$new_size -= 1;
					}
				}
				if (!$remove) array_push($new_crsids, $current_member);
			}
		}
		else{
			foreach ($this->members as $current_member) if ($current_member != $members) array_push($new_crsids, $current_member);
			$new_size -= 1;
		}
		$this->members = $new_crsids;
		$query = "UPDATE `".$this->ballot."` SET `crsids` = '".implode(",", $new_crsids)."', `size` = '".$new_size."' WHERE `group_id` = ".$this->group_id;
		$result = Database::getInstance()->query($query);
		if ($result){
			if ($this->updatePriority()){
				if (is_array($members)){
					foreach($members as $member){
						$user = new User($member);
						if (!$user->newGroup()) return false;
					}
					Database::getInstance()->query("commit");
					return true;
				}
				else{
					$user = new User($members);
					if ($user->newGroup()){
						Database::getInstance()->query("commit");
						return true;
					}
					else return false;
				}
			}
			else{
				Database::getInstance()->query("rollback");
				return false;
			}
		}
		else{
			Database::getInstance()->query("rollback");
			return false;
		}
	}
	
	public function remove_requesting($requesting){
		Database::getInstance()->query("begin");
		$new_requesting = array();
		foreach ($this->requesting as $current_requesting) {
			$remove = 0;
			foreach ($requesting as $remove_requesting) if ($current_requesting == $remove_requesting) $remove = 1;
			if (!$remove) array_push($new_requesting, $current_requesting);
		}
		if (empty($new_requesting)) $str_requesting = "NULL";
		else $str_requesting = "'".implode(",", $new_requesting)."'";
		$query = "UPDATE `".$this->ballot."` SET `requesting` = ".$str_requesting." WHERE `group_id` = ".$this->group_id;
		$result = Database::getInstance()->query($query);
		if ($result){
			foreach($requesting as $crsid){
				$query = "SELECT `requests` FROM `users` WHERE `crsid` = '".$crsid."'";
				$result = Database::getInstance()->query($query);
				if ($result){
					$row = $result->fetch_assoc();
					if ($row['requests'] == $this->group_id) $str_requests = "NULL";
					else{
						$new_requests = array();
						foreach (explode(",", $row['requests']) as $request) if ($request != $this->group_id) array_push($new_requests, $request);
						$str_requests = "'".implode(",", $new_requests)."'";
					}
					$query = "UPDATE `users` SET `requests` = ".$str_requests." WHERE `crsid` = '".$crsid."'";
					$result = Database::getInstance()->query($query);
					if (!$result){
						Database::getInstance()->query("rollback");
						return false;
					}
				}
				else{
					Database::getInstance()->query("rollback");
					return false;
				}
			}
			Database::getInstance()->query("commit");
			return true;
		}
		else{
			Database::getInstance()->query("rollback");
			return false;
		}
	}
	
	public function decline($user, $ballot_name, $decline_groups) {
		Database::getInstance()->query("begin");
		if ($user->getRequests() == $decline_groups[0]) $str_requests = "NULL";
		else{
			$new_requests = array();
			foreach (explode(",", $user->getRequests()) as $request){
				$decline = 0;
				foreach ($decline_groups as $decline_group) if ($request == $decline_group) $decline = 1;
				if (!$decline) array_push($new_requests, $request);
			}
			$str_requests = "'".implode(",", $new_requests)."'";
		}
		$query = "UPDATE `users` SET `requests` = ".$str_requests." WHERE `crsid` = '".$user->getCRSID()."'";
		$result = Database::getInstance()->query($query);
		if ($result){
			$errors = 0;
			foreach ($decline_groups as $group){
				$query = "SELECT `requesting` FROM `".$ballot_name."` WHERE `group_id` = ".$group;
				$result = Database::getInstance()->query($query);
				if ($result){
					$row = $result->fetch_assoc();
					if ($row['requesting'] == $user->getCRSID()) $str_requesting = "NULL";
					else {
						$new_requesting = array();
						foreach (explode(",", $row['requesting']) as $requesting) if ($requesting != $user->getCRSID()) array_push($new_requesting, $requesting);
						$str_requesting = "'".implode(",", $new_requesting)."'"; 
					}
					$query = "UPDATE `".$ballot_name."` SET `requesting` = ".$str_requesting." WHERE `group_id` = ".$group;
					$result = Database::getInstance()->query($query);
					if (!$result){
						Database::getInstance()->query("rollback");
						return false;
					}
				}
				else{
					Database::getInstance()->query("rollback");
					return false;
				}
			}
			Database::getInstance()->query("commit");
			return true;
		}
		else{
			Database::getInstance()->query("rollback");
			return false;
		}
	}

	public function newPosition($new_position){
		Database::getInstance()->query("begin");
		$updated_order = array();
		if ($new_position > $this->order){
			$query = "SELECT `group_id` FROM `".$this->ballot."` WHERE `order` <= ".$new_position." AND `order` > ".$this->order." ORDER BY `order` DESC";
			$result = Database::getInstance()->query($query);
			if ($result){
				$updated_order[$new_position] = $this->group_id;
				while(($row = $result->fetch_assoc()) != false){
					$new_position -= 1;
					$updated_order[$new_position] = $row['group_id'];
				}
			}
			else {
				Database::getInstance()->query("rollback");
				return false;
			}
		}
		else {
			$query = "SELECT `group_id` FROM `".$this->ballot."` WHERE `order` < ".$this->order." AND `order` >= ".$new_position." ORDER BY `order` ASC";
			$result = Database::getInstance()->query($query);
			if ($result){
				$updated_order[$new_position] = $this->group_id;
				while(($row = $result->fetch_assoc()) != false){
					$new_position += 1;
					$updated_order[$new_position] = $row['group_id'];
				}
			}
			else {
				Database::getInstance()->query("rollback");
				return false;
			}
		}
		foreach ($updated_order as $order => $group_id){
			$query = "UPDATE `".$this->ballot."` SET `order` = ".$order." WHERE `group_id` = ".$group_id;
			$result = Database::getInstance()->query($query);
			if (!$result){
				Database::getInstance()->query("rollback");
				return false;
			}
		}
		Database::getInstance()->query("commit");
		return true;
	}
	
	public function allocateRooms($rooms){
		Database::getInstance()->query("begin");
		$query = "UPDATE `room_ballot` SET `rooms` = '".implode(",",$rooms)."' WHERE `group_id` = ".$this->group_id;
		$result = Database::getInstance()->query($query);
		if ($result){
			if ($this->size == 1){
				$query = "UPDATE `users` SET `room` = ".$rooms[0]." WHERE `crsid` = '".$this->admin."'";
				$result = Database::getInstance()->query($query);
				if (!$result){
					Database::getInstance()->query("rollback");
					return false;
				}
			}
			$query = "UPDATE `rooms` SET `available` = 0 WHERE `id` IN ('".implode("', '", $rooms)."')";
			$result = Database::getInstance()->query($query);
			if ($result){
				$ballot = new BallotMaker();
				if ($ballot->pushOrder()){
					Database::getInstance()->query("commit");
					return true;
				}
				else{
					Database::getInstance()->query("rollback");
					return false;
				}
			}
			else{
				Database::getInstance()->query("rollback");
				return false;
			}
		}
		else{
			Database::getInstance()->query("rollback");
			return false;
		}
	}
	
	public function allocateHouse($house){
		Database::getInstance()->query("begin");
		$query = "UPDATE `housing_ballot` SET `house` = ".$house." WHERE `group_id` = ".$this->group_id;
		$result = Database::getInstance()->query($query);
		if ($result){
			$query = "UPDATE `houses` SET `available` = 0 WHERE `id` = ".$house;
			$result = Database::getInstance()->query($query);
			if ($result){
				$query = "SELECT `rooms` FROM `houses` WHERE `id` = ".$house;
				$result = Database::getInstance()->query($query);
				if ($result){
					$query = "UPDATE `rooms` SET `available` = 0 WHERE `id` IN (".$result->fetch_assoc()['rooms'].")";
					$result = Database::getInstance()->query($query);
					if ($result){
						$ballot = new BallotMaker();
						if ($ballot->pushOrder(1)){
							Database::getInstance()->query("commit");
							return true;
						}
						else{
							Database::getInstance()->query("rollback");
							return false;
						}
					}
					else{
						Database::getInstance()->query("rollback");
						return false;
					}
				}
				else{
					Database::getInstance()->query("rollback");
					return false;
				}
			}
			else{
				Database::getInstance()->query("rollback");
				return false;
			}
		}
		else{
			Database::getInstance()->query("rollback");
			return false;
		}
	}
}
?>
