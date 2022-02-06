<?php
require_once "Objects/User.php";
require_once "Objects/BallotMaker.php";
require_once "Objects/HTML.php";

class Registration {
	public static function page() {
		$user = new User();
		if ($user->isuser()) HTML::HTMLerror("You are already registered in the ballot");
		else {
			$ballot = new BallotMaker();
			if ($ballot->getStage() < 4){
				if (isset($_POST['submit_register'])){
					$errors = 0;
					if (!isset($_POST['consent'])){
						HTML::HTMLerror("You must agree to the terms of the ballot before you can use this system");
						$errors = 1;
					}
					if (!isset($_POST['name']) || $_POST['name'] == ""){
						HTML::HTMLerror("You must enter your name");
						$errors = 1;
					}
					elseif (!HTML::Stringchecker($_POST['name'])){
						HTML::HTMLerror("Sorry, you have entered an invalid character or word");
						$errors = 1;
					}
					if (!isset($_POST['year']) || $_POST['year'] == ""){
						HTML::HTMLerror("You must select your current year");
						$errors = 1;
					}
					if (!isset($_POST['room_ballot']) || $_POST['room_ballot'] == ""){
						HTML::HTMLerror("You must select a ballot");
						$errors = 1;
					}
					if ($errors == 0){
						if ($_POST['room_ballot'] == 0 && $_POST['year'] != "FIRSTYEAR") HTML::HTMLerror("Sorry, only first years can enter the housing ballot");
						elseif ($user->register($_POST['name'], $_POST['year'], $_POST['room_ballot'])){
							HTML::HTMLsuccess("You have successfully been added to the ballot! Please click on another tab or refresh the page for your Group Editor to be made available to you");
							return;
						}
						else HTML::HTMLerror("An error has occured adding you to the ballot, please email jcr.website@fitz.cam.ac.uk");
					}
				}?>
  	<div class="container">
  	<form action = "" method = "POST">
    <p>Please enter your details below</p>
    <p>Full Name: <input type="text" name="name" maxlength = "255"></p>
    <p>Current year: 
    <select name="year">
		<option value="">Please select</option>
		<option value="FIRSTYEAR">First year</option>
		<option value="SECONDYEAR">Second year</option>
		<option value="THIRDYEAR">Third year or above</option>
		<option value="THIRDYEARABROAD">Third year (currently living abroad)</option>
    </select></p>
    <p>Ballot: 
    <select name = "room_ballot">
    	<option value = "">Please select</option>
    	<option value = "1">Room Ballot</option>
   		<?if ($ballot->getStage() == 0) echo('<option value = "0">Housing Ballot</option>');?>
    </select></p>
    <input type="checkbox" name="consent" />
    <label for="consent">I consent to my data being used for the Fitzwilliam JCR Room Ballot</label><br />
    <input type="submit" name="submit_register" value="Submit" />
    </form>
  	</div>
  			<?}
  			else HTML::HTMLerror("Sorry, no ballots are open for registration right now");
		}
  }
}