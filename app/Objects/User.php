<?php

require_once "Database.php";
require_once "Group.php";
require_once "Version.php";
require_once "HTML.php";

class User {
  protected $crsid = NULL;
  protected $admin = 0;
  protected $user = 0;
  protected $name = NULL;
  protected $searching = NULL;
  protected $room = NULL;
  protected $group = null;
  protected $proxy = null;
  protected $requests = null;
  protected $priority = null;
  protected $ballot = NULL;
  protected $access = NULL;

  public function __construct($raven_crsid = $_SERVER['REMOTE_USER']) {
    // https://wiki.cam.ac.uk/raven/Accessing_authentication_information
    // Get logged in user
    $this->crsid = $raven_crsid;
    // update user/admin status
    $query = "SELECT * FROM users WHERE crsid = '".$this->crsid."'";
    $result = Database::getInstance()->query($query);
    if ($result){
    	if ($result->num_rows > 0){
    		$this->user = 1;
    		$row = $result->fetch_assoc();
    		$this->name = $row['name'];
    		$this->group = $row['group_id'];
    		$this->room = $row['room'];
    		$this->proxy = $row['proxy'];
    		$this->searching = $row['searching'];
    		$this->requests = $row['requests'];
    		$this->priority = $row['priority'];
    		$this->access = explode(",", $row['access']);
    		if ($row['room_ballot'] == 0) $this->ballot = "housing_ballot";
    		else $this->ballot = "room_ballot";
    	}
    }
    else throw new Exception("Failed to retrieve user by crsid");
    $query = "SELECT `name` FROM admin WHERE crsid = '".$this->crsid."'";
    $result = Database::getInstance()->query($query);
    if ($result){
    	if ($result->num_rows > 0){
    		$this->admin = 1;
    		if (!$this->user) $this->name = $result->fetch_assoc()['name'];
    	}
    }
    else throw new Exception("Failed to retrieve admin by crsid");
  } 
  
  
  public function register($name, $priority, $room_ballot) {
  	Database::getInstance()->query("begin");
  	$this->name = $name;
  	$this->priority = $priority;
  	$query = "INSERT INTO `users` (`crsid`, `name`, `priority`, `room_ballot`) VALUES ('".$this->crsid."', '".$this->name."', '".$this->priority."', '".$room_ballot."')";
  	$result = Database::getInstance()->query($query);
  	if ($result){
  		if ($room_ballot == 1) $this->ballot = "room_ballot";
  		else $this->ballot = "housing_ballot";
  		if ($this->newGroup()){
  			Database::getInstance()->query("commit");
  			return true;
  		}
  		else return false;
  	}
  	else{
  		Database::getInstance()->query("rollback");
  		return false;
  	}
  }
  
  public function newGroup(){
  	# A Database instance has to have already begun, ensure to still commit at the end of any function this one is used in
  	$query = "INSERT INTO `".$this->ballot."` (`crsids`, `priority`) VALUES ('".$this->crsid."','".$this->priority."')";
  	$result = Database::getInstance()->query($query);
  	if ($result){
  		$query = "SELECT `group_id` FROM `".$this->ballot."` WHERE `crsids` = '".$this->crsid."'";
  		$result = Database::getInstance()->query($query);
  		if ($result){
  			$query = "UPDATE `users` SET `group_id` = ".$result->fetch_assoc()['group_id'].", `room_ballot` = ";
  			if ($this->ballot == "room_ballot") $query .= 1;
  			else $query .= 0;
  			$query .= " WHERE `crsid` = '".$this->crsid."'";
  			$result = Database::getInstance()->query($query);
  			if ($result) return true;
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
  
  public function isadmin() {
  	return $this->admin;
  }
  
  public function isuser() {
  	return $this->user;
  }

  public function getCRSID() {
    return $this->crsid;
  }

  public function getName() {
    return $this->name;
  }
  
  public function getGroup() {
  	return $this->group;
  }
  
  public function isSearching() {
  	return $this->searching;
  }

  public function getProxy(){
  	return $this->proxy;
  }
  
  public function getRoom(){
  	return $this->room;
  }
  
  public function getBallot(){
  	return $this->ballot;
  }
  
  public function getRequests(){
  	return $this->requests;
  }
  
  public function getPriority(){
  	return $this->priority;
  }
  
  public function getAccess(){
  	return $this->access;
  }
  
  public function setProxy($proxy){
  	$query = "UPDATE `users` SET `proxy` = '".$proxy."' WHERE `crsid` = '".$this->crsid."'";
  	$result = Database::getInstance()->query($query);
  	if ($result) return true;
  	else return false;
  }
  
  public function lock(){
  	$query = "UPDATE `users` SET `searching` = 0 WHERE `crsid` = '".$this->crsid."'";
  	$result = Database::getInstance()->query($query);
  	if ($result){
  		$this->searching = 0;
  		return true;
  	}
  	else return false;
  }
  
  public function unlock(){
  	$query = "UPDATE `users` SET `searching` = 1 WHERE `crsid` = '".$this->crsid."'";
  	$result = Database::getInstance()->query($query);
  	if ($result){
  		$this->searching = 1;
  		return true;
  	}
  	else return false;
  }
  
  public function decline($decline_groups) {
  	Database::getInstance()->query("begin");
  	if ($this->requests == $decline_groups[0]) $str_requests = "NULL";
  	else{
  		$new_requests = array();
  		foreach (explode(",", $this->requests) as $request){
  			$decline = 0;
  			foreach ($decline_groups as $decline_group) if ($request == $decline_group) $decline = 1;
  			if (!$decline) array_push($new_requests, $request);
  		}
  		if (empty($new_requests)) $str_requests = "NULL";
  		else $str_requests = "'".implode(",", $new_requests)."'";
  	}
  	$query = "UPDATE `users` SET `requests` = ".$str_requests." WHERE `crsid` = '".$this->crsid."'";
  	$result = Database::getInstance()->query($query);
  	if ($result){
  		$errors = 0;
  		foreach ($decline_groups as $group){
  			$query = "SELECT `requesting` FROM `".$this->ballot."` WHERE `group_id` = ".$group;
  			$result = Database::getInstance()->query($query);
  			if ($result){
  				$row = $result->fetch_assoc();
  				if ($row['requesting'] == $this->crsid) $str_requesting = "NULL";
  				else {
  					$new_requesting = array();
  					foreach (explode(",", $row['requesting']) as $requesting) if ($requesting != $this->crsid) array_push($new_requesting, $requesting);
  					$str_requesting = "'".implode(",", $new_requesting)."'";
  				}
  				$query = "UPDATE `".$this->ballot."` SET `requesting` = ".$str_requesting." WHERE `group_id` = ".$group;
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
  
  public function destroy_user(){
  	$group = new Group($this->group, $this->ballot);
  	if ($group->getSize() == 1) {
  		if(!$group->remove()) return false;
  	}
  	elseif (!$group->remove($this->crsid)) return false;
  	if ($this->requests != null && $this->requests[0] != "") if (!$this->decline(array($this->requests))) return false;
  	$query = "DELETE FROM `users` WHERE `crsid` = '".$this->crsid."'";
  	$result = Database::getInstance()->query($query);
  	if ($result) return true;
  	else return false;
  }
  
  public function swapBallot(){
  	if ($this->requests != null && $this->requests[0] != ""){
  		Database::getInstance()->query("commit");
  		if (!$this->decline(explode(",", $this->requests))) return false;
  	}
  	$group = new Group($this->group, $this->ballot);
  	if ($group->getSize() > 1){
  		if (!$group->remove($this->crsid)) return false;
  	}
  	else if (!$group->remove()) return false;
  	Database::getInstance()->query("begin");
  	$this->ballot = "room_ballot";
  	if ($this->newGroup()){
  		if ($this->unlock()) return true;
  		else return false;
  	}
  	else return false;
  }
}
