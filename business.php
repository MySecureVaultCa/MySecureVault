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

	$siteTitle = $strings['1'];
	$pageTitle = $strings['354'];
	$pageDescription = $strings['355'];
	$pageKeywords = $strings['356'];
	$pageIndex = 'index';
	$pageFollow = 'nofollow';
	
	if(isset($_SESSION["token"])) {
		if (authenticateToken()) {
			$loadContent = true;
			$activeSession = true;
			
			if(in_array($_SESSION['userId'], $config['cmsAdmin'])) {
				$isAdmin = true;
			} else {
				$isAdmin = false;
			}
			
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
			
			if($_SERVER["REQUEST_METHOD"] == "POST" && $_POST["formAction"] == "saveBusinessInfo") {
				$businessName = htmlOutput($_POST['businessName']);
				$businessAddress = htmlOutput($_POST['businessAddress']);
				$businessCity = htmlOutput($_POST['businessCity']);
				$businessState = htmlOutput($_POST['businessState']);
				$businessCountry = htmlOutput($_POST['businessCountry']);
				$businessEmail = htmlOutput($_POST['businessEmail']);
				$businessPhone = htmlOutput($_POST['businessPhone']);
				$billingName = htmlOutput($_POST['billingName']);
				$billingAddress = htmlOutput($_POST['billingAddress']);
				$billingCity = htmlOutput($_POST['billingCity']);
				$billingState = htmlOutput($_POST['billingState']);
				$billingCountry = htmlOutput($_POST['billingCountry']);
				$billingEmail = htmlOutput($_POST['billingEmail']);
				$businessTerms = htmlOutput($_POST['businessTerms']);
				
				if(strlen($businessName) > 256) {
					$backToForm = true;
					$businessNameError = $strings['402'];
				} elseif(strlen($businessName) < 1) {
					$backToForm = true;
					$businessNameError = $strings['401'];
				}
				
				if(strlen($businessAddress) > 256) {
					$backToForm = true;
					$businessAddressError = $strings['404'];
				} elseif(strlen($businessAddress) < 1) {
					$backToForm = true;
					$businessAddressError = $strings['403'];
				}
				
				if(strlen($businessCity) > 256) {
					$backToForm = true;
					$businessCityError = $strings['406'];
				} elseif(strlen($businessCity) < 1) {
					$backToForm = true;
					$businessCityError = $strings['405'];
				}
				
				if(strlen($businessState) > 256) {
					$backToForm = true;
					$businessStateError = $strings['408'];
				} elseif(strlen($businessState) < 1) {
					$backToForm = true;
					$businessStateError = $strings['407'];
				}
				
				if(!validateCountry($businessCountry)) {
					$backToForm = true;
					$businessCountryError = $strings["27"];
				}
				
				if(strlen($businessEmail) > 320) {
					$backToForm = true;
					$businessEmailError = $strings["24"];
				} elseif(strlen($businessEmail) < 1) {
					$backToForm = true;
					$businessEmailError = $strings["25"];
				} elseif(!filter_var($businessEmail, FILTER_VALIDATE_EMAIL)) {
					$backToForm = true;
					$businessEmailError = $strings["26"];
				}
				
				if(strlen($businessPhone) > 30) {
					$backToForm = true;
					$businessPhoneError = $strings['410'];
				} elseif(strlen($businessPhone) < 3) {
					$backToForm = true;
					$businessPhoneError = $strings['409'];
				}
			}
			
			
			
			
			
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