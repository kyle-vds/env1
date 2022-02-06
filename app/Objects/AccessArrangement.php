<?php

require_once "User.php";

class Access {
	protected $id = null;
	protected $name = null;
	protected $users = null;
	
	public function __construct($id){
		$this->id = $id;
		$query = "SELECT * FROM `access` WHERE `id` = ".$id;
		$result = Database::getInstance()->query($query);
		if ($result){
			$row = $result->fetch_assoc();
			$this->name = $row['name'];
			if ($row['users'] != null && $row['users'] != "")$this->users = explode(",", $row['users']);
		}
		else throw new Exception("Unable to retrieve access arrangement data");
	}
	
	public function getID(){
		return $this->id;
	}
	
	public function getName(){
		return $this->name;
	}
	
	public function getUsers(){
		return $this->users;
	}
	
	public function remove(){
		if ($this->users != null) foreach ($this->users as $user) if (!$this->take($user)) return false;
		$query = "DELETE FROM `access` WHERE `id` = '".$this->id."'";
		$result = Database::getInstance()->query($query);
		if ($result) return true;
		else return false;
	}
	
	public function add($crsid){
		Database::getInstance()->query("begin");
		if ($this->users == null) $str_users = $crsid;
		else {
			array_push($this->users, $crsid);
			$str_users = implode(",", $this->users);
		}
		$query = "UPDATE `access` SET `users` = '".$str_users."' WHERE `id` = ".$this->id;
		$result = Database::getInstance()->query($query);
		if ($result){
			$user = new User($crsid);
			if ($user->getAccess()[0] == null) $str_access = $this->id;
			else {
				$new_access = array();
				$new_access = $user->getAccess();
				array_push($new_access, $this->id);
				$str_access = implode(",", $new_access);
			}
			$query = "UPDATE `users` SET `access` = '".$str_access."' WHERE `crsid` = '".$crsid."'";
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
		else {
			Database::getInstance()->query("rollback");
			return false;
		}
	}
	
	public function take($crsid){
		Database::getInstance()->query("begin");
		$new_users = array();
		foreach ($this->users as $users) if ($users != $crsid) array_push($new_users, $users);
		if (empty($new_users)) $str_users = "NULL";
		else $str_users = "'".implode(",", $new_users)."'";
		$query = "UPDATE `access` SET `users` = ".$str_users." WHERE `id` = ".$this->id;
		$result = Database::getInstance()->query($query);
		if ($result){
			$user = new User($crsid);
			$new_access = array();
			foreach($user->getAccess() as $access) if ($access != $this->id) array_push($new_access, $access);
			if (empty($new_access)) $str_access = "NULL";
			else $str_access = "'".implode(",", $new_access)."'";
			$query = "UPDATE `users` SET `access` = ".$str_access." WHERE `crsid` = '".$user->getCRSID()."'";
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
}