<?php

//UNCOMMENT FOR DEBUG PURPOSES
/*
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
*/

include "functions.php";
$currentPage = htmlspecialchars($_SERVER["PHP_SELF"]);
$parentPage = 'index.php';

if (initiateSession()) {
	
	$loadContent = true;
	
	if($_GET["control"] == "signup") {
		$signup = true;
	}
	
	if($_GET["control"] == "businessSignup") {
		$businessSignup = true;
		$signup = true;
	}
	
	if(isset($_GET["setLanguage"])) {
		if ($_GET["setLanguage"] == "en") {
			$_SESSION["language"] = "en";
		} elseif($_GET["setLanguage"] == "fr") {
			$_SESSION["language"] = "fr";
		} else {
			$_SESSION["language"] = "en";
		}
		$loadContent = false;
		header("HTTP/1.1 301 Moved Permanently");
		header("Location: $currentPage");
	}
	
	if(!databaseConnection()) {
		// echo 'Cannot connect to database';
		$backtoform = true;
		$certError = "Impossible de se connecter &agrave; la base de donn&eacute;es";
		$loadContent = true;
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
		header("Location: $currentPage");
	}
	
	if(isset($_SESSION["token"])) {
		if (authenticateToken()) {
			$loadContent = false;
			header("HTTP/1.1 301 Moved Permanently");
			header("Location: profile.php");
		} else {
			killCookie();
			$loadContent = false;
			header("HTTP/1.1 301 Moved Permanently");
			header("Location: $currentPage");
		}
	}
	
	if(!isset($_SESSION['language'])) {
		$_SESSION['language'] = detectLanguage(); 
	}
	
	// Get language strings for page...
	$language = $_SESSION['language'];
	$sql = "SELECT id, $language FROM langStrings";
	$db_rawStrings = $conn->query($sql);
	$strings = array();

	while($row = $db_rawStrings->fetch_assoc()) {
		$stringId = $row['id'];
		$strings["$stringId"] = $row["$language"];
	}
	
	$siteTitle = $strings['1'];
	$pageTitle = $siteTitle;
	$pageDescription = $strings['152'];
	$pageKeyworkds = 
	$pageIndex = 'index';
	$pageFollow = 'follow';
	
	if($_GET["reload"] == "no" && $_GET["control"] == "downloadCert") {
		// Do nothing...
	} else {
		if (!secureForm($_POST["formUid"])) {
			$backToForm = true;
			$alert_message = $strings['351'];
			$loadContent = true;
		}
	}
	
	if($_SERVER["REQUEST_METHOD"] == "GET" && $_GET["control"] == "downloadCert" && $_SESSION["certificateFile"] != "") {
		header("Content-type: application/octet-stream");
		header("Content-Disposition: attachment; filename=" . $_SESSION["certificateFilename"] . ".certificate");
		echo $_SESSION["certificateFile"];
		$loadContent = false;
	}
	
	if($_SERVER["REQUEST_METHOD"] == "GET" && $_GET["control"] == "anonymousRegistration") {
		$backToRegisterForm = true;
		$anonymousRegistration = true;
		$id = generateRandomIdentity();
		
		$certName = $id['name'];
		$certEmail = $id['email'];
		$certState = $id['province'];
		$certCity = $id['city'];
		$certCountry = $id['country'];
		
	}
	
	if ($_SERVER["REQUEST_METHOD"] == "POST" && $_POST["formAction"] == "login" && $backToForm != true) {
		// Input validation
		$cert = file_get_contents($_FILES['cert']['tmp_name']);
		$certPass = $_POST["certPass"];
		
		if(openssl_pkcs12_read($cert, $certData, $certPass)) {
			// Certificate is valid. Check what info we have on this dude...
			$readCert = openssl_x509_read ($certData['cert']);
			$parsedCert = openssl_x509_parse ($readCert);
			$certFingerprint = openssl_x509_fingerprint($readCert, "SHA256");
			$certPrivateKey = $certData['pkey'];
			$certRawPublicKey = openssl_pkey_get_details(openssl_pkey_get_public($readCert));
			$certPublicKey = $certRawPublicKey['key'];
			$certEmailAddress = htmlOutput(utf8_decode($parsedCert['subject']['emailAddress']));
			$certFullName = htmlOutput(utf8_decode($parsedCert['subject']['CN']));
			$certCountry = htmlOutput(utf8_decode($parsedCert['subject']['C']));
			$certState = htmlOutput(utf8_decode($parsedCert['subject']['ST']));
			$certCity = htmlOutput(utf8_decode($parsedCert['subject']['L']));
			$certSerialNumber = $parsedCert['serialNumber'];
			$certValidFrom = date('Y-m-d H:i:s', $parsedCert['validFrom_time_t']);
			$certValidTo = date('Y-m-d H:i:s', $parsedCert['validTo_time_t']);
			$currentDate = date('Y-m-d H:i:s');
			$encryptedFingerprint = encrypt($certPublicKey, $certSerialNumber.$certFingerprint, $config['currentPadding']);
			$decryptedFingerprint = decrypt($certPrivateKey, $encryptedFingerprint, $config['currentPadding']);
			
			$public = $_POST["public"];
			
			if($public == 'yes') {
				$privateDeviceSession = false;
			} else {
				$privateDeviceSession = true;
			}
			
			//Check if certificate has private Key, check if it is valid...
			if($certPrivateKey == "") {
				$certHasValidPrivateKey = false;
				
			} elseif($decryptedFingerprint != "" && $decryptedFingerprint == $certSerialNumber.$certFingerprint) {
				$certHasValidPrivateKey = true;
			} else {
				$certHasValidPrivateKey = false;
						echo $encryptedFingerprint;
			}
			
			// Check if certificate is currently valid
			if($currentDate > $certValidFrom && $currentDate < $certValidTo) {
				$certIsValid = true;
			} else {
				$certIsValid = false;
			}
			
			// Perform checks if certificate sounds legit...
			if($certIsValid && $certHasValidPrivateKey) {
				//Check if certificate is already stored in database
				$certSerialNumber = mysqli_real_escape_string($conn, $certSerialNumber);
				$sql = "SELECT id, serial, encrypted, userId, ivCertData, encryptedCertData, tagCertData, cipherSuiteCertData FROM certs WHERE serial='$certSerialNumber'";
				$db_rawCert = $conn -> query($sql);
				if (mysqli_num_rows($db_rawCert) == 1) {
						$certificateExists = true;
						//Extract certificate info from database
						$db_cert = $db_rawCert -> fetch_assoc();
						$dbCertId = $db_cert['id'];
						$dbCertSerial = $db_cert['serial'];
						$dbCertUserId = $db_cert['userId'];
						
						// Wait for auth to get cert data...
						$dbCertIvCertData = $db_cert['ivCertData'];
						$dbCertEncryptedCertData = $db_cert['encryptedCertData'];
						$dbCertTagCertData = $db_cert['tagCertData'];
						$dbCertCipherSuiteCertData = $db_cert['cipherSuiteCertData'];
						$waitForAuthToGetCertData = true;
						
				} else {
					// Check if email address already exists...
					$certificateExists = false;
					$certEmailAddress = mysqli_real_escape_string($conn, $certEmailAddress);
					
					if (emailExists($certEmailAddress) === false) {
						$emailExists = false;
					} else {
						$emailExists = true;
					}
				}
				
				// Check if user and/or certificate exists
				if(!$certificateExists && !$emailExists) {
					
						$backToForm = true;
						$certError = $strings["7"];
						$loadContent = true;
					
				} elseif($certificateExists) {
					//Certificate exists. Authenticate.
					
					if(authenticateUser($certSerialNumber, $certFingerprint, $certPrivateKey)) {
						// Everything good. Create session variables.
						//Get Encryption Key for this certificate.
						$sql = "SELECT padding, encryptedKey, version FROM encryptionKeys WHERE certId='$dbCertId'";
						$db_rawEncryptionKey = $conn->query($sql);
						$db_encryptionKey = $db_rawEncryptionKey->fetch_assoc();
						if($db_encryptionKey['padding'] == 'OPENSSL_PKCS1_PADDING') { $padding = OPENSSL_PKCS1_PADDING; } elseif($db_encryptionKey['padding'] == 'OPENSSL_PKCS1_OAEP_PADDING') { $padding = OPENSSL_PKCS1_OAEP_PADDING; }
						if($db_encryptionKey['version'] == '1') {
							$decryptedEncryptionKey = decrypt($certPrivateKey, $db_encryptionKey['encryptedKey'], $padding);
							$_SESSION['updateEncryptionKey'] = true;
						} elseif($db_encryptionKey['version'] == '2') {
							$rawDecryptedEncryptionKey = decrypt($certPrivateKey, $db_encryptionKey['encryptedKey'], $padding);
							$decryptedEncryptionKey = base64_encode($rawDecryptedEncryptionKey);
						}
						
						$expirationUnix = strtotime($certValidTo);
						$nowUnix = strtotime($currentDate);
						$expiresInSeconds = ($expirationUnix - $nowUnix);
						if($expiresInSeconds < 86400) {
							// URGENT! Less than 1 day before expiration...
							$validToColor = 'red';
							$validToWeight = 'bold';
							$remainingTimeBeforeExpiry = round($expiresInSeconds / 3600, 1);
							$remainingTimeString = $remainingTimeBeforeExpiry . ' ' . $strings['257'];
							$_SESSION['message'] = '<span class="w3-text-red">' . $strings['259'] . ' ' . $remainingTimeString . '. ' . $strings['260'] . '</span>';
						} elseif ($expiresInSeconds < 604800) {
							// FAST! Less than 1 week before expiration...
							$validToColor = 'red';
							$validToWeight = 'bold';
							$remainingTimeBeforeExpiry = round($expiresInSeconds / 86400, 1);
							$remainingTimeString = $remainingTimeBeforeExpiry . ' ' . $strings['258'];
							$_SESSION['message'] = '<span class="w3-text-orange">' . $strings['259'] . ' ' . $remainingTimeString . '. ' . $strings['260'] . '</span>';
						} elseif ($expiresInSeconds < 2592000) {
							// Renew soon... Less than 30 days before expiration...
							$validToColor = 'orange';
							$validToWeight = 'bold';
							$remainingTimeBeforeExpiry = round($expiresInSeconds / 86400, 1);
							$remainingTimeString = $remainingTimeBeforeExpiry . ' ' . $strings['258'];
							$_SESSION['message'] = '<span class="w3-text-orange">' . $strings['259'] . ' ' . $remainingTimeString . '. ' . $strings['260'] . '</span>';
						} else {
							$validToColor = 'black';
							$validToWeight = 'normal';
							$remainingTimeBeforeExpiry = round($expiresInSeconds / 86400, 1);
							$remainingTimeString = $remainingTimeBeforeExpiry . ' ' . $strings['258'];
						}
						
						$_SESSION['encryptionKey'] = $decryptedEncryptionKey;
						$_SESSION['userId'] = $dbCertUserId;
						$_SESSION['certId'] = $dbCertId;
						$_SESSION['certSerial'] = $dbCertSerial;
						
						$decryptedJsonCertData = decryptDataNextGen($dbCertIvCertData, $_SESSION['encryptionKey'], $dbCertEncryptedCertData, $dbCertCipherSuiteCertData, $dbCertTagCertData);
						$certData = json_decode($decryptedJsonCertData, true);
						
						$_SESSION['fullName'] = utf8_decode($certData['fullName']);
						$_SESSION['emailAddress'] = utf8_decode($certData['emailAddress']);
						$_SESSION['certFingerprint'] = utf8_decode($certData['fingerprint']);
						$_SESSION['certCountry'] = utf8_decode($certData['country']);
						$_SESSION['certState'] = utf8_decode($certData['state']);
						$_SESSION['certCity'] = utf8_decode($certData['city']);
						$_SESSION['language'] = utf8_decode($certData['language']);
						if ($certData['publicKey'] == '') {
							// Public Key is not stored yet. Add it so we can update encryption keys!
							$certData['publicKey'] = utf8_encode($certPublicKey);
							$jsonCert = json_encode($certData);
							$encryptedJsonCert = encryptDataNextGen($_SESSION['encryptionKey'], $jsonCert, $config['currentCipherSuite']);
							$ivCertData = $encryptedJsonCert['iv'];
							$encryptedCertData = $encryptedJsonCert['data'];
							$tagCertData = $encryptedJsonCert['tag'];
							
							$sql = "UPDATE certs SET ivCertData='$ivCertData', encryptedCertData='$encryptedCertData', tagCertData='$tagCertData', cipherSuiteCertData='$config[currentCipherSuite]' WHERE id='$_SESSION[certId]'";
							$conn -> query($sql);
							$_SESSION['message'] = $strings["226"];
							
						}
						
						$_SESSION['certPublicKey'] = utf8_decode($certData['publicKey']);
						
						// Check if this user is part of a business account...
						$sql = "SELECT businessAccount, cipherSuite, iv, entry, tag FROM users WHERE id='$_SESSION[userId]'";
						$db_rawUserAccount = $conn -> query($sql);
						$db_userAccount = $db_rawUserAccount -> fetch_assoc();
						if($db_userAccount['businessAccount'] == '1') {
							$_SESSION['businessAccount'] = true;
							// Set last login timestamp
							setLastLoginDate($_SESSION['certId']);
						} else {
							$_SESSION['businessAccount'] = false;
						}
						
						
						setToken();
						registerSession($privateDeviceSession);
						$loadContent = false;
						if($_SESSION['certificateFile'] != '') {
							unset($_SESSION['certificateFile']);
							unset($_SESSION['certificateFilename']);
						}
						header("HTTP/1.1 301 Moved Permanently");
						header("Location: profile.php");
						
					} else {
						// A forged certificate has been provided. Mothafucka.
						$backToForm = true;
						$certError = $strings["8"];
						$loadContent = true;
					}
				} elseif(!$certificateExists && $emailExists) {
					// Someone created a certificate for a user that does not already exists, but email address already in use in our system...
					$backToForm = true;
					$certError = $strings["6"];
					$loadContent = true;
				} else {
					//Someone forged a certificate... FUCKER!
					$backToForm = true;
					$certError = $strings["7"];
					$loadContent = true;
				}
			} else {
				$backToForm = true;
				$certError = $strings["8"];
				$loadContent = true;
			}
		} else {
			$backToForm = true;
			$certError = $strings["8"];
			$loadContent = true;
			
		}
	}
	
	// User trying to register a new certificate...
	if ($_SERVER["REQUEST_METHOD"] == "POST" && $_POST["formAction"] == "register" && $backToForm != true) {
		//Generate a new certificate with provided info...
		$certName = htmlOutput($_POST['certName']);
		$certEmail = htmlOutput($_POST['certEmail']);
		$certCountry = htmlOutput($_POST['certCountry']);
		$certState = htmlOutput($_POST['certState']);
		$certCity = htmlOutput($_POST['certCity']);
		$certPassword = $_POST['certPassword'];
		$certPasswordRetype = $_POST['certPasswordRetype'];
		$certBusinessAccount = $_POST['businessSignup'];
		
		if(strlen($certName) > 256) {
			$backToForm = true;
			$certNameError = $strings["22"];
		} elseif(strlen($certName) < 1) {
			$backToForm = true;
			$certNameError = $strings["23"];
		}
		
		if(strlen($certEmail) > 320) {
			$backToForm = true;
			$certEmailError = $strings["24"];
		} elseif(strlen($certEmail) < 1) {
			$backToForm = true;
			$certEmailError = $strings["25"];
		} elseif(!filter_var($certEmail, FILTER_VALIDATE_EMAIL)) {
			$backToForm = true;
			$certEmailError = $strings["26"];
		} else {
			$certEmail = mysqli_real_escape_string($conn, $certEmail);
			if(emailExists($certEmail) !== false) {
				$backToForm = true;
				$certEmailError = $strings["128"];
			}
		}
		
		if(!validateCountry($certCountry)) {
			$backToForm = true;
			$certCountryError = $strings["27"];
		}
		
		if(strlen($certState) > 256) {
			$backToForm = true;
			$certStateError = $strings["28"];
		} elseif(strlen($certState) < 1) {
			$backToForm = true;
			$certStateError = $strings["29"];
		}
		
		if(strlen($certCity) > 256) {
			$backToForm = true;
			$certCityError = $strings["30"];
		} elseif(strlen($certCity) < 1) {
			$backToForm = true;
			$certCityError = $strings["31"];
		}
		
		if(strlen($certPassword) > 128) {
			$backToForm = true;
			$certPasswordError = $strings["32"];
		} elseif(strlen($certPassword) < 15) {
			$backToForm = true;
			$certPasswordError = $strings["33"];
		}
		
		if($certPassword != $certPasswordRetype) {
			$backToForm = true;
			$certPasswordRetypeError = $strings["34"];
			unset($certPassword);
			unset($certPasswordRetype);
		}
		
		$encryptCertificate = true;
		
		if($backToForm == true) {
			$backToRegisterForm = true;
		} else {
			// All good, register new user...
			$dn = array(
				"countryName" => utf8_encode("$_POST[certCountry]"),
				"stateOrProvinceName" => utf8_encode("$_POST[certState]"),
				"localityName" => utf8_encode("$_POST[certCity]"),
				"organizationName" => "None",
				"organizationalUnitName" => "None",
				"commonName" => utf8_encode("$_POST[certName]"),
				"emailAddress" => utf8_encode("$_POST[certEmail]"),
			);
			
			$certificate = generateNewCertificate($dn, $certPassword);
			
			if($certificate != false) {
				if($certBusinessAccount === 'yes') {
					$_SESSION['newBusinessAccount'] = true;
				}
				registerCertificate($certificate, $certPassword, 'new', $encryptCertificate);
				
				$_SESSION["certificateFilename"] = date('Y-m-d_His') . ' - ' . utf8_decode($dn['commonName']);
				$_SESSION["certificateFile"] = $certificate;
				header("HTTP/1.1 301 Moved Permanently");
				header("Location: $currentPage");
				$loadContent = false;
				
			} else {
				echo 'NOK $certificate = false';
				$backToForm = true;
				$message = "Impossible de g&eacute;n&eacute;rer le certificat!";
				$loadContent = true;
			}
			
			// At the end, clear all variables.
			unset($newCertName);
			unset($newCertEmail);
			unset($newCertCountry);
			unset($newCertState);
			unset($newCertCity);
			unset($certPassword);
			unset($newCertPasswordRetype);
			
		}
	}
} else {
	// invalid session. Initiate a new one by reloading the page.
	$loadContent = false;
	header("HTTP/1.1 301 Moved Permanently");
    header("Location: $currentPage");
}

if($loadContent) {
	include 'indexContent.php';
}
?>