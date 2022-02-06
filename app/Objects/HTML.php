<?php
require_once "Database.php";

class HTML{
	
  public static function HTMLsuccess(string $string){ ?>
    <div class="container">
    <div class="alert alert-success">
      <?= $string; ?>
    </div>
    </div>
<?  }

  public static function HTMLwarning(string $string){ ?>
    <div class="container">
    <div class="alert alert-warning">
      <?= $string; ?>
    </div>
    </div>
<?  }

  public static function HTMLerror(string $string){ ?>
    <div class="container">
    <div class="alert alert-danger">
      <?= $string; ?>
    </div>
    </div>
<?  }

  public static function Stringchecker(string $string){
  	$characters = array("'", '"', "`", ";", "$", "<", ">");
  	$commands = array("delete", "insert", "drop", "alter", "update", "create", "use");
  	$lower_string = strtolower($string);
  	foreach ($characters as $character) if (strstr($lower_string, $character)) return false;
  	$a_string = array();
  	$a_string = explode(" ", $lower_string);
  	foreach($a_string as $string) foreach ($commands as $command) if ($string == $command) return false;
  	return true;
  }
  
  public static function Integerchecker($str_integer){
  	$numbers = array(1,2,3,4,5,6,7,8,9,0);
  	$integers = str_split($str_integer);
  	$errors = 0;
  	foreach ($integers as $integer){
  		$is_number = 0;
  		foreach ($numbers as $number) if ($number == $integer) $is_number = 1;
  		if (!$is_number) $errors = 1;
  	}
  	if ($errors) return false;
  	else return true;
  }
  
  public static function insertValues($text){
  	$query = "SELECT * FROM `key_value`";
  	$result = Database::getInstance()->query($query);
  	if ($result){
  		while (($row = $result->fetch_assoc()) != false){
  			$search = "{{".$row['key']."}}";
  			$text = preg_replace($search, $row['value'], $text);
  		}
  		return $text;
  	}
  	else throw new Exception("Unable to retrieve key_value pairs");
  }
  
  public static function getValue($key){
  	$query = "SELECT `value` FROM `key_value` WHERE `key` = '".$key."'";
  	$result = Database::getInstance()->query($query);
  	if ($result) return $result->fetch_assoc()['value'];
  	else throw new Exception("Unable to retrieve key");
  }
  
  public static function sendEmail($crsid, $subject) {
  	switch($subject){
  		case "Your turn in the ballot!":
  			$body = '
<p>Hello,</p>
<p>It is your turn in the ballot! You need to allocate rooms for yourself or group, please go to <a href="https://roomballot.fitzjcr.com/">https://roomballot.fitzjcr.com/</a> as soon as you can, to do so!</p>
<p>Many thanks,</p>
<p>the Fitz JCR</p>';
  			break;
  		case "URGENT: Please complete the ballot asap":
  			$body ='
<p>Hello,</p>
<p>You are taking a while to allocate yourself or your group rooms, please go to <a href="https://roomballot.fitzjcr.com/">https://roomballot.fitzjcr.com/</a> as soon as you can, to do so!</p>
<p>Many thanks,</p>
<p>the Fitz JCR</p>';
  			break;
  		case "URGENT: You have been given proxy access":
  			$body ='
<p>Hello,</p>
<p>A group has taken too long allocating themselves rooms, as their proxy, you have now been given permission to choose for them. Please go to <a href="https://roomballot.fitzjcr.com/">https://roomballot.fitzjcr.com/</a> as soon as you can, to do so!</p>
<p>Many thanks,</p>
<p>the Fitz JCR</p>';
  			break;
  		case "URGENT: Your proxy has been given access":
  			$body ='
<p>Hello,</p>
<p>You have taken too long to allocate yourself or your group rooms, therefore your proxy has now been given access to do so. This does not prevent you from still doing it yourself, please go to <a href="https://roomballot.fitzjcr.com/">https://roomballot.fitzjcr.com/</a> as soon as you can, to do so!</p>
<p>Many thanks,</p>
<p>the Fitz JCR</p>';
  			break;
  		case "URGENT: Pushed to end of ballot":
  			$body ='
<p>Hello,</p>
<p>I am sorry but both you and your proxy have now taken too long to allocate yourself a room. As a result of this, you have now been pushed to the end of the ballot so it can continue, please wait again until it is your turn.</p>
<p>Many thanks,</p>
<p>the Fitz JCR</p>';
  			break;
  		case "Ballot Completed!":
  			$body ='
<p>Hello,</p>
<p>The current ballot has now finished, please check to ensure no errors or issues have occured!</p>';
  			break;
  		default:
  			throw new Exception("Unable to retrieve email content");
  		
  	}
  	mail($crsid."@cam.ac.uk",
  		"=?UTF-8?B?" . base64_encode($subject) . "?=",
  		$body,
  		"MIME-Version: 1.0\r\nContent-type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: 8bit\r\nX-roomballot: " . Version::getVersion()
  		);
  }
}?>