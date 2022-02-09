<?php

//UNCOMMENT FOR DEBUG PURPOSES
/*
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
*/


include "functions.php";
$currentPage = htmlspecialchars($_SERVER["PHP_SELF"]);
$parentPage = 'usersAndGroups.php';

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
	$pageTitle = $strings['418'];
	$pageDescription = '';
	$pageKeywords = '';
	$pageIndex = 'noindex';
	$pageFollow = 'nofollow';
	
	if(isset($_SESSION["token"])) {
		if (authenticateToken()) {
			
			$businessPermission = getBusinessManagementPermissions();
			
			if($businessPermission['users'] == 'rw'){
				logAction('8');
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
				
				if($_GET['action'] == 'addUser') {
					$showAddUserForm = true;
					logAction('3');
				}
				
				if($_GET['action'] == 'cancelAddUser') {
					logAction('4');
				}
				
				if($_GET['action'] == 'addGroup') {
					$showAddGroupForm = true;
					logAction('5');
				}
				
				if($_GET['action'] == 'cancelAddGroup') {
					logAction('6');
				}
				
				if(ctype_digit($_GET['editUser'])) {
					$editUser = getBusinessUserInfoFromId($_GET['editUser']);
					if( $editUser !== false ) {
						// Check if user has the right to edit this user...
						$currentUser = getBusinessUserInfo($_SESSION['certId']);
						if((isEnterpriseAdmin($currentUser['id']) && isEnterpriseAdmin($editUser['id'])) || (isEnterpriseAdmin($editUser['id']) === false)) {
							$showEditUserForm = true;
							logAction('25', 'User: (ID: ' . $editUser['id'] . ') ' . $editUser['name']);
							
							// Set variables for form...
							$deviceNameStart = strpos($editUser['name'], ' - ');
							if ($deviceNameStart !== false) {
								// There is a device name.
								$editUserName = substr($editUser['name'], 0, $deviceNameStart);
								$editUserDevice = substr($editUser['name'], $deviceNameStart+3);
							} else {
								// No device name.
								$editUserName = $editUser['name'];
							}
							
							$editUserEmail = $editUser['email'];
							$editUserCity = $editUser['city'];
							
						} else {
							logAction('27', 'User: (ID: ' . $editUser['id'] . ') ' . $editUser['name']);
							$message = $strings['457'];
						}
					} else {
						logAction('26', 'User ID provided: ' . $_GET['editUser']);
						$message = $strings['457'];
					}
				}
				
				if(ctype_digit($_GET['revokeCert'])) {
					$certId = mysqli_real_escape_string($conn, $_GET['revokeCert']);
					$sql = "SELECT id, revoked FROM certs WHERE id='$certId' AND userId='$_SESSION[userId]'";
					$db_rawCert = $conn->query($sql);
					logAction('16', 'Query string:' . $sql);
					if(mysqli_num_rows($db_rawCert) == 1) {
						$db_cert = $db_rawCert -> fetch_assoc();
						$businessUserInfo = getBusinessUserInfo($certId);
						$currentUser = getBusinessUserInfo($_SESSION['certId']);
						if(((isEnterpriseAdmin($currentUser['id']) && isEnterpriseAdmin($businessUserInfo['id'])) || (isEnterpriseAdmin($businessUserInfo['id']) === false)) && ($_SESSION['certId'] != $certId) && $db_cert['revoked'] == '0') {
							// All checks are good, proceed with revoke
							$sql = "UPDATE certs SET revoked='1' WHERE id='$certId'";
							$conn -> query($sql);
							logAction('16', 'Query string:' . $sql);
							logAction('21', 'Certificate ID:' . $certId . ', Business user ID: ' . $businessUserInfo['id'] . ', Name: ' . $businessUserInfo['name'] . ' (' . $businessUserInfo['email'] . ')');
						} else {
							logAction('19', 'Certificate ID:' . $certId . ', User: (ID: ' . $businessUserInfo['id'] . ') ' . $businessUserInfo['name']);
							$message = $strings['71'];
						}
					} else {
						logAction('20', 'Certificate ID:' . $certId);
						$message = $strings['71'];
					}
				}
				
				if(ctype_digit($_GET['unrevokeCert'])) {
					$certId = mysqli_real_escape_string($conn, $_GET['unrevokeCert']);
					$sql = "SELECT id, revoked FROM certs WHERE id='$certId' AND userId='$_SESSION[userId]'";
					$db_rawCert = $conn->query($sql);
					logAction('16', 'Query string:' . $sql);
					if(mysqli_num_rows($db_rawCert) == 1) {
						$db_cert = $db_rawCert -> fetch_assoc();
						$businessUserInfo = getBusinessUserInfo($certId);
						$currentUser = getBusinessUserInfo($_SESSION['certId']);
						if(((isEnterpriseAdmin($currentUser['id']) && isEnterpriseAdmin($businessUserInfo['id'])) || (isEnterpriseAdmin($businessUserInfo['id']) === false)) && ($_SESSION['certId'] != $certId) && $db_cert['revoked'] == '1') {
							// All checks are good, proceed with revoke
							$sql = "UPDATE certs SET revoked='0' WHERE id='$certId'";
							$conn -> query($sql);
							logAction('16', 'Query string:' . $sql);
							logAction('22', 'Certificate ID:' . $certId . ', Business user ID: ' . $businessUserInfo['id'] . ', Name: ' . $businessUserInfo['name'] . ' (' . $businessUserInfo['email'] . ')');
						} else {
							logAction('23', 'Certificate ID:' . $certId . ', User: (ID: ' . $businessUserInfo['id'] . ') ' . $businessUserInfo['name']);
							$message = $strings['71'];
						}
					} else {
						logAction('24', 'Certificate ID:' . $certId);
						$message = $strings['71'];
					}
				}
				
				if(ctype_digit($_GET['downloadCert'])) {
					$certId = mysqli_real_escape_string($conn, $_GET['downloadCert']);
					$sql = "SELECT ivPkcs12File, encryptedPkcs12File, tagPkcs12File, cipherSuitePkcs12File, ivCertData, encryptedCertData, tagCertData, cipherSuiteCertData FROM certs WHERE id='$certId' AND userId='$_SESSION[userId]'";
					$db_rawCert = $conn->query($sql);
					logAction('16', 'Query string:' . $sql);
					if(mysqli_num_rows($db_rawCert) == 1) {
						$businessUserInfo = getBusinessUserInfo($certId);
						$currentUser = getBusinessUserInfo($_SESSION['certId']);
						if((isEnterpriseAdmin($currentUser['id']) && isEnterpriseAdmin($businessUserInfo['id'])) || (isEnterpriseAdmin($businessUserInfo['id']) === false)) {
							logAction('14', 'Certificate ID:' . $certId . ', Business user ID: ' . $businessUserInfo['id'] . ', Name: ' . $businessUserInfo['name'] . ' (' . $businessUserInfo['email'] . ')');
							$db_cert = $db_rawCert ->fetch_assoc();
							
							// Certificate data is encrypted. Decrypt it first.
							$clearCertJsonData = decryptDataNextGen($db_cert['ivCertData'], $_SESSION['encryptionKey'], $db_cert['encryptedCertData'], $db_cert['cipherSuiteCertData'], $db_cert['tagCertData']);
							$clearCertData = json_decode($clearCertJsonData, true);
							
							$certValidFrom = utf8_decode($clearCertData['validFrom']);
							$certValidTo = utf8_decode($clearCertData['validTo']);
							$certFingerprint = utf8_decode($clearCertData['fingerprint']);
							$certCountry = utf8_decode($clearCertData['country']);
							$certState = utf8_decode($clearCertData['state']);
							$certCity = utf8_decode($clearCertData['city']);
							$certEmailAddress = utf8_decode($clearCertData['emailAddress']);
							$certFullName = utf8_decode($clearCertData['fullName']);
							
							
							$validFrom = date('Y-m-d_His', strtotime($certValidFrom));
							$certificateFilename = $validFrom . ' - ' . $certFullName;
							$certificateFilename = utf8_decode($certificateFilename);
							
							$pkcs12Cert = decryptDataNextGen($db_cert['ivPkcs12File'], $_SESSION['encryptionKey'], $db_cert['encryptedPkcs12File'], $db_cert['cipherSuitePkcs12File'], $db_cert['tagPkcs12File']);
							header("Content-type: application/octet-stream");
							header("Content-Disposition: attachment; filename=" . $certificateFilename . ".certificate");
							echo $pkcs12Cert;
							
							$loadContent = false;
						} else {
							logAction('17', 'Certificate ID:' . $certId . ', User: (ID: ' . $businessUserInfo['id'] . ') ' . $businessUserInfo['name']);
							$message = $strings['111'];
						}
					} else {
						logAction('15', 'Certificate ID:' . $certId);
						$message = $strings['111'];
					}
				}
				
				if($_POST["formAction"] == "addUser") {
					$newUserName = htmlOutput($_POST["newUserName"]);
					$newUserDevice = htmlOutput($_POST["newUserDevice"]);
					$newUserEmail = htmlOutput($_POST["newUserEmail"]);
					$newUserCity = htmlOutput($_POST["newUserCity"]);
					$newUserState = htmlOutput($_POST["newUserState"]);
					$newUserCountry = htmlOutput($_POST["newUserCountry"]);
					$newUserPassType = htmlOutput($_POST["newUserPassType"]);
					$newUserPassphrase = $_POST["newUserPassphrase"];
					$newUserPassphraseRetype = $_POST["newUserPassphraseRetype"];
					$newUserDownloadPassphrase = $_POST["newUserDownloadPassphrase"];
					$newUserGroup1 = htmlOutput($_POST["newUserGroup1"]);
					$newUserGroup2 = htmlOutput($_POST["newUserGroup2"]);
					$newUserGroup3 = htmlOutput($_POST["newUserGroup3"]);
					$newUserGroup4 = htmlOutput($_POST["newUserGroup4"]);
					$newUserGroup5 = htmlOutput($_POST["newUserGroup5"]);
					
					if($newUserDevice == '') {
						// No device specified, use only the user's name
						$certName = $newUserName;
						$rawCertName = $_POST["newUserName"];
					} else {
						// A device was specified. Concatenate.
						$certName = $newUserName . ' - ' . $newUserDevice;
						$rawCertName = $_POST["newUserName"] . ' - ' . $_POST["newUserDevice"];
					}
					
					if(strlen($newUserName) < 1) {
						$backToAddUserForm = true;
						$newUserNameError = $strings['439'];
					}
					
					if(strlen($certName) > 256) {
						$backToAddUserForm = true;
						$newUserNameError = $strings['440'];
						if($newUserDevice != '') {$newUserDeviceError = $strings['440'];}
					} elseif(strlen($certName) < 1) {
						$backToAddUserForm = true;
						$newUserNameError = $strings['439'];
					}
					
					if(strlen($newUserEmail) > 320) {
						$backToAddUserForm = true;
						$newUserEmailError = $strings['100'];
					} elseif(strlen($newUserEmail) < 1) {
						$backToAddUserForm = true;
						$newUserEmailError = $strings['101'];
					} elseif(!filter_var($newUserEmail, FILTER_VALIDATE_EMAIL)) {
						$backToAddUserForm = true;
						$newUserEmailError = $strings['102'];
					}
					
					if(!validateCountry($newUserCountry)) {
						$backToAddUserForm = true;
						$newUserCountryError = $strings['103'];
					}
					
					if(strlen($newUserState) > 256) {
						$backToAddUserForm = true;
						$newUserStateError = $strings['104'];
					} elseif(strlen($newUserState) < 1) {
						$backToAddUserForm = true;
						$newUserStateError = $strings['105'];
					}
					
					if(strlen($newUserCity) > 256) {
						$backToAddUserForm = true;
						$newUserCityError = $strings['106'];
					} elseif(strlen($newUserCity) < 1) {
						$backToAddUserForm = true;
						$newUserCityError = $strings['107'];
					}
					
					if($newUserPassType == 'downloadPassphrase') {
						if(strlen($newUserDownloadPassphrase) < 15) {
							unset($newUserDownloadPassphrase);
							$backToForm = true;
							$newUserDownloadPassphraseError = $strings['283'];
						} elseif(strlen($newUserDownloadPassphrase) > 128) {
							unset($newUserDownloadPassphrase);
							$backToForm = true;
							$newUserDownloadPassphraseError = $strings['448'];
						}
					} elseif($newUserPassType == 'userPassphrase') {
						if(strlen($newUserPassphrase) < 15) {
							unset($newUserPassphrase);
							unset($newUserPassphraseRetype);
							$backToForm = true;
							$newUserPassphraseError = $strings['449'];
						} elseif(strlen($newUserPassphrase) > 128) {
							unset($newUserPassphrase);
							unset($newUserPassphraseRetype);
							$backToForm = true;
							$newUserDownloadPassphraseError = $strings['448'];
						} elseif($newUserPassphrase != $newUserPassphraseRetype) {
							unset($newUserPassphrase);
							unset($newUserPassphraseRetype);
							$backToForm = true;
							$newUserPassphraseRetypeError = $strings['110'];
						}
					} else {
						// No option selected, show error!
						unset($newUserDownloadPassphrase);
						unset($newUserPassphrase);
						unset($newUserPassphraseRetype);
						$backToForm = true;
						$newUserPassTypeError = $strings['450'];
					}
					
					$effectivePermission = getBusinessManagementPermissions();
					$businessInfo = getBusinessInfo($_SESSION['userId']);
					
					if($newUserGroup1 == '' || !ctype_digit($newUserGroup1)) {
						unset($newUserGroup1);
					} elseif((getGroupInfo($newUserGroup1) === false) || ($newUserGroup1 == $businessInfo['business']['owningGroup'] && $effectivePermission['business'] != 'rw')) {
						// Group is not valid. Unset.
						logAction('18', 'Group ID: ' . $newUserGroup1);
						$backToAddUserForm = true;
						$newUserGroup1Error = $strings['441'];
					}
					if($newUserGroup2 == '' || !ctype_digit($newUserGroup2)) {
						unset($newUserGroup2);
					} elseif((getGroupInfo($newUserGroup2) === false) || ($newUserGroup2 == $businessInfo['business']['owningGroup'] && $effectivePermission['business'] != 'rw')) {
						// Group is not valid. Unset.
						logAction('18', 'Group ID: ' . $newUserGroup2);
						$backToAddUserForm = true;
						$newUserGroup2Error = $strings['441'];
					}
					if($newUserGroup3 == '' || !ctype_digit($newUserGroup3)) {
						unset($newUserGroup3);
					} elseif((getGroupInfo($newUserGroup3) === false) || ($newUserGroup3 == $businessInfo['business']['owningGroup'] && $effectivePermission['business'] != 'rw')) {
						// Group is not valid. Unset.
						logAction('18', 'Group ID: ' . $newUserGroup3);
						$backToAddUserForm = true;
						$newUserGroup3Error = $strings['441'];
					}
					if($newUserGroup4 == '' || !ctype_digit($newUserGroup4)) {
						unset($newUserGroup4);
					} elseif((getGroupInfo($newUserGroup4) === false) || ($newUserGroup4 == $businessInfo['business']['owningGroup'] && $effectivePermission['business'] != 'rw')) {
						// Group is not valid. Unset.
						logAction('18', 'Group ID: ' . $newUserGroup4);
						$backToAddUserForm = true;
						$newUserGroup4Error = $strings['441'];
					}
					if($newUserGroup5 == '' || !ctype_digit($newUserGroup5)) {
						unset($newUserGroup5);
					} elseif((getGroupInfo($newUserGroup5) === false) || ($newUserGroup5 == $businessInfo['business']['owningGroup'] && $effectivePermission['business'] != 'rw')) {
						// Group is not valid. Unset.
						logAction('18', 'Group ID: ' . $newUserGroup5);
						$backToAddUserForm = true;
						$newUserGroup5Error = $strings['441'];
					}
					
					if($backToAddUserForm) {
						logAction('9');
						$showAddUserForm = true;
					} else {
						// Add groups to an array
						$newUserGroups[] = $newUserGroup1;
						$newUserGroups[] = $newUserGroup2;
						$newUserGroups[] = $newUserGroup3;
						$newUserGroups[] = $newUserGroup4;
						$newUserGroups[] = $newUserGroup5;
						
						foreach($newUserGroups as $id => $group) {
							// Remove null and empty values
							if($group === '' || is_null($group)) { unset ($newUserGroups[$id]); }
						}
						
						// Remove duplicates
						$userGroups = array_unique($newUserGroups);
						
						$businessInfo = getBusinessInfo($_SESSION['userId']);
						
						if($newUserPassType == 'downloadPassphrase') {
							// All is good! Write the required fields to create a link so the user will create his own certificate
							
							
							$userInfo['businessInfo'] = $businessInfo['business'];
							$userInfo['fullName'] = $newUserName;
							$userInfo['email'] = $newUserEmail;
							$userInfo['city'] = $newUserCity;
							$userInfo['state'] = $newUserState;
							$userInfo['country'] = $newUserCountry;
							$userInfo['groups'] = $userGroups;
							
							$jsonUserInfo = json_encode($jsonUserInfo);
							
							// Encrypt data for business's admins
							$encryptedUserInfo = encryptDataNextGen($_SESSION['encryptionKey'], $jsonUserInfo, $config['currentCipherSuite']);
							$iv = $encryptedUserInfo['iv'];
							$entry = $encryptedUserInfo['data'];
							$tag = $encryptedUserInfo['tag'];
							
							// Generate cryptographic material:
							$keypair = generateKeypair();
							$encryptionKey = generateEncryptionKeyNextGen();
							$encryptedEncryptionKey = encrypt($keypair['public'], $encryptionKey, $config['currentPadding']);
							$base64EncryptionKey = base64_encode($encryptionKey);
							
							// ENCRYPT PACKAGE WITH RECIPIENT ENCRYPTION KEY
							$encryptedPackage = encryptDataNextGen($base64EncryptionKey, $jsonUserInfo, $config['currentCipherSuite']);
							$packageIv = $encryptedPackage['iv'];
							$packageData = $encryptedPackage['data'];
							$packageTag = $encryptedPackage['tag'];
							
							// ENCRYPT PRIVATE KEY THAT WILL BE SENT BY EMAIL
							$privateKeyExportOptions = array(encrypt_key => true, encrypt_key_cipher => OPENSSL_CIPHER_AES_256_CBC);
							openssl_pkey_export($keypair['private'], $securePrivateKey, $newUserDownloadPassphrase, $privateKeyExportOptions);
							// $securePrivateKey IS THE CONTENT OF THE .PEM CERTIFICATE FILE, PROTECTED BY THE PASSPHRASE
							
							// GENERATE SECURE HASH TO PERMIT ACCESS TO PAGE
							$nonce = generateRandomString(64, 'aA1');
							$secureHashString = $downloadRecipient . $nonce;
							$secureHash = hash('sha256', $secureHashString);
							
							// Write package and user data to database...
							$sql = "INSERT INTO businessUsersQueue (userId, cipherSuite, iv, entry, tag, secureHash, downloadCipherSuite, downloadEncryptionKey, downloadIv, downloadEntry, downloadTag) VALUES ('$_SESSION[userId]', '$config[currentCipherSuite]', '$iv', '$entry', '$tag', '$secureHash', '$config[currentCipherSuite]', '$encryptedEncryptionKey', '$packageIv', '$packageData', '$packageTag')";
							$conn -> query($sql);
							logAction('16', 'Query string:' . $sql);
							
							// Log user creation
							logAction('7');
							
							//////////////////////////////////// THIS IS WHERE IM AT! //////////////////////////////////////////
							// Send email to user
							sendWelcomeEmail($newUserEmail, $securePrivateKey, $nonce);
							
							// $_SESSION['message'] = $strings['308'] . ' ' . $downloadRecipient . '<br><br>' . $strings['352'];
						} else {
							// Admin chose to create user and certificate at the same time. OK, let's do it.
							$dn = array(
								"countryName" => utf8_encode("$_POST[newUserCountry]"),
								"stateOrProvinceName" => utf8_encode("$_POST[newUserState]"),
								"localityName" => utf8_encode("$_POST[newUserCity]"),
								"organizationName" => utf8_encode($businessInfo['business']['name']),
								"organizationalUnitName" => "None",
								"commonName" => utf8_encode("$rawCertName"),
								"emailAddress" => utf8_encode("$_POST[newUserEmail]"),
							);
					
					$certificate = generateNewCertificate($dn, $newUserPassphrase);
					$encryptCertificate = true;
					
					if($certificate != false) {
						echo 'Register certificate';
						$certId = registerCertificate($certificate, $newUserPassphrase, $_SESSION['userId'], $encryptCertificate);
						
						// Initialize business user
						$businessUser['name'] = $newUserName;
						$businessUser['email'] = $newUserEmail;
						$businessUser['city'] = $newUserCity;
						$businessUser['state'] = $newUserState;
						$businessUser['country'] = $newUserCountry;
						$businessUser['personalQuota'] = 0;
						$businessUser['businessQuota'] = 0;
						$businessUser['certs'] = array($certId);
						
						$jsonEntry = json_encode($businessUser);
						
						$encryptedEntry = encryptDataNextGen($_SESSION['encryptionKey'], $jsonEntry, $config['currentCipherSuite']);
						$encryptedEntryIv = $encryptedEntry['iv'];
						$encryptedEntryData = $encryptedEntry['data'];
						$encryptedEntryTag = $encryptedEntry['tag'];
						
						$sql = "INSERT INTO businessUsers (userId, cipherSuite, iv, entry, tag) VALUES ('$_SESSION[userId]', '$config[currentCipherSuite]', '$encryptedEntry[iv]', '$encryptedEntry[data]', '$encryptedEntry[tag]')";
						$conn -> query($sql);
						$businessUserId = mysqli_insert_id($conn);
						logAction('16', 'Query string:' . $sql);
						logAction('11', 'Business user ID: ' . $businessUserId);
						
						foreach($userGroups as $group) {
							$groupInfo = getGroupInfo($group);
							$groupInfo['members'][] = $businessUserId;
							
							$jsonEntry = json_encode($groupInfo);
							$encryptedEntry = encryptDataNextGen($_SESSION['encryptionKey'], $jsonEntry, $config['currentCipherSuite']);
							$encryptedEntryIv = $encryptedEntry['iv'];
							$encryptedEntryData = $encryptedEntry['data'];
							$encryptedEntryTag = $encryptedEntry['tag'];
							
							$sql = "UPDATE businessGroups SET cipherSuite='$config[currentCipherSuite]', iv='$encryptedEntryIv', entry='$encryptedEntryData', tag='$encryptedEntryTag' WHERE id='$group'";
							$conn -> query($sql);
							logAction('16', 'Query string:' . $sql);
						}						
						
						$message = $strings['451'];
						$loadContent = true;
					} else {
						
						logAction('10');
						
						$backToForm = true;
						$message = $strings['452'];
						$loadContent = true;
					}
						}
					}
					
				}
				
				
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
	include 'usersAndGroupsContent.php';
}
?>