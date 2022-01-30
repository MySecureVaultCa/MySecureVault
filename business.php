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
				
				if($billingName == '') {
					$billingName = $businessName;
				} elseif(strlen($billingName) > 256) {
					$backToForm = true;
					$billingNameError = $strings['402'];
				} elseif(strlen($billingName) < 1) {
					$backToForm = true;
					$billingNameError = $strings['401'];
				}
				
				if($billingAddress == '' && $$billingCity == '' && $billingState == '') {
					$billingAddress = $businessAddress;
					$billingCity = $businessCity;
					$billingState = $businessState;
					$billingCountry = $businessCountry;
				} else {
					// Validata all fields individually
					if(strlen($billingAddress) > 256) {
						$backToForm = true;
						$billingAddressError = $strings['404'];
					} elseif(strlen($billingAddress) < 1) {
						$backToForm = true;
						$billingAddressError = $strings['403'];
					}
					
					if(strlen($billingCity) > 256) {
						$backToForm = true;
						$billingCityError = $strings['406'];
					} elseif(strlen($billingCity) < 1) {
						$backToForm = true;
						$billingCityError = $strings['405'];
					}
					
					if(strlen($billingState) > 256) {
						$backToForm = true;
						$billingStateError = $strings['408'];
					} elseif(strlen($billingState) < 1) {
						$backToForm = true;
						$billingStateError = $strings['407'];
					}
					
					if(!validateCountry($billingCountry)) {
						$backToForm = true;
						$billingCountryError = $strings["27"];
					}
				}
				
				if($billingEmail == '') {
					$billingEmail = $businessEmail;
				} elseif(strlen($billingEmail) > 320) {
					$backToForm = true;
					$billingEmailError = $strings["24"];
				} elseif(strlen($billingEmail) < 1) {
					$backToForm = true;
					$billingEmailError = $strings["25"];
				} elseif(!filter_var($billingEmail, FILTER_VALIDATE_EMAIL)) {
					$backToForm = true;
					$billingEmailError = $strings["26"];
				}
				
				if($businessTerms != 'accept') {
					$backToForm = true;
					$businessTermsError = $strings["411"];
				}
				
				if($backToForm != true) {
					// All validations successful!
					
					// Initialize business user
					$businessUser['name'] = $_SESSION['fullName'];
					$businessUser['personalQuota'] = 0;
					$businessUser['businessQuota'] = 0;
					$businessUser['certs'] = array($_SESSION['certId']);
					
					$jsonEntry = json_encode($businessUser);
					
					$encryptedEntry = encryptDataNextGen($_SESSION['encryptionKey'], $jsonEntry, $config['currentCipherSuite']);
					$encryptedEntryIv = $encryptedEntry['iv'];
					$encryptedEntryData = $encryptedEntry['data'];
					$encryptedEntryTag = $encryptedEntry['tag'];
					
					$sql = "INSERT INTO businessUsers (userId, cipherSuite, iv, entry, tag) VALUES ('$_SESSION[userId]', '$config[currentCipherSuite]', '$encryptedEntry[iv]', '$encryptedEntry[data]', '$encryptedEntry[tag]')";
					$conn -> query($sql);
					$businessUserId = mysqli_insert_id($conn);
					
					
				
					// Initialize enterprise admins group
					$businessGroup['name'] = 'Enterprise Admin';
					$businessGroup['members'] = array($businessUserId);
					$businessGroup['description'] = $strings['412'];
					
					$jsonEntry = json_encode($businessGroup);
					
					$encryptedEntry = encryptDataNextGen($_SESSION['encryptionKey'], $jsonEntry, $config['currentCipherSuite']);
					$encryptedEntryIv = $encryptedEntry['iv'];
					$encryptedEntryData = $encryptedEntry['data'];
					$encryptedEntryTag = $encryptedEntry['tag'];
					
					$sql = "INSERT INTO businessGroups (userId, cipherSuite, iv, entry, tag) VALUES ('$_SESSION[userId]', '$config[currentCipherSuite]', '$encryptedEntry[iv]', '$encryptedEntry[data]', '$encryptedEntry[tag]')";
					$conn -> query($sql);
					$enterpriseAdminGroupId = mysqli_insert_id($conn);
					
					
					// Initialize billing admins group
					$businessGroup['name'] = 'Billing Admin';
					$businessGroup['members'] = array($businessUserId);
					$businessGroup['description'] = $strings['413'];
					
					$jsonEntry = json_encode($businessGroup);
					
					$encryptedEntry = encryptDataNextGen($_SESSION['encryptionKey'], $jsonEntry, $config['currentCipherSuite']);
					$encryptedEntryIv = $encryptedEntry['iv'];
					$encryptedEntryData = $encryptedEntry['data'];
					$encryptedEntryTag = $encryptedEntry['tag'];
					
					$sql = "INSERT INTO businessGroups (userId, cipherSuite, iv, entry, tag) VALUES ('$_SESSION[userId]', '$config[currentCipherSuite]', '$encryptedEntry[iv]', '$encryptedEntry[data]', '$encryptedEntry[tag]')";
					$conn -> query($sql);
					$billingAdminGroupId = mysqli_insert_id($conn);
					
					
					// Initialize User managers group
					$businessGroup['name'] = 'User Manager';
					$businessGroup['members'] = array($businessUserId);
					$businessGroup['description'] = $strings['417'];
					
					$jsonEntry = json_encode($businessGroup);
					
					$encryptedEntry = encryptDataNextGen($_SESSION['encryptionKey'], $jsonEntry, $config['currentCipherSuite']);
					$encryptedEntryIv = $encryptedEntry['iv'];
					$encryptedEntryData = $encryptedEntry['data'];
					$encryptedEntryTag = $encryptedEntry['tag'];
					
					$sql = "INSERT INTO businessGroups (userId, cipherSuite, iv, entry, tag) VALUES ('$_SESSION[userId]', '$config[currentCipherSuite]', '$encryptedEntry[iv]', '$encryptedEntry[data]', '$encryptedEntry[tag]')";
					$conn -> query($sql);
					$userManagerGroupId = mysqli_insert_id($conn);
					
					// Initialize root folder
					/*
						ACL reference:
							r = Read permission (implies browse)
							w = Write permission
							s = Share permission
							b = Browse permission
					*/
					$businessFolder['name'] = '/';
					$businessFolder['parentFolder'] = '';
					$businessFolder['owner'] = $businessUserId;
					$businessFolder['owningGroup'] = $businessGroupId;
					$businessFolder['sharedWith'] = array();
					$businessFolder['acl']['u'] = 'rws';
					$businessFolder['acl']['g'] = 'rws';
					$businessFolder['acl']['o'] = 'r';
					
					$jsonEntry = json_encode($businessFolder);
					
					$encryptedEntry = encryptDataNextGen($_SESSION['encryptionKey'], $jsonEntry, $config['currentCipherSuite']);
					$encryptedEntryIv = $encryptedEntry['iv'];
					$encryptedEntryData = $encryptedEntry['data'];
					$encryptedEntryTag = $encryptedEntry['tag'];
					
					$sql = "INSERT INTO businessFolders (userId, cipherSuite, iv, entry, tag) VALUES ('$_SESSION[userId]', '$config[currentCipherSuite]', '$encryptedEntry[iv]', '$encryptedEntry[data]', '$encryptedEntry[tag]')";
					$conn -> query($sql);
					
					// Finish with business information
					$businessInfo['business']['name'] = $businessName;
					$businessInfo['business']['address'] = $businessAddress;
					$businessInfo['business']['city'] = $businessCity;
					$businessInfo['business']['state'] = $businessState;
					$businessInfo['business']['country'] = $businessCountry;
					$businessInfo['business']['email'] = $businessEmail;
					$businessInfo['business']['phone'] = $businessPhone;
					$businessInfo['business']['terms'] = $businessTerms;
					$businessInfo['business']['owner'] = $businessUserId;
					$businessInfo['business']['owningGroup'] = $enterpriseAdminGroupId;
					$businessInfo['business']['acl']['u'] = 'rw';
					$businessInfo['business']['acl']['g'] = 'rw';
					$businessInfo['business']['acl']['o'] = 'r';
					$businessInfo['billing']['name'] = $billingName;
					$businessInfo['billing']['address'] = $billingAddress;
					$businessInfo['billing']['city'] = $billingCity;
					$businessInfo['billing']['state'] = $billingState;
					$businessInfo['billing']['country'] = $billingCountry;
					$businessInfo['billing']['email'] = $billingEmail;
					$businessInfo['billing']['owner'] = $businessUserId;
					$businessInfo['billing']['owningGroup'] = $billingAdminGroupId;
					$businessInfo['billing']['acl']['u'] = 'rw';
					$businessInfo['billing']['acl']['g'] = 'rw';
					$businessInfo['billing']['acl']['o'] = '';
					$businessInfo['users']['owner'] = $businessUserId;
					$businessInfo['users']['owningGroup'] = $userManagerGroupId;
					$businessInfo['users']['acl']['u'] = 'rw';
					$businessInfo['users']['acl']['g'] = 'rw';
					$businessInfo['users']['acl']['o'] = 'r';
					
					
					$jsonEntry = json_encode($businessInfo);
					
					$encryptedEntry = encryptDataNextGen($_SESSION['encryptionKey'], $jsonEntry, $config['currentCipherSuite']);
					$encryptedEntryIv = $encryptedEntry['iv'];
					$encryptedEntryData = $encryptedEntry['data'];
					$encryptedEntryTag = $encryptedEntry['tag'];
					
					$sql = "UPDATE users SET cipherSuite='$config[currentCipherSuite]', iv='$encryptedEntry[iv]', entry='$encryptedEntry[data]', tag='$encryptedEntry[tag]' WHERE id='$_SESSION[userId]'";
					$conn -> query($sql);
					
					
					// Create a 10 days license
					
					/*
					$loadContent = false;
					header("HTTP/1.1 301 Moved Permanently");
					header("Location: profile.php");
					*/
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