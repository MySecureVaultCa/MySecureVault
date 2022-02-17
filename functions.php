<?php
/*
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
*/
require_once 'config.php';

function initiateSession() {
	global $config;
	
	session_set_cookie_params([
		'lifetime' => 2592000,
		'path' => '/',
		'domain' => $_SERVER['HTTP_HOST'],
		'secure' => true,
		'httponly' => true,
		'samesite' => 'strict'
	]);
	session_name($config['sessionCookieName']);
	session_start();
	$sessionId = session_id();
	if (!isset($_SESSION["validSession"])) {
		$_SESSION["validSession"] = hash('sha256', $sessionId);
		$_SESSION["sessionLastActivity"] = time();
		return true;
	} else {
		if ($_SESSION["validSession"] == hash('sha256', $sessionId) && time() - $_SESSION["sessionLastActivity"] < 2592000) {
			session_regenerate_id(true);
			$sessionId = session_id();
			$_SESSION["validSession"] = hash('sha256', $sessionId);
			$_SESSION["sessionLastActivity"] = time();
			return true;
		} else {
			// Invalid or expired session detected. Kill cookie.
			killCookie();
			return false;
		}
	}
}

function detectLanguage() {
	$languageString = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
	$preferredLanguage = substr($languageString, 0, 2);
	if($preferredLanguage == 'en') {
		// English is primary
		return 'en';
	} elseif($preferredLanguage == 'fr') {
		// French is primary
		return 'fr';
	} else {
		// Cannot set to preferred language. Search for secondary preferred language...
		$enPosition = strpos($languageString, 'en');
		$frPosition = strpos($languageString, 'fr');
		if($enPosition > $frPosition) {
			// English is not primary, but preferred.
			return 'en';
		} elseif($enPosition < $frPosition) {
			// French is not primary, but preferred.
			return 'fr';
		} else {
			//Default language: English
			return 'en';
		}
	}
}

function javascriptEscape($string) {
	$string = htmlspecialchars_decode($string, ENT_QUOTES);
	$string = htmlspecialchars($string, ENT_COMPAT);
	$string = str_replace("'", "\'", $string);
	return $string;
}

function killCookie() {
	global $config;
	$secureCookieHashName = $config['sessionCookieHashName'];
	if (isset($_SERVER['HTTP_COOKIE'])) {
	    $cookies = explode(';', $_SERVER['HTTP_COOKIE']);
	    foreach($cookies as $cookie) {
	        $parts = explode('=', $cookie);
	        $name = trim($parts[0]);
	        setcookie($name, '', time()-2592000);
	        setcookie($name, '', time()-2592000, '/');
	    }
		session_destroy();
		session_write_close();
	}
	
	if(isset($_COOKIE[$secureCookieHashName])) {
		$cookieOptions = array(
			'expires' => time() - 2592000,
			'path' => '/',
			'domain' => $_SERVER['HTTP_HOST'], // leading dot for compatibility or use subdomain
			'secure' => true,     // or false
			'httponly' => true,    // or false
			'samesite' => 'Strict' // None || Lax  || Strict
		);
		setcookie($secureCookieHashName, '', $cookieOptions);
	}
}

// Database connection function
function databaseConnection() {
	global $config;
	// *** Connection information - EXTREMELY SENSITIVE ***
	$servername = $config['dbServerName'];
	$username = $config['dbUsername'];
	$serverpassword = $config['dbPassword'];
	$dbname = $config['dbName'];
	// END connection information
	global $conn;
	$conn = new mysqli($servername, $username, $serverpassword, $dbname);
	if ($conn->connect_error) {
		die("Connection failed: " . $conn->connect_error);
		return false;
	} else {
		return true;
	}
}
// END database connection function

// Database connection function
function privateDatabaseConnection() {
	global $config;
	// *** Connection information - EXTREMELY SENSITIVE ***
	$servername = $config['loggingDbServerName'];
	$username = $config['loggingDbUsername'];
	$serverpassword = $config['loggingDbPassword'];
	$dbname = $config['loggingDbName'];
	// END connection information
	global $privateConn;
	$privateConn = new mysqli($servername, $username, $serverpassword, $dbname);
	if ($privateConn->connect_error) {
		die("Connection failed: " . $privateConn->connect_error);
		return false;
	} else {
		return true;
	}
}
// END database connection function

function htmlOutput($data) {
	$data = htmlspecialchars($data, ENT_QUOTES);
	return $data;
}

function htmlData($data) {
	$original = array('é', 'è', 'ê', 'ë', 'à', 'â', 'ç', 'û', 'ù', 'î', 'ï', 'ô', "\r\n\r\n", 'É', 'È', 'Ê', 'Ë', 'À', 'Â', 'Ç', 'Û', 'Ù', 'Î', 'Ï', 'Ô');
	$replaced = array('&eacute;', '&egrave;', '&ecirc;', '&euml;', '&agrave;', '&acirc;', '&ccedil;', '&ucirc;', '&ugrave;', '&icirc;', '&iuml;', '&ocirc;', '<br><br>', '&Eacute;', '&Egrave;', '&Ecirc;', '&Euml;', '&Agrave;', '&Acirc;', '&Ccedil;', '&Ucirc;', '&Ugrave;', '&Icirc;', '&Iuml;', '&Ocirc;');
	$data = str_replace($original, $replaced, $data);
	return $data;
}

function reverseHtmlData($data) {
	$replaced = array('é', 'è', 'ê', 'ë', 'à', 'â', 'ç', 'û', 'ù', 'î', 'ï', 'ô', "\r\n\r\n", 'É', 'È', 'Ê', 'Ë', 'À', 'Â', 'Ç', 'Û', 'Ù', 'Î', 'Ï', 'Ô');
	$original = array('&eacute;', '&egrave;', '&ecirc;', '&euml;', '&agrave;', '&acirc;', '&ccedil;', '&ucirc;', '&ugrave;', '&icirc;', '&iuml;', '&ocirc;', '<br><br>', '&Eacute;', '&Egrave;', '&Ecirc;', '&Euml;', '&Agrave;', '&Acirc;', '&Ccedil;', '&Ucirc;', '&Ugrave;', '&Icirc;', '&Iuml;', '&Ocirc;');
	$data = str_replace($original, $replaced, $data);
	return $data;
}

// Secure form code function
function secureForm($formUid) {
	$formUid = htmlOutput($formUid);
	
	if ($_SERVER["REQUEST_METHOD"] != "POST") {
		$_SESSION["secureFormCode"] = microtime();
		$_SESSION["secureFormCode"] .= random_int(100000000, 999999999);
		$_SESSION["secureFormCode"] = hash('sha256', $_SESSION["secureFormCode"]);
		$_SESSION["secureFormCode"] = substr($_SESSION["secureFormCode"], -20);
		return true;
	}
	
	if ($_SERVER["REQUEST_METHOD"] == "POST") {
		if ($_SESSION["secureFormCode"] != $formUid) {
			return false;
		}
		$_SESSION["secureFormCode"] = microtime();
		$_SESSION["secureFormCode"] .= random_int(100000000, 999999999);
		$_SESSION["secureFormCode"] = hash('sha256', $_SESSION["secureFormCode"]);
		$_SESSION["secureFormCode"] = substr($_SESSION["secureFormCode"], -20);
		return true;
	}
}
// END Secure form code function


function getClientIp(){
	$the_ip = $_SERVER["REMOTE_ADDR"];
	return $the_ip;
}

function registerSession($private=false) {
	global $conn;
	global $config;
	
	$currentDate = date('Y-m-d H:i:s');
	
	if($private) {$session['private'] = '1';} else {$session['private'] = '0';}
	$session['userAgent'] = htmlOutput($_SERVER['HTTP_USER_AGENT']);
	$session['certificate'] = mysqli_real_escape_string($conn, $_SESSION['certId']);
	$session['ipAddress'] = htmlOutput($_SERVER['REMOTE_ADDR']);
	$session['lastActivity'] = $currentDate;
	
	$jsonSession = json_encode($session);
	$encryptedSession = encryptDataNextGen($_SESSION['encryptionKey'], $jsonSession, $config['currentCipherSuite']);
	
	if($private === true) {
		// Private computer, set session to expire in 1 month and set secure cookie.
		$secureCookieString = generateRandomString(512, 'aA1');
		$secureCookieHash = hash("sha256", $secureCookieString);
		$_SESSION['secureCookieHash'] = $secureCookieHash;
		$cookieOptions = array(
			'expires' => time() + 2592000,
			'path' => '/',
			'domain' => $_SERVER['HTTP_HOST'], // leading dot for compatibility or use subdomain
			'secure' => true,     // or false
			'httponly' => true,    // or false
			'samesite' => 'Strict' // None || Lax  || Strict
		);
		setcookie($config['sessionCookieHashName'], $secureCookieHash, $cookieOptions);
		
		$expireSession = date('Y-m-d H:i:s', strtotime("+1 month", strtotime($currentDate)));
		
	} else {
		// Public computer, set session to expire in 5 minutes.
		$expireSession = date('Y-m-d H:i:s', strtotime("+5 minutes", strtotime($currentDate)));
	}
	
	$sql = "INSERT INTO sessions (userId, expires, cipherSuite, iv, session, tag) VALUES ('$_SESSION[userId]', '$expireSession', '$config[currentCipherSuite]', '$encryptedSession[iv]', '$encryptedSession[data]', '$encryptedSession[tag]')";
	$conn -> query($sql);
	
	$_SESSION['sessionId'] = mysqli_insert_id($conn);
	//var_dump($session);
}

function validateSession() {
	global $conn;
	global $config;
	
	$currentDate = date('Y-m-d H:i:s');
	
	$sql = "SELECT id, userId, expires, cipherSuite, iv, session, tag FROM sessions WHERE id='$_SESSION[sessionId]'";
	$db_rawSession = $conn->query($sql);
	if(mysqli_num_rows($db_rawSession) == 1) {
		$db_session = $db_rawSession -> fetch_assoc();
		if($currentDate < $db_session['expires']) {
			// Decrypt session data to update info...
			$jsonSession = decryptDataNextGen($db_session['iv'], $_SESSION['encryptionKey'], $db_session['session'], $db_session['cipherSuite'], $db_session['tag']);
			$session = json_decode($jsonSession, true);
			if($session['private'] == '1') {
				// Session is private. Validate cookie before going forward.
				$secureCookieHashName = $config['sessionCookieHashName'];
				$secureCookieHash = $_COOKIE[$secureCookieHashName];
				if(strlen($secureCookieHash) == 64 && ctype_xdigit($secureCookieHash) && $secureCookieHash == $_SESSION['secureCookieHash']) {
					// Cookie hash matches!
					$sessionIsValid = true;
				} else {
					// Cookie hash doesn't match. Mothafucka!
					$sessionIsValid = false;
				}
			} else {
				// Session is not private. Few chances an attacker may have guessed the session ID...
				$sessionIsValid = true;
			}
			
			if($sessionIsValid) {
				$session['lastActivity'] = $currentDate;
				$session['ipAddress'] = htmlOutput($_SERVER['REMOTE_ADDR']);
				$jsonSession = json_encode($session);
				$encryptedSession = encryptDataNextGen($_SESSION['encryptionKey'], $jsonSession, $config['currentCipherSuite']);
				
				// Update session info in database
				$sql = "UPDATE sessions SET cipherSuite='$config[currentCipherSuite]', iv='$encryptedSession[iv]', session='$encryptedSession[data]', tag='$encryptedSession[tag]' WHERE id='$_SESSION[sessionId]'";
				$conn->query($sql);
				
				return true;
			} else {
				return false;
			}
		} else {
			// Session is no longer valid. Delete from database.
			$sql = "DELETE FROM sessions WHERE id='$_SESSION[sessionId]'";
			$conn -> query($sql);
			return false;
		}
	} else {
		// Session no longer exists. return false.
		return false;
	}
}

function killSession() {
	global $conn;
	if(isset($_SESSION['sessionId']) || $_SESSION['sessionId'] != '' || !is_null($_SESSION['sessionId'])) {
		$sql = "DELETE FROM sessions WHERE id='$_SESSION[sessionId]'";
		$conn -> query($sql);
	}
}

function setToken() {
	$_SESSION['nonce'] = random_int(1, 9223372036854775807);
	$tokenString = $_SESSION['emailAddress'].$_SESSION['certFingerprint'].$_SESSION['nonce'];	
	$_SESSION['token'] = hash("sha256", $tokenString);
}

function authenticateToken() {
	global $conn;
	$sql = "SELECT id FROM users WHERE id='$_SESSION[userId]'";
	$db_rawUser = $conn -> query($sql);
	if(mysqli_num_rows($db_rawUser) == 1) {
		$db_user = $db_rawUser->fetch_assoc();
		$computedTokenString = $_SESSION['emailAddress'].$_SESSION['certFingerprint'].$_SESSION['nonce'];
		$computedToken = hash("sha256", $computedTokenString);
		if ($computedToken == $_SESSION['token']) {
			// Make sure certificate has not been revoked...
			$sql = "SELECT revoked FROM certs WHERE id='$_SESSION[certId]'";
			$db_rawCert = $conn->query($sql);
			$db_cert = $db_rawCert->fetch_assoc();
			if($db_cert['revoked'] === '1') {
				// Cert has been revoked. Kill session.
				killCookie();
				return false;
			} else {
				// Make sure the session is still valid and registered in database...
				if(validateSession() === true) {
					// ALL IS GOOD! Generate new token.
					setToken();
					// Set last access time in user account...
					$currentDate = date('Y-m-d H:i:s');
					$sql = "UPDATE users SET lastAccess='$currentDate' WHERE id='$_SESSION[userId]'";
					$conn -> query($sql);
					
					if($_SESSION['businessAccount'] == true) {
						$businessInfo = getBusinessInfo($_SESSION['userId']);
						if($businessInfo['status'] != 'uninitialized') {
							$user = getBusinessUserInfo($_SESSION['certId']);
							$user['lastActivity'] = date("Y-m-d H:i:s");
							setBusinessUserInfo($user['id'], $user);
						}
						if($user['deleted'] === '1') {
							killCookie();
							return false;
						}
					}
					
					return true;
				} else {
					killCookie();
					return false; // Freshly added 2022-02-16. If any problem encountered, check here!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
				}
			}
		} else {
			killCookie();
			return false;
		}
	} else {
		killCookie();
		return false;
	}
}

function encrypt($publicKey, $value, $padding) {
	if(openssl_public_encrypt($value, $encryptedValue, $publicKey, $padding)) {
		$base64EncryptedValue = base64_encode($encryptedValue);
		return $base64EncryptedValue;
	} else {
		return false;
	}
}

function decrypt($privateKey, $base64EncryptedValue, $padding){
	$encryptedValue = base64_decode($base64EncryptedValue);
	openssl_private_decrypt($encryptedValue, $value, $privateKey, $padding);
	return $value;
}

function encryptDataNextGen($key, $data, $cipherSuite) {
	// $key est une clé fournie en base 64
	$ivlen = openssl_cipher_iv_length($cipherSuite);
    $iv = openssl_random_pseudo_bytes($ivlen);
	$key = base64_decode($key);
	$encrypted['data'] = base64_encode(openssl_encrypt($data, $cipherSuite, $key, $options=OPENSSL_RAW_DATA, $iv, $tag));
	$encrypted['iv'] = base64_encode($iv);
	$encrypted['tag'] = base64_encode($tag);
	return $encrypted;
}

function decryptDataNextGen($iv, $key, $data, $cipherSuite, $tag=0) {
	global $config;
	if($cipherSuite != $config['currentCipherSuite']) {
		$_SESSION['updateCipherSuites'] = true;
	}
	
	$iv = base64_decode($iv);
	$key = base64_decode($key);
	$data = base64_decode($data);
	$tag = base64_decode($tag);
	$decrypted = openssl_decrypt($data, $cipherSuite, $key, $options=OPENSSL_RAW_DATA, $iv, $tag);
	return $decrypted;
}

function generateEncryptionKey(){
	$encryptionKey = base64_encode(openssl_random_pseudo_bytes(64));
	return $encryptionKey;
}

function generateEncryptionKeyNextGen(){
	$encryptionKey = openssl_random_pseudo_bytes(460);
	return $encryptionKey;
}

function generateRandomString($length, $type){
	if($type == 'aA') { $stringSpace = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';}
	if($type == 'aA1') { $stringSpace = 'abcdefghijklmnopqrstuvwxyz1234567890ABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890'; }
	if($type == 'aA1!') { $stringSpace = 'abcdefghijklmnopqrstuvwxyz1234567890ABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890!"/$%?&*()_+#-=@'; }
	
	$spaceLength = strlen($stringSpace) - 1;
	$string = '';
	
	while ($length > 0) {
		$charPos = random_int(0, $spaceLength);
		$string .= substr($stringSpace, $charPos, 1);
		$length--;
	}
	
	return $string;	
}

function emailExists($email) {
	// $email MUST BE ESCAPED BEFORE USING THIS FUNCTION!
	global $conn;
	global $config;
	$emailHashString = $config['salt'].$email;
	$emailHash = hash('sha256', $emailHashString);
	$sql = "SELECT id, userId FROM certs WHERE emailAddress='$email' OR hashedEmail='$emailHash'";
	$db_rawCert = $conn->query($sql);
	if(mysqli_num_rows($db_rawCert) > 0) {
		while($row = $db_rawCert->fetch_assoc()) {
			$userId = $row['userId'];
		}
		return $userId;
	} else {
		return false;
	}
}

function countryList($selected){
	if($selected == '') {$selected = 'CA';}
	$htmlString = '
	<option value="CA"'; if ($selected == 'CA') { $htmlString .= ' selected'; $selection = true; } $htmlString .= '>Canada</option>
	<option value="US"'; if ($selected == 'US') { $htmlString .= ' selected'; $selection = true; } $htmlString .= '>United States</option>
	<option value="MX"'; if ($selected == 'MX') { $htmlString .= ' selected'; $selection = true; } $htmlString .= '>Mexico</option>
	<option></option>
	<option value="AT"'; if ($selected == 'AT') { $htmlString .= ' selected'; $selection = true; } $htmlString .= '>Austria</option>
	<option value="BE"'; if ($selected == 'BE') { $htmlString .= ' selected'; $selection = true; } $htmlString .= '>Belgium</option>
	<option value="DK"'; if ($selected == 'DK') { $htmlString .= ' selected'; $selection = true; } $htmlString .= '>Denmark</option>
	<option value="FI"'; if ($selected == 'FI') { $htmlString .= ' selected'; $selection = true; } $htmlString .= '>Finland</option>
	<option value="GR"'; if ($selected == 'GR') { $htmlString .= ' selected'; $selection = true; } $htmlString .= '>Greece</option>
	<option value="FR"'; if ($selected == 'FR') { $htmlString .= ' selected'; $selection = true; } $htmlString .= '>France</option>
	<option value="DE"'; if ($selected == 'DE') { $htmlString .= ' selected'; $selection = true; } $htmlString .= '>Germany</option>
	<option value="IE"'; if ($selected == 'IE') { $htmlString .= ' selected'; $selection = true; } $htmlString .= '>Ireland</option>
	<option value="IT"'; if ($selected == 'IT') { $htmlString .= ' selected'; $selection = true; } $htmlString .= '>Italy</option>
	<option value="LI"'; if ($selected == 'LI') { $htmlString .= ' selected'; $selection = true; } $htmlString .= '>Liechtenstein</option>
	<option value="MC"'; if ($selected == 'MC') { $htmlString .= ' selected'; $selection = true; } $htmlString .= '>Monaco</option>
	<option value="NL"'; if ($selected == 'NL') { $htmlString .= ' selected'; $selection = true; } $htmlString .= '>Netherlands</option>
	<option value="NO"'; if ($selected == 'NO') { $htmlString .= ' selected'; $selection = true; } $htmlString .= '>Norway</option>
	<option value="PL"'; if ($selected == 'PL') { $htmlString .= ' selected'; $selection = true; } $htmlString .= '>Poland</option>
	<option value="PT"'; if ($selected == 'PT') { $htmlString .= ' selected'; $selection = true; } $htmlString .= '>Portugal</option>
	<option value="ES"'; if ($selected == 'ES') { $htmlString .= ' selected'; $selection = true; } $htmlString .= '>Spain</option>
	<option value="SE"'; if ($selected == 'SE') { $htmlString .= ' selected'; $selection = true; } $htmlString .= '>Sweden</option>
	<option value="CH"'; if ($selected == 'CH') { $htmlString .= ' selected'; $selection = true; } $htmlString .= '>Switzerland</option>
	<option value="GB"'; if ($selected == 'GB') { $htmlString .= ' selected'; $selection = true; } $htmlString .= '>United Kingdom</option>
	<option></option>
	<option value="AU"'; if ($selected == 'AU') { $htmlString .= ' selected'; $selection = true; } $htmlString .= '>Australia</option>
	<option value="BR"'; if ($selected == 'BR') { $htmlString .= ' selected'; $selection = true; } $htmlString .= '>Brazil</option>
	<option value="BG"'; if ($selected == 'BG') { $htmlString .= ' selected'; $selection = true; } $htmlString .= '>Bulgaria</option>
	<option value="KY"'; if ($selected == 'KY') { $htmlString .= ' selected'; $selection = true; } $htmlString .= '>Cayman Islands</option>
	<option value="CL"'; if ($selected == 'CL') { $htmlString .= ' selected'; $selection = true; } $htmlString .= '>Chile</option>
	<option value="CO"'; if ($selected == 'CO') { $htmlString .= ' selected'; $selection = true; } $htmlString .= '>Colombia</option>
	<option value="CR"'; if ($selected == 'CR') { $htmlString .= ' selected'; $selection = true; } $htmlString .= '>Costa Rica</option>
	<option value="HR"'; if ($selected == 'HR') { $htmlString .= ' selected'; $selection = true; } $htmlString .= '>Croatia</option>
	<option value="CU"'; if ($selected == 'CU') { $htmlString .= ' selected'; $selection = true; } $htmlString .= '>Cuba</option>
	<option value="CW"'; if ($selected == 'CW') { $htmlString .= ' selected'; $selection = true; } $htmlString .= '>Curaçao</option>
	<option value="CY"'; if ($selected == 'CY') { $htmlString .= ' selected'; $selection = true; } $htmlString .= '>Cyprus</option>
	<option value="CZ"'; if ($selected == 'CZ') { $htmlString .= ' selected'; $selection = true; } $htmlString .= '>Czech Republic</option>
	<option value="DO"'; if ($selected == 'DO') { $htmlString .= ' selected'; $selection = true; } $htmlString .= '>Dominican Republic</option>
	<option value="EC"'; if ($selected == 'EC') { $htmlString .= ' selected'; $selection = true; } $htmlString .= '>Ecuador</option>
	<option value="EG"'; if ($selected == 'EG') { $htmlString .= ' selected'; $selection = true; } $htmlString .= '>Egypt</option>
	<option value="SV"'; if ($selected == 'SV') { $htmlString .= ' selected'; $selection = true; } $htmlString .= '>El Salvador</option>
	<option value="EE"'; if ($selected == 'EE') { $htmlString .= ' selected'; $selection = true; } $htmlString .= '>Estonia</option>
	<option value="FJ"'; if ($selected == 'FJ') { $htmlString .= ' selected'; $selection = true; } $htmlString .= '>Fiji</option>
	<option value="GL"'; if ($selected == 'GL') { $htmlString .= ' selected'; $selection = true; } $htmlString .= '>Greenland</option>
	<option value="GD"'; if ($selected == 'GD') { $htmlString .= ' selected'; $selection = true; } $htmlString .= '>Grenada</option>
	<option value="GP"'; if ($selected == 'GP') { $htmlString .= ' selected'; $selection = true; } $htmlString .= '>Guadeloupe</option>
	<option value="GT"'; if ($selected == 'GT') { $htmlString .= ' selected'; $selection = true; } $htmlString .= '>Guatemala</option>
	<option value="HN"'; if ($selected == 'HN') { $htmlString .= ' selected'; $selection = true; } $htmlString .= '>Honduras</option>
	<option value="HK"'; if ($selected == 'HK') { $htmlString .= ' selected'; $selection = true; } $htmlString .= '>Hong Kong</option>
	<option value="HU"'; if ($selected == 'HU') { $htmlString .= ' selected'; $selection = true; } $htmlString .= '>Hungary</option>
	<option value="IS"'; if ($selected == 'IS') { $htmlString .= ' selected'; $selection = true; } $htmlString .= '>Iceland</option>
	<option value="IN"'; if ($selected == 'IN') { $htmlString .= ' selected'; $selection = true; } $htmlString .= '>India</option>
	<option value="JM"'; if ($selected == 'JM') { $htmlString .= ' selected'; $selection = true; } $htmlString .= '>Jamaica</option>
	<option value="JP"'; if ($selected == 'JP') { $htmlString .= ' selected'; $selection = true; } $htmlString .= '>Japan</option>
	<option value="KR"'; if ($selected == 'KR') { $htmlString .= ' selected'; $selection = true; } $htmlString .= '>Korea, Republic of</option>
	<option value="LV"'; if ($selected == 'LV') { $htmlString .= ' selected'; $selection = true; } $htmlString .= '>Latvia</option>
	<option value="LT"'; if ($selected == 'LT') { $htmlString .= ' selected'; $selection = true; } $htmlString .= '>Lithuania</option>
	<option value="LU"'; if ($selected == 'LU') { $htmlString .= ' selected'; $selection = true; } $htmlString .= '>Luxembourg</option>
	<option value="MY"'; if ($selected == 'MY') { $htmlString .= ' selected'; $selection = true; } $htmlString .= '>Malaysia</option>
	<option value="MV"'; if ($selected == 'MV') { $htmlString .= ' selected'; $selection = true; } $htmlString .= '>Maldives</option>
	<option value="MT"'; if ($selected == 'MT') { $htmlString .= ' selected'; $selection = true; } $htmlString .= '>Malta</option>
	<option value="MQ"'; if ($selected == 'MQ') { $htmlString .= ' selected'; $selection = true; } $htmlString .= '>Martinique</option>
	<option value="MA"'; if ($selected == 'MA') { $htmlString .= ' selected'; $selection = true; } $htmlString .= '>Morocco</option>
	<option value="NP"'; if ($selected == 'NP') { $htmlString .= ' selected'; $selection = true; } $htmlString .= '>Nepal</option>
	<option value="NC"'; if ($selected == 'NC') { $htmlString .= ' selected'; $selection = true; } $htmlString .= '>New Caledonia</option>
	<option value="NZ"'; if ($selected == 'NZ') { $htmlString .= ' selected'; $selection = true; } $htmlString .= '>New Zealand</option>
	<option value="PA"'; if ($selected == 'PA') { $htmlString .= ' selected'; $selection = true; } $htmlString .= '>Panama</option>
	<option value="PG"'; if ($selected == 'PG') { $htmlString .= ' selected'; $selection = true; } $htmlString .= '>Papua New Guinea</option>
	<option value="PY"'; if ($selected == 'PY') { $htmlString .= ' selected'; $selection = true; } $htmlString .= '>Paraguay</option>
	<option value="PE"'; if ($selected == 'PE') { $htmlString .= ' selected'; $selection = true; } $htmlString .= '>Peru</option>
	<option value="PH"'; if ($selected == 'PH') { $htmlString .= ' selected'; $selection = true; } $htmlString .= '>Philippines</option>
	<option value="PR"'; if ($selected == 'PR') { $htmlString .= ' selected'; $selection = true; } $htmlString .= '>Puerto Rico</option>
	<option value="RO"'; if ($selected == 'RO') { $htmlString .= ' selected'; $selection = true; } $htmlString .= '>Romania</option>
	<option value="KN"'; if ($selected == 'KN') { $htmlString .= ' selected'; $selection = true; } $htmlString .= '>Saint Kitts and Nevis</option>
	<option value="LC"'; if ($selected == 'LC') { $htmlString .= ' selected'; $selection = true; } $htmlString .= '>Saint Lucia</option>
	<option value="MF"'; if ($selected == 'MF') { $htmlString .= ' selected'; $selection = true; } $htmlString .= '>Saint Martin (French part)</option>
	<option value="PM"'; if ($selected == 'PM') { $htmlString .= ' selected'; $selection = true; } $htmlString .= '>Saint Pierre and Miquelon</option>
	<option value="SC"'; if ($selected == 'SC') { $htmlString .= ' selected'; $selection = true; } $htmlString .= '>Seychelles</option>
	<option value="SG"'; if ($selected == 'SG') { $htmlString .= ' selected'; $selection = true; } $htmlString .= '>Singapore</option>
	<option value="SX"'; if ($selected == 'SX') { $htmlString .= ' selected'; $selection = true; } $htmlString .= '>Sint Maarten (Dutch part)</option>
	<option value="SK"'; if ($selected == 'SK') { $htmlString .= ' selected'; $selection = true; } $htmlString .= '>Slovakia</option>
	<option value="SI"'; if ($selected == 'SI') { $htmlString .= ' selected'; $selection = true; } $htmlString .= '>Slovenia</option>
	<option value="SB"'; if ($selected == 'SB') { $htmlString .= ' selected'; $selection = true; } $htmlString .= '>Solomon Islands</option>
	<option value="TH"'; if ($selected == 'TH') { $htmlString .= ' selected'; $selection = true; } $htmlString .= '>Thailand</option>
	<option value="TT"'; if ($selected == 'TT') { $htmlString .= ' selected'; $selection = true; } $htmlString .= '>Trinidad and Tobago</option>
	<option value="TC"'; if ($selected == 'TC') { $htmlString .= ' selected'; $selection = true; } $htmlString .= '>Turks and Caicos Islands</option>
	<option value="TV"'; if ($selected == 'TV') { $htmlString .= ' selected'; $selection = true; } $htmlString .= '>Tuvalu</option>
	<option value="UY"'; if ($selected == 'UY') { $htmlString .= ' selected'; $selection = true; } $htmlString .= '>Uruguay</option>
	<option value="VU"'; if ($selected == 'VU') { $htmlString .= ' selected'; $selection = true; } $htmlString .= '>Vanuatu</option>
	<option value="VN"'; if ($selected == 'VN') { $htmlString .= ' selected'; $selection = true; } $htmlString .= '>Viet Nam</option>
	<option value="VG"'; if ($selected == 'VG') { $htmlString .= ' selected'; $selection = true; } $htmlString .= '>Virgin Islands, British</option>
	<option value="VI"'; if ($selected == 'VI') { $htmlString .= ' selected'; $selection = true; } $htmlString .= '>Virgin Islands, U.S.</option>
';

return $htmlString;
}

function validateCountry($cc) {
	if($cc == 'CA' || $cc == 'US' || $cc == 'MX' || $cc == 'AT' || $cc == 'BE' || $cc == 'DK' || $cc == 'FI' || $cc == 'GR' || $cc == 'FR' || $cc == 'DE' || $cc == 'IE' || $cc == 'IT' || $cc == 'LI' || $cc == 'MC' || $cc == 'NL' || $cc == 'NO' || $cc == 'PL' || $cc == 'PT' || $cc == 'ES' || $cc == 'SE' || $cc == 'CH' || $cc == 'GB' || $cc == 'AU' || $cc == 'BR' || $cc == 'BG' || $cc == 'KY' || $cc == 'CL' || $cc == 'CO' || $cc == 'CR' || $cc == 'HR' || $cc == 'CU' || $cc == 'CW' || $cc == 'CY' || $cc == 'CZ' || $cc == 'DO' || $cc == 'EC' || $cc == 'EG' || $cc == 'SV' || $cc == 'EE' || $cc == 'FJ' || $cc == 'GL' || $cc == 'GD' || $cc == 'GP' || $cc == 'GT' || $cc == 'HN' || $cc == 'HK' || $cc == 'HU' || $cc == 'IS' || $cc == 'IN' || $cc == 'JM' || $cc == 'JP' || $cc == 'KR' || $cc == 'LV' || $cc == 'LT' || $cc == 'LU' || $cc == 'MY' || $cc == 'MV' || $cc == 'MT' || $cc == 'MQ' || $cc == 'MA' || $cc == 'NP' || $cc == 'NC' || $cc == 'NZ' || $cc == 'PA' || $cc == 'PG' || $cc == 'PY' || $cc == 'PE' || $cc == 'PH' || $cc == 'PR' || $cc == 'RO' || $cc == 'KN' || $cc == 'LC' || $cc == 'MF' || $cc == 'PM' || $cc == 'SC' || $cc == 'SG' || $cc == 'SX' || $cc == 'SK' || $cc == 'SI' || $cc == 'SB' || $cc == 'TH' || $cc == 'TT' || $cc == 'TC' || $cc == 'TV' || $cc == 'UY' || $cc == 'VU' || $cc == 'VN' || $cc == 'VG' || $cc == 'VI') { return true; } else { return false; }
} 

function authenticateUser($certSerialNumber, $certFingerprint, $certPrivateKey) {
	global $conn;
	// DO NOT ESCAPE THE PRIVATE KEY!!!
	$certSerialNumber = mysqli_real_escape_string($conn, $certSerialNumber);
	$certFingerprint = mysqli_real_escape_string($conn, $certFingerprint);
	$sql = "SELECT padding, encrypted, revoked FROM certs WHERE serial='$certSerialNumber'";
	$db_rawCert = $conn->query($sql);
	$db_cert = $db_rawCert->fetch_assoc();
	if($db_cert['padding'] == 'OPENSSL_PKCS1_PADDING') { $padding = OPENSSL_PKCS1_PADDING; } elseif($db_cert['padding'] == 'OPENSSL_PKCS1_OAEP_PADDING') { $padding = OPENSSL_PKCS1_OAEP_PADDING; }
	
	if(decrypt($certPrivateKey, $db_cert['encrypted'], $padding) == $certSerialNumber.$certFingerprint && $db_cert['revoked'] !== '1') {
		return true;
	} else {
		return false;
	}
}

function generateKeypair(){
	$keypair = openssl_pkey_new(array(
		"private_key_bits" => 4096,
		"private_key_type" => OPENSSL_KEYTYPE_RSA
	));
	
	openssl_pkey_export($keypair, $key['private']);
	$publicKey = openssl_pkey_get_details($keypair);
	$key['public'] = $publicKey['key'];
	
	return $key;
}

function generateNewCertificate($dn, $password) {
	global $conn;
	$trial = 50; // After 50 trials of finding a new cert serial available, the function will fail...
	$foundCertSerial = 1;
	while($foundCertSerial > 0 && $trial > 0) {
		
		//Find a free serial number
		$serial = random_int(1, 9223372036854775807);
		$sql = "SELECT id FROM certs WHERE serial='$serial'";
		$db_rawCerts = $conn->query($sql);
		$foundCertSerial = mysqli_num_rows($db_rawCerts);
		$trial ++;
	}
	
	if($foundCertSerial == 1) {
		//Unable to find a free serial number...
		return false;
		// $message = "Impossible de g&eacute;n&eacute;rer un certificat. R&eacute;essayer plus tard.";
	} else {
		
		// We have found a free certificate number.
		$emailAddress = mysqli_real_escape_string($conn, $dn[emailAddress]);
		// Check if email address is already taken by another user...
		if(emailExists($emailAddress)) { $emailExists = true; } else { $emailExists = false; }
		
		$proceed = true;
		
		/*
		CANCEL ALL EMAIL VALIDATIONS
		
		if (!$emailExists) {
			// New email address... we can proceed with a new certificate request!
			$proceed = true;
		} elseif(!isset($_SESSION['emailAddress'])) {
			// Email address already exists in our databases, but user is not logged in. This is unacceptable!
			$proceed = false;
			//$message = "Cette adresse courriel existe d&eacute;j&agrave;. Si vous la possédez, ouvrez d'abord une session avec votre certificat existant avant de g&eacute;n&eacute;rer un nouveau certificat.";
			return false;
		} else {
			// Email address already exists in our databases and user seems to be logged in... Make sure the request is from the same user!
			$emailHash = hash('sha256', $emailAddress);
			$sql = "SELECT userId FROM certs WHERE emailAddress='$emailAddress' OR hashedEmail='$emailHash'";
			while($row = $db_rawCerts -> fetch_assoc()) {
				if($row['userId'] == $_SESSION['userId']) {
					// Same user. Seems good to proceed!
					$proceed = true;
				} else {
					
					$proceed = false;
					//$message = "Cette adresse courriel existe d&eacute;j&agrave;. Si vous la possédez, ouvrez d'abord une session avec votre certificat existant avant de g&eacute;n&eacute;rer un nouveau certificat.";
					return false;
				}
			}
		}
		*/
		
		if($proceed) {
			// All validations successful... create certificate!
			$privateKeyPassword = generateRandomString(20, 'aA1');
			
			$privateKey = openssl_pkey_new(array(
				"private_key_bits" => 4096,
				"private_key_type" => OPENSSL_KEYTYPE_RSA
			));
			
			$csr = openssl_csr_new($dn, $privateKey, array('digest_alg' => 'sha256'));
			$certificate = openssl_csr_sign($csr, null, $privateKey, $days=365, array('digest_alg' => 'sha256'), $serial);
			
			openssl_x509_export($certificate, $exportedCertificate);
			openssl_pkey_export($privateKey, $exportedPrivateKey, $privateKeyPassword);
			openssl_pkcs12_export($exportedCertificate, $pkcs12Certificate, array($exportedPrivateKey, $privateKeyPassword), $password);
			
			return $pkcs12Certificate;
		}
	}
}

function registerCertificate($cert, $certPass, $userId, $secure) {
	global $conn;
	global $config;
	// VALIDATIONS MUST HAVE BEEN MADE BEFORE USING THIS FUNCTION.
	// IT DOES NOT VERIFY IF USER ALREADY EXISTS, OR IF EMAIL ADDRESS ALREADY EXISTS.
	
	if($userId == 'new') {$newUser = true;} else {$newUser = false;}
	
	openssl_pkcs12_read($cert, $certData, $certPass);
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
	
	if ($newUser) {
		//Create new user
		$sql = "INSERT INTO users (registrationTime) VALUES ('$currentDate')";
		$conn->query($sql);
		$userId = mysqli_insert_id($conn);
		
		// Does the new user try to create a new business account?
		if($_SESSION['newBusinessAccount'] === true) {
			$sql = "UPDATE users SET businessAccount='1' WHERE id='$userId'";
			$conn->query($sql);
		}
		
		//Create password encryption key for NEW user
		$rawEncryptionKey = generateEncryptionKeyNextGen();
		$encryptionKeyVersion = '2';
		$base64EncryptionKey = base64_encode($rawEncryptionKey);
		
	} else {
		// Check which version is the current encryption key.
		$sql = "SELECT id, version FROM encryptionKeys WHERE certId='$_SESSION[certId]'";
		$db_rawEncryptionKey = $conn->query($sql);
		$encryptionKey = $db_rawEncryptionKey -> fetch_assoc();
		if($encryptionKey['version'] == '1') {
			$rawEncryptionKey = $_SESSION['encryptionKey'];
			$base64EncryptionKey = $_SESSION['encryptionKey'];
			$encryptionKeyVersion = '1';
		} elseif($encryptionKey['version'] == '2') {
			$rawEncryptionKey = base64_decode($_SESSION['encryptionKey']);
			$base64EncryptionKey = base64_encode($rawEncryptionKey);
			$encryptionKeyVersion = '2';
		}
	}
	
	// Encrypt encryption key with public key and save it.
	$encryptedEncryptionKey = encrypt($certPublicKey, $rawEncryptionKey, $config['currentPadding']);
	$encryptedPkcs12Package = encryptDataNextGen($base64EncryptionKey, $cert, $config['currentCipherSuite']);
	$encryptedPkcs12File = $encryptedPkcs12Package['data'];
	$ivPkcs12File = $encryptedPkcs12Package['iv'];
	$tagPkcs12File = $encryptedPkcs12Package['tag'];
	
	$encryptedFingerprint = mysqli_real_escape_string($conn, $encryptedFingerprint);
	
	if($secure === true) {
		//Store certificate data encrypted...
		$emailHashString = $config['salt'].$certEmailAddress;
		$emailHash = hash('sha256', $emailHashString);
		
		$jsonCertData['fullName'] = utf8_encode($certFullName);
		$jsonCertData['emailAddress'] = utf8_encode($certEmailAddress);
		$jsonCertData['fingerprint'] = utf8_encode($certFingerprint);
		$jsonCertData['country'] = utf8_encode($certCountry);
		$jsonCertData['state'] = utf8_encode($certState);
		$jsonCertData['city'] = utf8_encode($certCity);
		$jsonCertData['language'] = utf8_encode($_SESSION['language']);
		$jsonCertData['validFrom'] = utf8_encode($certValidFrom);
		$jsonCertData['validTo'] = utf8_encode($certValidTo);
		$jsonCertData['publicKey'] = utf8_encode($certPublicKey);
		
		
		$jsonCert = json_encode($jsonCertData);
		$encryptedJsonCert = encryptDataNextGen($base64EncryptionKey, $jsonCert, $config['currentCipherSuite']);
		$ivCertData = $encryptedJsonCert['iv'];
		$encryptedCertData = $encryptedJsonCert['data'];
		$tagCertData = $encryptedJsonCert['tag'];
		
		$sql = "INSERT INTO certs (serial, padding, encrypted, userId, revoked, ivPkcs12File, encryptedPkcs12File, tagPkcs12File, cipherSuitePkcs12File, hashedEmail, ivCertData, encryptedCertData, tagCertData, cipherSuiteCertData) VALUES ('$certSerialNumber', '$config[currentPaddingString]', '$encryptedFingerprint', '$userId', '0', '$ivPkcs12File', '$encryptedPkcs12File', '$tagPkcs12File', '$config[currentCipherSuite]', '$emailHash', '$ivCertData', '$encryptedCertData', '$tagCertData', '$config[currentCipherSuite]')";
		$conn->query($sql);
		
	}
	
	$certId = mysqli_insert_id($conn);
	//echo '<pre>';
	//var_dump($readCert);
	//echo '</pre>';
	
	// Save encrypted encryption key for certificate
	$sql = "INSERT INTO encryptionKeys (certId, padding, encryptedKey, version) VALUES ('$certId', '$config[currentPaddingString]', '$encryptedEncryptionKey', '$encryptionKeyVersion')";
	$conn->query($sql);
	
	return $certId;
}

function changeCertLanguage($newLanguage) {
	global $conn;
	global $config;
	$newLanguage = mysqli_real_escape_string($conn, $newLanguage);
	if(!is_null($_SESSION['fullName'])) {
		$sql = "SELECT ivCertData, encryptedCertData, tagCertData, cipherSuiteCertData FROM certs WHERE id='$_SESSION[certId]' && userId='$_SESSION[userId]'";
		if ($db_rawCert = $conn->query($sql)) {
			if(mysqli_num_rows($db_rawCert) == 1) {
				$row = $db_rawCert->fetch_assoc();
				// Certificate is encrypted. Decrypt, update, encrypt.
				$jsonCertData = decryptDataNextGen($row['ivCertData'], $_SESSION['encryptionKey'], $row['encryptedCertData'], $row['cipherSuiteCertData'], $row['tagCertData']);
				$certData = json_decode($jsonCertData, true);
				if(utf8_decode($certData['language']) == 'en' || utf8_decode($certData['language']) == 'fr') {
					$certData['language'] = utf8_encode($newLanguage);
					$newJsonCertData = json_encode($certData);
					$encryptedJsonCert = encryptDataNextGen($_SESSION['encryptionKey'], $newJsonCertData, $config['currentCipherSuite']);
					$ivCertData = $encryptedJsonCert['iv'];
					$encryptedCertData = $encryptedJsonCert['data'];
					$tagCertData = $encryptedJsonCert['tag'];
					$sql = "UPDATE certs SET ivCertData='$ivCertData', encryptedCertData='$encryptedCertData', tagCertData='$tagCertData', cipherSuiteCertData='$config[currentCipherSuite]' WHERE id='$_SESSION[certId]' && userId='$_SESSION[userId]'";
					// echo $newJsonCertData;
					if ($conn->query($sql)) {
						return true;
					} else {
						return false;
					}
				} else {
					return false;
				}
			} else {
				return false;
			}
		} else {
			return false;
		}
	} else {
		return false;
	}
}

function registerVisit() {
	global $privateConn;
	$ipAddress = mysqli_real_escape_string($privateConn, $_SERVER['REMOTE_ADDR']);
	$page = mysqli_real_escape_string($privateConn, $_SERVER['PHP_SELF']);
	$queryString = mysqli_real_escape_string($privateConn, $_SERVER['QUERY_STRING']);
	$userAgent = mysqli_real_escape_string($privateConn, $_SERVER['HTTP_USER_AGENT']);
	
	$sql = "INSERT INTO visits (ipAddress, page, queryString, userAgent) VALUES ('$ipAddress', '$page', '$queryString', '$userAgent')";
	$privateConn -> query($sql);
}

function getCertificateInfo(){
	global $config;
	$cert = file_get_contents($config['baseUrl'] . '/cert.pem');
	$readCert = openssl_x509_read($cert);
	$parsedCert = openssl_x509_parse($cert);
	$certInfo['serial'] = $parsedCert['serialNumberHex'];
	$certInfo['sha1'] = openssl_x509_fingerprint($readCert, 'SHA1');
	$certInfo['sha256'] = openssl_x509_fingerprint($readCert, 'SHA256');
	return $certInfo;
}

function detectUserDevice($httpUserAgent){
	$httpUserAgent = strtolower($httpUserAgent);
	
	//Detect device first
	if(strpos($httpUserAgent, 'android') !== false) {
		$device['osIcon'] = '<i class="fa fa-android androidColor"></i>';
		$device['osName'] = 'Android';
		$device['typeIcon'] = '<i class="fa fa-mobile"></i>';
	} elseif(strpos($httpUserAgent, 'windows') !== false) {
		$device['osIcon'] = '<i class="fa fa-windows windowsColor"></i>';
		$device['osName'] = 'Windows';
		$device['typeIcon'] = '<i class="fa fa-desktop"></i>';
	} elseif(strpos($httpUserAgent, 'ipad') !== false) {
		$device['osIcon'] = '<i class="fa fa-apple"></i>';
		$device['osName'] = 'iOS';
		$device['typeIcon'] = '<i class="fa fa-tablet"></i>';
	} elseif(strpos($httpUserAgent, 'iphone') !== false) {
		$device['osIcon'] = '<i class="fa fa-apple"></i>';
		$device['osName'] = 'iOS';
		$device['typeIcon'] = '<i class="fa fa-mobile"></i>';
	} elseif(strpos($httpUserAgent, 'macintosh') !== false) {
		$device['osIcon'] = '<i class="fa fa-apple"></i>';
		$device['osName'] = 'Mac OS X';
		$device['typeIcon'] = '<i class="fa fa-desktop"></i>';
	} elseif(strpos($httpUserAgent, 'cros') !== false) {
		$device['osIcon'] = '<i class="fa fa-chrome chromeColor"></i>';
		$device['osName'] = 'Chrome OS';
		$device['typeIcon'] = '<i class="fa fa-desktop"></i>';
	} elseif(strpos($httpUserAgent, 'linux') !== false) {
		$device['osIcon'] = '<img class="sessionIcon" src="img/iconTux.png" />';
		$device['osName'] = 'Linux';
		$device['typeIcon'] = '<i class="fa fa-desktop"></i>';
	} else {
		$device['typeIcon'] = '<i class="fa fa-desktop"></i>';
	}
	
	//Check if it is an Android tablet.
	if(strpos($httpUserAgent, 'sm-t') !== false) {
		$device['typeIcon'] = '<i class="fa fa-tablet"></i>';
	}
	
	// Then detect browser
	if(strpos($httpUserAgent, 'firefox')) {
		$device['browserIcon'] = '<img class="sessionIcon" src="img/iconFirefox.png" />';
	} elseif(strpos($httpUserAgent, 'edg')) {
		$device['browserIcon'] = '<img class="sessionIcon" src="img/iconEdge.png" />';
	} elseif(strpos($httpUserAgent, 'chrome')) {
		$device['browserIcon'] = '<img class="sessionIcon" src="img/iconChrome.png" />';
	} elseif(strpos($httpUserAgent, 'safari')) {
		$device['browserIcon'] = '<img class="sessionIcon" src="img/iconSafari.png" />';
	}
	
	return $device;
}

function isIos() {
	$device = detectUserDevice($_SERVER['HTTP_USER_AGENT']);
	if($device['osName'] == 'iOS') {
		return true;
	} else {
		return false;
	}
}

function retrieveFaviconNextGen($url){
	// $url MUST HAVE BEEN FILTERED BEFORE CALLING THIS FUNCTION!
	$connectTimeout = 3;
	$sessionTimeout = 5;
	$maxRedir = 5;
	$userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:84.0) Gecko/20100101 Firefox/84.0';
	$file = './cookies/' . microtime;
	
	$urlParts = parse_url($url);
	
	// First, check if we find an icon in the page code with the full path:
	$site = $urlParts['scheme'] . '://' . $urlParts['host'] . $urlParts['path'];
	$curlSession = curl_init($site);
	if($curlSession !== false) {
		curl_setopt($curlSession, CURLOPT_FAILONERROR, true);
		curl_setopt($curlSession, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($curlSession, CURLOPT_MAXREDIRS , $maxRedir);
		curl_setopt($curlSession, CURLOPT_FRESH_CONNECT, true);
		curl_setopt($curlSession, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curlSession, CURLOPT_CONNECTTIMEOUT, $connectTimeout);
		curl_setopt($curlSession, CURLOPT_TIMEOUT, $sessionTimeout);
		curl_setopt($curlSession, CURLOPT_USERAGENT, $userAgent);
		curl_setopt($curlSession, CURLOPT_COOKIEJAR, $file);
		curl_setopt($curlSession, CURLOPT_AUTOREFERER, true);
		curl_setopt($curlSession, CURLOPT_REFERER, 'https://mysecurevault.ca');
		$output = curl_exec($curlSession);
		$curlFinalPath = parse_url(curl_getinfo($curlSession,  CURLINFO_EFFECTIVE_URL));
		curl_close($curlSession);
		
		if($output !== false) {
			// We got some HTML. Process it!
			$doc = new DOMDocument();
			$doc->loadHTML($output);
			$items = $doc->getElementsByTagName('link');
			if(count($items) > 0) {
				foreach ($items as $link) {
					foreach ($link->attributes as $name => $value) {
						$attr[$name]=$link->getAttribute($name);
					}
					if($attr['rel'] == 'icon' || $attr['rel'] == 'ICON' || $attr['rel'] == 'shortcut icon' || $attr['rel'] == 'SHORTCUT ICON') {
						$location = $attr['href'];
						$faviconLocation = parse_url($location);
						if(substr($location, -3, 3) != 'svg' && substr($location, -3, 3) != 'SVG') {
							// REFUSE SVG FILES!
							if($faviconLocation['host'] == '') {
								//relative path
								if(substr($curlFinalPath['path'], -1, 1) == '/') {
									// Path ends with /. this is good.
									$path = $curlFinalPath['path'];
								} elseif($curlFinalPath['path'] == '') {
									// No path provided.
									$path = '/';
								} elseif($curlFinalPath['path'] != '' && substr($curlFinalPath['path'], -1, 1) != '/') {
									// Path does not end with /. Could be a file name. like index.php. We have to remove it!
									$path = substr($curlFinalPath['path'], 0, (strrpos($curlFinalPath['path'], '/') + 1));
								}
								
								if($path == '/') {
									// We are already at the root. Make sure the favicon doesn't try to go up the hierarchy.
									while(substr($faviconLocation['path'], 0, 3) == '../') {
										$pathLength = strlen($faviconLocation['path']);
										$faviconLocation['path'] = substr($faviconLocation['path'], 3, $pathLength);
									}
									$faviconLocation['path'] = '/' . $faviconLocation['path'];
								}
								
								if(substr($faviconLocation['path'], 0, 1) == '/') {
									// Favicon begins with /, so we start from the root.
									$favicons[] = $curlFinalPath['scheme'] . '://' . $curlFinalPath['host'] . $faviconLocation['path'];
								} else {
									$favicons[] = $curlFinalPath['scheme'] . '://' . $curlFinalPath['host'] . $path . $faviconLocation['path'];
								}
							} else {
								//absolute path
								if($faviconLocation['scheme'] == '') { $faviconLocation['scheme'] = $curlFinalPath['scheme']; }
								$favicons[] = $faviconLocation['scheme'] . '://' . $faviconLocation['host'] . $faviconLocation['path'];
							}
						}
					}
				}
			}
		}
	}
	
	if(!isset($favicons)) {
		// Last method didn't work. Try with naked host.
		$site = $urlParts['scheme'] . '://' . $urlParts['host'];
		
		$curlSession = curl_init($site);
		if($curlSession !== false) {
			curl_setopt($curlSession, CURLOPT_FAILONERROR, true);
			curl_setopt($curlSession, CURLOPT_FOLLOWLOCATION, true);
			curl_setopt($curlSession, CURLOPT_MAXREDIRS , $maxRedir);
			curl_setopt($curlSession, CURLOPT_FRESH_CONNECT, true);
			curl_setopt($curlSession, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curlSession, CURLOPT_CONNECTTIMEOUT, $connectTimeout);
			curl_setopt($curlSession, CURLOPT_TIMEOUT, $sessionTimeout);
			curl_setopt($curlSession, CURLOPT_USERAGENT, $userAgent);
			curl_setopt($curlSession, CURLOPT_COOKIEJAR, $file);
			curl_setopt($curlSession, CURLOPT_AUTOREFERER, true);
			curl_setopt($curlSession, CURLOPT_REFERER, 'https://mysecurevault.ca');
			$output = curl_exec($curlSession);
			$curlFinalPath = parse_url(curl_getinfo($curlSession,  CURLINFO_EFFECTIVE_URL));
			curl_close($curlSession);
			
			if($output !== false) {
				// We got some HTML. Process it!
				$doc = new DOMDocument();
				$doc->loadHTML($output);
				$items = $doc->getElementsByTagName('link');
				if(count($items) > 0) {
					foreach ($items as $link) {
						foreach ($link->attributes as $name => $value) {
							$attr[$name]=$link->getAttribute($name);
						}
						if($attr['rel'] == 'icon' || $attr['rel'] == 'ICON' || $attr['rel'] == 'shortcut icon' || $attr['rel'] == 'SHORTCUT ICON') {
							$location = $attr['href'];
							$faviconLocation = parse_url($location);
							if(substr($location, -3, 3) != 'svg' && substr($location, -3, 3) != 'SVG') {
								// REFUSE SVG FILES
								if($faviconLocation['host'] == '') {
									//relative path
									if(substr($curlFinalPath['path'], -1, 1) == '/') {
										// Path ends with /. this is good.
										$path = $curlFinalPath['path'];
									} elseif($curlFinalPath['path'] == '') {
										// No path provided.
										$path = '/';
									} elseif($curlFinalPath['path'] != '' && substr($curlFinalPath['path'], -1, 1) != '/') {
										// Path does not end with /. Could be a file name. like index.php. We have to remove it!
										$path = substr($curlFinalPath['path'], 0, (strrpos($curlFinalPath['path'], '/') + 1));
									}
									
									if($path == '/') {
										// We are already at the root. Make sure the favicon doesn't try to go up the hierarchy.
										while(substr($faviconLocation['path'], 0, 3) == '../') {
											$pathLength = strlen($faviconLocation['path']);
											$faviconLocation['path'] = substr($faviconLocation['path'], 3, $pathLength);
										}
										$faviconLocation['path'] = '/' . $faviconLocation['path'];
									}
									
									if(substr($faviconLocation['path'], 0, 1) == '/') {
										// Favicon begins with /, so we start from the root.
										$favicons[] = $curlFinalPath['scheme'] . '://' . $curlFinalPath['host'] . $faviconLocation['path'];
									} else {
										$favicons[] = $curlFinalPath['scheme'] . '://' . $curlFinalPath['host'] . $path . $faviconLocation['path'];
									}
								} else {
									//absolute path
									if($faviconLocation['scheme'] == '') { $faviconLocation['scheme'] = $curlFinalPath['scheme']; }
									$favicons[] = $faviconLocation['scheme'] . '://' . $faviconLocation['host'] . $faviconLocation['path'];
								}
							}
						}
					}
				}
			}
		}
	}
	
	if(!isset($favicons)) {
		// Lest resort, try getting our hands on the favicon.ico file at the root...
		$site = $urlParts['scheme'] . '://' . $urlParts['host'] . '/favicon.ico';
		
		$curlSession = curl_init($site);
		if($curlSession !== false) {
			curl_setopt($curlSession, CURLOPT_FAILONERROR, true);
			curl_setopt($curlSession, CURLOPT_FOLLOWLOCATION, true);
			curl_setopt($curlSession, CURLOPT_MAXREDIRS , $maxRedir);
			curl_setopt($curlSession, CURLOPT_FRESH_CONNECT, true);
			curl_setopt($curlSession, CURLOPT_NOBODY, true);
			curl_setopt($curlSession, CURLOPT_CONNECTTIMEOUT, $connectTimeout);
			curl_setopt($curlSession, CURLOPT_TIMEOUT, $sessionTimeout);
			curl_setopt($curlSession, CURLOPT_USERAGENT, $userAgent);
			curl_setopt($curlSession, CURLOPT_COOKIEJAR, $file);
			curl_setopt($curlSession, CURLOPT_AUTOREFERER, true);
			curl_setopt($curlSession, CURLOPT_REFERER, 'https://mysecurevault.ca');
			$output = curl_exec($curlSession);
			$httpCode = curl_getinfo($curlSession, CURLINFO_HTTP_CODE);
			curl_close($curlSession);
			
			if($httpCode == 200) {
				$favicons[] = $site;
			}
		}
	}
	// var_dump($favicons);
	if(!isset($favicons)) {
		return false;
	} else {
		$favicons = array_unique($favicons);
		if(count($favicons) == 1) {
			// Only one found. Return it.
			$favicon = $favicons[0];
		} else {
			// We have to choose one...
			foreach($favicons as $key => $value) {
				if(strpos($value, '32x32') !== false || strpos($value, '32') !== false) {
					$favicon32 = $favicons[$key];
				} elseif(strpos($value, '96x96' || strpos($value, '96') !== false) !== false) {
					$favicon96 = $favicons[$key];
				} elseif(strpos($value, '128×128' || strpos($value, '128') !== false) !== false) {
					$favicon128 = $favicons[$key];
				} elseif(strpos($value, '152×152' || strpos($value, '152') !== false) !== false) {
					$favicon152 = $favicons[$key];
				} elseif(strpos($value, '192×192' || strpos($value, '192') !== false) !== false) {
					$favicon192 = $favicons[$key];
				} elseif(strpos($value, '196×196' || strpos($value, '196') !== false) !== false) {
					$favicon196 = $favicons[$key];
				}
			}
			
			if(isset($favicon32)) {
				$favicon = $favicon32;
			} elseif(isset($favicon96)) {
				$favicon = $favicon96;
			} elseif(isset($favicon128)) {
				$favicon = $favicon128;
			} elseif(isset($favicon152)) {
				$favicon = $favicon152;
			} elseif(isset($favicon192)) {
				$favicon = $favicon192;
			} elseif(isset($favicon196)) {
				$favicon = $favicon196;
			} else {
				// Can't get a hold on one... chose the first in the array...
				$favicon = $favicons[0];
			}
		}
		
		$curlSession = curl_init($favicon);
		curl_setopt($curlSession, CURLOPT_FAILONERROR, true);
		curl_setopt($curlSession, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($curlSession, CURLOPT_MAXREDIRS, $maxRedir);
		curl_setopt($curlSession, CURLOPT_FRESH_CONNECT, true);
		curl_setopt($curlSession, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curlSession, CURLOPT_CONNECTTIMEOUT, $connectTimeout);
		curl_setopt($curlSession, CURLOPT_TIMEOUT, $sessionTimeout);
		curl_setopt($curlSession, CURLOPT_USERAGENT, $userAgent);
		curl_setopt($curlSession, CURLOPT_COOKIEJAR, $file);
		curl_setopt($curlSession, CURLOPT_AUTOREFERER, true);
		curl_setopt($curlSession, CURLOPT_REFERER, 'https://mysecurevault.ca');
		$output = curl_exec($curlSession);
		$httpCode = curl_getinfo($curlSession, CURLINFO_HTTP_CODE);
		curl_close($curlSession);
		
		if($httpCode == 200) {
			$faviconType = exif_imagetype($favicon);
			if($faviconType == IMAGETYPE_GIF) {
				$faviconReturn['type'] = 'image/gif';
			} elseif($faviconType == IMAGETYPE_JPEG) {
				$faviconReturn['type'] = 'image/jpeg';
			} elseif($faviconType == IMAGETYPE_PNG) {
				$faviconReturn['type'] = 'image/png';
			} elseif($faviconType == IMAGETYPE_ICO) {
				$faviconReturn['type'] = 'image/x-icon';
			} else {
				return false;
			}
			$faviconReturn['url'] = $favicon;
			$faviconReturn['image'] = base64_encode($output);
			return $faviconReturn;
		} else {
			// Getting favicon failed :-(
			return false;
			//echo $favicon;
		}
	}
}

function faviconBasedOnName($name) {
	global $config;
	$connectTimeout = 3;
	$sessionTimeout = 5;
	$userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:84.0) Gecko/20100101 Firefox/84.0';
	
	$name = strtolower($name);
	if(strpos($name, 'mysql') !== false) {
		$faviconReturn['url'] = $config['baseUrl'] . '/img/iconMysql.png';
	} elseif(strpos($name, 'linux') !== false) {
		$faviconReturn['url'] = $config['baseUrl'] . '/img/iconLinux.jpg';
	} elseif(strpos($name, 'nextcloud') !== false) {
		$faviconReturn['url'] = $config['baseUrl'] . '/img/iconNextcloud.png';
	} elseif(strpos($name, 'powerdns') !== false) {
		$faviconReturn['url'] = $config['baseUrl'] . '/img/iconPowerDNS.jpg';
	}
	
	if(isset($faviconReturn)) {
		$curlSession = curl_init($faviconReturn['url']);
		curl_setopt($curlSession, CURLOPT_FAILONERROR, true);
		curl_setopt($curlSession, CURLOPT_FOLLOWLOCATION, false);
		curl_setopt($curlSession, CURLOPT_FRESH_CONNECT, true);
		curl_setopt($curlSession, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curlSession, CURLOPT_CONNECTTIMEOUT, $connectTimeout);
		curl_setopt($curlSession, CURLOPT_TIMEOUT, $sessionTimeout);
		curl_setopt($curlSession, CURLOPT_USERAGENT, $userAgent);
		curl_setopt($curlSession, CURLOPT_REFERER, 'https://mysecurevault.ca');
		$output = curl_exec($curlSession);
		$httpCode = curl_getinfo($curlSession, CURLINFO_HTTP_CODE);
		curl_close($curlSession);
		
		if($httpCode == 200) {
			$faviconType = exif_imagetype($faviconReturn['url']);
			if($faviconType == IMAGETYPE_GIF) {
				$faviconReturn['type'] = 'image/gif';
			} elseif($faviconType == IMAGETYPE_JPEG) {
				$faviconReturn['type'] = 'image/jpeg';
			} elseif($faviconType == IMAGETYPE_PNG) {
				$faviconReturn['type'] = 'image/png';
			} elseif($faviconType == IMAGETYPE_ICO) {
				$faviconReturn['type'] = 'image/x-icon';
			} else {
				return false;
			}
			$faviconReturn['image'] = base64_encode($output);
			return $faviconReturn;
		} else {
			// Getting favicon failed :-(
			return false;
		}
	} else {
		return false;
	}
}

function sendFileByEmail($email, $securePrivateKey, $nonce, $type) {
	// $keypair est un array qui contient ['public'] et ['private']
	// $cert est un fichier de certificat pkcs12 à envoyer
	// $pass est une phrase secrète
	// $email est une adresse courriel | MUST HAVE BEEN VALIDATED FIRST!
	global $config;
	global $strings;
	
	if($type == 'certificate') {
		$subject = $strings['284'];
		
		$plaintext = $strings['285'] . $config['baseUrl'] . '/download.php?email=' . urlencode($email) . '&nonce=' . $nonce . '
	' . $strings['292'];
		
		$html = $strings['286'] . '<a href="' . $config['baseUrl'] . '/download.php?email=' . urlencode($email) . '&nonce=' . $nonce . '">' . $config['baseUrl'] . '/download.php</a></li>' . $strings['291'];
	} else {
		$subject = $strings['309'];
		
		$plaintext = $strings['310'] . $config['baseUrl'] . '/download.php?email=' . urlencode($email) . '&nonce=' . $nonce . '
	' . $strings['311'];
		
		$html = $strings['312'] . '<a href="' . $config['baseUrl'] . '/download.php?email=' . urlencode($email) . '&nonce=' . $nonce . '">' . $config['baseUrl'] . '/download.php</a></li>' . $strings['313'];
		
	}
	
	if (sendEmail($email, $subject, $plaintext, $html, $securePrivateKey)) {
		return true;
	} else {
		return false;
	}
}

function sendWelcomeEmail($email, $securePrivateKey, $nonce, $type) {
	// $keypair est un array qui contient ['public'] et ['private']
	// $cert est un fichier de certificat pkcs12 à envoyer
	// $pass est une phrase secrète
	// $email est une adresse courriel | MUST HAVE BEEN VALIDATED FIRST!
	global $config;
	global $strings;
	

	$subject = $strings['284'];
	
	$plaintext = $strings['285'] . $config['baseUrl'] . '/welcome.php?email=' . urlencode($email) . '&nonce=' . $nonce . '
' . $strings['292'];
	
	$html = $strings['286'] . '<a href="' . $config['baseUrl'] . '/welcome.php?email=' . urlencode($email) . '&nonce=' . $nonce . '">' . $config['baseUrl'] . '/welcome.php</a></li>' . $strings['291'];

	
	if (sendEmail($email, $subject, $plaintext, $html, $securePrivateKey)) {
		return true;
	} else {
		return false;
	}
}


function sendEmail($to_email, $subject, $plaintext, $html, $attachment) {
	global $config;
	include_once('Mail.php');
	include_once('Mail/mime.php');
	
	$params['host'] = $config['mailServer'];
	$params['port'] = $config['mailServerPort'];
	$params['auth'] = $config['mailServerAuth'];
	$params['username'] = $config['mailServerUsername'];
	$params['password'] = $config['mailServerPassword'];
	// $params["debug"] = true;
	
	$from_email = $config['mailServerFrom'];
	$sender = $config['mailServerSender'];
	$to_email = $to_email;
	$subject = $subject;
	$text = $plaintext;
	$html = $html;
	$crlf = "\n";
	
	$headers = array();
	$headers['From'] = $sender;
	$headers['Return-Path'] = $from_email;
	$headers['To'] = $to_email;
	$headers['Subject'] = $subject;
	
	  /*      
	echo '<pre>';
	echo var_dump($headers);
	echo '</pre>';
	*/
	
	
	    
	// Creating the Mime message
	
	$mime = new Mail_mime($crlf);
	
	// Setting the body of the email
	$cid="mysecurevaultlogo";
	$mime->addHTMLimage('img/weblogoen.png', 'image/png', '', true, $cid);
	
	$mime->addAttachment($attachment, 'application/octet-stream', 'download.privatekey', false, 'base64');
	
	$mime->setTXTBody($text);
	$mime->setHTMLBody($html);
	
	$body = $mime->get();
	$headers = $mime->headers($headers);
	
	// Sending the email
	
	$mail =& Mail::factory('smtp', $params);
	$mail->send($to_email, $headers, $body);
	
	if (PEAR::isError($mail)) {
	    $return = '<pre>';
	    $return .= $mail->getMessage();
	    $return .= '</pre>';
	    return $return;
	    
	} else {
		return true;
	}
}

function queryFromTor(){
	global $conn;
	$clientIp = $_SERVER['REMOTE_ADDR'];
	$sql = "SELECT list FROM torExitNodes ORDER BY id DESC LIMIT 1";
	$db_rawList = $conn -> query($sql);
	if(mysqli_num_rows($db_rawList) == 1) {
		$db_list = $db_rawList -> fetch_assoc();
		$listArray = json_decode($db_list['list'], true);
		if(in_array($clientIp, $listArray)) {
			return true;
		} else {
			return false;
		}
	} else {
		return false;
	}
}

function generateRandomIdentity() {
	$firstNames = array('Jake', 'Karl', 'Robbie', 'Elmer', 'Josh', 'Victor', 'Julian', 'Nicholas', 'Ambrose', 'Tad', 'Tyron', 'Kevin', 'Paris', 'Gino', 'Broderick', 'Clinton', 'Zack', 'Edward', 'Andrea', 'Royce', 'Vernon', 'August', 'Jospeh', 'Darryl', 'Curtis', 'Carrol', 'Gavin', 'Erin', 'Mckinley', 'Gustavo', 'Chi', 'Clement', 'Johnathon', 'Jae', 'Giovanni', 'Christoper', 'Erwin', 'Edmond', 'Lawrence', 'Miguel', 'Lynn', 'Valentine', 'Henry', 'Moises', 'Alonso', 'Lonnie', 'Cordell', 'Jordan', 'Esteban', 'Noble', 'Idella', 'Audrie', 'Veola', 'Leighann', 'Ione', 'Sherrie', 'Phillis', 'Svetlana', 'Janine', 'Jung', 'Shana', 'Han', 'Dulcie', 'Ashton', 'Jenee', 'Sarina', 'Pa', 'Zella', 'Bethann', 'Lulu', 'Reta', 'Sylvia', 'Arla', 'Jessia', 'Reagan', 'Charlesetta', 'Elene', 'Dimple', 'Aileen', 'Lael', 'Julianne', 'An', 'Kaitlin', 'Sheena', 'Giselle', 'Laraine', 'Bess', 'Shena', 'Jayna', 'Jackeline', 'Lissette', 'Madge', 'Maud', 'Mazie', 'Roseanna', 'Karissa', 'Raelene', 'Celena', 'Janey', 'Isabell');
	$lastNames = array('Hawn', 'Seawright', 'Boyes', 'Ribera', 'Erazo', 'Wiseman', 'Sultan', 'Dimaio', 'Bibb', 'Wilmoth', 'Cheek', 'Hiemstra', 'Canter', 'Baccus', 'Hanberry', 'Brouse', 'Ogletree', 'Efird', 'Bjerke', 'Croyle', 'Noyes', 'Knight', 'Perrino', 'Inman', 'Pulaski', 'Bracewell', 'Burlew', 'Corvin', 'Cort', 'Anselmo', 'Prinz', 'Arguelles', 'Urquhart', 'Koga', 'Brockington', 'Suits', 'Mclachlan', 'Hands', 'Wages', 'Belser', 'Foshee', 'Zarate', 'Fillion', 'Hollon', 'Bunt', 'Steil', 'Boettcher', 'Nealey', 'Hand', 'Outlaw', 'Braddock', 'Purpura', 'Knoll', 'Madonna', 'Taing', 'Tindle', 'Bulluck', 'Hungerford', 'Mazzola', 'Cuff', 'Mitton', 'Schendel', 'Nading', 'Barreiro', 'Bond', 'Foutch', 'Manwaring', 'Prowell', 'Melara', 'Deeds', 'Sporer', 'Atkin', 'Mcguinness', 'Zuniga', 'Jetton', 'Foulk', 'Deegan', 'Crane', 'Niemi', 'Settles', 'Sable', 'Emmett', 'Knebel', 'Lobue', 'Billingsly', 'Scheer', 'Mary', 'Loney', 'Vartanian', 'Arevalo', 'Watson', 'Ladouceur', 'Fritsch', 'Medley', 'Palen', 'Yeatman', 'Mcewen', 'Spieker', 'Huntsberry', 'Higley');
	$domainName = array('anonymail.com', 'unknownmail.com', 'secretmail.com', 'donotmail.com', 'randomail.com', 'donotcontact.com', 'donottrack.com', 'junkmail.com', 'hiddenmail.com', 'nomail.com');
	$provinces = array('British Columbia', 'Alberta', 'Saskatchewan', 'Manitoba', 'Ontario', 'Quebec', 'New Brunswick', 'Nova Scotia', 'Newfoundland', 'Prince Edward Island');
	$states = array('Alabama', 'Alaska', 'Arizona', 'Arkansas', 'California', 'Colorado', 'Connecticut', 'Delaware', 'Florida', 'Georgia', 'Hawaii', 'Idaho', 'Illinois', 'Indiana', 'Iowa', 'Kansas', 'Kentucky', 'Louisiana', 'Maine', 'Maryland', 'Massachusetts', 'Michigan', 'Minnesota', 'Mississippi', 'Missouri', 'Montana', 'Nebraska', 'Nevada', 'New Hampshire', 'New Jersey', 'New Mexico', 'New York', 'North Carolina', 'North Dakota', 'Ohio', 'Oklahoma', 'Oregon', 'Pennsylvania', 'Rhode Island', 'South Carolina', 'South Dakota', 'Tennessee', 'Texas', 'Utah', 'Vermont', 'Virginia', 'Washington', 'West Virginia', 'Wisconsin', 'Wyoming');
	$cities = array('Brandon', 'Medicine Hat', 'Kelowna', 'Jersey', 'Chertsey', 'Quebec', 'Salisbury', 'Pickerton', 'Mechanicville', 'Rawdon', 'Beauport', 'Saint-Laurent', 'St-John', 'St-Boniface', 'Levis', 'Delson', 'Macamic', 'Guelph', 'Timmins', 'Vaughan', 'Thorold', 'Woodstock', 'Oakville', 'Corner Brook', 'Mount Pearl', 'Moncton', 'Miramichi', 'Dieppe', 'Campbellton', 'Winkler', 'Selkirk', 'Steinbach', 'Morden', 'Brandon', 'Vernon', 'Victoria', 'Port Calumet', 'Surrey', 'Port Coquitlam', 'Merritt', 'Greenwood', 'Springfield', 'Kamloops', 'New Westminster', 'Grand Forks', 'Dawson Creek', 'Chilliwack', 'Leduc', 'Red Deer', 'Spruce Grove', 'Beaumont', 'Kenora', 'Kingston', 'Pembroke', 'Sarnia', 'Charlottetown', 'Amqui', 'Tring Jonction', 'Boischatel', 'Brownsburg-Chatham', 'Brossard', 'Boisbriand', 'Disraeli', 'Gracefield', 'Kirkland', 'Laval', 'Mercier', 'Longueuil', 'Montreal', 'Toronto', 'Paspebiac', 'Neuville', 'Portneuf', 'Acton Vale', 'Saguenay', 'Roberval', 'Westmount', 'Estevan', 'Swift Current', 'Yorkton', 'Regina', 'Martensville', 'Lewiston', 'Coeur d\'Alene', 'Billings');
	$countries = array('CA', 'US');
	
	$numFirst = count($firstNames) - 1;
	$numLast = count($lastNames) - 1;
	$numDomains = count($domainName) - 1;
	$numProvinces = count($provinces) - 1;
	$numStates = count($states) - 1;
	$numCities = count($cities) - 1;
	$numCountries = count($countries) - 1;
	
	$firstNameIndex = random_int(0, $numFirst);
	$lastNameIndex = random_int(0, $numLast);
	$domainIndex = random_int(0, $numDomains);
	$cityIndex = random_int(0, $numCities);
	$countryIndex = random_int(0, $numCountries);
	
	$id['country'] = $countries[$countryIndex];
	
	
	if($id['country'] == 'CA') {
		$provinceIndex = random_int(0, $numProvinces);
		$id['province'] = $provinces[$provinceIndex];
	} elseif($id['country'] == 'US') {
		$stateIndex = random_int(0, $numStates);
		$id['province'] = $states[$stateIndex];
	}
	
	$id['name'] = $firstNames[$firstNameIndex] . ' ' . $lastNames[$lastNameIndex];
	$id['email'] = $firstNames[$firstNameIndex] . '.' . $lastNames[$lastNameIndex] . '@' . $domainName[$domainIndex];
	$id['city'] = $cities[$cityIndex];
	
	return $id;
}


//////////////////////////// BUSINESS FUNCTIONS ////////////////////////////

function getBusinessInfo($userId) {
	global $conn;
	
	$sql = "SELECT cipherSuite, iv, entry, tag FROM users WHERE id='$userId' AND businessAccount='1'";
	$db_rawBusinessInfo = $conn -> query($sql);
	
	$db_businessInfo = $db_rawBusinessInfo -> fetch_assoc();
	
	if(mysqli_num_rows($db_rawBusinessInfo) < 1 || is_null($db_businessInfo['entry']) || $db_businessInfo['entry'] == '') {
		$businessInfo = array();
		$businessInfo['status'] = 'uninitialized';
	} else {		
		// There is info in here... decrypt it and turn it into an array!
		$jsonBusinessInfo = decryptDataNextGen($db_businessInfo['iv'], $_SESSION['encryptionKey'], $db_businessInfo['entry'], $db_businessInfo['cipherSuite'], $db_businessInfo['tag']);
		$businessInfo = json_decode($jsonBusinessInfo, true);
		
		$businessInfo['status'] = 'initialized';
	}
	
	return $businessInfo;
}

function getBusinessUserInfo($certId) {
	// This function returns the user's information based on the certificate ID provided
	global $conn;
	
	$sql = "SELECT id, cipherSuite, iv, entry, tag FROM businessUsers WHERE userId='$_SESSION[userId]'";
	$db_rawUsers = $conn -> query($sql);
	while ($db_user = $db_rawUsers -> fetch_assoc()) {
		$jsonUserInfo = decryptDataNextGen($db_user['iv'], $_SESSION['encryptionKey'], $db_user['entry'], $db_user['cipherSuite'], $db_user['tag']);
		$userInfo = json_decode($jsonUserInfo, true);
		
		if(in_array($certId, $userInfo['certs'])) {
			$businessUser = json_decode($jsonUserInfo, true);
			$businessUser['id'] = $db_user['id'];
		}
	}
	return $businessUser;	
}

function getBusinessUserInfoFromId($userId) {
	// This function returns the user's information based on the certificate ID provided
	global $conn;
	$userId = mysqli_real_escape_string($conn, $userId);
	
	$sql = "SELECT id, userId, cipherSuite, iv, entry, tag FROM businessUsers WHERE userId='$_SESSION[userId]' AND id='$userId'";
	$db_rawUsers = $conn -> query($sql);
	if(mysqli_num_rows($db_rawUsers) === 1) {
		while ($db_user = $db_rawUsers -> fetch_assoc()) {
			$jsonUserInfo = decryptDataNextGen($db_user['iv'], $_SESSION['encryptionKey'], $db_user['entry'], $db_user['cipherSuite'], $db_user['tag']);
			$businessUser = json_decode($jsonUserInfo, true);
			$businessUser['id'] = $db_user['id'];
		}
	} else {
		return false;
	}
	return $businessUser;	
}

function setBusinessUserInfo($userId, $userDataArray) {
	global $conn;
	global $config;
	
	$jsonUserInfo = json_encode($userDataArray);
	$encryptedUserInfo = encryptDataNextGen($_SESSION['encryptionKey'], $jsonUserInfo, $config['currentCipherSuite']);
	$iv = $encryptedUserInfo['iv'];
	$entry = $encryptedUserInfo['data'];
	$tag = $encryptedUserInfo['tag'];
	
	$sql = "UPDATE businessUsers SET cipherSuite='$config[currentCipherSuite]', iv='$iv', entry='$entry', tag='$tag' WHERE id='$userId'";
	$conn -> query($sql);
}

function isEnterpriseAdmin($businessUserId) {
	$userGroups = getBusinessUserGroups($businessUserId);
	$businessInfo = getBusinessInfo($_SESSION['userId']);
	if($businessInfo['business']['owner'] == $businessUserId) {
		return true;
	} elseif (in_array($businessInfo['business']['owningGroup'], $userGroups)) {
		return true;
	} else {
		return false;
	}
}

function isBusinessOwner($businessUserId) {
	$businessInfo = getBusinessInfo($_SESSION['userId']);
	if($businessInfo['business']['owner'] == $businessUserId) {
		return true;
	} else {
		return false;
	}
}

function getBusinessUserGroups($businessUserId) {
	// This function returns the database group ID numbers of which a business user account is a member.
	
	global $conn;
	
	$sql = "SELECT id, userId, cipherSuite, iv, entry, tag FROM businessGroups WHERE userId='$_SESSION[userId]'";
	$db_rawBusinessGroups = $conn -> query($sql);
	
	if(mysqli_num_rows($db_rawBusinessGroups) > 0) {
		// There are groups... loop through all of them!
		$groups = array();
		while($db_businessGroups = $db_rawBusinessGroups -> fetch_assoc()) {
			$jsonGroup = decryptDataNextGen($db_businessGroups['iv'], $_SESSION['encryptionKey'], $db_businessGroups['entry'], $db_businessGroups['cipherSuite'], $db_businessGroups['tag']);
			$group = json_decode($jsonGroup, true);
			if(in_array($businessUserId, $group['members'])) {
				$groups[] = $db_businessGroups['id'];
			}
		}
		return $groups;
	} else {
		return false;
	}
}

function getGroupInfo($groupId) {
	global $conn;
	
	mysqli_real_escape_string($conn, $groupId);
	$sql = "SELECT id, userId, cipherSuite, iv, entry, tag FROM businessGroups WHERE id='$groupId' AND userId='$_SESSION[userId]'";
	$db_rawGroup = $conn -> query($sql);
	if(mysqli_num_rows($db_rawGroup) > 0) {
		$db_group = $db_rawGroup -> fetch_assoc();
		
		$jsonGroup = decryptDataNextGen($db_group['iv'], $_SESSION['encryptionKey'], $db_group['entry'], $db_group['cipherSuite'], $db_group['tag']);
		$businessGroup = json_decode($jsonGroup, true);
		$businessGroup['id'] = $groupId;
		
		return $businessGroup;
	} else {
		return false;
	}
}

function getAllBusinessUsers() {
	// This function returns an array with all the users and their certs
	global $conn;
	
	$sql = "SELECT id, cipherSuite, iv, entry, tag FROM businessUsers WHERE userId='$_SESSION[userId]'";
	$db_rawBusinessUsers = $conn -> query($sql);
	$businessUsers = array();
	while($db_businessUsers = $db_rawBusinessUsers -> fetch_assoc()) {
		$jsonUser = decryptDataNextGen($db_businessUsers['iv'], $_SESSION['encryptionKey'], $db_businessUsers['entry'], $db_businessUsers['cipherSuite'], $db_businessUsers['tag']);
		
		$businessUser = json_decode($jsonUser, true);
		$businessUser['id'] = $db_businessUsers['id'];
		$businessUsers[] = $businessUser;
	}
	
	return $businessUsers;
}

function getAllBusinessGroups() {
	global $conn;
	
	$sql = "SELECT id, cipherSuite, iv, entry, tag FROM businessGroups WHERE userId='$_SESSION[userId]'";
	$db_rawBusinessGroups = $conn -> query($sql);
	$businessGroups = array();
	while($db_businessGroups = $db_rawBusinessGroups -> fetch_assoc()) {
		$jsonGroup = decryptDataNextGen($db_businessGroups['iv'], $_SESSION['encryptionKey'], $db_businessGroups['entry'], $db_businessGroups['cipherSuite'], $db_businessGroups['tag']);
		
		$businessGroup = json_decode($jsonGroup, true);
		$businessGroup['id'] = $db_businessGroups['id'];
		$businessGroups[] = $businessGroup;
	}
	
	return $businessGroups;
}

function businessGroupsList($selected) {
	$effectivePermission = getBusinessManagementPermissions();
	$businessInfo = getBusinessInfo($_SESSION['userId']);
	
	$groups = getAllBusinessGroups();
	$htmlString = '<option></option>';
	
	foreach($groups as $group) {
		if($group['id'] == $businessInfo['business']['owningGroup']) {
			if($effectivePermission['business'] == 'rw') {
				if ($selected == $group['id']){ $select = ' selected';}
				
				$htmlString .= '<option value="' . $group['id'] . '"' . $select . '>' . $group['name'] . '</option>';
				unset($select);
			}
		} else {
			if ($selected == $group['id']){ $select = ' selected';}
				
			$htmlString .= '<option value="' . $group['id'] . '"' . $select . '>' . $group['name'] . '</option>';
			unset($select);
		}
	}
	
	return $htmlString;
}

function businessGroupsCheckboxes($userGroups=array(), $forUser='') {
	// $groups is an array of groups IDs!
	// $businessUserId is the requesting user.
	
	$effectivePermission = getBusinessManagementPermissions();
	$businessInfo = getBusinessInfo($_SESSION['userId']);
	$businessUser = getBusinessUserInfo($_SESSION['certId']);
	$groups = getAllBusinessGroups();
	$htmlString = '<div class="w3-row">';
	
	foreach($groups as $group) {
		if($group['id'] == $businessInfo['business']['owningGroup']) {
			if($effectivePermission['business'] == 'rw') {
				// Only an enterprise admin can manage enterprise admins!
				if (in_array($group['id'], $userGroups)){ $checked = ' checked="checked"';}
				if(isBusinessOwner($forUser)) {
					// One cannot remove the business Owner from the enterprise admin group!!!
					$disabled = ' disabled="disabled"';
					$htmlString .='<input type="hidden" name="groups[]" value="' . $group['id'] . '">';
				}
				$htmlString .= '<div class="w3-padding w3-half"> <input class="w3-check" type="checkbox" name="groups[]" value="' . $group['id'] . '"' . $checked . $disabled . '> ' . $group['name'] . '</div>';
				unset($checked);
				unset($disabled);
			}
		} else {
			if (in_array($group['id'], $userGroups)){ $checked = ' checked';}
				
			$htmlString .= '<div class="w3-padding w3-half"> <input class="w3-check" type="checkbox" name="groups[]" value="' . $group['id'] . '"' . $checked . '> ' . $group['name'] . '</div>';
			unset($checked);
		}
	}
	$htmlString .= '</div>';
	
	return $htmlString;
}

function getBusinessUserHistory($userId) {
	// $userId is the numeric ID of a business user.
	global $conn;
	global $config;
	
	$sql = "SELECT id, businessUserId, cipherSuite, iv, entry, tag, checksum FROM businessUsersArchive WHERE businessUserId='$userId' LIMIT 20";
	$db_rawUserHistory = $conn -> query($sql);
	if(mysqli_num_rows($db_rawUserHistory) >= 1) {
		// There is a user history. Loop through all entries and return an array
		$history = array();
		while($db_history = $db_rawUserHistory -> fetch_assoc()) {
			$jsonhistory = decryptDataNextGen($db_history['iv'], $_SESSION['encryptionKey'], $db_history['entry'], $db_history['cipherSuite'], $db_history['tag']);
			$history[] = json_decode($jsonhistory, true);
		}
		return $history;
	} else {
		return false;
	}
}

function updateUserInfo($userId, $newUserInfo) {
	// $userId is the numeric ID of the user being updated.
	// $newUserInfo is an array that contains all the new user's information.
	global $conn;
	global $config;
	
	$currentUserInfo = getBusinessUserInfoFromId($userId);
	$currentUserInfo['editDate'] = date('Y-m-d H:i:s');
	$currentUserInfo['groups'] = getBusinessUserGroups($businessUserId);
	$jsonCurrentUserInfo = json_encode($currentUserInfo);
	
	// Get ID and checksum of last history row
	$sql = "LOCK TABLE businessUsersArchive WRITE";
	$conn -> query($sql);
	
	$sql = "SELECT id, checksum FROM businessUsersArchive WHERE businessUserId='$userId' AND checksum IS NOT NULL ORDER BY id DESC LIMIT 1";
	$db_rawHistoryEntry = $conn -> query($sql);
	if(mysqli_num_rows($db_rawHistoryEntry) > 0) {
		$db_historyEntry = $db_rawHistoryEntry -> fetch_assoc();
		// Not the first entry, add hash from last entry
		$entryHashString = $config['salt'] . $jsonCurrentUserInfo . $db_historyEntry['checksum'];
	} else {
		// This is the first entry! Generate first hash
		$entryHashString = $config['salt'] . $jsonCurrentUserInfo;
	}
	
	$entryHash = hash('sha256', $entryHashString);
	
	// Encrypt log message before insert
	$encryptedEntry = encryptDataNextGen($_SESSION['encryptionKey'], $jsonCurrentUserInfo, $config['currentCipherSuite']);
	$encryptedEntryIv = $encryptedEntry['iv'];
	$encryptedEntryData = $encryptedEntry['data'];
	$encryptedEntryTag = $encryptedEntry['tag'];
	
	$sql = "INSERT INTO businessUsersArchive (businessUserId, cipherSuite, iv, entry, tag, checksum) VALUES ('$userId', '$config[currentCipherSuite]', '$encryptedEntry[iv]', '$encryptedEntry[data]', '$encryptedEntry[tag]', '$entryHash')";
	$conn -> query($sql);

	$sql = "UNLOCK TABLES";
	$conn -> query($sql);
	
	// Update user info...
	$newUserInfo['lastModified'] = date("Y-m-d H:i:s");
	$jsonNewUserInfo = json_encode($newUserInfo);
	
	$encryptedEntry = encryptDataNextGen($_SESSION['encryptionKey'], $jsonNewUserInfo, $config['currentCipherSuite']);
	$encryptedEntryIv = $encryptedEntry['iv'];
	$encryptedEntryData = $encryptedEntry['data'];
	$encryptedEntryTag = $encryptedEntry['tag'];
	
	$sql = "UPDATE businessUsers SET cipherSuite='$config[currentCipherSuite]', iv='$encryptedEntryIv', entry='$encryptedEntryData', tag='$encryptedEntryTag' WHERE id='$userId'";
	$conn -> query($sql);
}

function updateUserGroups($userId, $groups) {
	global $conn;
	global $config;
	
	// $userId is the numeric ID of the user being updated.
	// $groups is an array that contains all the user's groups.
	$businessGroups = getAllBusinessGroups();
	$userInfo = getBusinessUserInfoFromId($userId);
	foreach($businessGroups as $group) {
		if(in_array($group['id'], $groups)) {
			// User should be a member of this group
			// is he yet?
			if(!in_array($userId, $group['members'])) {
				// User is not already a member of this group... add it!
				$group['members'][] = $userId;
				logAction('39', 'User ID: ' . $userId . ', Name: ' . $userInfo['name'] . ', Group ID:' . $group['id'] . ', Group name:' . $group['name']);
				$updated = true;
			}
		} else {
			// User should not be a member of this group
			if(in_array($userId, $group['members'])) {
				// User is a member of this group, but has been removed... remove it!
				$membershipKey = array_search($userId, $group['members']);
				unset($group['members'][$membershipKey]);
				// Reindex array to remove empty elements
				$group['members'] = array_values($group['members']);
				logAction('40', 'User ID: ' . $userId . ', Name: ' . $userInfo['name'] . ', Group ID:' . $group['id'] . ', Group name:' . $group['name']);
				$updated = true;
			}
		}
		if($updated) {
			// Update group in database...
			$jsonGroup = json_encode($group);
			
			$encryptedEntry = encryptDataNextGen($_SESSION['encryptionKey'], $jsonGroup, $config['currentCipherSuite']);
			$encryptedEntryIv = $encryptedEntry['iv'];
			$encryptedEntryData = $encryptedEntry['data'];
			$encryptedEntryTag = $encryptedEntry['tag'];
			
			$sql = "UPDATE businessGroups SET cipherSuite='$config[currentCipherSuite]', iv='$encryptedEntryIv', entry='$encryptedEntryData', tag='$encryptedEntryTag' WHERE id='$group[id]'";
			$conn -> query($sql);
			
			unset($updated);
		}
	}
}

function getBusinessUserCertInfo($certId) {
	global $conn;
	
	$sql = "SELECT id, serial, revoked, ivCertData, encryptedCertData, tagCertData, cipherSuiteCertData FROM certs WHERE id='$certId'";
	$db_rawCert = $conn -> query($sql);
	$db_cert = $db_rawCert -> fetch_assoc();
	
	$jsonCert = decryptDataNextGen($db_cert['ivCertData'], $_SESSION['encryptionKey'], $db_cert['encryptedCertData'], $db_cert['cipherSuiteCertData'], $db_cert['tagCertData']);
	
	$certInfo = json_decode($jsonCert, true);
	$certInfo['id'] = $certId;
	$certInfo['serial'] = $db_cert['serial'];
	$certInfo['revoked'] = $db_cert['revoked'];
	
	$currentDate = date('Y-m-d H:i:s');
	$expirationUnix = strtotime($certInfo['validTo']);
	$nowUnix = strtotime($currentDate);
	$expiresInSeconds = ($expirationUnix - $nowUnix);
	
	$certInfo['daysToExpire'] = round($expiresInSeconds / 86400, 1);
		
	return $certInfo;
}

function setLastLoginDate($certId) {
	global $conn;
	global $config;
	
	$user = getBusinessUserInfo($certId);
	
	$user['lastLogin'] = date('Y-m-d H:i:s');
	$user['lastActivity'] = date('Y-m-d H:i:s');
	
	$jsonUser = json_encode($user);
	// echo $jsonUser;
	$encryptedUser = encryptDataNextGen($_SESSION['encryptionKey'], $jsonUser, $config['currentCipherSuite']);
	$iv = $encryptedUser['iv'];
	$entry = $encryptedUser['data'];
	$tag = $encryptedUser['tag'];
	
	$sql = "UPDATE businessUsers SET cipherSuite='$config[currentCipherSuite]', iv='$iv', entry='$entry', tag='$tag' WHERE id='$user[id]'";
	$conn -> query($sql);
	
}

function getBusinessManagementPermissions() {
	$businessInfo = getBusinessInfo($_SESSION['userId']);
	$businessUser = getBusinessUserInfo($_SESSION['certId']);
	$businessUserGroups = getBusinessUserGroups($businessUser['id']);
	
	if($businessInfo['business']['owner'] == $businessUser['id'])   {
		
		// This is the business owner... welcome in my lord...
		// Grab permission on the object
		$effectivePermission['business'] = $businessInfo['business']['acl']['u'];
	} elseif(in_array($businessInfo['business']['owningGroup'], $businessUserGroups)) {
		
		// User is in the owning group
		$effectivePermission['business'] = $businessInfo['business']['acl']['g'];
	} else {
		
		// Get permission for "other"
		$effectivePermission['business'] = $businessInfo['business']['acl']['o'];
	}
	
	if($businessInfo['billing']['owner'] == $businessUser['id'] || in_array($businessInfo['business']['owningGroup'], $businessUserGroups))   {
		// This is the billing owner or enterprise admin... welcome in my lord...
		// Grab permission on the object
		$effectivePermission['billing'] = $businessInfo['billing']['acl']['u'];
	} elseif(in_array($businessInfo['billing']['owningGroup'], $businessUserGroups)) {
		// User is in the owning group
		$effectivePermission['billing'] = $businessInfo['billing']['acl']['g'];
	} else {
		// Get permission for "other"
		$effectivePermission['billing'] = $businessInfo['billing']['acl']['o'];
	}
	
	if($businessInfo['users']['owner'] == $businessUser['id'] || in_array($businessInfo['business']['owningGroup'], $businessUserGroups))   {
		// This is the users owner or enterprise admin... welcome in my lord...
		// Grab permission on the object
		$effectivePermission['users'] = $businessInfo['users']['acl']['u'];
	} elseif(in_array($businessInfo['users']['owningGroup'], $businessUserGroups)) {
		// User is in the owning group
		$effectivePermission['users'] = $businessInfo['users']['acl']['g'];
	} else {
		// Get permission for "other"
		$effectivePermission['users'] = $businessInfo['users']['acl']['o'];
	}
	
	if($businessInfo['logging']['owner'] == $businessUser['id'] || in_array($businessInfo['business']['owningGroup'], $businessUserGroups))   {
		// This is the logging owner or enterprise admin... welcome in my lord...
		// Grab permission on the object
		$effectivePermission['logging'] = $businessInfo['logging']['acl']['u'];
	} elseif(in_array($businessInfo['logging']['owningGroup'], $businessUserGroups)) {
		// User is in the owning group
		$effectivePermission['logging'] = $businessInfo['logging']['acl']['g'];
	} else {
		// Get permission for "other"
		$effectivePermission['logging'] = $businessInfo['logging']['acl']['o'];
	}
	
	return $effectivePermission;
}

function logAction($messageId, $customInformation=''){
	global $config;
	global $conn;
	date_default_timezone_set('UTC');
	$originalMessageId = $messageId;
	/*
		Facility values used in the app:
			1 - User level messages
			10 - PRIVATE Security / authorization message (login, logout, failed login, attempt unauthorized action)
			13 - Audit / action logging
			14 - Alert
		
		Severity values:
			0 - Emergency: system is unusable
            1 - Alert: action must be taken immediately
            2 - Critical: critical conditions
            3 - Error: error conditions
            4 - Warning: warning conditions
            5 - Notice: normal but significant condition
            6 - Informational: informational messages
            7 - Debug: debug-level messages
	*/
	
	// Get message from database
	
	$sql = "SELECT id, facility, severity, message FROM businessLogMessages WHERE id='$messageId'";
	$db_rawMessage = $conn -> query($sql);
	if(mysqli_num_rows($db_rawMessage) == 1) {
		// Message ID is good. extract it.
		$db_message = $db_rawMessage -> fetch_assoc();
		$facility = $db_message["facility"];
		$severity = $db_message["severity"];
		$messageContent = $db_message["message"];
		
	} else {
		
		// This message does not exist. Log an application error (Facility 5, severity 3).
		$sql = "SELECT id, facility, severity, message FROM businessLogMessages WHERE id='1'";
		$db_rawMessage = $conn -> query($sql);
		$db_message = $db_rawMessage -> fetch_assoc();
		$messageId = '1';
		$facility = $db_message["facility"];
		$severity = $db_message["severity"];
		$messageContent = $db_message["message"];
		
	}
	
	$priValue = ($facility * 8) + $severity;
	$version = '1';
	$timestamp = date("Y-m-d\\TH:i:s") . '.00Z';
	
	$businessUserInfo = getBusinessUserInfo($_SESSION['certId']);
	
	$messageArray['clientIpAddress'] = $_SERVER['REMOTE_ADDR'];
	$messageArray['page'] = $_SERVER['REQUEST_URI'];
	$messageArray['businessId'] = $_SESSION['userId'];
	$messageArray['businessUserId'] = $businessUserInfo['id'];
	$messageArray['businessUserName'] = $businessUserInfo['name'];
	$messageArray['originalMessageId'] = $originalMessageId;
	$messageArray['actualMessageId'] = $messageId;
	$messageArray['messageContent'] = $messageContent;
	$messageArray['customInformation'] = $customInformation;
	
	$message = json_encode($messageArray);
	
	if (strlen($message) > $config['syslogMaxMessageLength']) {
		// Syslog message is TOO LONG! Change message information...
		$sql = "SELECT id, facility, severity, message FROM businessLogMessages WHERE id='2'";
		$db_rawMessage = $conn -> query($sql);
		$db_message = $db_rawMessage -> fetch_assoc();
		$messageId = '2';
		$facility = $db_message["facility"];
		$severity = $db_message["severity"];
		$messageArray['originalMessageExcerpt'] = substr($messageContent, 0, $config['syslogExcerptLength']);
		$messageContent = $db_message["message"];
		$messageArray['actualMessageId'] = '2';
		$messageArray['messageContent'] = $messageContent;
		
		$message = json_encode($messageArray);
	}
	
	$syslogMessage = '<' . $priValue . '>' . $version . ' ' . $timestamp . ' ' . $config['baseUrl'] . ' MySecureVault - ID' . $messageId . ' - BOM' . $message;
	
	// Get ID and checksum of last log row
	$sql = "LOCK TABLE businessSyslog WRITE";
	$conn -> query($sql);
	
	$sql = "SELECT id, checksum FROM businessSyslog WHERE userId='$_SESSION[userId]' AND checksum IS NOT NULL ORDER BY id DESC LIMIT 1";
	$db_rawSyslogEntry = $conn -> query($sql);
	if(mysqli_num_rows($db_rawSyslogEntry) > 0) {
		$db_syslogEntry = $db_rawSyslogEntry -> fetch_assoc();
		// Not the first entry, add hash from last entry
		$entryHashString = $config['salt'] . $syslogMessage . $db_syslogEntry['checksum'];
	} else {
		// This is the first entry! Generate first hash
		$entryHashString = $config['salt'] . $syslogMessage;
	}
	
	$entryHash = hash('sha256', $entryHashString);
	
	// Encrypt log message before insert
	$encryptedEntry = encryptDataNextGen($_SESSION['encryptionKey'], $syslogMessage, $config['currentCipherSuite']);
	$encryptedEntryIv = $encryptedEntry['iv'];
	$encryptedEntryData = $encryptedEntry['data'];
	$encryptedEntryTag = $encryptedEntry['tag'];
	
	$sql = "INSERT INTO businessSyslog (userId, cipherSuite, iv, entry, tag, checksum) VALUES ('$_SESSION[userId]', '$config[currentCipherSuite]', '$encryptedEntry[iv]', '$encryptedEntry[data]', '$encryptedEntry[tag]', '$entryHash')";
	$conn -> query($sql);

	$sql = "UNLOCK TABLES";
	$conn -> query($sql);
}



/// ****************** DEBUG CODE **********************
// FOR SQL QUERIES
/* if ($conn->query($sql) === true) {
	echo 'OK';
} else {
	echo $conn->error;
}*/
































?>