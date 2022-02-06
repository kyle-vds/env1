<?php
require_once "Objects/Database.php";
require_once "Objects/User.php";
require_once "Objects/HTML.php";
require_once "Objects/Group.php";

class ImageEditor {
	public static function page() {
		if (isset($_POST['submit_upload'])){
			$errors = 0;
			if (!isset($_POST['location']) || $_POST['location'] == "") $errors = 1;
			if (!isset($_FILES['my_file']) || $_FILES['my_file']['name']=="") $errors = 1;
			if ($errors) HTML::HTMLerror("Am image file and a location to attach it to must be selected before it can be uploaded");
			else{
				$path = pathinfo($_FILES['my_file']['name']);
				$ext = $path['extension'];
				$types = array('jpg', 'jpeg', 'gif', 'png', 'apng', 'svg', 'bmp', 'ico');
				$valid = 0;
				foreach ($types as $type) if ($ext == $type) $valid = 1;
				if ($valid){
					$target_dir = "include/Ballot_images";
					$temp_name = $_FILES['my_file']['tmp_name'];
					$map = 0;
					$errors = 0;
					switch ($_POST['location']){
						case "blockmap":
							$map = 1;
							$target_dir .= "/Block_map";
							if (opendir($target_dir) != FALSE){
								while (($old_file = readdir()) != false) if (!($old_file == ".." || $old_file == ".")) unlink($target_dir."/".$old_file);
								closedir();
							}
							$filename = "/Map_of_Blocks";
							break;
						case "housemap":
							$map = 1;
							$target_dir .= "/House_map";
							if (opendir($target_dir) != FALSE){
								while (($old_file = readdir()) != false) if (!($old_file == ".." || $old_file == ".")) unlink($target_dir."/".$old_file);
								closedir();
							}
							$filename = "/Map_of_Houses";
							break;
						default:
							if (isset($_POST['description']) && $_POST['description'] != ""){
								if (HTML::Stringchecker($_POST['description'])){
									$house = new House($_POST['location']);
									$filename = "/".$house->getName();
									if ($house->getImages() != NULL) $image_num = count($house->getImages()) + 1;
									else $image_num = 1;
									$filename .= "_Image_". $image_num;
								}
								else $errors = 1;
							}
							else $errors = 1;
					}
					if (!$errors) {
						$filename = str_replace(" ", "_", $filename);
						$path_filename_ext = $target_dir.$filename.".".$ext;
						if (file_exists($path_filename_ext)) HTML::HTMLerror("Sorry, the file you are trying to upload already exists");
						else{
							move_uploaded_file($temp_name,$path_filename_ext);
							if ($map) HTML::HTMLsuccess("File Uploaded Successfully");
							else{
								Database::getInstance()->query("begin");
								$query = "INSERT INTO `images` (`src`, `description`, `house`) VALUES ('".$path_filename_ext."', '".$_POST['description']."', ".$house->getID().")";
								$result = Database::getInstance()->query($query);
								if ($result) {
									$query = "SELECT `id` FROM `images` WHERE `src` = '".$path_filename_ext."'";
									$result = Database::getInstance()->query($query);
									if ($result){
										if (!$house->update_images($result->fetch_assoc()['id']))$errors = 1;
									}
									else $errors = 1;
								}
								else $errors = 1;
								if ($errors) {
									HTML::HTMLerror("An error occured uploading the image, please contact jcr.website@fitz.cam.ac.uk");
									Database::getInstance()->query("rollback");
								}
								else{
									HTML::HTMLsuccess("File Uploaded Successfully");
									Database::getInstance()->query("commit");
								}
							}
						}
					}
					else HTML::HTMLerror("Please enter a short, and valid, description of the image, for example what room it shows");
				}
				else HTML::HTMLerror("Please upload a compatable image type: jpg, jpeg, gif, png, apng, svg, bmp or ico");
			}
		}
		if (isset($_POST['submit_remove'])){
			if (isset($_POST['remove'])){
				Database::getInstance()->query("begin");
				$errors = 0;
				$query = "SELECT * FROM `images` WHERE `id` IN ('".implode("', '", $_POST['remove'])."')";
				$result_images = Database::getInstance()->query($query);
				if ($result_images){
					while (($row = $result_images->fetch_assoc()) != null){
						unlink($row['src']);
						$query = "DELETE FROM `images` WHERE `id` = ".$row['id'];
						$result = Database::getInstance()->query($query);
						if ($result) {
							$house = new House($row['house']);
							$new_images = array();
							foreach($house->getImages() as $house_image) if ($house_image != $row['id']) array_push($new_images, $house_image);
							if (empty($new_images)) $str_images = "NULL";
							else $str_images = "'".implode(",", $new_images)."'";
							$query = "UPDATE `houses` SET `images` = ".$str_images." WHERE `id` = ".$row['house'];
							$result = Database::getInstance()->query($query);
							if (!$result) $errors = 1;
						}
						else $errors = 1;
					}
				}
				else $errors = 1;
				if ($errors){	
					Database::getInstance()->query("rollback");
					HTML::HTMLerror("An error occured when trying to delete an image, please contact jcr.website@fitz.cam.ac.uk");
				}
				else{
					Database::getInstance()->query("commit");
					HTML::HTMLsuccess("Successfully deleted images");
				}
			}
			else HTML::HTMLerror("Please select an image below to remove it");
		}
		$user = new User();
		if(!$user->isadmin()) {
			HTML::HTMLerror("You do not have admin permission");
			return;
		}
		else{
			$query = "SELECT `id`, `name` FROM `houses`";
			$result_houses = Database::getInstance()->query($query);
			if ($result_houses){
				$query = "SELECT * FROM `images` WHERE `src` NOT IN ('include/Ballot_images/Block_map/Map_of_Blocks', 'include/Ballot_images/House_map/Map_of_Houses')";
				$result_images = Database::getInstance()->query($query);
  			if ($result_images){?>
  <div class = "container">
  <form method="POST" enctype = "multipart/form-data">
  <p>To upload an image, please select a location for it to be displayed:
  <select name="location">
		<option value="">Please select</option>
		<option value="blockmap">Map of Blocks</option>
		<option value="housemap">Map of Houses</option>
				<?while (($row = $result_houses->fetch_assoc()) != null){?>
  		<option value="<?= $row['id'] ?>"><?= $row['name'] ?></option>
				<?}?>
  </select> 
  <p>For an image of a house/block, please give a short description of what it shows: <input type = "text" name = "description" maxlength = "255"></p>
  <p><input type = "file" name = "my_file"></p>
  <p><input type = "submit" name = "submit_upload" value = "Upload Image"></p>
  <p>To delete images, select them from the table below and click here: 
  <input type = "submit" name = "submit_remove" value = "Remove Images"></p>
  <table class="table table-condensed table-bordered table-hover">
  <thead>
  <tr>
  <td>Image</td>
  <td>House</td>
  <td>Description</td>
  <td>Remove</td>
  </tr>
  </thead>
				<?while (($row = $result_images->fetch_assoc()) != null){
					$house = new House($row['house']);?>
  <tr>
  <td><?= $row['src'] ?></td>
  <td><?= $house->getName() ?></td>
  <td><?= $row['description'] ?></td>
  <td><input type = "checkbox" name = "remove[]" value = "<?= $row['id'] ?>"></td>
  </tr>
				<?}?>  
  </table>
  </form>
  </div>
  		<?
  			}
  			else throw new Exception("Unable to retrieve images");
  		}
  		else throw new Exception("Unable to retrieve houses");
  	}
  }
}