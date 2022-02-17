<?php
// These are scheduled tasks to get informations and cleanup old shit.

include "functions.php";

function getTorExitNodes(){
	if(databaseConnection()) {
		global $conn;
		$url = 'https://check.torproject.org/torbulkexitlist';
		$connectTimeout = 3;
		$sessionTimeout = 5;
		$maxRedir = 5;
		$userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:84.0) Gecko/20100101 Firefox/84.0';
		
		$curlSession = curl_init($url);
		if($curlSession !== false) {
			curl_setopt($curlSession, CURLOPT_FAILONERROR, true);
			curl_setopt($curlSession, CURLOPT_FOLLOWLOCATION, true);
			curl_setopt($curlSession, CURLOPT_MAXREDIRS , $maxRedir);
			curl_setopt($curlSession, CURLOPT_FRESH_CONNECT, true);
			curl_setopt($curlSession, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curlSession, CURLOPT_CONNECTTIMEOUT, $connectTimeout);
			curl_setopt($curlSession, CURLOPT_TIMEOUT, $sessionTimeout);
			curl_setopt($curlSession, CURLOPT_USERAGENT, $userAgent);
			curl_setopt($curlSession, CURLOPT_AUTOREFERER, true);
			curl_setopt($curlSession, CURLOPT_REFERER, 'https://mysecurevault.ca');
			$output = curl_exec($curlSession);
			$httpCode = curl_getinfo($curlSession, CURLINFO_HTTP_CODE);
			curl_close($curlSession);
			$listSha256 = hash('sha256', $output);
			
			if($httpCode == 200 && $output != '') {
				$sql = "SELECT id, timestamp, sha256, list FROM torExitNodes ORDER BY id DESC LIMIT 1";
				$db_rawList = $conn -> query($sql);
				if(mysqli_num_rows($db_rawList) == 1) {
					$db_list = $db_rawList -> fetch_assoc();
					if($db_list['sha256'] != $listSha256) {
						$proceedWithInsert = true;
					} else {
						return false;
					}					
				} else {
					$proceedWithInsert = true;
				}
				if($proceedWithInsert == true) {
					$ips = explode("\n", utf8_encode($output));
					$jsonIps = json_encode($ips);
					$sql = "INSERT INTO torExitNodes (sha256, list) VALUES ('$listSha256', '$jsonIps')";
					if($conn -> query($sql)) {
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

function staleAccountsCleanup() {
	global $conn;
	$currentDate = date('Y-m-d H:i:s');
	$nowUnixTime = strtotime($currentDate);
	
	if(databaseConnection()) {
		
		// First, check accounts that have never logged in...
		$sql = "SELECT id, registrationTime, lastAccess FROM users WHERE lastAccess IS NULL";
		$db_rawUsers = $conn -> query($sql);
		if(mysqli_num_rows($db_rawUsers) > 0) {
			// There are users who never logged in... loop through each of them...
			while($db_user = $db_rawUsers -> fetch_assoc()) {
				$registrationUnixTime = strtotime($db_user['registrationTime']);
				$difference = $nowUnixTime - $registrationUnixTime;
				if($difference > 604800) {
					// Account never logged in and is older than 1 week. Probably just a trial turned bad. Delete it.
					$sql = "DELETE FROM users WHERE id='$db_user[id]'";
					$conn -> query($sql);
				}
			}
		}
		
		// Check accounts that have not logged in for more than 1 year... they can no longer login...
		$sql = "SELECT id, registrationTime, lastAccess FROM users WHERE lastAccess IS NOT NULL";
		$db_rawUsers = $conn -> query($sql);
		if(mysqli_num_rows($db_rawUsers) > 0) {
			while($db_user = $db_rawUsers -> fetch_assoc()) {
				$lastAccessUnixTime = strtotime($db_user['lastAccess']);
				$difference = $nowUnixTime - $lastAccessUnixTime;
				if($difference > 32400000) {
					// Account has not logged in in the pas 375 days. Can no longer login, for sure.
					$sql = "DELETE FROM users WHERE id='$db_user[id]'";
					$conn -> query($sql);
				}
			}
		}
		
	} else {
		return false;
	}
}

function staleDownloadLinksCleanup() {
	global $conn;
	$currentDate = date('Y-m-d H:i:s');
	$nowUnixTime = strtotime($currentDate);
	
	if(databaseConnection()) {
		$sql = "SELECT id, timestamp FROM downloadLinks";
		$db_rawLinks = $conn -> query($sql);
		if(mysqli_num_rows($db_rawLinks) > 0) {
			while($db_link = $db_rawLinks -> fetch_assoc()) {
				$linkTimestampUnixTime = strtotime($db_link['timestamp']);
				$difference = $nowUnixTime - $linkTimestampUnixTime;
				if($difference > 86400) {
					$sql = "DELETE FROM downloadLinks WHERE id='$db_link[id]'";
					$conn -> query($sql);
				}
			}
		}
	} else {
		return false;
	}	
}


// THESE ARE THE AUTOMATED JOBS...
getTorExitNodes();
staleAccountsCleanup();
staleDownloadLinksCleanup();


?>