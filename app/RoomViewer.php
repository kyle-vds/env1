<?php
require_once "Objects/Database.php";
require_once "Objects/Rooms.php";
require_once "Objects/HTML.php";
require_once "Objects/Houses.php";
require_once "Objects/AccessArrangement.php";

class RoomViewer {
	public static function page($room = true) {
		if (isset($_POST['select_house'])){
			$name = str_replace(" ", "_", $_POST['select_house']);
			if (isset($_POST[$name])){
				$house = new House($_POST[$name]);
				$query = "SELECT * FROM `images` WHERE `house` = ".$house->getID();
				$descriptions = [];
				$srcs = [];
				$images = Database::getInstance()->query($query);
				while(($image = $images->fetch_assoc()) != null){
					$srcs[] = $image['src'];
					$descriptions[] = $image['description'];
				}?>
  <div class='container'>
  <h2><?= $house->getName() ?></h2>
  <div class="row">
  <div class="col-md-4">
<?    			if($images->num_rows > 0){ ?>
  <div id="gallery">
  <div id="large">
  <div class="thumbnail">
  <a href='<?= $srcs[0] ?>' id="gallery-link">
  <img id="gallery-large" src="<?= $srcs[0] ?>" style="width: 100%;"/>
  </a>
  <div id="gallery-caption" class="caption">
  <?= $descriptions[0]; ?>
  </div>
  </div>
  </div>
  <div id="smalls" class='ballot-smalls'>
<?        			for($i = 0; $i < count($srcs); $i++){ ?>
  <a href="<?= $srcs[$i] ?>">
  <img class="ballot-gallery" src="<?= $srcs[$i]; ?>" width=100 />
  </a>
					<?}?>
  </div>
  </div>
  <script>
        			var galleryImg = document.getElementById("gallery-large");
        			var galleryLnk = document.getElementById("gallery-link");
        			var galleryDsc = document.getElementById("gallery-caption");
        			var smallImages = document.getElementById("smalls").getElementsByTagName("a");
        			var descs = [<?= '"'.join('", "', array_map(function($s){ return str_replace("\n", " ", addslashes($s)); }, $descriptions)).'"'; ?>];
        			for(i = 0; i < smallImages.length; i++){
          				smallImages[i].ord = i;
          				smallImages[i].onclick = function(e){
            			var img = this.getElementsByTagName("img")[0]
            			galleryImg.src = img.src;
            			galleryImg.attributes['title'] = img.attributes['title'];
            			galleryLnk.attributes["href"].value = img.src;
            			galleryDsc.innerHTML = descs[this.ord];
            			e.preventDefault();
            			return false;
          			}
        		}
  </script>
  				<?}?>
  </div>
  <div class="col-md-8">  
  <?= $house->getDescription() ?>
  				<?foreach($house->getFloors_Rooms() as $floor => $floors){?>
  <h3><?= $house->getFloor($floor)?></h3>
  <table class="table table-condensed table-bordered table-hover">
  <thead>
  <tr>
  <td>Room</td>
  <td>Rent</td>
  <td>Available?</td>
  <td>Access Arrangements</td>
  </tr>
  </thead>
  					<?foreach($floors as $rooms){
  						$room = new Room($rooms);?>
  <tr>
  <td><?= $room->getName()?></td>
  <td><?= $room->getPrice()?></td>
  <td><? if ($room->isAvailable()) echo("Yes"); else echo("No");?></td>
  <td>
  						<?if ($room->getAccess() != null){
  							$first_arrangement = 1;
  							foreach($room->getAccess() as $access){
  								if ($first_arrangement) $first_arrangement = 0;
  								else echo("<br>");
  								$arrangement = new Access($access);
  								echo($arrangement->getName());
  							}
  						}?>
  </td>
  </tr>
  					<?}?>
  </table>
  				<?}?>
  <form action = "" method = "POST">
  <input type = "submit" name = "return" value = "Return to Previous Page">
  </form>
  </div>
  </div>
  </div>
  			<?}
  			else throw new Exception("Unable to retrieve room data");
		}
  		else {
  			$query = "SELECT `id` FROM `houses` WHERE `house` = ";
  			if ($room) {
  				$query .= 0;
  				$title = "Block";
  				$src = "include/Ballot_images/Block_map/Map_of_Blocks";  		
  			}
  			else {
  				$query .= 1;
  				$title = "House";
  				$src = "include/Ballot_images/House_map/Map_of_Houses"; 
  			}
  			$result = Database::getInstance()->query($query);
  			if ($result) {?>
  <div class='container'>
  <div class="row">
  <div class="col-md-5"><img src='<?= $src ?>' width="406" height="576" usemap="#map" /></div>
  <div class="col-md-7">
  <form action = "" method = "POST">
  <table class="table table-condensed table-bordered table-hover">
  <thead>
  <tr>
  <td><?= $title ?></td>
  <td>Size</td>
  <td>Rooms Available</td>
  <td>Maximum Rent (pounds/week)</td>
  <td>Minimum Rent (pounds/week)</td>
  </tr>
  </thead>
    		    <?while(($row = $result->fetch_assoc()) != false){
    		    	$house = new House($row['id']);
    		    	if ($house->getRooms() != null){
    		    		$min_rent = 10000;
    		    		$max_rent = 0;
    		    		$available = 0;
    		    		foreach($house->getFloors_Rooms() as $floor){
    		    			foreach($floor as $rooms){
    		    				$room = new Room($rooms);
    		    				if ($room->isAvailable()) $available += 1;
    		    				if ($room->getPrice() < $min_rent) $min_rent = $room->getPrice();
    		    				elseif ($room->getPrice() > $max_rent) $max_rent = $room->getPrice();
    		    			}
    		    		}?>
   	<tr>
   	<td><input type = "submit" name = "select_house" value = "<?= $house->getName() ?>">
   	<input type = "hidden" name = "<?= str_replace(" ", "_", $house->getName()) ?>" value = "<?= $house->getID() ?>"></td>
   	<td><?= $house->getSize() ?></td>
   	<td><?= $available ?></td>
   	<td><?= $max_rent ?></td>
   	<td><?= $min_rent ?></td>
    </tr>    	
  					<?}
  					else {?>
  	<tr>
  	<td><?= $house->getName() ?></td>
  	<td></td><td></td><td></td><td></td>
  	</tr>
  					<?}
  				}?>
  	</table>
  	</form>
    </div>
  	</div>
  	</div>
  			<?}
  			else throw new Exception("Unable to retrieve houses");
		}
	}
}