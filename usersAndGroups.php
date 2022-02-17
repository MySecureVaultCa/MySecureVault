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
							$editUserName = $editUser['name'];
							$editUserEmail = $editUser['email'];
							$editUserCity = $editUser['city'];
							$editUserState = $editUser['state'];
							$editUserCountry = $editUser['country'];
							$editUserGroups = getBusinessUserGroups($editUser['id']);
							
						} else {
							logAction('27', 'User: (ID: ' . $editUser['id'] . ') ' . $editUser['name']);
							$message = $strings['457'];
						}
					} else {
						logAction('26', 'User ID provided: ' . $_GET['editUser']);
						$message = $strings['457'];
					}
				}
				
				if(ctype_digit($_GET['deleteUser'])) {
					
					$deleteUser = getBusinessUserInfoFromId($_GET['deleteUser']);
					if( $deleteUser !== false ) {
						$currentUser = getBusinessUserInfo($_SESSION['certId']);
						if((isEnterpriseAdmin($currentUser['id']) && isEnterpriseAdmin($deleteUser['id'])) || (isEnterpriseAdmin($deleteUser['id']) === false)) {
							if(isBusinessOwner($deleteUser['id'])) {
								logAction('44', 'User: (ID: ' . $deleteUser['id'] . ') ' . $deleteUser['name']);
								$message = $strings['462'];
							} else {
								logAction('45', 'User: (ID: ' . $deleteUser['id'] . ') ' . $deleteUser['name']);
								$deleteUser['deleted'] = '1';
								updateUserInfo($deleteUser['id'], $deleteUser);
								updateUserGroups($deleteUser['id'], array());
							}
						} else {
							logAction('43', 'User: (ID: ' . $deleteUser['id'] . ') ' . $deleteUser['name']);
							$message = $strings['462'];
						}
					} else {
						logAction('42', 'User ID provided: ' . $_GET['deleteUser']);
						$message = $strings['462'];
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
							$backToAddUserForm = true;
							$newUserDownloadPassphraseError = $strings['283'];
						} elseif(strlen($newUserDownloadPassphrase) > 128) {
							unset($newUserDownloadPassphrase);
							$backToAddUserForm = true;
							$newUserDownloadPassphraseError = $strings['448'];
						}
					} elseif($newUserPassType == 'userPassphrase') {
						if(strlen($newUserPassphrase) < 15) {
							unset($newUserPassphrase);
							unset($newUserPassphraseRetype);
							$backToAddUserForm = true;
							$newUserPassphraseError = $strings['449'];
						} elseif(strlen($newUserPassphrase) > 128) {
							unset($newUserPassphrase);
							unset($newUserPassphraseRetype);
							$backToAddUserForm = true;
							$newUserDownloadPassphraseError = $strings['448'];
						} elseif($newUserPassphrase != $newUserPassphraseRetype) {
							unset($newUserPassphrase);
							unset($newUserPassphraseRetype);
							$backToAddUserForm = true;
							$newUserPassphraseRetypeError = $strings['110'];
						}
					} else {
						// No option selected, show error!
						unset($newUserDownloadPassphrase);
						unset($newUserPassphrase);
						unset($newUserPassphraseRetype);
						$backToAddUserForm = true;
						$newUserPassTypeError = $strings['450'];
					}
					
					$effectivePermission = getBusinessManagementPermissions();
					$businessInfo = getBusinessInfo($_SESSION['userId']);
					
					foreach($_POST['groups'] as $key => $group){
						if(!ctype_digit($group)) {
							$group = htmlOutput($group);
							unset($_POST['groups'][$key]);
							$backToAddUserForm = true;
							$newUserGroupError = $strings['441'];
							logAction('37', 'Group ID: ' . $group);
						} else {
							//OK that's a number, check if number is OK...
							if((getGroupInfo($group) === false) || ($group == $businessInfo['business']['owningGroup'] && $effectivePermission['business'] != 'rw')) {
								logAction('18', 'Group ID: ' . $group);
								$backToAddUserForm = true;
								$newUserGroupError = $strings['441'];
							} else {
								$newUserGroups[] = $group;
							}
						}
					}
					
					
					if($backToAddUserForm) {
						logAction('9');
						$showAddUserForm = true;
					} else {
						// Add groups to an array
						
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
								// echo 'Register certificate';
								$certId = registerCertificate($certificate, $newUserPassphrase, $_SESSION['userId'], $encryptCertificate);
								
								// Initialize business user
								$businessUser['name'] = $newUserName;
								$businessUser['email'] = $newUserEmail;
								$businessUser['city'] = $newUserCity;
								$businessUser['state'] = $newUserState;
								$businessUser['country'] = $newUserCountry;
								$businessUser['createdOn'] = date('Y-m-d H:i:s');
								$businessUser['lastModified'] = '';
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
				
				if($_POST["formAction"] == "editUser" && ctype_digit($_POST["editUser"])) {
					// Post to edit a user!
					// First, check if user has the right to edit this user!
					$effectivePermission = getBusinessManagementPermissions();
					$editUser = getBusinessUserInfoFromId($_POST["editUser"]);
					
					if($editUser !== false) {
						if($effectivePermission['users'] == 'rw') {
							$businessInfo = getBusinessInfo($_SESSION['userId']);
							$editingUser = getBusinessUserInfo($_SESSION['certId']);
							if(isEnterpriseAdmin($editingUser['id']) && isEnterpriseAdmin($editUser['id'])) {
								// Editing user is enterprise admin, and tries to edit an enterprise admin. That's OK, but check if it is the business owner to prevent removing it from the Enterprise Admin group...
								$canEditThisUser = true;
								logAction('33', 'User edited: (' . $editUser['id'] . ') ' . $editUser['name']);
								if(isBusinessOwner($editUser['id'])) {
									$editedUserIsBusinessOwner = true;
									logAction('34', 'User edited: (' . $editUser['id'] . ') ' . $editUser['name']);
								}
							} elseif(!isEnterpriseAdmin($editUser['id'])) {
								// User has user management right and does not need to edit an enterprise admin... that's OK.
								$canEditThisUser = true;
								logAction('32', 'User edited: (' . $editUser['id'] . ') ' . $editUser['name']);
							} else {
								// probably trying to edit an enterprise admin while not being one. Probable hacking attempt
								logAction('30', 'User edited: (' . $editUser['id'] . ') ' . $editUser['name']);
							}
							
						} else {
							$canEditThisUser = false;
							logAction('31', 'User edited: (' . $editUser['id'] . ') ' . $editUser['name']);
						}
					} else {
						$canEditThisUser = false;
						// Trying to edit a non existent user, or one from another business account! Probably a hacking attempt!
						logAction('26', 'User edited: (' . $editUser['id'] . ') ' . $editUser['name']);
					}
					
					if ($canEditThisUser) {
						$editUserName = htmlOutput($_POST["editUserName"]);
						$editUserEmail = htmlOutput($_POST["editUserEmail"]);
						$editUserCity = htmlOutput($_POST["editUserCity"]);
						$editUserState = htmlOutput($_POST["editUserState"]);
						$editUserCountry = htmlOutput($_POST["editUserCountry"]);
						
						if(strlen($editUserName) < 1) {
							$backToEditUserForm = true;
							$editUserNameError = $strings['439'];
						} elseif(strlen($editUserName) > 256) {
							$backToEditUserForm = true;
							$editUserNameError = $strings['439'];
						}
						
						if(strlen($editUserEmail) > 320) {
							$backToEditUserForm = true;
							$editUserEmailError = $strings['100'];
						} elseif(strlen($editUserEmail) < 1) {
							$backToEditUserForm = true;
							$editUserEmailError = $strings['101'];
						} elseif(!filter_var($editUserEmail, FILTER_VALIDATE_EMAIL)) {
							$backToEditUserForm = true;
							$editUserEmailError = $strings['102'];
						}
						
						if(!validateCountry($editUserCountry)) {
							$backToEditUserForm = true;
							$editUserCountryError = $strings['103'];
						}
						
						if(strlen($editUserState) > 256) {
							$backToEditUserForm = true;
							$editUserStateError = $strings['104'];
						} elseif(strlen($editUserState) < 1) {
							$backToEditUserForm = true;
							$editUserStateError = $strings['105'];
						}
						
						if(strlen($editUserCity) > 256) {
							$backToEditUserForm = true;
							$editUserCityError = $strings['106'];
						} elseif(strlen($editUserCity) < 1) {
							$backToEditUserForm = true;
							$editUserCityError = $strings['107'];
						}
						
						// var_dump($_POST['groups']);
						foreach($_POST['groups'] as $key => $group){
							if(!ctype_digit($group)) {
								$group = htmlOutput($group);
								unset($_POST['groups'][$key]);
								$backToEditUserForm = true;
								$editUserGroupError = $strings['441'];
								logAction('36', 'Group ID: ' . $group);
							} else {
								//OK that's a number, check if number is OK...
								if((getGroupInfo($group) === false) || ($group == $businessInfo['business']['owningGroup'] && $effectivePermission['business'] != 'rw')) {
									logAction('35', 'Group ID: ' . $group);
									$backToEditUserForm = true;
									$editUserGroupError = $strings['441'];
								} else {
									$editUserGroups[] = $group;
								}
							}
						}
						
						if($editedUserIsBusinessOwner === true) {
							// make sure there was no attempt to take the business owner out!
							if(!in_array($businessInfo['business']['owningGroup'], $editUserGroups)) {
								$editUserGroups[] = $businessInfo['business']['owningGroup'];
								logAction('38', 'Group ID: ' . $businessInfo['business']['owningGroup']);
								$backToEditUserForm = true;
								$editUserGroupError = $strings['441'];
							}
						}
						
						
					} else {
						logAction('29', 'User edited: (' . $editUser['id'] . ') ' . $editUser['name']);
						$showEditUserForm = true;
						$backToEditUserForm = true;
					}
					
					if($backToEditUserForm) {
						logAction('28', 'User edited: (' . $editUser['id'] . ') ' . $editUser['name']);
						$showEditUserForm = true;
					} else {
						// All good! update user info
						logAction('41', 'User ID: ' . $editUser['id'] . ', Name: ' . $editUser['name']);
						
						$editUser['name'] = $editUserName;
						$editUser['email'] = $editUserEmail;
						$editUser['city'] = $editUserCity;
						$editUser['state'] = $editUserState;
						$editUser['country'] = $editUserCountry;
						
						updateUserInfo($editUser['id'], $editUser);
						
						updateUserGroups($editUser['id'], $editUserGroups);
						
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