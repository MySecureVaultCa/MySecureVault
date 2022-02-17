<?php

//UNCOMMENT FOR DEBUG PURPOSES
/*
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
*/

	include "functions.php";
	$parentPage = 'download.php';
	$currentPage = htmlspecialchars($_SERVER["PHP_SELF"]);
	
	
	if (initiateSession("msvDownload")) {
		
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
		$pageTitle = $strings['287'];
		$pageDescription = '';
		$pageKeyworkds = '';
		$pageIndex = 'noindex';
		$pageFollow = 'nofollow';
		
		if($_GET["reload"] == "no" && $_GET["control"] == "download") {
			// Do nothing...
		} else {
			if (!secureForm($_POST["formUid"])) {
				$backToForm = true;
				$alert_message = $strings['351'];
				$loadContent = true;
			}
		}
		
		if($_SERVER["REQUEST_METHOD"] == "GET" && $_GET["email"] != "" && strlen($_GET["nonce"]) == 64) {
			if($_SESSION['validInitialQuery'] == true){
				// There was another download session going. Unset everything before going forth...
				foreach($_SESSION as $key => $value){
					unset($_SESSION[$key]);
				}
			}
			
			// Validate email address
			$_SESSION['validInitialQuery'] = true;
			$recipientEmail = urldecode($_GET["email"]);
			$nonce = $_GET["nonce"];
			if(filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
				$secureHashString = $recipientEmail . $nonce;
				$secureHash = hash('sha256', $secureHashString);
				
				// Check if we can find the database record for this hash...
				$sql = "SELECT id, timestamp, access FROM downloadLinks WHERE secureHash='$secureHash'";
				$db_rawDownload = $conn->query($sql);
				if(mysqli_num_rows($db_rawDownload) == 1) {
					$loadContent = true;
					$db_download = $db_rawDownload->fetch_assoc();
					if($db_download['access'] == '' || is_null($db_download['access'])){ $accessTimes = 0; } else { $accessTimes = $db_download['access']; }
					$accessTimes = $accessTimes + 1;
					$_SESSION['downloadLinkId'] = $db_download['id'];
					$sql = "UPDATE downloadLinks SET access='$accessTimes' WHERE id='$_SESSION[downloadLinkId]'";
					$conn -> query($sql);
					
				} else {
					// Does not match any known record... Nothing to do here.
					$loadContent = false;
					header("HTTP/1.1 301 Moved Permanently");
					header("Location: index.php");
				}
			} else {
				// invalid email address. Nothing to do here.
				$loadContent = false;
				header("HTTP/1.1 301 Moved Permanently");
				header("Location: index.php");
			}
		} elseif ($_SERVER["REQUEST_METHOD"] == "POST" && $_POST["formAction"] == "prepareDownload" && $_SESSION['validInitialQuery'] == true && ctype_digit($_SESSION['downloadLinkId'])) {
			$loadContent = true;
			if($_FILES['privateKey']['tmp_name'] == '' || $_POST["passphrase"] == "") {
				$backToForm = true;
				$keyError = $strings['303'];
			} else {
				$encryptedPrivateKey = file_get_contents($_FILES['privateKey']['tmp_name']);
				$keyPass = $_POST["passphrase"];
				if($privateKeyResource = openssl_pkey_get_private($encryptedPrivateKey, $keyPass)) {
					openssl_pkey_export($privateKeyResource, $privateKey);
					$sql = "SELECT id, downloadEncryptionKey, downloadIv, downloadData, downloadTag, downloadCipherSuite, timestamp FROM downloadLinks WHERE id='$_SESSION[downloadLinkId]'";
					$db_rawDownload = $conn -> query($sql);
					$db_download = $db_rawDownload -> fetch_assoc();
					if ($rawEncryptionKey = decrypt($privateKey, $db_download['downloadEncryptionKey'], $config['currentPadding'])) {
						$_SESSION["ephemeralEncryptionKey"] = base64_encode($rawEncryptionKey);
						
						$decryptedDownload = decryptDataNextGen($db_download['downloadIv'], $_SESSION['ephemeralEncryptionKey'], $db_download['downloadData'], $db_download['downloadCipherSuite'], $db_download['downloadTag']);
						$payload = json_decode($decryptedDownload, true);
						$_SESSION['payload'] = $payload;
					} else {
						$backToForm = true;
						$keyError = $strings['303'];
					}
				} else {
					$backToForm = true;
					$keyError = $strings['303'];
				}
			}
		} elseif ($_SERVER["REQUEST_METHOD"] == "GET" && $_GET["control"] == "download" && $_SESSION['payload'] != '' && $_SESSION['validInitialQuery'] == true) {
			$sql = "SELECT id, downloads FROM downloadLinks WHERE id='$_SESSION[downloadLinkId]'";
			$db_rawLinkInfo = $conn->query($sql);
			if(mysqli_num_rows($db_rawLinkInfo) == 1) {
				$db_linkInfo = $db_rawLinkInfo -> fetch_assoc();
				if($db_linkInfo['downloads'] == '' || is_null($db_linkInfo['downloads'])) { $downloadTimes = 0;} else { $downloadTimes = $db_linkInfo['downloads'];}
				$downloadTimes = $downloadTimes + 1;
				$sql = "UPDATE downloadLinks SET downloads='$downloadTimes' WHERE id='$_SESSION[downloadLinkId]'";
				$conn -> query($sql);
				
				if ($_SESSION['payload']['type'] == 'certificate') {
				
					$filename = preg_replace('/[^A-Za-z0-9_\-]/', '_', htmlspecialchars_decode(utf8_decode($_SESSION['payload']['validFrom'] . ' - ' . $_SESSION['payload']['fullName']), ENT_QUOTES));
					$fileContent = base64_decode(utf8_decode($_SESSION['payload']['file']));
					$loadContent = false;
					
					header("Content-type: application/octet-stream");
					header("Content-Disposition: attachment; filename=$filename.certificate");
					echo $fileContent;
					
				} elseif($_SESSION['payload']['type'] == 'password' || $_SESSION['payload']['type'] == 'note') {
					$filename = preg_replace('/[^A-Za-z0-9_\-]/', '_', htmlspecialchars_decode(utf8_decode($_SESSION['payload']['name']), ENT_QUOTES));
					$fileContent = htmlspecialchars_decode(base64_decode(utf8_decode($_SESSION['payload']['file'])), ENT_QUOTES);
					$loadContent = false;
					
					header("Content-type: text/plain");
					header("Content-Disposition: attachment; filename=$filename.txt");
					echo $fileContent;
					
				} elseif($_SESSION['payload']['type'] == 'file') {
					$filename = utf8_decode($_SESSION['payload']['fileName']);
					$fileType = $_SESSION['payload']['fileType'];
					$fileContent = base64_decode(utf8_decode($_SESSION['payload']['file']));
					$loadContent = false;
					
					header("Content-type: $fileType");
					header("Content-Disposition: attachment; filename=$filename");
					echo $fileContent;
					
				}
			} else {
				// Download link may have been deleted since... cancel download and show notification!
				$message = $strings['345'];
				$loadContent = true;
			}
			
			
		} elseif($_SERVER["REQUEST_METHOD"] == "GET" && $_GET["control"] == "clearDownload" && $_SESSION['validInitialQuery'] == true) {
			$sql = "DELETE FROM downloadLinks WHERE id='$_SESSION[downloadLinkId]'";
			$conn -> query($sql);
			
			unset($_SESSION['payload']);
			$loadContent = false;
			header("HTTP/1.1 301 Moved Permanently");
			header("Location: index.php");
		} elseif ($_SESSION['validInitialQuery'] == true) {
			$loadContent = true;
		} else {
			// Nothing to do here...
			
			$loadContent = false;
			header("HTTP/1.1 301 Moved Permanently");
			header("Location: index.php");
		}
	} else {
		
		// invalid session. Redirect to home page.
		$loadContent = false;
		header("HTTP/1.1 301 Moved Permanently");
		header("Location: index.php");
	}

if ($loadContent == true) { ?>

<!DOCTYPE html>
<html lang="<?php echo $_SESSION['language']; ?>">
	<head>
		<?php include 'head.php'; ?>
		
		<script type="text/javascript">
 		function showhide(id) {
	    	var e = document.getElementById(id);
	    	
	    	if (e.style.display == 'block') {
		    	e.style.display = 'none';
		    } else {
			    e.style.display = 'block';
			}
    	
    	// e.style.display = (e.style.display == 'block') ? 'none' : 'block';
 		}
		
		function showhidePlus(div, icon) {
			showhide(div);
			var e = document.getElementById(div);
	    	
	    	if (e.style.display == 'block') {
		    	document.getElementById(icon).className = 'fa fa-minus-square';
		    } else if (e.style.display == 'none') {
			    document.getElementById(icon).className = 'fa fa-plus-square';
			}
		}
		
		
		</script>
		
	</head>
	<body>
		<?php include 'header.php'; ?>
		<div class="w3-row">
			<div class="w3-third">
				<?php 
					if(isset($message)) {
						echo '<div class="w3-card-4 w3-margin" id="notificationArea" style="display:block;">
							<h3 class="w3-indigo w3-center">Notification<a style="padding:6px;" class="w3-right" href="javascript:showhide(\'notificationArea\')"><i class="fa fa-times-circle"></i></a></h3>
							<div class="w3-center w3-padding" style="padding-bottom: 20px!important;">
								<span class="w3-large w3-text-indigo">' . $message . '</span>
							</div>
						</div>';
					} else {
						echo '<div class="w3-hide-large">&nbsp;</div>';
					}
					include 'leftMenu.php';
				?>
			</div>
			<div class="w3-third">
			<?php if($_SESSION['payload'] == '') { ?>
				<div class="w3-card-4 w3-margin">
					<div class="w3-container w3-blue w3-center">
						<h3><?php echo $strings['287'] ?></h3>
					</div>
					<form class="w3-container" method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" enctype="multipart/form-data">
						<?php
							echo '<input type="hidden" name="formUid" value="' . $_SESSION["secureFormCode"] . '">';
							echo '<input type="hidden" name="formAction" value="prepareDownload">';
						?>
						<div class="w3-padding-16">
							<label><?php echo $strings['288']; ?></label>
							<input class="w3-input" type="file" name="privateKey">
						</div>
						<div class="w3-padding-16">
							<label><?php echo $strings['290']; ?></label>
							<input class="w3-input" type="password" name="passphrase">
						</div>
						<?php if (isset($keyError)) { echo '<div class="w3-text-red">' . $keyError . '</div>';} ?>
						<div class="w3-padding-16 w3-center">
							<input class="w3-btn w3-blue" type="submit" value="<?php echo $strings['289']; ?>" name="submit">
						</div>
					</form>
				</div>
			<?php } else { ?>
				<div class="w3-card-4 w3-margin">
					<div class="w3-container w3-blue w3-center">
						<h3><?php echo $strings['287'] ?></h3>
					</div>
					<div class="w3-container">
						<h4 class="w3-center"><?php echo $strings['305'] ?><br><span class="w3-medium">(<a href="javascript:showhide('downloadDetail')"><?php echo $strings['306'] ?></a>)</span></h4>
						<div id="downloadDetail" class="w3-padding" style="display:none;">
							<ul class="w3-ul">
								<?php
								// Lines displayed depend on type
								$fileSize = strlen(base64_decode(utf8_decode($_SESSION['payload']['file'])));
								if($fileSize < 1024) {
									$fileSizeUnit = $strings['199'];
								} elseif($fileSize < 1048576) {
									$fileSizeUnit = $strings['200'];
									$fileSize = $fileSize / 1024;
									$fileSize = round($fileSize, 2);
								} else {
									$fileSizeUnit = $strings['201'];
									$fileSize = $fileSize / 1024 / 1024;
									$fileSize = round($fileSize, 2);
								}
								
								if($_SESSION['payload']['type'] == 'certificate') {
									echo '<li>' . $strings['302'] . ': <i class="fa fa-certificate"></i> ' . $strings['233'] . '</li>';
									echo '<li>' . $strings['301'] . ': ' . utf8_decode($_SESSION['payload']['fullName']) . ' (' . utf8_decode($_SESSION['payload']['emailAddress']) . ')</li>';
									echo '<li>' . $strings['79'] . ': ' . utf8_decode($_SESSION['payload']['serial']) . '</li>';
									echo '<li>' . $strings['197'] . ': ' . $fileSize . $fileSizeUnit . '</li>';
									
								} elseif($_SESSION['payload']['type'] == 'password') {
									echo '<li class="wrapLongText">' . $strings['302'] . ': ***' . $strings['41'] . '***</li>';
									echo '<li>' . $strings['316'] . ': text/plain</li>';
									echo '<li class="wrapLongText">';
									echo $strings['39'] . ': ';
									if($_SESSION['payload']['favicon'] != '') {
										echo '<img class="favicon" src="data:' . $_SESSION['payload']['faviconType'] . ';base64, ' . $_SESSION['payload']['favicon'] . '" /> ';
									}
									echo $_SESSION['payload']['name'] . '</li>';
									echo '<li>' . $strings['197'] . ': ' . $fileSize . $fileSizeUnit . '</li>';
									
								} elseif($_SESSION['payload']['type'] == 'note') {
									echo '<li class="wrapLongText">' . $strings['302'] . ': <i class="fa fa-pencil-square-o"></i> ' . $strings['299'] . '</li>';
									echo '<li>' . $strings['147'] . ': ' . $_SESSION['payload']['name'] . '</li>';
									echo '<li>' . $strings['316'] . ': text/plain</li>';
									echo '<li>' . $strings['197'] . ': ' . $fileSize . $fileSizeUnit . '</li>';
								} elseif($_SESSION['payload']['type'] == 'file') {
									echo '<li class="wrapLongText">' . $strings['302'] . ': <i class="fa fa-file-o"></i> ' . $strings['300'] . '</li>';
									echo '<li>' . $strings['182'] . ': ' . $_SESSION['payload']['description'] . '</li>';
									echo '<li>' . $strings['196'] . ': ' . $_SESSION['payload']['fileName'] . '</li>';
									echo '<li>' . $strings['316'] . ': ' . $_SESSION['payload']['fileType'] . '</li>';
									echo '<li>' . $strings['197'] . ': ' . $fileSize . $fileSizeUnit . '</li>';
								}
								
								
								?>
							</ul>
							
						</div>
						<div class="w3-center">
							<a class="w3-btn w3-blue w3-margin" href="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>?control=download&reload=no"><i class="fa fa-download"></i> <?php echo $strings['83']; ?></a>
						</div>
					</div>
				</div>
				
				<div class="w3-padding w3-center">
					<?php echo $strings['314']; ?><br>
					<a class="w3-btn w3-red w3-margin" href="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>?control=clearDownload"><i class="fa fa-times"></i> <?php echo $strings['315']; ?></a>
				</div>
			<?php } ?>
			</div>
			<div class="w3-third">
				<h3 class="w3-center"><?php echo $strings['317']; ?></h3>
				<ul class="w3-ul">
					<li><?php echo $strings['318']; ?></li>
					<li><?php echo $strings['319']; ?></li>
					<li><?php echo $strings['320']; ?></li>
					<li><?php echo $strings['321']; ?></li>
					<li><?php echo $strings['322']; ?></li>
					<li><?php echo $strings['323']; ?></li>
					<li><?php echo $strings['324']; ?></li>
				</ul>
			</div>
		</div>
		<?php include 'footer.php'; ?>
	</body>
</html>

<? } ?>