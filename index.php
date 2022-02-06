<?php

require_once "app/Objects/ErrorHandler.php";
require_once "app/Maintenance.php";
require_once "app/Objects/Database.php";
require_once "app/Layout.php";
require_once "app/Registration.php";
require_once "app/GroupEditor.php";
require_once "app/RoomViewer.php";
require_once "app/BallotViewer.php";
require_once "app/PageEditor.php";
require_once "app/ControlPanel.php";
require_once "app/BallotEditor.php";
require_once "app/RoomAllocator.php";
require_once "app/RoomEditor.php";
require_once "app/ImageEditor.php";

// buffer the output
ob_start();

// if no page is requested then serve up the home page
if (isset($_GET["q"]) && $_GET['q'] != "") {
    $url = rtrim($_GET["q"], "/");
} else {
    $url = "home";
}

$queryString = "SELECT *  FROM `pages` WHERE `url` LIKE '" . $url . "'";
$result = Database::getInstance()->query($queryString);
$row = $result->fetch_assoc();

Layout::HTMLheader("Fitz JCR Housing Ballot System");
Layout::HTMLnavbar();

// check if the page requested actually exists or not
if (isset($row)) {
    Layout::HTMLcontent($row["title"], $row["content"]);

    // paint any other page content that is more than just text
    switch ($url) {
    	case "home":
    		break;
    	case "registration":
    		Registration::page();
    		break;
    	case "groups":
    		GroupEditor::page();
    		break;
    	case "rooms":
    		RoomViewer::page(true);
    		break;
    	case "houses":
    		RoomViewer::page(false);
    		break;
    	case "roomballot":
    		BallotViewer::page(true);
    		break;
    	case "housingballot":
    		BallotViewer::page(false);
    		break;
    	case "pageeditor":
    		PageEditor::page();
    		break;
    	case "controlpanel":
    		ControlPanel::page();
    		break;
    	case "balloteditor":
    		BallotEditor::page();
    		break;
    	case "roomallocator":
    		RoomAllocator::page();
    		break;
    	case "roomeditor":
    		RoomEditor::page();
    		break;
    	case "imageeditor":
    		ImageEditor::page();
    		break;
    	default:
    		throw new Exception("Failed to retrieve page name");
    }
} else {
    http_response_code(404);
    Layout::HTMLcontent("Fitz JCR Housing Ballot System", "The page requested does not exist.");
}

Layout::HTMLfooter();

// return the buffered content all at once
ob_flush();

?>
