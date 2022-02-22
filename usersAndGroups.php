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
				
				if($_GET['action'] == 'cancelEditGroup') {
					logAction('61');
				}
				
				if($_GET['action'] == 'cancelIssueCert') {
					logAction('53');
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
				
				if(ctype_digit($_GET['issueCert'])) {
					$userId = htmlOutput($_GET['issueCert']);
					$userInfo = getBusinessUserInfoFromId($userId);
					$currentUser = getBusinessUserInfo($_SESSION['certId']);
					if( $userInfo !== false ) {
						// The user is valid. Since any user manager or enterprise admin can ISSUE a certificate for any user, let's process the request and show the certificate issuance form!
						$showIssueCertificateForm = true;
						logAction('52', 'Issue certificate for user: ' . $userId) . ', User name: ' . $userInfo['name'];
					} else {
						logAction('51', 'User ID provided: ' . $userId);
						$message = $strings['467'];
					}
				}
				
				if(ctype_digit($_GET['editGroup'])) {
					$editGroup = getGroupInfo($_GET['editGroup']);
					$businessInfo = getBusinessInfo($_SESSION['userId']);
					if($editGroup !== false) {
						if($editGroup['id'] != $businessInfo['business']['owningGroup'] && $editGroup['id'] != $businessInfo['billing']['owningGroup'] && $editGroup['id'] != $businessInfo['users']['owningGroup'] && $editGroup['id'] != $businessInfo['logging']['owningGroup']) {
							// OK to edit this group...
							$showEditGroupForm = true;
							logAction('62', 'Group: (ID: ' . $editGroup['id'] . ') ' . $editGroup['name']['en']);
							
							$editGroupId = $editGroup['id'];
							$editGroupNameEn = $editGroup['name']['en'];
							$editGroupNameFr = $editGroup['name']['fr'];
							$editGroupDescriptionEn = $editGroup['description']['en'];
							$editGroupDescriptionFr = $editGroup['description']['fr'];
							
						} else {
							// Trying to edit one of the system groups. This is not permitted!
							logAction('60', 'Group ID: ' . $editGroup['id'] . ', Group name: ' . $editGroup['name']['en']);
							$message = $strings['490'];
						}
					} else {
						logAction('59', 'Group ID provided: ' . $_GET['editGroup']);
						$message = $strings['490'];
					}
				}
				
				if(ctype_digit($_GET['deleteCert'])) {
					$certUser = getBusinessUserInfo($_GET['deleteCert']);
					if( $certUser !== false ) {
						if($_SESSION['certId'] != $_GET['deleteCert']) {
							$currentUser = getBusinessUserInfo($_SESSION['certId']);
							if((isEnterpriseAdmin($currentUser['id']) && isEnterpriseAdmin($certUser['id'])) || (isEnterpriseAdmin($certUser['id']) === false)) {
								// Make sure the certificate was revoked before being deleted.
								$certId = mysqli_real_escape_string($conn, $_GET['deleteCert']);
								$sql = "SELECT id, revoked, deleted FROM certs WHERE id='$certId' AND revoked='1' AND deleted='0'";
								$db_rawCert = $conn -> query($sql);
								if(mysqli_num_rows($db_rawCert) == 1) {
									// all controls passed...
									$sql = "UPDATE certs SET deleted='1' WHERE id='$certId'";
									$conn -> query($sql);
									$message = $strings['65'];
									logAction('49', 'User: (ID: ' . $certUser['id'] . ') ' . $certUser['name'] . ', Certificate ID: ' . htmlOutput($_GET['deleteCert']));
								} else {
									logAction('50', 'User: (ID: ' . $certUser['id'] . ') ' . $certUser['name'] . ', Certificate ID: ' . htmlOutput($_GET['deleteCert']));
								}
							} else {
								logAction('46', 'User: (ID: ' . $certUser['id'] . ') ' . $certUser['name'] . ', Certificate ID: ' . htmlOutput($_GET['deleteCert']));
								$message = $strings['464'];
							}
						} else {
							logAction('47', 'User: (ID: ' . $certUser['id'] . ') ' . $certUser['name'] . ', Certificate ID: ' . htmlOutput($_GET['deleteCert']));
							$message = $strings['464'];
						}
					} else {
						logAction('48', 'Certificate ID provided: ' . htmlOutput($_GET['deleteCert']));
						$message = $strings['464'];
					}
				}
				
				if(ctype_digit($_GET['deleteUser'])) {
					
					$deleteUser = getBusinessUserInfoFromId($_GET['deleteUser']);
					if( $deleteUser !== false && $deleteUser['deleted'] != '1') {
						$currentUser = getBusinessUserInfo($_SESSION['certId']);
						if((isEnterpriseAdmin($currentUser['id']) && isEnterpriseAdmin($deleteUser['id'])) || (isEnterpriseAdmin($deleteUser['id']) === false)) {
							if(isBusinessOwner($deleteUser['id'])) {
								logAction('44', 'User: (ID: ' . $deleteUser['id'] . ') ' . $deleteUser['name']);
								$message = $strings['462'];
							} else {
								logAction('45', 'User: (ID: ' . $deleteUser['id'] . ') ' . $deleteUser['name']);
								$message = $strings['476'];
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
				
				if(ctype_digit($_GET['deleteGroup'])) {
					$deleteGroup = getGroupInfo($_GET['deleteGroup']);
					$businessInfo = getBusinessInfo($_SESSION['userId']);
					if($deleteGroup !== false && $deleteGroup['deleted'] != '1') {
						if($deleteGroup['id'] != $businessInfo['business']['owningGroup'] && $deleteGroup['id'] != $businessInfo['billing']['owningGroup'] && $deleteGroup['id'] != $businessInfo['users']['owningGroup'] && $deleteGroup['id'] != $businessInfo['logging']['owningGroup']) {
							// OK to delete this group
							logAction('68', 'Group: (ID: ' . $deleteGroup['id'] . ') ' . $deleteGroup['name']['en']);
							
							$deleteGroup['deleted'] = '1';
							
							$jsonEntry = json_encode($deleteGroup);
							$encryptedEntry = encryptDataNextGen($_SESSION['encryptionKey'], $jsonEntry, $config['currentCipherSuite']);
							$encryptedEntryIv = $encryptedEntry['iv'];
							$encryptedEntryData = $encryptedEntry['data'];
							$encryptedEntryTag = $encryptedEntry['tag'];
							
							$sql = "UPDATE businessGroups SET cipherSuite='$config[currentCipherSuite]', iv='$encryptedEntryIv', entry='$encryptedEntryData', tag='$encryptedEntryTag' WHERE id='$deleteGroup[id]'";
							$conn -> query($sql);
						} else {
							// Trying to delete one of the system groups. This is not permitted!
							logAction('67', 'Group ID: ' . $deleteGroup['id'] . ', Group name: ' . $deleteGroup['name']['en']);
							$message = $strings['491'];
						}
					} else {
						logAction('69', 'Group ID provided: ' . $_GET['deleteGroup']);
						$message = $strings['491'];
					}
				}
				
				if(ctype_digit($_GET['revokeCert'])) {
					$certId = mysqli_real_escape_string($conn, $_GET['revokeCert']);
					$sql = "SELECT id, revoked FROM certs WHERE id='$certId' AND userId='$_SESSION[userId]' AND revoked='0'";
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
							$message = $strings['69'];
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
					$sql = "SELECT id, revoked FROM certs WHERE id='$certId' AND userId='$_SESSION[userId]' AND revoked='1'";
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
							$message = $strings['73'];
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
								
								updateUserGroups($businessUserId, $userGroups);
								
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
					
					if($editUser !== false && $editUser['deleted'] != '1' ) {
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
						logAction('28', 'User modified: (' . $editUser['id'] . ') ' . $editUser['name']);
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
						
						$message = $strings['495'];
					}
				}
				
				if($_POST['formAction'] == 'issueCert' && ctype_digit($_POST['certUserId'])) {
					$userId = htmlOutput($_POST['certUserId']);
					$userInfo = getBusinessUserInfoFromId($userId);
					$currentUser = getBusinessUserInfo($_SESSION['certId']);
					if( $userInfo !== false && $userInfo['deleted'] != '1' ) {
						// The user is valid. Since any user manager or enterprise admin can ISSUE a certificate for any user, let's validate everything.
						logAction('55', 'Issue certificate for user: ' . $userId . ', User name: ' . $userInfo['name'] . ' (' . $userInfo['email'] . ')');
						
						$certDevice = htmlOutput($_POST['certDevice']);
						$certPassType = $_POST['certPassType'];
						$certPassphrase = $_POST['certPassphrase'];
						$certPassphraseRetype = $_POST['certPassphraseRetype'];
						$certDownloadPassphrase = $_POST['certDownloadPassphrase'];
						
						if($certPassType == 'downloadPassphrase') {
							if(strlen($certDownloadPassphrase) < 15) {
								unset($certDownloadPassphrase);
								$backToIssueCertificateForm = true;
								$certDownloadPassphraseError = $strings['283'];
							} elseif(strlen($certDownloadPassphrase) > 128) {
								unset($certDownloadPassphrase);
								$backToIssueCertificateForm = true;
								$certDownloadPassphraseError = $strings['448'];
							}
						} elseif($certPassType == 'userPassphrase') {
							if(strlen($certPassphrase) < 15) {
								unset($certPassphrase);
								unset($certPassphraseRetype);
								$backToIssueCertificateForm = true;
								$certPassphraseError = $strings['449'];
							} elseif(strlen($certPassphrase) > 128) {
								unset($certPassphrase);
								unset($certPassphraseRetype);
								$backToIssueCertificateForm = true;
								$certPassphraseError = $strings['448'];
							} elseif($certPassphrase != $certPassphraseRetype) {
								unset($certPassphrase);
								unset($certPassphraseRetype);
								$backToIssueCertificateForm = true;
								$certPassphraseRetypeError = $strings['110'];
							}
						} else {
							// No option selected, show error!
							unset($certDownloadPassphrase);
							unset($certPassphrase);
							unset($certPassphraseRetype);
							$backToIssueCertificateForm = true;
							$certPassTypeError = $strings['450'];
						}
						
						if($backToIssueCertificateForm === true) {
							$showIssueCertificateForm = true;
							logAction('56', 'Issue certificate for user: ' . $userId . ', User name: ' . $userInfo['name'] . ' (' . $userInfo['email'] . ')');
						} else {
							// All validations successful. Process.
							
							if($certPassType == 'userPassphrase') {
								if(strlen($_POST['certDevice']) > 0) {
									$certDN = $userInfo['name'] . ' - ' . $_POST['certDevice'];
								} else {
									$certDN = $userInfo['name'];
								}
								$dn = array(
									"countryName" => utf8_encode("$userInfo[country]"),
									"stateOrProvinceName" => utf8_encode("$userInfo[state]"),
									"localityName" => utf8_encode("$userInfo[city]"),
									"organizationName" => "None",
									"organizationalUnitName" => "None",
									"commonName" => utf8_encode("$certDN"),
									"emailAddress" => utf8_encode("$userInfo[email]"),
								);
								
								$certificate = generateNewCertificate($dn, $certPassphrase);
								
								if($certificate != false) {
									$certId = registerCertificate($certificate, $certPassphrase, $_SESSION['userId'], true);
									logAction('57', 'New certificate ID: ' . $certId . '');
									
									$userInfo['certs'][] = $certId;
									updateUserInfo($userId, $userInfo);
									
									$message = $strings['477'];
									
								} else {
									logAction('58', 'New certificate ID: ' . $certId . '');
								}
							} else {
								echo 'Not implemented yet';
							}
							
							
						}
						
						
					} else {
						logAction('54', 'User ID provided: ' . $userId);
						$message = $strings['467'];
					}					
				}
				
				if($_POST['formAction'] == 'addGroup') {
					$englishGroupName = htmlOutput($_POST['englishGroupName']);
					$frenchGroupName = htmlOutput($_POST['frenchGroupName']);
					$englishGroupDescription = htmlOutput($_POST['englishGroupDescription']);
					$frenchGroupDescription = htmlOutput($_POST['frenchGroupDescription']);
					
					if(strlen($englishGroupName) < 1) {
						$englishGroupNameError = $strings['485'];
						$backToAddGroupForm = true;
					} elseif(strlen($englishGroupName) > 256) {
						$englishGroupNameError = $strings['486'];
						$backToAddGroupForm = true;
					}
					
					if(strlen($frenchGroupName) < 1) {
						$frenchGroupName = $englishGroupName;
					} elseif(strlen($frenchGroupName) > 256) {
						$frenchGroupNameError = $strings['486'];
						$backToAddGroupForm = true;
					}
					
					if(strlen($englishGroupDescription) < 1) {
						$englishGroupDescriptionError = $strings['488'];
						$backToAddGroupForm = true;
					} elseif(strlen($englishGroupDescription) > 1024) {
						$englishGroupDescriptionError = $strings['487'];
						$backToAddGroupForm = true;
					}
					
					if(strlen($frenchGroupDescription) < 1) {
						$frenchGroupDescription = $englishGroupDescription;
					} elseif(strlen($frenchGroupDescription) > 1024) {
						$frenchGroupDescriptionError = $strings['487'];
						$backToAddGroupForm = true;
					}
					
					if($backToAddGroupForm) {
						$showAddGroupForm = true;
						logAction('71');
					} else {
						// All validations are good, proceed with group creation...
						
						$group['name']['en'] = $englishGroupName;
						$group['name']['fr'] = $frenchGroupName;
						$group['members'] = array();
						$group['description']['en'] = $englishGroupDescription;
						$group['description']['fr'] = $frenchGroupDescription;
						
						$jsonEntry = json_encode($group);
						$encryptedEntry = encryptDataNextGen($_SESSION['encryptionKey'], $jsonEntry, $config['currentCipherSuite']);
						$encryptedEntryIv = $encryptedEntry['iv'];
						$encryptedEntryData = $encryptedEntry['data'];
						$encryptedEntryTag = $encryptedEntry['tag'];
						
						$sql = "INSERT INTO businessGroups (userId, cipherSuite, iv, entry, tag) VALUES ('$_SESSION[userId]', '$config[currentCipherSuite]', '$encryptedEntry[iv]', '$encryptedEntry[data]', '$encryptedEntry[tag]')";
						$conn -> query($sql);
						
						$group['id'] = mysqli_insert_id($conn);
						
						logAction('70', 'Group created: (' . $group['id'] . ') ' . $group['name']['en']);
						$message = $strings['493'];
					}
					
				}
				
				if($_POST['formAction'] == 'editGroup' && ctype_digit($_POST['editGroup'])) {
					$editGroup = getGroupInfo($_POST['editGroup']);
					$businessInfo = getBusinessInfo($_SESSION['userId']);
					if($editGroup !== false) {
						if($editGroup['id'] != $businessInfo['business']['owningGroup'] && $editGroup['id'] != $businessInfo['billing']['owningGroup'] && $editGroup['id'] != $businessInfo['users']['owningGroup'] && $editGroup['id'] != $businessInfo['logging']['owningGroup']) {
							// All validations passed... process information!
							$editGroupId = $editGroup['id'];
							$editGroupNameEn = $_POST['editGroupNameEn'];
							$editGroupNameFr = $_POST['editGroupNameFr'];
							$editGroupDescriptionEn = $_POST['editGroupDescriptionEn'];
							$editGroupDescriptionFr = $_POST['editGroupDescriptionFr'];
							
							if(strlen($editGroupNameEn) < 1) {
								$editGroupNameEnError = $strings['485'];
								$backToEditGroupForm = true;
							} elseif(strlen($editGroupNameEn) > 256) {
								$editGroupNameEnError = $strings['486'];
								$backToEditGroupForm = true;
							}
							
							if(strlen($editGroupNameFr) < 1) {
								$editGroupNameFr = $editGroupNameEn;
							} elseif(strlen($editGroupNameFr) > 256) {
								$editGroupNameFrError = $strings['486'];
								$backToEditGroupForm = true;
							}
							
							if(strlen($editGroupDescriptionEn) < 1) {
								$editGroupDescriptionEnError = $strings['488'];
								$backToEditGroupForm = true;
							} elseif(strlen($editGroupDescriptionEn) > 1024) {
								$editGroupDescriptionEnError = $strings['487'];
								$backToEditGroupForm = true;
							}
							
							if(strlen($editGroupDescriptionFr) < 1) {
								$editGroupDescriptionFr = $editGroupDescriptionEn;
							} elseif(strlen($editGroupDescriptionFr) > 1024) {
								$editGroupDescriptionFrError = $strings['487'];
								$backToEditGroupForm = true;
							}
							
							if($backToEditGroupForm) {
								$showEditGroupForm = true;
								logAction('63', 'Group ID: ' . $editGroup['id'] . ', Group name: ' . $editGroup['name']['en']);
							} else {
								logAction('64', 'Group modified: (' . $editGroup['id'] . ') ' . $editGroup['name']['en']);
								
								$editGroup['name']['en'] = $editGroupNameEn;
								$editGroup['name']['fr'] = $editGroupNameFr;
								$editGroup['description']['en'] = $editGroupDescriptionEn;
								$editGroup['description']['fr'] = $editGroupDescriptionFr;
								
								$jsonEntry = json_encode($editGroup);
								$encryptedEntry = encryptDataNextGen($_SESSION['encryptionKey'], $jsonEntry, $config['currentCipherSuite']);
								$encryptedEntryIv = $encryptedEntry['iv'];
								$encryptedEntryData = $encryptedEntry['data'];
								$encryptedEntryTag = $encryptedEntry['tag'];
								
								$sql = "UPDATE businessGroups SET cipherSuite='$config[currentCipherSuite]', iv='$encryptedEntryIv', entry='$encryptedEntryData', tag='$encryptedEntryTag' WHERE id='$editGroup[id]'";
								$conn -> query($sql);
								
								$message = $strings['494'];
							}
							
						} else {
							logAction('65', 'Group ID: ' . $editGroup['id'] . ', Group name: ' . $editGroup['name']['en']);
							$message = $strings['490'];
						}
					} else {
						logAction('66', 'Group ID provided: ' . $_GET['editGroup']);
						$message = $strings['490'];
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