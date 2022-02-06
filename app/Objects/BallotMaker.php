<?php

require_once("Database.php");

class BallotMaker{
	
	protected $year = NULL;
	protected $stage = NULL;
	protected $name = NULL;
	protected $seed = NULL;
	protected $ballotPriorities = array();
	protected $criteria_col = NULL;
	protected $position = NULL;
	protected $proxy = NULL;
	
	public function __construct($room_ballot = 2){
		$query = "SELECT * FROM ballot_log WHERE stage != 6";
		$result = Database::getInstance()->query($query);
		if ($result){
			if ($result->num_rows > 0){
				$row = $result->fetch_assoc();
				$this->year = $row['year'];
				$this->stage = $row['stage'];
				$this->position = $row['position'];
				$this->proxy = $row['proxy'];
				if ($room_ballot == 2){
					if ($this->stage < 3) $room_ballot = 0;
					else $room_ballot = 1;
				}
				if ($room_ballot){
					$this->name = "room_ballot";
					$this->seed = $row['rb_seed'];
					$this->criteria_col = "priority";
					$this->ballotPriorities = ["Second Years and Third Years Abroad" => "= 'SECONDYEAR' OR 'THIRDYEARABROAD'", "Third Years with confirmed fourth" => "= 'THIRDYEAR'", "First Years" => "= 'FIRSTYEAR'"];
				}
				else {
					$this->name = "housing_ballot";
					$this->seed = $row['hb_seed'];
					$this->criteria_col = "size";
					$this->ballotPriorities = ["Groups of 9" => "= 9", "Groups of 8" => "= 8", "Groups of 7" => "= 7", "Groups of 6" => "= 6", "Groups of 5" => "= 5", "Groups of 4" => "= 4"];
				}
			}
			else $this->stage = 6;
		}
		else{
			HTML::HTMLerror("Error finding current ballot, please email jcr.website@fitz.cam.ac.uk");
		}
	}
	
	public function getStageName(){
		switch ($this->stage){
			case 0: 
				return "Registration Open";
			case 1: 
				return "Housing Ballot Groups Locked";
			case 2:
				return "Housing Ballot Order Locked";
			case 3: 
				return "Housing Ballot Closed";
			case 4: 
				return "Room Ballot Groups Locked";
			case 5:
				return "Room Ballot Order Locked";
			case 6: 
				return "Room Ballot Closed";
			default:
				throw new Exception("Failed to get ballot stage name");
		}
	}
	
	public function getStageInstruction(){
		switch ($this->stage){
			case 0:
				return "Lock the Housing Ballot Groups";
			case 1:
				return "Lock the Housing Ballot Order";
			case 2:
				return "Close the Housing Ballot";
			case 3:
				return "Lock the Room Ballot Groups";
			case 4:
				return "Lock the Room Ballot Order";
			case 5:
				return "Close the Room Ballot";
			default:
				throw new Exception("Failed to get ballot stage instruction");
		}
	}
	
	public function getYear(){
		return $this->year;
	}
	
	public function getStage(){
		return $this->stage;
	}
	
	public function getName(){
		return $this->name;
	}
	
	public function getSeed(){
		return $this->seed;
	}
	
	public function getBallotPriorities(){
		return $this->ballotPriorities;
	}
	
	public function getPosition(){
		return $this->position;
	}
	
	public function getCriteriaCol(){
		return $this->criteria_col;
	}
	
	public function showRemainingGroups(){
		$this->ballotPriorities["Groups currently with too few members for a house"] = "< 4";
		return;
	}
	
	public function getProxy(){
		return $this->proxy;
	}
	
	private static function fetchSeed() {
		$session = curl_init("https://www.random.org/integers/?num=1&min=100000000&max=1000000000&col=5&base=10&format=plain&rnd=new");
		curl_setopt($session, CURLOPT_HTTPGET, true);
		curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
		$response = curl_exec($session);
		curl_close ($session);
		return $response;
	}
	
	public function drawSeed(){
		$seed = self::fetchSeed();
		$this->seed = $seed * self::fetchSeed();
		$query = "UPDATE `ballot_log` SET `";
		if ($this->stage == 1) $query .= "hb_seed";
		elseif ($this->stage == 4) $query .= "rb_seed";
		else return false;
		$query .= "` = ".$this->seed." WHERE `year` = ".$this->year;
		$result = Database::getInstance()->query($query);
		return ($result);
	}
	
	public function drawOrder(){
		Database::getInstance()->query("begin");
		if ($this->seed != NULL && $this->seed != ""){
			mt_srand($this->seed);
			$order = array();
			foreach($this->ballotPriorities as $ballotPriority => $criteria){
				$shuffling = array();
				$query = "SELECT `group_id` FROM `".$this->name."` WHERE ".$this->criteria_col.$criteria;
				$result = Database::getInstance()->query($query);
				if ($result){
					$size = 0;
					while (($row = $result->fetch_assoc())!= false){
						array_push($shuffling, $row['group_id']);
						$size += 1;
					}
					for ($n = $size; $n > 0; $n --){
						if ($n == 1) array_push($order, $shuffling[0]);
						else {
							$index = mt_rand(0, $n - 1);
							array_push($order, $shuffling[$index]);
							array_splice($shuffling, $index, 1);
						}
					}
				}
				else{
					Database::getInstance()->query("rollback");
					return false;
				}
			}
			$position = 1;
			$errors = 0;
			foreach($order as $group_id){
				$query = "UPDATE `".$this->name."` SET `order` = ".$position." WHERE `group_id` = ".$group_id;
				$position += 1;
				$result = Database::getInstance()->query($query);
				if (!$result) $errors = 1;
			}
			if ($errors){
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
	
	public function PushBallot($year = null){
		Database::getInstance()->query("begin");
		if ($this->getStage() == 0){
			$query = "UPDATE `users` SET `searching` = 0 WHERE `room_ballot` = 0";
			$result = Database::getInstance()->query($query);
			if (!$result){
				Database::getInstance()->query("rollback");
				return false;
			}
		}
		elseif ($this->getStage() == 1 || $this->getStage() == 4){
			$query = "UPDATE `ballot_log` SET `position` = 1 WHERE `year` = ".$this->year;
			$result = Database::getInstance()->query($query);
			if (!$result){
				Database::getInstance()->query("rollback");
				return false;
			}
		}
		elseif ($this->getStage() == 2){
			$query = "SELECT `crsid` FROM `users` WHERE `room` IS NULL AND `room_ballot` = 0";
			$result = Database::getInstance()->query($query);
			if ($result){
				while (($row = $result->fetch_assoc()) != false){
					$user = new User($row['crsid']);
					if (!$user->swapBallot()){
						Database::getInstance()->query("rollback");
						return false;
					}
				}
			}
			else{
				Database::getInstance()->query("rollback");
				return false;
			}
		}
		elseif ($this->getStage() == 3){
			$query = "UPDATE `users` SET `searching` = 0 WHERE `room_ballot` = 1";
			$result = Database::getInstance()->query($query);
			if (!$result){
				Database::getInstance()->query("rollback");
				return false;
			}
		}
		elseif ($this->getStage() == 6){
			if (isset($year) && $year != ""){
				$query = "INSERT INTO `ballot_log` (`year`, `stage`) VALUES ('".$year."', 0)";
				$result = Database::getInstance()->query($query);
				if ($result){
					$db_delete = array('users', 'housing_ballot', 'room_ballot');
					$errors = 0;
					foreach($db_delete as $delete){
						if (!$errors){
							$query = "DELETE FROM `".$delete."`";
							$result = Database::getInstance()->query($query);
							if (!$result) $errors = 1;
						}
					}
					if (!$errors){
						$query = "UPDATE `access` SET `users` = NULL";
						$result = Database::getInstance()->query($query);
						if ($result){
							$db_update = array('rooms', 'houses');
							$errors = 0;
							foreach($db_update as $update){
								if (!$errors){
									$query = "UPDATE `".$update."` SET `available` = 1";
									$result = Database::getInstance()->query($query);
									if (!$result) $errors = 1;
								}
							}
							if (!$errors){
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
		
		$new_stage = $this->getStage() + 1;
		$query = "UPDATE `ballot_log` SET `stage` = ".$new_stage." WHERE `year` = ".$this->getYear();
		$result = Database::getInstance()->query($query);
		if ($result) {
			Database::getInstance()->query("commit");
			return true;
		}
		else {
			Database::getInstance()->query("rollback");
			return false;
		}
	}
	
	public function pushOrder($housing_ballot = 0, $prev_position = null){
		if ($prev_position != null) $new_position = $prev_position + 1;
		else $new_position = $this->position + 1;
		$query = "UPDATE `ballot_log` SET `position` = ".$new_position.", `proxy` = NULL WHERE `year` = ".$this->year;
		$result = Database::getInstance()->query($query);
		if ($result){
			$query = "SELECT `group_id` FROM `".$this->name."` WHERE `order` = ".$new_position;
			$result = Database::getInstance()->query($query);
			if ($result){
				if ($result->num_rows > 0){
					$group = new Group($result->fetch_assoc()['group_id'], $this->name);
					if ($housing_ballot){
						$query = "SELECT `id` FROM `houses` WHERE `size` = ".$group->getSize()." AND `available` = 1";
						$result = Database::getInstance()->query($query);
						if ($result->num_rows > 0){
							HTML::sendEmail($group->getAdmin(), "Your turn in the ballot!");
							return true;
						}
						else {
							if ($this->pushOrder(1, $new_position)) return true;
							else return false;
						}
					}
					else{
						HTML::sendEmail($group->getAdmin(), "Your turn in the ballot!");
						return true;
					}
				}
				else{
					$query = "SELECT `crsid` FROM `admin`";
					$result = Database::getInstance()->query($query);
					if ($result){
						while (($row = $result->fetch_assoc()) != null) HTML::sendEmail($row['crsid'], "Ballot Completed!");
						return true;
					}
					else return false;
				}
			}
			else return false;
		}
		else return false;
	}
	
	
	
	
	
}