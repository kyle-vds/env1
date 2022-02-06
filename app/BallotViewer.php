<?php
require_once "Objects/Database.php";
require_once "Objects/User.php";
require_once "Objects/BallotMaker.php";

class BallotViewer {
	public static function page($room = true) {
		if ($room) $ballot = new BallotMaker(1);
		else{
			$ballot = new BallotMaker(0);
			if ($ballot->getStage() == 0) $ballot->showRemainingGroups();
		}
		?>
  <div class = "container">	
  <p>Ballot Seed: <strong>
<?	if ($ballot->getSeed() == NULL || $ballot->getSeed() == "") echo("TBC");
	else echo($ballot->getSeed());
?></strong></p>
  <table class="table table-condensed table-bordered table-hover">
  <thead>
  <tr>
  <td>Position</td>
  <td>Group Members</td>
  </tr>
  </thead>
  
  <? foreach($ballot->getBallotPriorities() as $ballotPriority => $criteria){
  		$query = "SELECT `order`, `crsids` FROM ".$ballot->getName()." WHERE ".$ballot->getCriteriaCol().$criteria." ORDER BY `order`";
  		$result = Database::getInstance()->query($query);
  	?>      <tr><td colspan="2"><h3><?= $ballotPriority ?></h3></td></tr>
  	<?	if ($result){
  			while (($row = $result->fetch_assoc())!= false){?>
          <tr>
            <td class="col-md-1" style="text-align: right;"><?if ($row['order'] == NULL) echo("TBC"); else echo($row['order'])?></td>
            <td class="col-md-8"><?
            $crsids = array();
            $names = array();
            $crsids = explode(",", $row['crsids']);
            foreach($crsids as $crsid){
            	$user = new User($crsid);
            	array_push($names, $user->getName($crsid));
            }
            echo(implode("<br>", $names));
            ?></td>
          </tr>
<?     		}
  		}
  		else throw new Exception("Failed to get list of balloting members");
  	}
?>
   </table>
   </div><?
	}
}