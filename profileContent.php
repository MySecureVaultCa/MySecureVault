<?php 

if(isset($_SESSION['message'])) {
	$message = $_SESSION['message'];
	unset($_SESSION['message']);
}



//UNCOMMENT FOR DEBUG PURPOSES
/*
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
*/

if(!isset($loadContent) || $loadContent === false) {
	header("HTTP/1.1 301 Moved Permanently");
	header("Location: $parentPage");
}

if(!isset($_SESSION['language'])) { $_SESSION['language'] = 'en'; }

// Get all entries and load them to the right array, depending on the entry type...
$sql = "SELECT id, cipherSuite, iv, entry, tag FROM entries WHERE userId='$_SESSION[userId]'";
$db_rawEntries = $conn->query($sql);

// Initialize all arrays
$passwordsArray = array();
$notesArray = array();
$filesArray = array();

$userDataSize = 0;

while ($entry = $db_rawEntries->fetch_assoc()) {
	// Decrypt entry first
	
	$cipherSuite = $entry['cipherSuite'];
	$iv = $entry['iv'];
	$tag = $entry['tag'];
	$encryptedEntry = $entry['entry'];
	$userDataSize = $userDataSize + strlen($encryptedEntry);
	$jsonEntry = decryptDataNextGen($iv, $_SESSION['encryptionKey'], $encryptedEntry, $cipherSuite, $tag);
	

	// Turn JSON to Array then extract data from array
	$entryArray = json_decode($jsonEntry, true);
	
	$id = $entry['id'];
	
	if($entryArray['type'] == 'password') {
		$entryName = utf8_decode($entryArray['name']);
		$entryUsername = utf8_decode($entryArray['username']);
		$entryPassword = utf8_decode($entryArray['password']);
		$entryUrl = utf8_decode($entryArray['url']);
		$entryFavicon = utf8_decode($entryArray['favicon']);
		$entryFaviconType = utf8_decode($entryArray['faviconType']);
		$entryComment = utf8_decode($entryArray['comment']);
		$entryTimestamp = utf8_decode($entryArray['timestamp']);
		$entryLastModified = utf8_decode($entryArray['lastModified']);
		$orderString = $entryName.$id;
		
		$row = array('id' => $id, 'name' => $entryName, 'username' => $entryUsername, 'password' => $entryPassword, 'url' => $entryUrl, 'favicon' => $entryFavicon, 'faviconType'=> $entryFaviconType, 'comment' => $entryComment, 'timestamp' => $entryTimestamp, 'lastModified' => $entryLastModified);
		
		$passwordsArray[$orderString] = $row;
		// if($isAdmin) { echo '<pre>'; var_dump($passwordsArray); echo '</pre>'; }
	} elseif($entryArray['type'] == 'note') {
		$entryName = utf8_decode($entryArray['name']);
		$entryContent = utf8_decode($entryArray['content']);
		$entryTimestamp = utf8_decode($entryArray['timestamp']);
		$entryLastModified = utf8_decode($entryArray['lastModified']);
		$orderString = $entryName.$id;
		
		$row = array('id' => $id, 'name' => $entryName, 'content' => $entryContent, 'timestamp' => $entryTimestamp, 'lastModified' => $entryLastModified);
		
		$notesArray[$orderString] = $row;
		
	} elseif($entryArray['type'] == 'file') {
		$entryTimestamp = utf8_decode($entryArray['timestamp']);
		$entryLastModified = utf8_decode($entryArray['lastModified']);
		$entryDescription = utf8_decode($entryArray['description']);
		$entryFilename = utf8_decode($entryArray['fileName']);
		$entryFileType = utf8_decode($entryArray['fileType']);
		$entryFileSize = utf8_decode($entryArray['fileSize']);
		$entryFileMd5 = utf8_decode($entryArray['fileMd5']);
		$entryFileSha1 = utf8_decode($entryArray['fileSha1']);
		$entryFileSha256 = utf8_decode($entryArray['fileSha256']);
		$entryStoredFileSize = utf8_decode($entryArray['storedFileSize']);
		
		// Add file size to user data size...
		$userDataSize = $userDataSize + $entryStoredFileSize;
		
		// Get fileId for this file entry
		$sql = "SELECT id FROM files WHERE entryId='$id'";
		$db_rawFile = $conn->query($sql);
		$db_file = $db_rawFile -> fetch_assoc();
		$fileId = $db_file['id'];
		
		$entryFileId = utf8_decode($entryArray['fileId']); // The ID of the file entry in the "files" table
		$orderString = $entryDescription.$id;
		
		$row = array('id' => $id, 'fileName' => $entryFilename, 'description' => $entryDescription, 'fileId' => $fileId, 'fileType' => $entryFileType, 'fileSize' => $entryFileSize, 'storedFileSize' => $entryStoredFileSize, 'fileMd5' => $entryFileMd5, 'fileSha1' => $entryFileSha1, 'fileSha256' => $entryFileSha256, 'timestamp' => $entryTimestamp, 'lastModified' => $entryLastModified);
		
		$filesArray[$orderString] = $row;
		
	} else {
		$entryName = utf8_decode($entryArray['name']);
		$entryUsername = utf8_decode($entryArray['username']);
		$entryPassword = utf8_decode($entryArray['password']);
		$entryComment = utf8_decode($entryArray['comment']);
		$orderString = $entryName.$id;
		
		$row = array('id' => $id, 'name' => $entryName, 'username' => $entryUsername, 'password' => $entryPassword, 'comment' => $entryComment);
		
		$passwordsArray[$orderString] = $row;
	}
}


if($_SESSION['updateCipherSuites'] === true) {
	$cipherSuiteMessage = '<span class="w3-text-orange">' . $strings['216'] . '</span><br><a class="w3-btn w3-indigo w3-margin" href="' . $_SERVER['PHP_SELF'] . '?control=updateDataCipher"><i class="fa fa-refresh"></i> ' . $strings['218'] . '</a>';
	if(isset($message)) {
		$message .= '<br><br>' . $cipherSuiteMessage;
	} else {
		$message = $cipherSuiteMessage;
	}
}

if($_SESSION['updateEncryptionKey'] === true) {
	$encryptionKeyMessage = '<span class="w3-text-orange">' . $strings['242'] . '</span><br><a class="w3-btn w3-indigo w3-margin" href="' . $_SERVER['PHP_SELF'] . '?control=generateNewEncryptionKey"><i class="fa fa-key"></i> ' . $strings['243'] . '</a>';
	if(isset($message)) {
		$message .= '<br><br>' . $encryptionKeyMessage;
	} else {
		$message = $encryptionKeyMessage;
	}
}



$userDataSize = $userDataSize / 1024 / 1024; // Size in MB
$userDataSize = round($userDataSize, 2);
$userDataCap = 100;
$userDataUsagePercent = $userDataSize / $userDataCap * 100;
$userDataUsagePercent = round($userDataUsagePercent, 0);
if($userDataUsagePercent > 90) {
	$dataUsageColor = 'red';
} elseif($userDataUsagePercent > 80) {
	$dataUsageColor = 'orange';
} else {
	$dataUsageColor = 'indigo';
}

$_SESSION['userDataSize'] = $userDataSize;
$_SESSION['userDataCap'] = $userDataCap;

//reorder entries for each category.
ksort($passwordsArray);
ksort($notesArray);
ksort($filesArray);

?>

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
		
		function showPass(id,buttonId,iconId) {
			//
			
			
			var z = document.getElementById(iconId).className;
			if (z === "fa fa-eye") {
				document.getElementById(iconId).className = "fa fa-eye-slash";
			} else {
				document.getElementById(iconId).className = "fa fa-eye";
			}
			
			var y = document.getElementById(buttonId);
			if (y.textContent === "Afficher"){
				y.textContent = "Masquer";
			} else if (y.textContent === "Show"){
				y.textContent = "Hide";
			} else if (y.textContent === "Hide"){
				y.textContent = "Show";
			} else if (y.textContent === "Masquer"){
				y.textContent = "Afficher";
			}
			
			var x = document.getElementById(id);
			if (x.type === "password") {
				x.type = "text";
			} else {
				x.type = "password";
			}
		}
		
		//document.getElementById("searchField").value.onkeypress = function() {searchEntry()};
		
		function searchEntry() {
			var searchString = document.getElementById("searchField").value;
			var lowerCaseString = searchString.toLowerCase();
			// alert(searchString);
			
			var entries = document.getElementsByClassName('searchable');
			for(var i = 0; i < entries.length; i++) {
				// var name = document.getElementsByName(entries[i]).toString();
				var title = entries[i].title;
				var lowerCaseTitle = title.toLowerCase();
				if(lowerCaseTitle.includes(lowerCaseString)) {
					entries[i].style.display = 'block';
				} else {
					entries[i].style.display = 'none';
				}
			}
		}
		
		function searchNoteEntry() {
			var searchString = document.getElementById("noteSearchField").value;
			var lowerCaseString = searchString.toLowerCase();
			// alert(searchString);
			
			var entries = document.getElementsByClassName('searchableNote');
			for(var i = 0; i < entries.length; i++) {
				// var name = document.getElementsByName(entries[i]).toString();
				var title = entries[i].title;
				var lowerCaseTitle = title.toLowerCase();
				if(lowerCaseTitle.includes(lowerCaseString)) {
					entries[i].style.display = 'block';
				} else {
					entries[i].style.display = 'none';
				}
			}
		}
		
		function searchFileEntry() {
			var searchString = document.getElementById("fileSearchField").value;
			var lowerCaseString = searchString.toLowerCase();
			// alert(searchString);
			
			var entries = document.getElementsByClassName('searchableFile');
			for(var i = 0; i < entries.length; i++) {
				// var name = document.getElementsByName(entries[i]).toString();
				var title = entries[i].title;
				var lowerCaseTitle = title.toLowerCase();
				if(lowerCaseTitle.includes(lowerCaseString)) {
					entries[i].style.display = 'block';
				} else {
					entries[i].style.display = 'none';
				}
			}
		}
		
		
		function deleteEntry(entryId, entryName) {
			var r = confirm("<?php echo $strings['58'] ?>:\n" + entryName);
			if ( r == true ) {
				document.location = "profile.php?deleteEntry=" + entryId;
			}
		}
		
		function deleteLink(linkId, linkName) {
			var r = confirm("<?php echo $strings['343'] ?>:\n" + linkName);
			if ( r == true ) {
				document.location = "profile.php?deleteDownloadLink=" + linkId;
			}
		}
		
		function revokeCert(certId, certName) {
			var r = confirm("<?php echo $strings['59'] ?>:\n" + certName);
			if ( r == true ) {
				document.location = "profile.php?revokeCert=" + certId;
			}
		}
		
		function unrevokeCert(certId, certName) {
			var r = confirm("<?php echo $strings['60'] ?>:\n" + certName);
			if ( r == true ) {
				document.location = "profile.php?unrevokeCert=" + certId;
			}
		}
		
		function deleteCert(certId, certName) {
			var r = confirm("<?php echo $strings['61'] ?>:\n" + certName);
			if ( r == true ) {
				document.location = "profile.php?deleteCert=" + certId;
			}
		}
		
		function copyString(spanId) {
			var copyText = document.getElementById(spanId);
			var textArea = document.createElement("textarea");
			textArea.value = copyText.textContent;
			document.body.appendChild(textArea);
			textArea.select();
			document.execCommand("Copy");
			textArea.remove();
		}
		
		function copyPass(inputId) {
			var originalType = document.getElementById(inputId).type;
			
			document.getElementById(inputId).type = 'text';
			
			var password = document.getElementById(inputId);
			
			password.select();
			password.setSelectionRange(0, 99999);
			document.execCommand("copy");
			document.getElementById(inputId).blur();
			
			document.getElementById(inputId).type = originalType;
		}
		
		function hidePlaceholder(field) {
			document.getElementById(field).placeholder = "";
		}
		
		function showPlaceholder(field,text) {
			document.getElementById(field).placeholder = text;
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
		
		function fileInputNameEscape(fileInputId) {
			var fileName = document.getElementById(fileInputId).files[0].name;
			if(fileName.includes("'")) {
				alert('Filename includes forbidden characters.');
			}
		}
		
 	</script>
	</head>
	<body>
		<?php include 'header.php'; ?>
		<div class="w3-row">
			<div class="w3-third">
				<?php if ($_SESSION['deleteAccount'] != '') { ?>
					<div class="w3-card-4 w3-margin w3-border-red" style="display:block;">
						<h3 class="w3-red w3-center"><?php echo $strings['271']; ?></h3>
						<div class="w3-center w3-padding" style="padding-bottom: 20px!important;">
							<span class="w3-text-red"><?php echo $strings['272']; ?></span>
							<div class="w3-center">
								<div class="w3-padding letterSpacing"><?php echo $_SESSION['deleteAccount']; ?></div>
								<form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="w3-container" >
									<input type="hidden" name="formAction" value="deleteAccount">
									<?php echo '<input type="hidden" name="formUid" value="' . $_SESSION["secureFormCode"] . '">'; ?>
									<input type="text" name="deleteAccount" class="w3-border w3-border-red letterSpacing w3-center w3-round" size="12" style="padding:3px;">
									<?php if(isset($deleteAccountError)) { echo '<div class="w3-text-red">' . $deleteAccountError . '</div>'; } ?>
									<div class="w3-padding-16">
										<input class="w3-btn w3-red w3-margin-bottom" type="submit" value="<?php echo $strings['274']; ?>" name="submit"><br>
										<a class="w3-btn w3-blue" href="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>?control=cancelAccountDeletion"><?php echo $strings['273']; ?></a>
									</div>
								</form>
							</div>
						</div>
					</div>
				<?php } ?>
				
				<?php if ($_SESSION['sendByEmail'] != '') { ?>
					<div class="w3-card-4 w3-margin w3-border-red" style="display:block;">
						<h3 class="w3-blue w3-center"><?php echo $strings['279']; ?></h3>
						<div class="w3-padding" style="padding-bottom: 20px!important;">
							<?php echo $strings['280'] . '<br><br><a href="javascript:showhide(\'downloadDetail\')">' . $strings['295'] . '</a>
							<div id="downloadDetail" class="w3-padding" style="display:none;">
								<ul class="w3-ul">';
								if($_SESSION['sendByEmail']['type'] == 'certificate') {
									// Get certificate information for display
									$certId = $_SESSION['sendByEmail']['id'];
									$sql = "SELECT id, serial, ivCertData, encryptedCertData, tagCertData, cipherSuiteCertData FROM certs WHERE userId='$_SESSION[userId]' AND id='$certId'";
									$db_rawCert = $conn->query($sql);
									$db_cert = $db_rawCert -> fetch_assoc();
									// Cert data is encrypted! Decrypt, then build variables...
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
									$certPublicKey = utf8_decode($clearCertData['publicKey']);
									
									echo '<li>Type: <i class="fa fa-id-card"></i> ' . $strings['233'] . '</li>';
									echo '<li>' . $strings['79'] . ': ' . $db_cert['serial'] . '</li>';
									echo '<li>' . $strings['301'] . ': ' . $certFullName . '</li>';
								} else {
									$entryId = $_SESSION['sendByEmail']['id'];
									if($_SESSION['sendByEmail']['type'] == 'password') {
										foreach ($passwordsArray as $row) {
											if($row['id'] == $entryId) {
												// Found! Build variables for display.
												$passName = $row['name'];
												$passUsername = $row['username'];
												$passPassword = $row['password'];
												$passwordLength = strlen($passPassword);
												$passUrl = $row['url'];
												$passFavicon = $row['favicon'];
												$passFaviconType = $row['faviconType'];
												$passComment = $row['comment'];
											}
										}
										
										echo '<li class="wrapLongText">Type: ***' . str_replace(' ', '*', $strings['41']) . '***</li>';
										echo '<li class="wrapLongText">' . $strings['39'] . ': ';
										if ($passFavicon != '') {
											echo '<img class="favicon" src="data:' . $passFaviconType . ';base64, ' . $passFavicon . '" /> ';
										}
										echo $passName . '</li>';
										echo '<li class="wrapLongText">' . $strings['40'] . ': ' . $passUsername . '</li>';
										echo '<li class="wrapLongText">' . $strings['41'] . ': '; while($passwordLength > 0) { echo '*'; $passwordLength--; } echo '</li>';
									} elseif ($_SESSION['sendByEmail']['type'] == 'note') {
										foreach ($notesArray as $row) {
											if($row['id'] == $entryId) {
												// Found! Build variables for display.
												$noteName = $row['name'];
												$noteContent = $row['content'];
												$noteTimestamp = $row['timestamp'];
												$noteLastModified = $row['lastModified'];
											}
										}
										
										echo '<li>Type: <i class="fa fa-pencil-square-o"></i> ' . $strings['299'] . '</li>';
										echo '<li>' . $strings['147'] . ': ' . $noteName . '</li>';
										if ($noteTimestamp != '') { echo '<li>' . $strings['249'] . ': ' . $noteTimestamp . '</li>'; }
										if ($noteLastModified != '') { echo '<li>' . $strings['250'] . ': ' . $noteLastModified . '</li>'; }
									} elseif ($_SESSION['sendByEmail']['type'] == 'file') {
										foreach ($filesArray as $row) {
											if($row['id'] == $entryId) {
												// Found! Build variables for display.
												$fileTimestamp = $row['timestamp'];
												$fileFileName = $row['fileName'];
												$fileDescription = $row['description'];
												$fileFileId = $row['fileId'];
												$fileFileType = $row['fileType'];
												$fileFileSize = $row['fileSize'];
												$fileStoredFileSize = $row['storedFileSize'];
											
												// Set display of file size
												if($fileFileSize < 1024) {
													$fileFileSizeUnit = $strings['199'];
												} elseif($fileFileSize < 1048576) {
													$fileFileSizeUnit = $strings['200'];
													$fileFileSize = $fileFileSize / 1024;
													$fileFileSize = round($fileFileSize, 2);
													$fileStoredFileSize = $fileStoredFileSize / 1024;
													$fileStoredFileSize = round($fileStoredFileSize, 2);
												} else {
													$fileFileSizeUnit = $strings['201'];
													$fileFileSize = $fileFileSize / 1024 / 1024;
													$fileFileSize = round($fileFileSize, 2);
													$fileStoredFileSize = $fileStoredFileSize / 1024 / 1024;
													$fileStoredFileSize = round($fileStoredFileSize, 2);
												}
											}
										}
										
										echo '<li>Type: <i class="fa fa-file-o"></i> ' . $strings['300'] . '</li>';
										echo '<li>' . $strings['39'] . ': ' . $fileDescription . '</li>';
										echo '<li>' . $strings['196'] . ': ' . $fileFileName . '</li>';
										echo '<li>' . $strings['302'] . ': ' . $fileFileType . '</li>';
										echo '<li>' . $strings['197'] . ': ' . $fileFileSize . $fileFileSizeUnit . '</li>';
									}
									
								}
							echo '</ul>
							</div>';
							?>
							
							<form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="w3-container" >
								<input type="hidden" name="formAction" value="sendByEmail">
								<?php echo '<input type="hidden" name="formUid" value="' . $_SESSION["secureFormCode"] . '">'; ?>
								
								<div class="w3-padding-16">
									<label><?php echo $strings['12']; ?></label>
									<input type="email" name="emailAddress" class="w3-input w3-border w3-border-blue w3-round"<?php if(isset($downloadRecipient)) { echo ' value="' . $downloadRecipient . '"'; } ?>>
									<?php if(isset($downloadEmailError)) { echo '<div class="w3-text-red">' . $downloadEmailError . '</div>'; } ?>
								</div>
								<div class="w3-padding-16">
									<label><?php echo $strings['282']; ?></label>
									<input type="text" name="downloadPassphrase" class="w3-input w3-border w3-border-blue w3-round"<?php if(isset($downloadPassphrase)) { echo ' value="' . $downloadPassphrase . '"'; } ?>>
									<?php if(isset($downloadPassphraseError)) { echo '<div class="w3-text-red">' . $downloadPassphraseError . '</div>'; } ?>
								</div>
								
								<div class="w3-padding-16 w3-center">
									<input class="w3-btn w3-blue w3-margin" type="submit" value="<?php echo $strings['281']; ?>" name="submit">
									<a class="w3-btn w3-red w3-margin" href="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>?control=cancelSendByEmail"><?php echo $strings['44']; ?> <i class="fa fa-times-circle"></i></a>
								</div>
							</form>
						</div>
					</div>
				<?php } ?>
				
				<?php 
					if(isset($message)) {
						echo '<div class="w3-card-4 w3-margin" id="notificationArea" style="display:block;">
							<h3 class="w3-indigo w3-center">Notification<a style="margin-right: 6px;" class="w3-right" href="javascript:showhide(\'notificationArea\')"><i class="fa fa-times-circle"></i></a></h3>
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
				<!-- PASSWORDS -->
				<div class="w3-card-4 w3-margin">
					<div class="w3-blue w3-center" style="padding: 7px">
						<h3 style="margin: 0px;"><a href="javascript:showhidePlus('passwords', 'passPlus')"><i class="fa <?php if(isset($editEntry) || isset($backToAddEntryForm)) { echo 'fa-minus-square'; } else { echo 'fa-plus-square'; } ?>" id="passPlus"></i></a> <a href="javascript:showhidePlus('passwords', 'passPlus')"><?php echo $strings['36']; ?></a></h3>
					</div>
					<div id="passwords" style="display:<?php if(isset($editEntry) || isset($backToAddEntryForm)) { echo 'block'; } else { echo 'none'; } ?>;">
						<div class="w3-container">
							<?php 
							if($_SESSION['userDataSize'] < $_SESSION['userDataCap']) {
								echo '<a href="javascript:showhide(\'addEntry\')" class="w3-button w3-blue w3-margin">' . $strings['37'] . ' <i class="fa fa-plus-circle"></i></a>';
							} else {
								echo '<div class="w3-padding w3-text-red">' . $strings['219'] . '</div>';
							}
							?>
						</div>
						<div class="w3-container w3-card-4" id="addEntry" style="display:<?php if(isset($backToAddEntryForm)) { echo 'block'; } else { echo 'none'; } ?>">
						<h4><?php echo $strings['37']; ?></h4>
						<?php // var_dump($_SESSION["secureFormCode"]); ?>
						<form class="w3-container" method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
							<?php
								echo '<input type="hidden" name="formUid" value="' . $_SESSION["secureFormCode"] . '">';
								echo '<input type="hidden" name="formAction" value="addEntry">';
							?>
							<div class="w3-padding-16">
								<label><?php echo $strings['39']; ?>*</label>
								<input class="w3-input" type="text" name="newEntryName"<?php if(isset($newEntryName)) { echo ' value="' . $newEntryName . '"'; } ?>>
								<?php if (isset($newEntryNameError)) { echo '<div class="w3-text-red">' . $newEntryNameError . '</div>';} ?>
							</div>
							<div class="w3-padding-16">
								<label><?php echo $strings['40']; ?></label>
								<input class="w3-input" type="text" name="newEntryUsername"<?php if(isset($newEntryUsername)) { echo ' value="' . $newEntryUsername . '"'; } ?>>
								<?php if (isset($newEntryUsernameError)) { echo '<div class="w3-text-red">' . $newEntryUsernameError . '</div>';} ?>
							</div>
							<div class="w3-padding-16">
								<label><?php echo $strings['41']; ?>*</label>
								<input class="w3-input" type="password" name="newEntryPassword" id="newEntryPassword"<?php if(isset($newEntryPassword)) { echo ' value="' . $newEntryPassword . '"'; } ?>>
								<a class="w3-btn w3-blue" href="javascript:showPass('newEntryPassword', 'newEntryPasswordButton', 'newEntryPasswordIcon')"><i class="fa fa-eye" id="newEntryPasswordIcon"></i> <span id="newEntryPasswordButton"><?php echo $strings['43']; ?></span></a>
								<?php if (isset($newEntryPasswordError)) { echo '<div class="w3-text-red">' . $newEntryPasswordError . '</div>';} ?>
							</div>
							<div class="w3-padding-16">
								<label><?php echo $strings['265']; ?></label>
								<input class="w3-input" type="url" name="newEntryUrl"<?php if(isset($newEntryUrl)) { echo ' value="' . $newEntryUrl . '"'; } ?>>
								<?php if (isset($newEntryUrlError)) { echo '<div class="w3-text-red">' . $newEntryUrlError . '</div>';} ?>
							</div>
							<div class="w3-padding-16">
								<label><?php echo $strings['42']; ?></label>
								<textarea class="w3-input" name="newEntryComment"><?php if(isset($newEntryComment)) { echo $newEntryComment; } ?></textarea>
							</div>
							
							
							<div class="w3-padding-16 w3-right">
								<a class="w3-btn w3-red" href="javascript:showhide('addEntry')"><?php echo $strings['44']; ?> <i class="fa fa-undo"></i></a>
								<input class="w3-btn w3-blue" type="submit" value="<?php echo $strings['45']; ?>" name="submit">
							</div>
						</form>
						
						</div>
						<div class="w3-container w3-card-4">
							<div class="w3-row w3-section">
								<div class="w3-col" style="width:50px">
									<i class="w3-xxlarge fa fa-search"></i>
								</div>
								<div class="w3-rest">
									<input type="text" id="searchField" class="w3-input w3-border w3-round" value="" onclick="javascript:hidePlaceholder('searchField')" onBlur="javascript:showPlaceholder('searchField','<?php echo $strings['46']; ?>')" onkeyup="javascript:searchEntry()" placeholder="<?php echo $strings['46']; ?>">
								</div>
							</div>
							<script>document.getElementById("searchField").value.onkeyup = function() {searchEntry();}</script>
							<hr>
						<?php
							// Get all entries for user, decrypt them, load them.
							
							foreach ($passwordsArray as $row) {
								
								$entryName = $row['name'];
								$entryUsername = $row['username'];
								$entryPassword = $row['password'];
								$entryUrl = $row['url'];
								$entryFavicon = $row['favicon'];
								$entryFaviconType = $row['faviconType'];
								$entryComment = $row['comment'];
							
								if ($editEntry == $row['id']) {
									if(!isset($editEntryName)) {$editEntryName = $entryName;}
									if(!isset($editEntryUsername)) {$editEntryUsername = $entryUsername;}
									if(!isset($editEntryPassword)) {$editEntryPassword = $entryPassword;}
									if(!isset($editEntryUrl)) {$editEntryUrl = $entryUrl;}
									if(!isset($editEntryComment)) {$editEntryComment = $entryComment;}
									
									
									// Display edition form
									echo '<div class="w3-card-4 w3-container w3-padding"><h4>' . $strings['48'] . ' "' . $entryName . '"</h4>';
									echo '<form class="w3-container" method="POST" action="' . htmlspecialchars($_SERVER["PHP_SELF"]) . '">';
									echo '<input type="hidden" name="formUid" value="' . $_SESSION["secureFormCode"] . '">';
									echo '<input type="hidden" name="formAction" value="editEntry">';
									echo '<input type="hidden" name="editEntry" value="' . $row['id'] . '">';
									
									echo '
										<div class="w3-padding-16">
											<label>' . $strings['39'] . '</label>
											<input autofocus class="w3-input" type="text" name="editEntryName"';
												if(isset($editEntryName)) { echo ' value="' . $editEntryName . '"'; }
											echo '>';
												if (isset($editEntryNameError)) { echo '<div class="w3-text-red">' . $editEntryNameError . '</div>';}
										echo '</div>';
										echo '<div class="w3-padding-16">
											<label>' . $strings['40'] . '</label>
											<input class="w3-input" type="text" name="editEntryUsername"';
												if(isset($editEntryUsername)) { echo ' value="' . $editEntryUsername . '"'; }
											echo '>';
												if (isset($editEntryUsernameError)) { echo '<div class="w3-text-red">' . $editEntryUsernameError . '</div>';}
										echo '</div>';
										echo '<div class="w3-padding-16">
											<label>' . $strings['41'] . '</label>
											<input class="w3-input" type="password" name="editEntryPassword" id="editEntryPassword"';
												if(isset($editEntryPassword)) { echo ' value="' . $editEntryPassword . '"'; }
											echo '><a class="w3-btn w3-blue" href="javascript:showPass(\'editEntryPassword\', \'editEntryPasswordButton\', \'editEntryPasswordIcon\')"><i class="fa fa-eye" id="editEntryPasswordIcon"></i> <span id="editEntryPasswordButton">' . $strings['43'] . '</span></a>';
												if (isset($editEntryPasswordError)) { echo '<div class="w3-text-red">' . $editEntryPasswordError . '</div>';}
										echo '</div>';
										echo '<div class="w3-padding-16">
											<label>' . $strings['265'] . '</label>
											<input class="w3-input" type="url" name="editEntryUrl"';
												if(isset($editEntryUrl)) { echo ' value="' . $editEntryUrl . '"'; }
											echo '>';
												if (isset($editEntryUrlError)) { echo '<div class="w3-text-red">' . $editEntryUrlError . '</div>';}
										echo '</div>';
										
										// Count lines in textarea
											if(isset($editEntryComment)) {
												$lines_arr = preg_split('/\r/',$editEntryComment);
												$num_newlines = count($lines_arr);
												if ($num_newlines < 5) { $textAreaLines = 5; } else { $textAreaLines = $num_newlines; }
											} else {
												$textAreaLines = 5;
											}
										
										echo '<div class="w3-padding-16">
											<label>' . $strings['42'] . '</label>
											
											<textarea rows="' . $textAreaLines . '" class="w3-input w3-border w3-round" name="editEntryComment">';
											if(isset($editEntryComment)) {
												echo $editEntryComment; 
											}
										echo '</textarea>
										</div>
										<a class="w3-btn w3-red" href="profile.php">' . $strings['44'] . ' <i class="fa fa-undo"></i></a>
										<input class="w3-btn w3-blue" type="submit" value="' . $strings['47'] . '" name="submit">
										
										</form>
									</div>
									<hr>';
									
								} elseif (!isset($editEntry)) {
									
									// Display record
									echo '<div class="searchable" title="' . htmlOutput($entryName) . ' ' . htmlOutput($entryUsername) . '" style="display:block"><a href="javascript:showhide(\'entry' . $row['id'] . '\')"><h4>'; 
									if($entryFavicon != '') { echo '<img class="favicon" src="data:' . $entryFaviconType . ';base64, ' . $entryFavicon . '" /></a> <a href="javascript:showhide(\'entry' . $row['id'] . '\')">'; }
									echo $entryName . '</h4></a>';
									echo '<div class="w3-container w3-padding-16" id="entry' . $row['id'] . '" style="display: none">
									<ul class="w3-ul">
									<li>' . $strings['40'] . ': <span id="username' . $row['id'] . '">' . $entryUsername . '</span> <a class="w3-blue w3-margin-left w3-btn" href="javascript:copyString(\'username' . $row['id'] . '\')"><i class="fa fa-files-o"></i> ' . $strings['49'] . '</a></li>
									<li>' . $strings['41'] . ': <input type="password" id="pass' . $row['id'] . '" class="w3-input w3-border-0" value="' . $entryPassword . '" style="width: 70%; display: inline-block"><br><a class="w3-blue w3-btn" href="javascript:copyPass(\'pass' . $row['id'] . '\')"><i class="fa fa-files-o"></i> ' . $strings['49'] . '</a> <a class="w3-btn w3-blue" href="javascript:showPass(\'pass' . $row['id'] . '\', \'passButton' . $row['id'] . '\', \'passButtonIcon' . $row['id'] . '\')"><i class="fa fa-eye" id="passButtonIcon' . $row['id'] . '"></i> <span id="passButton' . $row['id'] . '">' . $strings['43'] . '</span></a></li>';
									
									if ($row['url'] != '') { echo '<li class="wrapLongText">' . $strings['265'] . ':<br><a href="' . $row['url'] . '" target="_blank">' . $row['url'] . '</a></li>'; }
									echo '<li>' . $strings['42'] . ':
									<pre class="wrapLongText" style="white-space: pre-wrap;">' . $entryComment . '</pre></li>';
									if ($row['timestamp'] != '') { echo '<li>' . $strings['249'] . ': ' . $row['timestamp'] . '</li>'; }
									if ($row['lastModified'] != '') { echo '<li>' . $strings['250'] . ': ' . $row['lastModified'] . '</li>'; }
									echo '</ul>
									<hr>
									<a class="w3-btn w3-blue" href="profile.php?editEntry=' . $row['id'] . '">' . $strings['50'] . ' <i class="fa fa-pencil"></i></a>';
									echo '<div class="w3-padding-small" style="display:inline-block;"><a class="w3-btn w3-blue" href="' . $_SERVER["PHP_SELF"] . '?sendByEmail=' . $row['id'] . '&type=password">' . $strings['277'] . ' <i class="fa fa-share-square-o"></i></a></div>';
									echo '<a class="w3-btn w3-red" href="javascript:void(0)" onclick="deleteEntry(\'' . $row['id'] . '\',\'' . javascriptEscape($entryName) . '\')">' . $strings['51'] . ' <i class="fa fa-times"></i></a>
									</div>
									<hr></div>';
								}
							}
						?>
							
						</div>
					</div>
				</div>
				<!-- NOTES -->
				<div class="w3-card-4 w3-margin">
					<div class="w3-blue w3-center" style="padding: 7px">
						<h3 style="margin: 0px;"><a href="javascript:showhidePlus('notes', 'notesPlus')"><i class="fa <?php if(isset($editNoteEntry) || isset($backToAddNoteEntryForm)) { echo 'fa-minus-square'; } else { echo 'fa-plus-square'; } ?>" id="notesPlus"></i></a> <a href="javascript:showhidePlus('notes', 'notesPlus')"><?php echo $strings['145']; ?></a></h3>
					</div>
					<div id="notes" style="display:<?php if(isset($editNoteEntry) || isset($backToAddNoteEntryForm)) { echo 'block'; } else { echo 'none'; } ?>;">
						<div class="w3-container">
							<?php 
							if($_SESSION['userDataSize'] < $_SESSION['userDataCap']) {
								echo '<a href="javascript:showhide(\'addNoteEntry\')" class="w3-button w3-blue w3-margin">' . $strings['148'] . ' <i class="fa fa-plus-circle"></i></a>';
							} else {
								echo '<div class="w3-padding w3-text-red">' . $strings['219'] . '</div>';
							}
							?>						
						</div>
						<div class="w3-container w3-card-4" id="addNoteEntry" style="display:<?php if(isset($backToAddNoteEntryForm)) { echo 'block'; } else { echo 'none'; } ?>">
						<h4><?php echo $strings['148']; ?></h4>
						<?php // var_dump($_SESSION["secureFormCode"]); ?>
						<form class="w3-container" method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
							<?php
								echo '<input type="hidden" name="formUid" value="' . $_SESSION["secureFormCode"] . '">';
								echo '<input type="hidden" name="formAction" value="addNoteEntry">';
							?>
							<div class="w3-padding-16">
								<label><?php echo $strings['147']; ?></label>
								<input class="w3-input" type="text" name="newNoteEntryName"<?php if(isset($newNoteEntryName)) { echo ' value="' . $newNoteEntryName . '"'; } ?>>
								<?php if (isset($newNoteEntryNameError)) { echo '<div class="w3-text-red">' . $newNoteEntryNameError . '</div>';} ?>
							</div>
							<div class="w3-padding-16">
								<label><?php echo $strings['146']; ?></label>
								<textarea class="w3-input" name="newNoteEntryContent"><?php if(isset($newNoteEntryContent)) { echo $newNoteEntryContent; } ?></textarea>
							</div>
							
							
							<div class="w3-padding-16 w3-right">
								<a class="w3-btn w3-red" href="javascript:showhide('addNoteEntry')"><?php echo $strings['44']; ?> <i class="fa fa-undo"></i></a>
								<input class="w3-btn w3-blue" type="submit" value="<?php echo $strings['45']; ?>" name="submit">
							</div>
						</form>
						
						</div>
						<div class="w3-container w3-card-4">
							<div class="w3-row w3-section">
								<div class="w3-col" style="width:50px">
									<i class="w3-xxlarge fa fa-search"></i>
								</div>
								<div class="w3-rest">
									<input type="text" id="noteSearchField" class="w3-input w3-border w3-round" value="" onclick="javascript:hidePlaceholder('noteSearchField')" onBlur="javascript:showPlaceholder('noteSearchField','<?php echo $strings['46']; ?>')" onkeyup="javascript:searchNoteEntry()" placeholder="<?php echo $strings['46']; ?>">
								</div>
							</div>
							<script>document.getElementById("noteSearchField").value.onkeyup = function() {searchNoteEntry();}</script>
							<hr>
						<?php
							// Get all entries for user, decrypt them, load them.
							
							foreach ($notesArray as $row) {
								// Decrypt row first
								
								$noteEntryName = $row['name'];
								$noteEntryContent = $row['content'];
							
								if ($editNoteEntry == $row['id']) {
									if(!isset($editNoteEntryName)) {$editNoteEntryName = $noteEntryName;}
									if(!isset($editNoteEntryContent)) {$editNoteEntryContent = $noteEntryContent;}
									
									
									// Display edition form
									echo '<div class="w3-card-4 w3-container w3-padding"><h4>' . $strings['48'] . ' "' . $noteEntryName . '"</h4>';
									echo '<form class="w3-container" method="POST" action="' . htmlspecialchars($_SERVER["PHP_SELF"]) . '">';
									echo '<input type="hidden" name="formUid" value="' . $_SESSION["secureFormCode"] . '">';
									echo '<input type="hidden" name="formAction" value="editNoteEntry">';
									echo '<input type="hidden" name="editNoteEntry" value="' . $row['id'] . '">';
									
									echo '
										<div class="w3-padding-16">
											<label>' . $strings['39'] . '</label>
											<input autofocus class="w3-input" type="text" name="editNoteEntryName"';
												if(isset($editNoteEntryName)) { echo ' value="' . $editNoteEntryName . '"'; }
											echo '>';
												if (isset($editNoteEntryNameError)) { echo '<div class="w3-text-red">' . $editNoteEntryNameError . '</div>';}
										echo '</div>';
										
										
										// Count lines in textarea
											if(isset($editNoteEntryContent)) {
												$lines_arr = preg_split('/\r/',$editNoteEntryContent);
												$num_newlines = count($lines_arr);
												if ($num_newlines < 5) { $textAreaLines = 5; } else { $textAreaLines = $num_newlines; }
											} else {
												$textAreaLines = 5;
											}
										echo '<div class="w3-padding-16">
											<label>' . $strings['149'] . '</label>
											
											<textarea rows="' . $textAreaLines . '" class="w3-input w3-border w3-round" name="editNoteEntryContent">';
											if(isset($editNoteEntryContent)) {
												echo $editNoteEntryContent; 
											}
										echo '</textarea>
										</div>
										<a class="w3-btn w3-red" href="profile.php">' . $strings['44'] . ' <i class="fa fa-undo"></i></a>
										<input class="w3-btn w3-blue" type="submit" value="' . $strings['47'] . '" name="submit">
										
										</form>
									</div>
									<hr>';
									
								} elseif (!isset($editNoteEntry)) {
									
									// Display record
									echo '<div class="searchableNote" title="' . htmlOutput($noteEntryName) . '" style="display:block"><a href="javascript:showhide(\'entry' . $row['id'] . '\')"><h4>' . $noteEntryName . '</h4></a>';
									echo '<div class="w3-container w3-padding-16" id="entry' . $row['id'] . '" style="display: none">';
									if ($row['timestamp'] != '') { echo $strings['249'] . ': ' . $row['timestamp'] . '<br>'; }
									if ($row['lastModified'] != '') { echo $strings['250'] . ': ' . $row['lastModified'] . '<br>'; }
									echo '<pre class="wrapLongText" style="white-space: pre-wrap;">' . $noteEntryContent . '</pre>
									<hr>
									<a class="w3-btn w3-blue" href="profile.php?editNoteEntry=' . $row['id'] . '">' . $strings['50'] . ' <i class="fa fa-pencil"></i></a>';
									echo '<div class="w3-padding-small" style="display:inline-block;"><a class="w3-btn w3-blue" href="' . $_SERVER["PHP_SELF"] . '?sendByEmail=' . $row['id'] . '&type=note">' . $strings['277'] . ' <i class="fa fa-share-square-o"></i></a></div>';
									echo '<a class="w3-btn w3-red" href="javascript:void(0)" onclick="deleteEntry(\'' . $row['id'] . '\',\'' . javascriptEscape($noteEntryName) . '\')">' . $strings['51'] . ' <i class="fa fa-times"></i></a>
									</div>
									<hr></div>';
								}
							}
						?>
							
						</div>
					</div>
				</div>
				
				<!-- FILES -->
				<div class="w3-card-4 w3-margin">
					<div class="w3-blue w3-center" style="padding: 7px">
						<h3 style="margin: 0px;"><a href="javascript:showhidePlus('files', 'filesPlus')"><i class="fa <?php if(isset($editFileEntry) || isset($backToAddFileEntryForm)) { echo 'fa-minus-square'; } else { echo 'fa-plus-square'; } ?>" id="filesPlus"></i></a> <a href="javascript:showhidePlus('files', 'filesPlus')"><?php echo $strings['180']; ?></a></h3>
					</div>
					<div id="files" style="display:<?php if(isset($editFileEntry) || isset($backToAddFileEntryForm)) { echo 'block'; } else { echo 'none'; } ?>;">
						<div class="w3-container">
							<?php 
							if($_SESSION['userDataSize'] < $_SESSION['userDataCap']) {
								echo '<a href="javascript:showhide(\'addFileEntry\')" class="w3-button w3-blue w3-margin">' . $strings['181'] . ' <i class="fa fa-plus-circle"></i></a>';
							} else {
								echo '<div class="w3-padding w3-text-red">' . $strings['219'] . '</div>';
							}
							?>						
						</div>
						<div class="w3-container w3-card-4" id="addFileEntry" style="display:<?php if(isset($backToAddFileEntryForm)) { echo 'block'; } else { echo 'none'; } ?>">
							<h4><?php echo $strings['181']; ?></h4>
							<?php // var_dump($_SESSION["secureFormCode"]); ?>
							<form class="w3-container" method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" enctype="multipart/form-data">
								<?php
									echo '<input type="hidden" name="formUid" value="' . $_SESSION["secureFormCode"] . '">';
									echo '<input type="hidden" name="formAction" value="addFileEntry">';
								?>
								<div class="w3-padding-16">
									<label><?php echo $strings['182']; ?></label>
									<input class="w3-input" type="text" name="newFileDescription"<?php if(isset($newFileDescription)) { echo ' value="' . $newFileDescription . '"'; } ?>>
									<?php if (isset($newFileDescriptionError)) { echo '<div class="w3-text-red">' . $newFileDescriptionError . '</div>';} ?>
								</div>
								<div class="w3-padding-16">
									<label><?php echo $strings['183']; ?></label>
									<input class="w3-input" type="file" name="newFile" onChange="javascript:fileInputNameEscape('newFileInput')" id="newFileInput">
									<?php if (isset($newFileError)) { echo '<div class="w3-text-red">' . $newFileError . '</div>';} ?>
									<span class="w3-small"><?php echo $strings['184']; ?></span>
								</div>
								<div class="w3-padding-16 w3-right">
									<a class="w3-btn w3-red" href="javascript:showhide('addFileEntry')"><?php echo $strings['44']; ?> <i class="fa fa-undo"></i></a>
									<input class="w3-btn w3-blue" type="submit" value="<?php echo $strings['45']; ?>" name="submit">
								</div>
							</form>
						</div>
						<div class="w3-container w3-card-4">
							<div class="w3-row w3-section">
								<div class="w3-col" style="width:50px">
									<i class="w3-xxlarge fa fa-search"></i>
								</div>
								<div class="w3-rest">
									<input type="text" id="fileSearchField" class="w3-input w3-border w3-round" value="" onclick="javascript:hidePlaceholder('fileSearchField')" onBlur="javascript:showPlaceholder('fileSearchField','<?php echo $strings['46']; ?>')" onkeyup="javascript:searchFileEntry()" placeholder="<?php echo $strings['46']; ?>">
								</div>
							</div>
							<script>document.getElementById("fileSearchField").value.onkeyup = function() {searchFileEntry();}</script>
							<hr>
						<?php
							// Get all entries for user, decrypt them, load them.
							
							foreach ($filesArray as $row) {
								
								$fileEntryTimestamp = $row['timestamp'];
								$fileEntryFileName = $row['fileName'];
								$fileEntryDescription = $row['description'];
								$fileEntryFileId = $row['fileId'];
								$fileEntryFileType = $row['fileType'];
								$fileEntryFileSize = $row['fileSize'];
								$fileEntryStoredFileSize = $row['storedFileSize'];
							
								// Set display of file size
								if($fileEntryFileSize < 1024) {
									$fileEntryFileSizeUnit = $strings['199'];
								} elseif($fileEntryFileSize < 1048576) {
									$fileEntryFileSizeUnit = $strings['200'];
									$fileEntryFileSize = $fileEntryFileSize / 1024;
									$fileEntryFileSize = round($fileEntryFileSize, 2);
									$fileEntryStoredFileSize = $fileEntryStoredFileSize / 1024;
									$fileEntryStoredFileSize = round($fileEntryStoredFileSize, 2);
								} else {
									$fileEntryFileSizeUnit = $strings['201'];
									$fileEntryFileSize = $fileEntryFileSize / 1024 / 1024;
									$fileEntryFileSize = round($fileEntryFileSize, 2);
									$fileEntryStoredFileSize = $fileEntryStoredFileSize / 1024 / 1024;
									$fileEntryStoredFileSize = round($fileEntryStoredFileSize, 2);
								}
							
								if ($editFileEntry == $row['id']) {
									if(!isset($editFileDescription)) {$editFileDescription = $fileEntryDescription;}
									
									// Display edition form
									echo '<div class="w3-card-4 w3-container w3-padding"><h4>' . $strings['48'] . ' "' . $fileEntryDescription . '"</h4>';
									echo '<form class="w3-container" method="POST" action="' . htmlspecialchars($_SERVER["PHP_SELF"]) . '" enctype="multipart/form-data">';
									echo '<input type="hidden" name="formUid" value="' . $_SESSION["secureFormCode"] . '">';
									echo '<input type="hidden" name="formAction" value="editFileEntry">';
									echo '<input type="hidden" name="editFileEntry" value="' . $row['id'] . '">';
									echo '
										<div class="w3-padding-16">
											<label>' . $strings['39'] . '</label>
											<input autofocus class="w3-input" type="text" name="editFileDescription"';
												if(isset($editFileDescription)) { echo ' value="' . $editFileDescription . '"'; }
											echo '>';
												if (isset($editFileDescriptionError)) { echo '<div class="w3-text-red">' . $editFileDescriptionError . '</div>';}
										echo '</div>';
										echo '<ul class="w3-ul">
											<li>' . $strings['196'] . ': ' . $fileEntryFileName . '</li>
											<li>' . $strings['197'] . ': ' . $fileEntryFileSize . $fileEntryFileSizeUnit . '</li>
										</ul>';
										echo '<div class="w3-padding w3-border w3-border-blue w3-margin-bottom">
											<label>' . $strings['202'] . '</label>
											<input class="w3-input" type="file" name="editFile">';
											if (isset($editFileError)) { echo '<div class="w3-text-red">' . $editFileError . '</div>';}
											echo '<span class="w3-small">' . $strings['184'] . '</span>
										</div>'; 
										
										echo '<a class="w3-btn w3-red" href="profile.php">' . $strings['44'] . ' <i class="fa fa-undo"></i></a>
										<input class="w3-btn w3-blue" type="submit" value="' . $strings['47'] . '" name="submit">
										
										</form>
									</div>
									<hr>';
									
								} elseif (!isset($editFileEntry)) {
									// Display record
									echo '<div class="searchableFile" title="' . htmlOutput($fileEntryDescription) . ' ' . htmlOutput($fileEntryFileName) . '" style="display:block"><a href="javascript:showhide(\'entry' . $row['id'] . '\')"><h4>' . $fileEntryDescription . '</h4></a>';
									echo '<div class="w3-container w3-padding-16 wrapLongText" id="entry' . $row['id'] . '" style="display:none; ">
									' . $strings['196'] . ': ' . $fileEntryFileName . ' (<a href="javascript:showhide(\'fileDetail' . $row['id'] . '\')">' . $strings['238'] . '</a>)
									<div class="w3-padding" style="display:none" id="fileDetail' . $row['id'] . '">
										<ul class="w3-ul">
											<li>' . $strings['302'] . ': ' . $fileEntryFileType . '</li>
											<li>' . $strings['197'] . ': ' . $fileEntryFileSize . $fileEntryFileSizeUnit . '</li>
											<li>' . $strings['198'] . ': ' . $fileEntryStoredFileSize . $fileEntryFileSizeUnit . '</li>';
											if ($row['fileMd5'] != '') { echo '<li>' . $strings['261'] . ': ' . $row['fileMd5'] . '</li>'; }
											if ($row['fileSha1'] != '') { echo '<li>' . $strings['262'] . ': ' . $row['fileSha1'] . '</li>'; }
											if ($row['fileSha256'] != '') { echo '<li>' . $strings['263'] . ': ' . $row['fileSha256'] . '</li>'; }
											if ($row['timestamp'] != '') { echo '<li>' . $strings['249'] . ': ' . $row['timestamp'] . '</li>'; }
											if ($row['lastModified'] != '') { echo '<li>' . $strings['250'] . ': ' . $row['lastModified'] . '</li>'; }
										echo '</ul>
									</div>
									<hr>
									<a class="w3-btn w3-blue w3-margin-bottom" href="' . $_SERVER["PHP_SELF"] . '?downloadFile=' . $row['id'] . '&reload=no">' . $strings['83'] . ' <i class="fa fa-download"></i></a>
									<a class="w3-btn w3-blue w3-margin-bottom" href="profile.php?editFileEntry=' . $row['id'] . '">' . $strings['50'] . ' <i class="fa fa-pencil"></i></a> ';
									echo '<a class="w3-btn w3-blue w3-margin-bottom" href="' . $_SERVER["PHP_SELF"] . '?sendByEmail=' . $row['id'] . '&type=file">' . $strings['277'] . ' <i class="fa fa-share-square-o"></i></a> ';
									echo '<a class="w3-btn w3-red w3-margin-bottom" href="javascript:void(0)" onclick="deleteEntry(\'' . $row['id'] . '\',\'' . javascriptEscape($fileEntryDescription) . '\')">' . $strings['51'] . ' <i class="fa fa-times"></i></a>
									</div>
									<hr></div>';
								}
							}
						?>
							
						</div>
					</div>
				</div>
			</div>
			<div class="w3-third">
				<div class="w3-card-4 w3-margin">
					<h3 class="w3-indigo w3-center"><?php echo $strings['171']; ?></h3>
					<div class="w3-center w3-padding" style="padding-bottom: 20px!important;">
						<div style="width:100%; border:1px solid">
							<div class="w3-<?php echo $dataUsageColor; ?>" style="width:<?php echo $userDataUsagePercent; ?>%;">
								&nbsp;
							</div>
						</div>
						<?php echo $userDataSize; ?>MB / <?php echo $userDataCap; ?>MB
					</div>
				</div>
				
				<?php
					if(queryFromTor()){ ?>
						<div class="w3-card-4 w3-margin" id="torAccess" style="display:block;">
							<h4 class="w3-center w3-padding"><a href="javascript:showhide('torInfo')"><i class="fa fa-check-circle-o w3-text-green"></i></a> <a href="javascript:showhide('torInfo')"><?php echo $strings['327']; ?></a></h4>
							<div class="w3-padding w3-border-blue w3-border-top" id="torInfo" style="display:none;">
								<?php echo $strings['330']; ?>
							</div>
						</div>
					<?php } else { ?>
						<div class="w3-card-4 w3-margin" id="torAccess" style="display:block;">
							<h4 class="w3-center w3-padding"><a href="javascript:showhide('torInfo')"><i class="fa fa-info-circle w3-text-blue"></i></a> <a href="javascript:showhide('torInfo')"><?php echo $strings['328']; ?></a></h4>
							<div class="w3-padding w3-border-blue w3-border-top" id="torInfo" style="display:none;">
								<?php echo $strings['329']; ?>
							</div>
						</div>
				<?php } ?>
				
				<!-- CERTIFICATES -->
				<div class="w3-card-4 w3-margin">
					<div class="w3-green w3-center" style="padding: 7px">
						<h3 style="margin: 0px;"><a href="javascript:showhidePlus('certificates', 'certPlus')"><i class="fa <?php if($showCerts === true) { echo 'fa-minus-square'; } else { echo 'fa-plus-square'; } ?>" id="certPlus"></i></a> <a href="javascript:showhidePlus('certificates', 'certPlus')"><?php echo $strings['62']; ?></a></h3>
					</div>
					<div class="w3-container" id="certificates" style="display: <?php if($showCerts === true) { echo 'block'; } else { echo 'none'; } ?>">
						<p class="w3-center"><?php echo $strings['63']; ?></p>
						<?php
							$sql = "SELECT id, serial, revoked, ivCertData, encryptedCertData, tagCertData, cipherSuiteCertData FROM certs WHERE userId='$_SESSION[userId]' ORDER BY id DESC";
							$db_rawCerts = $conn->query($sql);
							while($db_cert = $db_rawCerts -> fetch_assoc()) {
								// Cert data is encrypted! Decrypt, then build variables...
								
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
								$certPublicKey = utf8_decode($clearCertData['publicKey']);
								if ($certPublicKey == '') { $certPublicKey = $strings['224']; $canUpdateEncryptionKey = false; } else { $certPublicKey = $strings['225']; }
								if ($canUpdateEncryptionKey !== false) { $canUpdateEncryptionKey = true; }
								$certSecure = true;
								
								$currentDate = date('Y-m-d H:i:s');
								$expirationUnix = strtotime($certValidTo);
								$nowUnix = strtotime($currentDate);
								$expiresInSeconds = ($expirationUnix - $nowUnix);
								if($expiresInSeconds < 86400) {
									// URGENT! Less than 1 day before expiration...
									$validToColor = 'red';
									$validToWeight = 'bold';
									$remainingTimeBeforeExpiry = round($expiresInSeconds / 3600, 1);
									$remainingTimeString = $remainingTimeBeforeExpiry . ' ' . $strings['257'];
								} elseif ($expiresInSeconds < 604800) {
									// FAST! Less than 1 week before expiration...
									$validToColor = 'red';
									$validToWeight = 'bold';
									$remainingTimeBeforeExpiry = round($expiresInSeconds / 86400, 1);
									$remainingTimeString = $remainingTimeBeforeExpiry . ' ' . $strings['258'];
								} elseif ($expiresInSeconds < 2592000) {
									// Renew soon... Less than 30 days before expiration...
									$validToColor = 'orange';
									$validToWeight = 'bold';
									$remainingTimeBeforeExpiry = round($expiresInSeconds / 86400, 1);
									$remainingTimeString = $remainingTimeBeforeExpiry . ' ' . $strings['258'];
								} else {
									$validToColor = 'black';
									$validToWeight = 'normal';
									$remainingTimeBeforeExpiry = round($expiresInSeconds / 86400, 1);
									$remainingTimeString = $remainingTimeBeforeExpiry . ' ' . $strings['258'];
								}
								
								
								if ($db_cert['revoked'] === '1') { $certColor = 'red'; } else { $certColor = 'green'; }
								echo '<div class="w3-margin w3-card-4 w3-border w3-border-' . $certColor . '">';
									echo '<div class="w3-' . $certColor . ' w3-padding"><i class="fa fa-id-card"></i> ' . $certFullName . ' (' . $certEmailAddress . ')</div>';
									if ($_SESSION['certSerial'] == $db_cert['serial']) { echo '<div class="w3-text-green w3-padding"><i class="fa fa-user"></i> ' . $strings['76'] . '</div>';}
									if ($db_cert['revoked'] === '1') { echo '<div class="w3-text-red w3-padding">' . $strings['77'] . '</div>'; }
									echo '<div class="w3-padding wrapLongText">
										' . $strings['78'] . ': ' . $certCity . ', ' . $certState . ', ' . $certCountry . '<br>
										' . $strings['79'] . ': ' . $db_cert['serial'] . '<br>
										' . $strings['80'] . ': ' . $certValidFrom . '<br>
										' . $strings['81'] . ': <span style="font-weight:' . $validToWeight . ';" class="w3-text-' . $validToColor . '">' . $certValidTo . ' <span class="w3-small">(' . $remainingTimeString . ')</span></span><br>
										' . $strings['82'] . ': ' . $certFingerprint . '<br>
										' . $strings['223'] . ': ' . $certPublicKey . '<br>';
									echo '</div>';
									echo '<div class="w3-padding">';
									if ($db_cert['revoked'] !== '1') {
										echo '<div class="w3-padding-small" style="display:inline-block;"><a class="w3-btn w3-blue" href="' . $_SERVER["PHP_SELF"] . '?downloadCert=' . $db_cert['id'] . '&reload=no">' . $strings['83'] . ' <i class="fa fa-download"></i></a></div>';
										echo '<div class="w3-padding-small" style="display:inline-block;"><a class="w3-btn w3-blue" href="' . $_SERVER["PHP_SELF"] . '?sendByEmail=' . $db_cert['id'] . '&type=certificate">' . $strings['277'] . ' <i class="fa fa-share-square-o"></i></a></div>';
									}
									if ($_SESSION['certSerial'] != $db_cert['serial'] && $db_cert['revoked'] !== '1') {
										echo '<div class="w3-padding-small" style="display:inline-block;"><a class="w3-btn w3-red" href="javascript:void(0)" onclick="revokeCert(\'' . $db_cert['id'] . '\', \'' . $certFullName . '\')">' . $strings['84'] . ' <i class="fa fa-times"></i></a></div>';
									} elseif($_SESSION['certSerial'] != $db_cert['serial'] && $db_cert['revoked'] === '1') {
										echo '<div class="w3-padding-small" style="display:inline-block;"><a class="w3-btn w3-blue" href="javascript:void(0)" onclick="unrevokeCert(\'' . $db_cert['id'] . '\', \'' . $certFullName . '\')">' . $strings['85'] . ' <i class="fa fa-repeat"></i></a></div>';
										echo '<div class="w3-padding-small" style="display:inline-block;"><a class="w3-btn w3-red" href="javascript:void(0)" onclick="deleteCert(\'' . $db_cert['id'] . '\', \'' . javascriptEscape($certFullName) . '\')">' . $strings['86'] . ' <i class="fa fa-times"></i></a></div>';
									}
								echo '</div></div>
								<hr>';
							}
						?>
						<a class="w3-margin w3-btn w3-blue" href="javascript:showhide('addCert')"><?php echo $strings['87']; ?> <i class="fa fa-certificate"></i></a>
						<div id="addCert" style="display:<?php if($backToAddCertForm) {echo 'block';} else {echo 'none';} ?>;">
							<form class="w3-container" method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
								<?php
								echo '<input type="hidden" name="formUid" value="' . $_SESSION["secureFormCode"] . '">';
								echo '<input type="hidden" name="formAction" value="addCert">';
								?>
								
								<div class="w3-padding-16">
									<label><?php echo $strings['88']; ?></label>
									<input class="w3-input" type="text" name="newCertName"<?php if(isset($newCertName)) { echo ' value="' . $newCertName . '"'; } else { echo ' value="' . $_SESSION['fullName'] . '"'; } if($backToAddCertForm) { echo ' autofocus';} ?>>
									<?php if (isset($newCertNameError)) { echo '<div class="w3-text-red">' . $newCertNameError . '</div>';} ?>
								</div>
								<div class="w3-padding-16">
									<label><?php echo $strings['89']; ?></label>
									<input class="w3-input" type="text" name="newCertEmail"<?php if(isset($newCertEmail)) { echo ' value="' . $newCertEmail . '"'; } else { echo ' value="' . $_SESSION['emailAddress'] . '"'; } ?>>
									<?php if (isset($newCertEmailError)) { echo '<div class="w3-text-red">' . $newCertEmailError . '</div>';} ?>
								</div>
								<div class="w3-padding-16">
									<label><?php echo $strings['90']; ?></label>
									<select class="w3-select" name="newCertCountry">
									<?php echo countryList($newCertCountry); ?>
									</select>
									<?php if (isset($newCertCountryError)) { echo '<div class="w3-text-red">' . $newCertCountryError . '</div>';} ?>
								</div>
								<div class="w3-padding-16">
									<label><?php echo $strings['91']; ?></label>
									<input class="w3-input" type="text" name="newCertState"<?php if(isset($newCertState)) { echo ' value="' . $newCertState . '"'; } else { echo ' value="' . $_SESSION['certState'] . '"'; } ?>>
									<?php if (isset($newCertStateError)) { echo '<div class="w3-text-red">' . $newCertStateError . '</div>';} ?>
								</div>
								<div class="w3-padding-16">
									<label><?php echo $strings['92']; ?></label>
									<input class="w3-input" type="text" name="newCertCity"<?php if(isset($newCertCity)) { echo ' value="' . $newCertCity . '"'; } else { echo ' value="' . $_SESSION['certCity'] . '"'; } ?>>
									<?php if (isset($newCertCityError)) { echo '<div class="w3-text-red">' . $newCertCityError . '</div>';} ?>
								</div>
								<div class="w3-padding-16">
									<label><?php echo $strings['93']; ?></label>
									<input class="w3-input" type="password" name="newCertPassword" id="newCertPassword"<?php if(isset($newCertPassword)) { echo ' value="' . $newCertPassword . '"'; } ?>>
									<?php if (isset($newCertPasswordError)) { echo '<div class="w3-text-red">' . $newCertPasswordError . '</div>';} ?>
									<span class="w3-small"><?php echo $strings['178']; ?></span>
								</div>
								<div class="w3-padding-16">
									<label><?php echo $strings['94']; ?></label>
									<input class="w3-input" type="password" name="newCertPasswordRetype"<?php if(isset($newCertPasswordRetype)) { echo ' value="' . $newCertPasswordRetype . '"'; } ?>>
									<?php if (isset($newCertPasswordRetypeError)) { echo '<div class="w3-text-red">' . $newCertPasswordRetypeError . '</div>';} ?>
								</div>
								
								<div class="w3-padding-16 w3-right">
									<a class="w3-btn w3-red w3-margin-bottom" href="javascript:showhide('addCert')"><?php echo $strings['44']; ?> <i class="fa fa-undo"></i></a>
									<input class="w3-btn w3-blue w3-margin-bottom" type="submit" value="<?php echo $strings['95']; ?>" name="submit">
								</div>
							</form>
						</div>
					</div>
				</div>
				<div class="w3-card-4 w3-margin">
					<div class="w3-green w3-center" style="padding: 7px">
						<h3 style="margin: 0px;"><a href="javascript:showhidePlus('sessions', 'sessionsPlus')"><i class="fa fa-plus-square" id="sessionsPlus"></i></a> <a href="javascript:showhidePlus('sessions', 'sessionsPlus')"><?php echo $strings['231']; ?></a></h3>
					</div>
					<div class="w3-container" id="sessions" style="display:none;">
						<?php
							$sql = "SELECT id, expires, cipherSuite, iv, session, tag FROM sessions WHERE userId='$_SESSION[userId]'";
							$db_rawSessions = $conn -> query($sql);
							while($db_session = $db_rawSessions->fetch_assoc()) {
								// first, decrypt record...
								$jsonSessionData = decryptDataNextGen($db_session['iv'], $_SESSION['encryptionKey'], $db_session['session'], $db_session['cipherSuite'], $db_session['tag']);
								$session = json_decode($jsonSessionData, true);
								
								//Then get cert data
								$sql = "SELECT id, serial, revoked, ivCertData, encryptedCertData, tagCertData, cipherSuiteCertData FROM certs WHERE userId='$_SESSION[userId]' AND id='$session[certificate]'";
								$db_rawCerts = $conn->query($sql);
								$db_cert = $db_rawCerts -> fetch_assoc();
								$jsonCertData = decryptDataNextGen($db_cert['ivCertData'], $_SESSION['encryptionKey'], $db_cert['encryptedCertData'], $db_cert['cipherSuiteCertData'], $db_cert['tagCertData']);
								$cert = json_decode($jsonCertData, true);
								
								$device = detectUserDevice($session['userAgent']);
								
								$sessionIpAddress = utf8_decode($session['ipAddress']);
								$sessionLastActivity = utf8_decode($session['lastActivity']);
								$sessionUserAgent = utf8_decode($session['userAgent']);
								$certFullName = utf8_decode($cert['fullName']);
								
								echo '<div class="w3-padding">
									<div class="w3-xxlarge">' . $device['typeIcon'] . ' ' . $device['osIcon'] . ' ' . $device['browserIcon'] . ' <span class="w3-small">(<a href="javascript:showhide(\'sessionDetail' . $db_session['id'] . '\')">' . $strings['238'] . '</a>)</span></div>
									<div id="sessionDetail' . $db_session['id'] . '" style="display:none" class="w3-small w3-padding">' . $sessionUserAgent . '</div>';
									if ($_SESSION['sessionId'] == $db_session['id']) { echo '<div class="w3-text-green"><i class="fa fa-user"></i> ' . $strings['237'] . '</div>';}
									if ($session['private'] == '0') { echo '<div class="w3-text-orange"><i class="fa fa-globe"></i> ' . $strings['239'] . '</div>';}
									echo $strings['232'] . ': ' . $sessionIpAddress . '<br>
									' . $strings['233'] . ': <a href="javascript:showhide(\'certDetail' . $db_session['id'] . '\')">' . $certFullName . '</a><br>
									<div class="w3-padding" id="certDetail' . $db_session['id'] . '" style="display:none;">' . $strings['79'] . ': ' . $db_cert['serial'] . '<br>
									' . $strings['81'] . ': ' . $cert['validTo'] . '
									</div>
									' . $strings['236'] . ': ' . $sessionLastActivity . '<br>
									' . $strings['234'] . ': ' . $db_session['expires'] . '<br>';
									if ($_SESSION['sessionId'] != $db_session['id']) { echo '<br><a class="w3-btn w3-red" href="' . $_SERVER['PHP_SELF'] . '?logoutSession=' . $db_session['id'] . '">' . $strings['38'] . '</a><br>'; }
								echo '</div>
								<hr>';
							}
						?>
					</div>
				</div>
				<?php
					$sql = "SELECT id, userIv, userData, userTag, userCipherSuite, timestamp, access, downloads FROM downloadLinks WHERE userId='$_SESSION[userId]'";
					$db_rawLinks = $conn -> query($sql);
					if(mysqli_num_rows($db_rawLinks) > 0) {
				?>
				<!-- SHARED FILES -->
				<div class="w3-card-4 w3-margin">
					<div class="w3-green w3-center" style="padding: 7px">
						<h3 style="margin: 0px;"><a href="javascript:showhidePlus('sharedFiles', 'sharedFilesPlus')"><i class="fa fa-plus-square" id="sharedFilesPlus"></i></a> <a href="javascript:showhidePlus('sharedFiles', 'sharedFilesPlus')"><?php echo $strings['333']; ?></a></h3>
					</div>
					<div class="w3-container" id="sharedFiles" style="display: none">
						<p class="w3-center"><?php echo $strings['334']; ?></p>
						<?php
						while($db_link = $db_rawLinks -> fetch_assoc()) {
							$jsonLinkData = decryptDataNextGen($db_link['userIv'], $_SESSION['encryptionKey'], $db_link['userData'], $db_link['userCipherSuite'], $db_link['userTag']);
							$linkData = json_decode($jsonLinkData, true);
							if($linkData['type'] == 'certificate') {
								$linkCertArray[] = array('id' => $db_link['id'], 'recipient' => utf8_decode($linkData['recipient']), 'access' => $db_link['access'], 'downloads' => $db_link['downloads'], 'fullName' => utf8_decode($linkData['fullName']), 'emailAddress' => utf8_decode($linkData['emailAddress']), 'fingerprint' => utf8_decode($linkData['fingerprint']), 'serial' => utf8_decode($linkData['serial']), 'validFrom' => utf8_decode($linkData['validFrom']), 'validTo' => utf8_decode($linkData['validTo']), 'city' => utf8_decode($linkData['city']), 'province' => utf8_decode($linkData['province']), 'country' => utf8_decode($linkData['country']), 'linkExpires' => utf8_decode($db_link['timestamp']));
							} elseif($linkData['type'] == 'password') {
								$linkPassArray[] = array('id' => $db_link['id'], 'recipient' => utf8_decode($linkData['recipient']), 'access' => $db_link['access'], 'downloads' => $db_link['downloads'], 'name' => utf8_decode($linkData['name']), 'username' => utf8_decode($linkData['username']), 'favicon' => utf8_decode($linkData['favicon']), 'faviconType' => utf8_decode($linkData['faviconType']), 'linkExpires' => utf8_decode($db_link['timestamp']));
							} elseif($linkData['type'] == 'note') {
								$linkNoteArray[] = array('id' => $db_link['id'], 'recipient' => utf8_decode($linkData['recipient']), 'access' => $db_link['access'], 'downloads' => $db_link['downloads'], 'name' => utf8_decode($linkData['name']), 'linkExpires' => utf8_decode($db_link['timestamp']));
							} elseif($linkData['type'] == 'file') {
								$linkFileArray[] = array('id' => $db_link['id'], 'recipient' => utf8_decode($linkData['recipient']), 'access' => $db_link['access'], 'downloads' => $db_link['downloads'], 'description' => utf8_decode($linkData['description']), 'fileName' => utf8_decode($linkData['fileName']), 'fileType' => utf8_decode($linkData['fileType']), 'fileSize' =>  utf8_decode($linkData['fileSize']), 'linkExpires' => utf8_decode($db_link['timestamp']));
							}
						}
						
						if(isset($linkCertArray)){
							echo '<hr>';
							echo '<h4 class="w3-center">' . $strings['335'] . '</h4>';
							foreach($linkCertArray as $linkCert){
								$linkExpiration = date('Y-m-d H:i:s', strtotime($linkCert['linkExpires']) + 86400);
								if($linkCert['access'] == '' || $linkCert['access'] == '0' || is_null($linkCert['access'])) { $linkAccessed = $strings['348']; } elseif($linkCert['access'] == '1') { $linkAccessed = $strings['349']; } else { $linkAccessed = $linkCert['access'] . ' ' . $strings['350']; }
								if($linkCert['downloads'] == '' || $linkCert['downloads'] == '0' || is_null($linkCert['downloads'])) { $fileDownloaded = $strings['348']; } elseif($linkCert['downloads'] == '1') { $fileDownloaded = $strings['349']; } else { $fileDownloaded = $linkCert['downloads'] . ' ' . $strings['350']; }
								
								echo '<div class="w3-padding">
									<i class="fa fa-id-card"></i> ' . $linkCert['fullName'] . '<br>
									' . $strings['79'] . ': ' . $linkCert['serial'] . '<br>
									' . $strings['336'] . ': <a href="mailto:' . $linkCert['recipient'] . '">' . $linkCert['recipient'] . '</a><br>
									' . $strings['337'] . ': ' . $linkExpiration . '<br>
									' . $strings['346'] . ': ' . $linkAccessed . '<br>
									' . $strings['347'] . ': ' . $fileDownloaded . '<br>
									<a class="w3-btn w3-red w3-margin-bottom" href="javascript:void(0)" onclick="javascript:deleteLink(\'' . $linkCert['id'] . '\', \'' . javascriptEscape($linkCert['fullName']) . '\')">' . $strings['315'] . '</a>
									<div style="height:10px;"></div>
								</div>';
							}
						}
						
						if(isset($linkPassArray)) {
							echo '<hr>';
							echo '<h4 class="w3-center">' . $strings['340'] . '</h4>';
							foreach($linkPassArray as $linkPass) {
								$linkExpiration = date('Y-m-d H:i:s', strtotime($linkPass['linkExpires']) + 86400);
								if($linkPass['access'] == '' || $linkPass['access'] == '0' || is_null($linkPass['access'])) { $linkAccessed = $strings['348']; } elseif($linkPass['access'] == '1') { $linkAccessed = $strings['349']; } else { $linkAccessed = $linkPass['access'] . ' ' . $strings['350']; }
								if($linkPass['downloads'] == '' || $linkPass['downloads'] == '0' || is_null($linkPass['downloads'])) { $fileDownloaded = $strings['348']; } elseif($linkPass['downloads'] == '1') { $fileDownloaded = $strings['349']; } else { $fileDownloaded = $linkPass['downloads'] . ' ' . $strings['350']; }
								
								echo '<div class="w3-padding">';
									if ($linkPass['favicon'] != '') {
										echo '<img class="favicon" src="data:' . $linkPass['faviconType'] . ';base64, ' . $linkPass['favicon'] . '" /> ';
									} else {
										echo '<i class="fa fa-user"></i> ';
									}
								echo $linkPass['name'] . '<br>
								' . $strings['40'] . ': ' . $linkPass['username'] . '<br>
								' . $strings['336'] . ': <a href="mailto:' . $linkPass['recipient'] . '">' . $linkPass['recipient'] . '</a><br>
								' . $strings['337'] . ': ' . $linkExpiration . '<br>
									' . $strings['346'] . ': ' . $linkAccessed . '<br>
									' . $strings['347'] . ': ' . $fileDownloaded . '<br>
									<a class="w3-btn w3-red w3-margin-bottom" href="javascript:void(0)" onclick="javascript:deleteLink(\'' . $linkPass['id'] . '\', \'' . javascriptEscape($linkPass['name']) . '\')">' . $strings['315'] . '</a>
									<div style="height:10px;"></div>
								</div>';
							}
						}
						
						if(isset($linkNoteArray)) {
							echo '<hr>';
							echo '<h4 class="w3-center">' . $strings['341'] . '</h4>';
							foreach($linkNoteArray as $linkNote) {
								$linkExpiration = date('Y-m-d H:i:s', strtotime($linkNote['linkExpires']) + 86400);
								if($linkNote['access'] == '' || $linkNote['access'] == '0' || is_null($linkNote['access'])) { $linkAccessed = $strings['348']; } elseif($linkNote['access'] == '1') { $linkAccessed = $strings['349']; } else { $linkAccessed = $linkNote['access'] . ' ' . $strings['350']; }
								if($linkNote['downloads'] == '' || $linkNote['downloads'] == '0' || is_null($linkNote['downloads'])) { $fileDownloaded = $strings['348']; } elseif($linkNote['downloads'] == '1') { $fileDownloaded = $strings['349']; } else { $fileDownloaded = $linkNote['downloads'] . ' ' . $strings['350']; }
								
								echo '<div class="w3-padding">
									<i class="fa fa-pencil-square-o"></i> ' . $linkNote['name'] . '<br>
									' . $strings['336'] . ': <a href="mailto:' . $linkNote['recipient'] . '">' . $linkNote['recipient'] . '</a><br>
									' . $strings['337'] . ': ' . $linkExpiration . '<br>
									' . $strings['346'] . ': ' . $linkAccessed . '<br>
									' . $strings['347'] . ': ' . $fileDownloaded . '<br>
									<a class="w3-btn w3-red w3-margin-bottom" href="javascript:void(0)" onclick="javascript:deleteLink(\'' . $linkNote['id'] . '\', \'' . javascriptEscape($linkNote['name']) . '\')">' . $strings['315'] . '</a>
									<div style="height:10px;"></div>
								</div>';
							}
						}
						
						if(isset($linkFileArray)) {
							echo '<hr>';
							echo '<h4 class="w3-center">' . $strings['342'] . '</h4>';
							foreach($linkFileArray as $linkFile) {
								$fileSize = $linkFile['fileSize'];
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
									$fileStoredFileSize = $fileStoredFileSize / 1024 / 1024;
								}
								$linkExpiration = date('Y-m-d H:i:s', strtotime($linkFile['linkExpires']) + 86400);
								if($linkFile['access'] == '' || $linkFile['access'] == '0' || is_null($linkFile['access'])) { $linkAccessed = $strings['348']; } elseif($linkFile['access'] == '1') { $linkAccessed = $strings['349']; } else { $linkAccessed = $linkFile['access'] . ' ' . $strings['350']; }
								if($linkFile['downloads'] == '' || $linkFile['downloads'] == '0' || is_null($linkFile['downloads'])) { $fileDownloaded = $strings['348']; } elseif($linkFile['downloads'] == '1') { $fileDownloaded = $strings['349']; } else { $fileDownloaded = $linkFile['downloads'] . ' ' . $strings['350']; }
								
								echo '<div class="w3-padding">
									<i class="fa fa-file-o"></i> ' . $linkFile['description'] . '<br>
								' . $strings['196'] . ': ' . $linkFile['fileName'] . '<br>
								' . $strings['316'] . ': ' . $linkFile['fileType'] . '<br>
								' . $strings['197'] . ': ' . $fileSize . $fileSizeUnit . '<br>
								' . $strings['336'] . ': <a href="mailto:' . $linkPass['recipient'] . '">' . $linkPass['recipient'] . '</a><br>
								' . $strings['337'] . ': ' . $linkExpiration . '<br>
								' . $strings['346'] . ': ' . $linkAccessed . '<br>
								' . $strings['347'] . ': ' . $fileDownloaded . '<br>
									<a class="w3-btn w3-red w3-margin-bottom" href="javascript:void(0)" onclick="javascript:deleteLink(\'' . $linkFile['id'] . '\', \'' . javascriptEscape($linkFile['description']) . '\')">' . $strings['315'] . '</a>
									<div style="height:10px;"></div>
								</div>';
							}
						}
						?>
					</div>
				</div>
				<?php } ?>
				
				<!-- TOOLS -->
				<div class="w3-card-4 w3-margin">
					<div class="w3-container">
						<h3 class="w3-center"><?php echo $strings['96']; ?> <i class="fa fa-wrench"></i></h3>
						<hr>
						<div class="w3-container w3-center">
							<h4><?php echo $strings['151']; ?></h4>
							<pre><?php echo generateRandomString(25, 'aA1!') ?></pre>
							<pre><?php echo generateRandomString(25, 'aA1') ?></pre>
							<p class="w3-padding w3-small"><?php echo $strings['153']; ?></p>
						</div>
						<hr>
						<div class="w3-container w3-center">
							<a class="w3-btn w3-blue w3-margin" href="profile.php?download=csvPasswords&reload=no"><i class="w3-margin w3-xxxlarge fa fa-file-text"></i><br><?php echo $strings['97']; ?></a>
							<?php if($canUpdateEncryptionKey) { ?>
								<a class="w3-btn w3-blue w3-margin" href="profile.php?control=generateNewEncryptionKey"><i class="w3-margin w3-xxxlarge fa fa-key"></i><br><?php echo $strings['227']; ?></a>
							<?php } else { ?>
								<a class="w3-btn w3-light-grey w3-margin" title="<?php echo $strings['229']; ?>" href="#"><i class="w3-margin w3-xxxlarge fa fa-key"></i><br><?php echo $strings['228']; ?></a>
							<?php } ?>
							<a class="w3-btn w3-red w3-margin" href="profile.php?control=deleteAccount"><i class="w3-margin w3-xxxlarge fa fa-user-times"></i><br><?php echo $strings['270']; ?></a>
						</div>
						<hr>
					</div>
				</div>
			</div>
		</div>
		<?php include 'footer.php'; ?>
	</body>
</html>