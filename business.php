<?php

//UNCOMMENT FOR DEBUG PURPOSES
/*
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
*/


include "functions.php";
$currentPage = htmlspecialchars($_SERVER["PHP_SELF"]);
$parentPage = 'business.php';

if (initiateSession()) {
	
	if(!databaseConnection()) {
		// echo 'Cannot connect to database';
		$loadContent = false;
		header("HTTP/1.1 301 Moved Permanently");
		header("Location: index.php");
	}
	
	if(!privateDatabaseConnection()) {
		$stats = false;
	} else {
		registerVisit();
	}
	
	if($_GET["control"] == "logout") {
		killSession();
		killCookie();
		$loadContent = false;
		header("HTTP/1.1 301 Moved Permanently");
		header("Location: /index.php");
	}
	
	if(isset($_GET["setLanguage"])) {
		if ($_GET["setLanguage"] == "en") {
			$_SESSION["language"] = "en";
		} elseif($_GET["setLanguage"] == "fr") {
			$_SESSION["language"] = "fr";
		} else {
			$_SESSION["language"] = "en";
		}
		
		// Check if we have to change language variable for user...
		if(!is_null($_SESSION['fullName'])) {
			// Update language in certificate...
			changeCertLanguage($_SESSION['language']);
		}
	}
	
	// Get language strings...
	if(!isset($_SESSION['language'])) { $_SESSION['language'] = 'en'; }
	$language = $_SESSION['language'];
	$sql = "SELECT id, $language FROM langStrings"; 
	$db_rawStrings = $conn->query($sql);
	//echo $sql;
	$strings = array();
	//var_dump($_SESSION);
	while($row = $db_rawStrings->fetch_assoc()) {
		$stringId = $row['id'];
		$strings["$stringId"] = $row["$language"];
	}

	if(isset($_SESSION["token"])) {
		if (authenticateToken()) {
			$loadContent = true;
			$activeSession = true;
			
			if(in_array($_SESSION['userId'], $config['cmsAdmin'])) {
				$isAdmin = true;
			} else {
				$isAdmin = false;
			}
			
			$siteTitle = $strings['35'];
			$pageTitle = $siteTitle . ' - ' . $_SESSION["fullName"] . ' - ' . $strings['251'];
			$pageDescription = 
			$pageKeyworkds = 
			$pageIndex = 'noindex';
			$pageFollow = 'nofollow';
			
			if($_GET["reload"] == "no") {
				// Do nothing...
			} else {
				if (!secureForm($_POST["formUid"])) {
					$backToForm = true;
					$alert_message = $strings['351'];
					$loadContent = true;
				}
			}
			
			/****** Page logic goes here! ******/
			
			
			
			
			
			
		} else {
			killCookie();
			$loadContent = false;
			header("HTTP/1.1 301 Moved Permanently");
			header("Location: index.php");
		}
	} else {
		// Session not opened, show marketing content...
		$activeSession = false;
		$loadContent = true;
	}
} else {
	// invalid session. Back to the login page, and kill cookie...
	killCookie();
	$loadContent = false;
	header("HTTP/1.1 301 Moved Permanently");
    header("Location: index.php");
}

if($loadContent) {
	include 'businessContent.php';
}
?>