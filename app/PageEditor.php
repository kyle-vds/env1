<?php
require_once "Objects/Database.php";
require_once "Objects/User.php";
require_once "Objects/HTML.php";

class PageEditor {
	public static function page() {
		if(isset($_POST['submit_page'])){
			$query = "UPDATE pages SET content = '".$_POST['text']."' WHERE url = '".$_POST['current_page']."'";
			$result = Database::getInstance()->query($query, true);
			if($result) HTML::HTMLsuccess('Page content successfully updated!');
			else HTML::HTMLerror("Failed to update content, this maybe because of a single ' character, these cause problems when used by the code. If so, use {ap} instead");
		}
		$user = new User();
		if(!$user->isadmin()) {
			HTML::HTMLerror("You do not have admin permission");
			return;
		}
    else {?>
    <div class = "container">  
    <form action = "" method = "POST">
    	<select name = "page">
    		<option value = "">Select a Page to Edit</option>
    		<?$query = "SELECT url, title FROM pages";
    		$result = Database::getInstance()->query($query);
    		while (($row = $result->fetch_assoc())!=false) echo("<option value = '".$row["url"]."'>".$row["title"]."</option>");
    		?>
    	</select>
    	<input type = "submit" name = "submit_select" value = "Select Page">
<?		$page = NULL;
		if(isset($_POST['submit_select'])) $page = $_POST['page'];
		elseif (isset($_POST['submit_page'])) $page = $_POST['current_page'];
		if ($page != NULL && $page != ""){	
			$query = "SELECT * FROM pages WHERE url = '".$page."'";
			$result = Database::getInstance()->query($query);
			if ($result){
				$row = $result->fetch_assoc();
				echo("<h3>".$row['title']."</h3>");
?>
    	<textarea name = "text" cols = "170" rows = "10"><?= $row['content'] ?></textarea>
    	<input type = "hidden" name = "current_page" value = "<? if (isset($_POST['page']) && $_POST['page'] != "") echo($_POST['page']); else echo($_POST['current_page']);?>">
    	<input type = "submit" name = "submit_page" value = "Update Page">
    	<?	}
    		else throw new Exception("Failed to retrieve page data");
		}?>
    </form>
    </div>
<?	}
	}
}