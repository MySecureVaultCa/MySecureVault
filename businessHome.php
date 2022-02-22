<?php

//UNCOMMENT FOR DEBUG PURPOSES
/*
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
*/


include "functions.php";
$currentPage = htmlspecialchars($_SERVER["PHP_SELF"]);
$parentPage = 'businessHome.php';

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
	$pageTitle = $strings['498'];
	$pageDescription = '';
	$pageKeywords = '';
	$pageIndex = 'noindex';
	$pageFollow = 'nofollow';
	
	if(isset($_SESSION["token"])) {
		if (authenticateToken()) {
			
			$businessPermission = getBusinessManagementPermissions();
			
			if($businessPermission !== false){
				logAction('72');
				$loadContent = true;
				
				if($_GET["reload"] == "no") {
				// Do nothing...
				} else {
					if (!secureForm($_POST["formUid"])) {
						$backToForm = true;
						$alert_message = $strings['351'];
						$loadContent = true;
					}
				}
				
				if(ctype_digit($_GET['folderId'])) {
					$changeFolder = getFolderInfo($_GET['folderId']);
					if($changeFolder !== false) {
						
					} else {
						logAction('73', 'Requested Folder ID: ' . htmlOutput($_GET['folderId']));
					}
				}
				
				if(ctype_digit($_SESSION['currentFolderId'])) {
					$currentFolder = getFolderInfo($_SESSION['currentFolderId']);
				} else {
					$currentFolder = getRootFolder();
					$_SESSION['currentFolderId'] = $currentFolder['id'];
				}
				
				$effectiveFolderPermissions = getFolderEffectivePermission($currentFolder['id']);
				
				if($_GET['action'] == 'addFolder' && ctype_digit($_GET['parentFolder'])) {
					// trying to add a subfolder to a parent folder...
					$parentFolder = htmlOutput($_GET['parentFolder']);
					$parentFolderPermissions = getFolderEffectivePermission($parentFolder);
					if($parentFolderPermissions !== false) {
						if(strpos($parentFolderPermissions, 'w') !== false) {
							// All good, show add folder form
							$parentFolderInfo = getFolderInfo($parentFolder);
							$showAddFolderForm = true;
							logAction('76', 'Parent folder ID: ' . $parentFolder . ', Parent folder name: ' . $parentFolderInfo['name']);
						} else {
							logAction('75', 'Parent folder ID provided: ' . $parentFolder);
							$message = $strings['504'];
						}
					} else {
						logAction('74', 'Parent folder ID provided: ' . $parentFolder);
						$message = $strings['504'];
					}
				}
				
				if($_GET['action'] == 'cancelAddFolder') {
					logAction('77');
				}
				
				/*
				PAGE LOGIC GOES HERE
				*/
				
				
				
			} else {
				// Nothing to do here
				$loadContent = false;
				header("HTTP/1.1 301 Moved Permanently");
				header("Location: index.php");
			}
		} else {
			killCookie();
			$loadContent = false;
			header("HTTP/1.1 301 Moved Permanently");
			header("Location: index.php");
		}
	} else {
		killCookie();
		$loadContent = false;
		header("HTTP/1.1 301 Moved Permanently");
		header("Location: index.php");
	}
} else {
	// invalid session. Back to the login page, and kill cookie...
	killCookie();
	$loadContent = false;
	header("HTTP/1.1 301 Moved Permanently");
    header("Location: index.php");
}

if($loadContent) {
	include 'businessHomeContent.php';
}
?>