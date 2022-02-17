<?php

//UNCOMMENT FOR DEBUG PURPOSES
/*
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
*/


include "functions.php";
$currentPage = htmlspecialchars($_SERVER["PHP_SELF"]);
$parentPage = 'profile.php';

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

	if(isset($_SESSION["token"])) {
		if (authenticateToken()) {
			$loadContent = true;
			
			if(isset($_GET["setLanguage"])) {
				if ($_GET["setLanguage"] == "en") {
					$_SESSION["language"] = "en";
				} elseif($_GET["setLanguage"] == "fr") {
					$_SESSION["language"] = "fr";
				} else {
					$_SESSION["language"] = "en";
				}
				// Update language in certificate...
				changeCertLanguage($_SESSION['language']);
				$loadContent = false;
				header("HTTP/1.1 301 Moved Permanently");
				header("Location: $currentPage");
			}
			
			if(in_array($_SESSION['userId'], $config['cmsAdmin'])) {
				$isAdmin = true;
			} else {
				$isAdmin = false;
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
			
			$siteTitle = $strings['35'];
			$pageTitle = $siteTitle . ' - ' . $_SESSION["fullName"] . ' - ' . $strings['251'];
			$pageDescription = 
			$pageKeyworkds = 
			$pageIndex = 'noindex';
			$pageFollow = 'nofollow';
			
			if($_GET["reload"] == "no") {
				// Do nothing...
			} else {
				if (!secureForm($_POST["formUid"])) {
					$backToForm = true;
					$alert_message = $strings['351'];
					$loadContent = true;
				}
			}
			
			// Is this a business user?
			if($_SESSION['businessAccount']) {
				$businessInfo = getBusinessInfo($_SESSION['userId']);
				if($businessInfo['status'] == 'uninitialized') {
					$loadContent = false;
					header("HTTP/1.1 301 Moved Permanently");
					header("Location: business.php");
				} else {
					// Business is initialized! load business settings for user...
										
				}
			} else {
				
			}
			
			// ************** Post form ADD PASSWORD ENTRY **************
			if($_SERVER["REQUEST_METHOD"] == "POST" && $_POST["formAction"] == "addEntry" && $backToForm != true) {
				$newEntryName = htmlOutput($_POST["newEntryName"]);
				$newEntryUsername = htmlOutput($_POST["newEntryUsername"]);
				$newEntryPassword = htmlOutput($_POST["newEntryPassword"]);
				$newEntryUrl = filter_var($_POST["newEntryUrl"], FILTER_SANITIZE_URL);
				$newEntryComment = htmlOutput($_POST["newEntryComment"]);
				
				if(strlen($newEntryName) > 256) {
					$backToForm = true;
					$newEntryNameError = $strings['52'];
				} elseif(strlen($newEntryName) < 1) {
					$backToForm = true;
					$newEntryNameError = $strings['53'];
				}
				
				if(strlen($newEntryUsername) > 320) {
					$backToForm = true;
					$newEntryUsernameError = $strings['54'];
				}
				
				if(strlen($newEntryPassword) < 1) {
					$backToForm = true;
					$newEntryPasswordError = $strings['55'];
				}
				
				if(strlen($newEntryUrl) > 0) {
					if(substr($newEntryUrl, 0, 7) == 'http://' || substr($newEntryUrl, 0, 8) == 'https://') {
						$newEntryUrl = $newEntryUrl;
					} else {
						$newEntryUrl = 'https://' . $newEntryUrl;
					}
					
					if(filter_var($newEntryUrl, FILTER_VALIDATE_URL)) {
						if(strlen($newEntryUrl) > 2048) {
							$backToForm = true;
							$newEntryUrlError = $strings['266'];
						}
					} else {
						$backToForm = true;
						$newEntryUrlError = $strings['267'];
					}
				}
				
				if($backToForm == true) {
					$backToAddEntryForm = true;
				} else {
					// Validation successful, encrypt entry then add it to database.
					$entry['type'] = utf8_encode('password');
					$entry['timestamp'] = date('Y-m-d H:i:s');
					$entry['name'] = utf8_encode($newEntryName);
					$entry['username'] = utf8_encode($newEntryUsername);
					$entry['password'] = utf8_encode($newEntryPassword);
					$entry['url'] = utf8_encode($newEntryUrl);
					$entry['comment'] = utf8_encode($newEntryComment);
					
					if(strlen($newEntryUrl) > 0) {
						$favicon = retrieveFaviconNextGen($newEntryUrl);
						if($favicon !== false || $favicon['image'] != '') {
							$entry['faviconType'] = utf8_encode($favicon['type']);
							$entry['favicon'] = utf8_encode($favicon['image']);
						}
					} else {
						$favicon = faviconBasedOnName($newEntryName);
						if($favicon !== false || $favicon['image'] != '') {
							$entry['faviconType'] = utf8_encode($favicon['type']);
							$entry['favicon'] = utf8_encode($favicon['image']);
						}
					}
					
					$jsonEntry = json_encode($entry);
					
					$encryptedEntry = encryptDataNextGen($_SESSION['encryptionKey'], $jsonEntry, $config['currentCipherSuite']);
					
					$iv = $encryptedEntry['iv'];
					$encryptedData = $encryptedEntry['data'];
					$tag = $encryptedEntry['tag'];
					$entrySizeMb = strlen($encryptedData) / 1024 / 1024;
					$userDataSizeAfter = $_SESSION['userDataSize'] + $entrySizeMb;
					
					if ($userDataSizeAfter <= $_SESSION['userDataCap']) {
						if(strlen($encryptedData) < 4194304) {
							$sql = "INSERT INTO entries (userId, cipherSuite, iv, entry, tag) VALUES ('$_SESSION[userId]', '$config[currentCipherSuite]', '$iv', '$encryptedData', '$tag')";
							if ($conn->query($sql) === true) {
								$message = $strings['56'];
							} else {
								$message = $strings['57'] . '<br>';
								$message .= $conn->error;
							}
							
							// After adding entry to database, unset new entry variables to clear form.
							unset($newEntryName);
							unset($newEntryUsername);
							unset($newEntryPassword);
							unset($newEntryComment);
							unset($newEntryUrl);
						} else {
							$stringLength = strlen($encryptedData) / 1024 / 1024;
							$stringLength = round($stringLength, 2);
							$message = '<span style="color: red;">' . $strings['172'] . '<br>';
							$message .= $strings['173'] . ' ' . $stringLength . $strings['174'] . '</span>';
						}
					} else {
						// User would burst quota. Prevent write!
						$message = '<span style="color: red;">' . $strings['220'] . '</span>';
					}
				}
			}
			
			// ************** Post form ADD NOTE ENTRY **************
			if($_SERVER["REQUEST_METHOD"] == "POST" && $_POST["formAction"] == "addNoteEntry" && $backToForm != true) {
				$newNoteEntryName = htmlOutput($_POST["newNoteEntryName"]);
				$newNoteEntryContent = htmlOutput($_POST["newNoteEntryContent"]);
				
				if(strlen($newNoteEntryName) > 256) {
					$backToForm = true;
					$newNoteEntryNameError = $strings['52'];
				} elseif(strlen($newNoteEntryName) < 1) {
					$backToForm = true;
					$newNoteEntryNameError = $strings['53'];
				}
				
				if($backToForm == true) {
					$backToAddNoteEntryForm = true;
				} else {
					// Validation successful, encrypt entry then add it to database.
					
					$entry['type'] = utf8_encode('note');
					$entry['timestamp'] = utf8_encode(date('Y-m-d H:i:s'));
					$entry['name'] = utf8_encode($newNoteEntryName);
					$entry['content'] = utf8_encode($newNoteEntryContent);
					
					$jsonEntry = json_encode($entry);
					
					$encryptedEntry = encryptDataNextGen($_SESSION['encryptionKey'], $jsonEntry, $config['currentCipherSuite']);
					
					$iv = $encryptedEntry['iv'];
					$encryptedData = $encryptedEntry['data'];
					$tag = $encryptedEntry['tag'];
					$entrySizeMb = strlen($encryptedData) / 1024 / 1024;
					$userDataSizeAfter = $_SESSION['userDataSize'] + $entrySizeMb;
					
					if ($userDataSizeAfter <= $_SESSION['userDataCap']) {
						if(strlen($encryptedData) < 4194304) {
							$sql = "INSERT INTO entries (userId, cipherSuite, iv, entry, tag) VALUES ('$_SESSION[userId]', '$config[currentCipherSuite]', '$iv', '$encryptedData', '$tag')";
							if ($conn->query($sql) === true) {
								$message = $strings['150'];
							} else {
								$message = $strings['57'] . '<br>';
								$message .= $conn->error;
							}
							// After adding entry to database, unset new entry variables to clear form.
							unset($newNoteEntryName);
							unset($newNoteEntryContent);
						} else {
							$stringLength = strlen($encryptedData) / 1024 / 1024;
							$stringLength = round($stringLength, 2);
							$message = '<span style="color: red;">' . $strings['172'] . '<br>';
							$message .= $strings['173'] . ' ' . $stringLength . $strings['174'] . '</span>';
						}
					} else {
						// User would burst quota. Prevent write!
						$message = '<span style="color: red;">' . $strings['220'] . '</span>';
					}
				}
			}
			
			// ************** Post form ADD FILE ENTRY **************
			if($_SERVER["REQUEST_METHOD"] == "POST" && $_POST["formAction"] == "addFileEntry" && $backToForm != true) {
				$newFileDescription = htmlOutput($_POST["newFileDescription"]);
				$newFileName = htmlOutput($_FILES['newFile']['name']);
				$newFileType = htmlOutput($_FILES['newFile']['type']);
				$newFileSize = htmlOutput($_FILES['newFile']['size']);
				$newFileContent = file_get_contents($_FILES['newFile']['tmp_name']);
				
				
				/*
				echo $newFileDescription . '<br>';
				echo $newFileName . '<br>';
				echo $newFileType . '<br>';
				echo $newFileSize . '<br>';
				*/
				
				
				if(strlen($newFileDescription) > 512) {
					$backToForm = true;
					$newFileDescriptionError = $strings['190'];
				} elseif(strlen($newFileDescription) < 1) {
					$backToForm = true;
					$newFileDescriptionError = $strings['191'];
				}
				
				if($newFileName == '' || is_null($newFileName) || !isset($newFileName)) {
					$backToForm = true;
					$newFileError = $strings['192'];
				} elseif($newFileSize == '0') {
					$backToForm = true;
					$newFileError = $strings['193'];
				} elseif($newFileSize > 15728640) {
					$backToForm = true;
					$newFileError = $strings['194'];
				}
				
				if($backToForm == true) {
					$backToAddFileEntryForm = true;
				} else {
					
					// Validation successful, encrypt entry then add it to database.
					
					$entry['type'] = utf8_encode('file');
					$entry['timestamp'] = date('Y-m-d H:i:s');
					$entry['description'] = utf8_encode($newFileDescription);
					$entry['fileName'] = utf8_encode($newFileName);
					$entry['fileType'] = utf8_encode($newFileType);
					$entry['fileSize'] = utf8_encode($newFileSize);
					$entry['storedFileSize'] = strlen($newFileContent) * 133 / 100;
					$entry['fileMd5'] = utf8_encode(hash('md5', $newFileContent));
					$entry['fileSha1'] = utf8_encode(hash('sha1', $newFileContent));
					$entry['fileSha256'] = utf8_encode(hash('sha256', $newFileContent));
					
					$jsonEntry = json_encode($entry);
					
					$encryptedEntry = encryptDataNextGen($_SESSION['encryptionKey'], $jsonEntry, $config['currentCipherSuite']);
					$encryptedEntryIv = $encryptedEntry['iv'];
					$encryptedEntryData = $encryptedEntry['data'];
					$encryptedEntryTag = $encryptedEntry['tag'];
					
					$encryptedFile = encryptDataNextGen($_SESSION['encryptionKey'], $newFileContent, $config['currentCipherSuite']);
					$encryptedFileIv = $encryptedFile['iv'];
					$encryptedFileContent = $encryptedFile['data'];
					$encryptedFileTag = $encryptedFile['tag'];
					
					$entrySize = strlen($encryptedEntryData) + strlen($encryptedFileContent);
					$entrySizeMb = $entrySize / 1024 / 1024;
					$userDataSizeAfter = $_SESSION['userDataSize'] + $entrySizeMb;
					
					if ($userDataSizeAfter <= $_SESSION['userDataCap']) {
						$sql = "INSERT INTO entries (userId, cipherSuite, iv, entry, tag) VALUES ('$_SESSION[userId]', '$config[currentCipherSuite]', '$encryptedEntryIv', '$encryptedEntryData', '$encryptedEntryTag')";
						if ($conn->query($sql) === true) {
							$entryId = mysqli_insert_id($conn);
							
							$sql = "INSERT INTO files (entryId, cipherSuite, iv, content, originalDataFormat, tag) VALUES ('$entryId', '$config[currentCipherSuite]', '$encryptedFileIv', '$encryptedFileContent', 'raw', '$encryptedFileTag')";
							if ($conn->query($sql) === true) {
								$message = $strings['195'];
							} else {
								$message = $strings['57'] . '<br>';
								$message .= $conn->error;
							}
						} else {
							$message = $strings['57'] . '<br>';
							$message .= $conn->error;
							$message .= $sql;
						}
						// After adding entry to database, unset new entry variables to clear form.
						unset($newFileDescription);
					} else {
						// User would burst quota. Prevent write!
						$message = '<span style="color: red;">' . $strings['220'] . '</span>';
					}
				}
			}
			
			// ************** Post form ADD NEW FILES NEXTGEN **************
			if($_SERVER["REQUEST_METHOD"] == "POST" && $_POST["formAction"] == "addFileEntryNextGen" && $backToForm != true) {
				$newFilegroupDescription = htmlOutput($_POST["newFileDescription"]);
				$numFiles = count($_FILES['fileUploads']['tmp_name']);
				$fileIndex = 0;
				
				if(strlen($newFilegroupDescription) > 512) {
					$backToForm = true;
					$newFilegroupDescriptionError = $strings['190'];
				} elseif(strlen($newFilegroupDescription) < 1) {
					$backToForm = true;
					$newFilegroupDescriptionError = $strings['191'];
				}
				
				if($numFiles < 1) {
					$backToForm = true;
					$message = 'Vous devez t&eacute;l&eacute;charger au moins 1 fichier';
				} elseif($numFiles > 50) {
					$backToForm = true;
					$message = 'Vous ne pouvez t&eacute;l&eacute;charger plus de 50 fichiers &agrave; la fois';
				}
				
				if($backToForm == true) {
					
				} else {
					$fileList = array();
					while($numFiles > $fileIndex) {
						$newFileName = $_FILES['fileUploads']['name'][$fileIndex];
						$newFileType = $_FILES['fileUploads']['type'][$fileIndex];
						$newFileContent = file_get_contents($_FILES['fileUploads']['tmp_name'][$fileIndex]);
						$newFileSize = strlen($newFileContent);
						
						if($newFileName == '' || is_null($newFileName) || !isset($newFileName) || $newFileSize == 0) {
							// File is invalid. Silently skip
						} else {
							$encryptedFile = encryptDataNextGen($_SESSION['encryptionKey'], $newFileContent, $config['currentCipherSuite']);
							$encryptedFileIv = $encryptedFile['iv'];
							$encryptedFileContent = $encryptedFile['data'];
							$encryptedFileTag = $encryptedFile['tag'];
							
							$file['filename'] = $newFileName;
							$file['filetype'] = $newFileType;
							$file['filesize'] = $newFileSize;
							
							// $files[] = 
							
						}
					}
				}
			}
			
			// ************** Post form EDIT PASSWORD ENTRY **************
			if($_SERVER["REQUEST_METHOD"] == "POST" && $_POST["formAction"] == "editEntry" && $backToForm != true && ctype_digit($_POST['editEntry'])) {
				$editEntry = $_POST['editEntry'];
				$editEntryName = htmlOutput($_POST["editEntryName"]);
				$editEntryUsername = htmlOutput($_POST["editEntryUsername"]);
				$editEntryPassword = htmlOutput($_POST["editEntryPassword"]);
				$editEntryUrl = filter_var($_POST["editEntryUrl"], FILTER_SANITIZE_URL);
				$editEntryComment = htmlOutput($_POST["editEntryComment"]);
				
				// Before processing, make sure the record belongs to this user...
				$escapedEntryId = mysqli_real_escape_string($conn, $editEntry);
				$sql = "SELECT id, cipherSuite, iv, entry, tag FROM entries WHERE id='$escapedEntryId' AND userId='$_SESSION[userId]'";
				$db_rawEntry = $conn -> query($sql);
				if (mysqli_num_rows($db_rawEntry) > 0) {
					//ALL GOOD, process...
					
					if(strlen($editEntryName) > 256) {
						$backToForm = true;
						$editEntryNameError = $strings['52'];
					} elseif(strlen($editEntryName) < 1) {
						$backToForm = true;
						$editEntryNameError = $strings['53'];
					}
					
					if(strlen($editEntryUsername) > 320) {
						$backToForm = true;
						$editEntryUsernameError = $strings['54'];
					}
					
					if(strlen($editEntryPassword) < 1) {
						$backToForm = true;
						$editEntryPasswordError = $strings['55'];
					}
					
					if(strlen($editEntryUrl) > 0) {
					if(substr($editEntryUrl, 0, 7) == 'http://' || substr($editEntryUrl, 0, 8) == 'https://') {
						$editEntryUrl = $editEntryUrl;
					} else {
						$editEntryUrl = 'https://' . $editEntryUrl;
					}
					
					if(filter_var($editEntryUrl, FILTER_VALIDATE_URL)) {
						if(strlen($editEntryUrl) > 2048) {
							$backToForm = true;
							$editEntryUrlError = $strings['266'];
						}
					} else {
						$backToForm = true;
						$editEntryUrlError = $strings['267'];
					}
				}
					
					if($backToForm == true) {
						$backToEditEntryForm = true;
					} else {
						// echo 'Validation successful... Update record!';
						// first, extract current record before updating.
						$db_entry = $db_rawEntry -> fetch_assoc();
						$jsonEntry = decryptDataNextGen($db_entry['iv'], $_SESSION['encryptionKey'], $db_entry['entry'], $db_entry['cipherSuite'], $db_entry['tag']);
						$entry = json_decode($jsonEntry, true);
						
						$entry['type'] = utf8_encode('password');
						$entry['name'] = utf8_encode($editEntryName);
						$entry['username'] = utf8_encode($editEntryUsername);
						$entry['password'] = utf8_encode($editEntryPassword);
						$entry['url'] = utf8_encode($editEntryUrl);
						$entry['comment'] = utf8_encode($editEntryComment);
						$entry['lastModified'] = utf8_encode(date('Y-m-d H:i:s'));
					
						if(strlen($editEntryUrl) > 0) {
							$favicon = retrieveFaviconNextGen($editEntryUrl);
							if($favicon !== false || $favicon['image'] != '') {
								$entry['faviconType'] = utf8_encode($favicon['type']);
								$entry['favicon'] = utf8_encode($favicon['image']);
							}
						} else {
							$favicon = faviconBasedOnName($editEntryName);
							if($favicon !== false || $favicon['image'] != '') {
								$entry['faviconType'] = utf8_encode($favicon['type']);
								$entry['favicon'] = utf8_encode($favicon['image']);
							}
						}
						
						$jsonEntry = json_encode($entry);
					
						$encryptedEntry = encryptDataNextGen($_SESSION['encryptionKey'], $jsonEntry, $config['currentCipherSuite']);
						
						$iv = $encryptedEntry['iv'];
						$encryptedData = $encryptedEntry['data'];
						$tag = $encryptedEntry['tag'];
						
						$entrySizeMb = strlen($encryptedData) / 1024 / 1024;
						$userDataSizeAfter = $_SESSION['userDataSize'] + $entrySizeMb;
						
						if ($userDataSizeAfter <= $_SESSION['userDataCap']) {
							if(strlen($encryptedData) < 4194304) {
								$sql = "UPDATE entries SET cipherSuite='$config[currentCipherSuite]', iv='$iv', entry='$encryptedData', tag='$tag' WHERE id='$escapedEntryId'";
								if($conn->query($sql)){
									$message = $strings['126'];
								} else {
									$message = $strings['127'];
								}
								
								unset($editEntry);
							} else {
								$stringLength = strlen($encryptedData) / 1024 / 1024;
								$stringLength = round($stringLength, 2);
								$message = '<span style="color: red;">' . $strings['172'] . '<br>';
								$message .= $strings['173'] . ' ' . $stringLength . $strings['174'] . '</span>';
							}
						} else {
							// User would burst quota. Prevent write!
							$message = '<span style="color: red;">' . $strings['220'] . '</span>';
						}
					}
				}
			}
			
			// ************** Post form EDIT NOTE ENTRY **************
			if($_SERVER["REQUEST_METHOD"] == "POST" && $_POST["formAction"] == "editNoteEntry" && $backToForm != true && ctype_digit($_POST['editNoteEntry'])) {
				$editNoteEntry = $_POST['editNoteEntry'];
				$editNoteEntryName = htmlOutput($_POST["editNoteEntryName"]);
				$editNoteEntryContent = htmlOutput($_POST["editNoteEntryContent"]);
				
				// Before processing, make sure the record belongs to this user...
				$escapedEntryId = mysqli_real_escape_string($conn, $editNoteEntry);
				$sql = "SELECT id, cipherSuite, iv, entry, tag FROM entries WHERE id='$escapedEntryId' AND userId='$_SESSION[userId]'";
				$db_rawEntry = $conn -> query($sql);
				if (mysqli_num_rows($db_rawEntry) > 0) {
					//ALL GOOD, process...
					
					if(strlen($editNoteEntryName) > 256) {
						$backToForm = true;
						$editNoteEntryNameError = $strings['52'];
					} elseif(strlen($editNoteEntryName) < 1) {
						$backToForm = true;
						$editNoteEntryNameError = $strings['53'];
					}
					
					if($backToForm == true) {
						$backToEditNoteEntryForm = true;
					} else {
						// echo 'Validation successful... Update record!';
						// first, extract current record before updating.
						$db_entry = $db_rawEntry -> fetch_assoc();
						$jsonEntry = decryptDataNextGen($db_entry['iv'], $_SESSION['encryptionKey'], $db_entry['entry'], $db_entry['cipherSuite'], $db_entry['tag']);
						$entry = json_decode($jsonEntry, true);
						
						$entry['type'] = utf8_encode('note');
						$entry['name'] = utf8_encode($editNoteEntryName);
						$entry['content'] = utf8_encode($editNoteEntryContent);
						$entry['lastModified'] = utf8_encode(date('Y-m-d H:i:s'));
					
						$jsonEntry = json_encode($entry);
					
						$encryptedEntry = encryptDataNextGen($_SESSION['encryptionKey'], $jsonEntry, $config['currentCipherSuite']);
						
						$iv = $encryptedEntry['iv'];
						$encryptedData = $encryptedEntry['data'];
						$tag = $encryptedEntry['tag'];
						
						$entrySizeMb = strlen($encryptedData) / 1024 / 1024;
						$userDataSizeAfter = $_SESSION['userDataSize'] + $entrySizeMb;
						
						if ($userDataSizeAfter <= $_SESSION['userDataCap']) {
							if(strlen($encryptedData) < 4194304) {
								$sql = "UPDATE entries SET cipherSuite='$config[currentCipherSuite]', iv='$iv', entry='$encryptedData', tag='$tag' WHERE id='$escapedEntryId'";
								if($conn->query($sql)){
									$message = $strings['126'];
								} else {
									$message = $strings['127'];
								}
								
								unset($editNoteEntry);
							} else {
								$stringLength = strlen($encryptedData) / 1024 / 1024;
								$stringLength = round($stringLength, 2);
								$message = '<span style="color: red;">' . $strings['172'] . '<br>';
								$message .= $strings['173'] . ' ' . $stringLength . $strings['174'] . '</span>';
							}
						} else {
							// User would burst quota. Prevent write!
							$message = '<span style="color: red;">' . $strings['220'] . '</span>';
						}
					}
				}
			}
			
			// ************** Post form EDIT FILE ENTRY **************
			if($_SERVER["REQUEST_METHOD"] == "POST" && $_POST["formAction"] == "editFileEntry" && $backToForm != true && ctype_digit($_POST['editFileEntry'])) {
				// var_dump($_POST);
				$editFileEntry = $_POST['editFileEntry'];
				$editFileDescription = htmlOutput($_POST["editFileDescription"]);
				$editFileName = htmlOutput($_FILES['editFile']['name']);
				$editFileType = htmlOutput($_FILES['editFile']['type']);
				$editFileSize = htmlOutput($_FILES['editFile']['size']);
				$editFileContent = file_get_contents($_FILES['editFile']['tmp_name']);
				
				
				// Before processing, make sure the record belongs to this user...
				$escapedEntryId = mysqli_real_escape_string($conn, $editFileEntry);
				$sql = "SELECT id, cipherSuite, iv, entry, tag FROM entries WHERE id='$escapedEntryId' AND userId='$_SESSION[userId]'";
				$db_rawEntry = $conn -> query($sql);
				
				// echo $sql;
				if (mysqli_num_rows($db_rawEntry) > 0) {
					//ALL GOOD, process...
					
					if(strlen($editFileDescription) > 512) {
						$backToForm = true;
						$editFileDescriptionError = $strings['190'];
					} elseif(strlen($editFileDescription) < 1) {
						$backToForm = true;
						$editFileDescriptionError = $strings['191'];
					}
					
					if($editFileName == '' || is_null($editFileName) || !isset($editFileName)) {
						$doNotUpdateFile = true;
					} elseif($editFileSize == '0') {
						$doNotUpdateFile = true;
					} elseif($editFileSize > 15728640) {
						$backToForm = true;
						$editFileError = $strings['194'];
					} else {
						$doNotUpdateFile = false;
					}
					
					if($backToForm == true) {
						$backToEditFileEntryForm = true;
					} else {
						// echo 'Validation successful... Update record!';
						// first, extract current record before updating.
						$db_entry = $db_rawEntry -> fetch_assoc();
						$jsonEntry = decryptDataNextGen($db_entry['iv'], $_SESSION['encryptionKey'], $db_entry['entry'], $db_entry['cipherSuite'], $db_entry['tag']);
						$entry = json_decode($jsonEntry, true);
						
						$entry['type'] = utf8_encode('file');
						$entry['lastModified'] = utf8_encode(date('Y-m-d H:i:s'));
						$entry['description'] = utf8_encode($editFileDescription);
						
						if($doNotUpdateFile === false) {
							// The file will be updated with this entry update. Get data from what was uploaded.
							
							$entry['fileName'] = utf8_encode($editFileName);
							$entry['fileType'] = utf8_encode($editFileType);
							$entry['fileSize'] = utf8_encode($editFileSize);
							$entry['storedFileSize'] = strlen($editFileContent) * 133 / 100;
							$entry['fileMd5'] = utf8_encode(hash('md5', $editFileContent));
							$entry['fileSha1'] = utf8_encode(hash('sha1', $editFileContent));
							$entry['fileSha256'] = utf8_encode(hash('sha256', $editFileContent));
							
						}
						
						$jsonEntry = json_encode($entry);
						
						$encryptedEntry = encryptDataNextGen($_SESSION['encryptionKey'], $jsonEntry, $config['currentCipherSuite']);
						$encryptedEntryIv = $encryptedEntry['iv'];
						$encryptedEntryData = $encryptedEntry['data'];
						$encryptedEntryTag = $encryptedEntry['tag'];
						
						$entrySizeMb = strlen($encryptedEntryData) / 1024 / 1024;
						$userDataSizeAfter = $_SESSION['userDataSize'] + $entrySizeMb;
						
						if ($userDataSizeAfter <= $_SESSION['userDataCap']) {
							$updateEntryData = true;
							
							if($doNotUpdateFile === false) {
								$encryptedFile = encryptDataNextGen($_SESSION['encryptionKey'], $editFileContent, $config['currentCipherSuite']);
								$encryptedFileIv = $encryptedFile['iv'];
								$encryptedFileContent = $encryptedFile['data'];
								$encryptedFileTag = $encryptedFile['tag'];
								
								$fileSizeMb = strlen($encryptedFileContent) / 1024 / 1024;
								$userDataSizeAfter = $_SESSION['userDataSize'] + $fileSizeMb;
								
								if ($userDataSizeAfter <= $_SESSION['userDataCap']) {
									$sql = "UPDATE files SET cipherSuite='$config[currentCipherSuite]', iv='$encryptedFileIv', content='$encryptedFileContent', originalDataFormat='raw', tag='$encryptedFileTag' WHERE entryId='$escapedEntryId'";
									if($conn->query($sql)){
										$message = $strings['126'];
										$updateEntryData = true;
									} else {
										$message = $strings['127'];
										$updateEntryData = false;
									}
								} else {
									// User would burst quota. Prevent write and revert entry data!
									$updateEntryData = false;
									$message = '<span style="color: red;">' . $strings['222'] . '</span>';
								}
							}
							
							if ($updateEntryData === true) {
								$sql = "UPDATE entries SET cipherSuite='$config[currentCipherSuite]', iv='$encryptedEntryIv', entry='$encryptedEntryData', tag='$encryptedEntryTag' WHERE id='$escapedEntryId'";
								if($conn->query($sql)){
									$message = $strings['126'];
								} else {
									$message = $strings['127'];
								}
							}
						
							unset($editFileEntry);
						} else {
							// User would burst quota just with entry data, let alone the file!. Prevent write!
							$message = '<span style="color: red;">' . $strings['221'] . '</span>';
						}
					}
				}
			}
			
			// ************** Post form Add Certificate **************
			if($_SERVER["REQUEST_METHOD"] == "POST" && $_POST['formAction'] == 'addCert') {
				//Generate a new certificate with provided info...
				$newCertName = htmlOutput($_POST['newCertName']);
				$newCertEmail = htmlOutput($_POST['newCertEmail']);
				$newCertCountry = htmlOutput($_POST['newCertCountry']);
				$newCertState = htmlOutput($_POST['newCertState']);
				$newCertCity = htmlOutput($_POST['newCertCity']);
				$newCertPassword = $_POST['newCertPassword'];
				$newCertPasswordRetype = $_POST['newCertPasswordRetype'];
				
				if(strlen($newCertName) > 256) {
					$backToForm = true;
					$newCertNameError = $strings['98'];
				} elseif(strlen($newCertName) < 1) {
					$backToForm = true;
					$newCertNameError = $strings['99'];
				}
				
				if(strlen($newCertEmail) > 320) {
					$backToForm = true;
					$newCertEmailError = $strings['100'];
				} elseif(strlen($newCertEmail) < 1) {
					$backToForm = true;
					$newCertEmailError = $strings['101'];
				} elseif(!filter_var($newCertEmail, FILTER_VALIDATE_EMAIL)) {
					$backToForm = true;
					$newCertEmailError = $strings['102'];
				}
				
				if(!validateCountry($newCertCountry)) {
					$backToForm = true;
					$newCertCountryError = $strings['103'];
				}
				
				if(strlen($newCertState) > 256) {
					$backToForm = true;
					$newCertStateError = $strings['104'];
				} elseif(strlen($newCertState) < 1) {
					$backToForm = true;
					$newCertStateError = $strings['105'];
				}
				
				if(strlen($newCertCity) > 256) {
					$backToForm = true;
					$newCertCityError = $strings['106'];
				} elseif(strlen($newCertCity) < 1) {
					$backToForm = true;
					$newCertCityError = $strings['107'];
				}
				
				if(strlen($newCertPassword) > 128) {
					$backToForm = true;
					$newCertPasswordError = $strings['108'];
				} elseif(strlen($newCertPassword) < 15) {
					$backToForm = true;
					$newCertPasswordError = $strings['109'];
				}
				
				if($newCertPassword != $newCertPasswordRetype) {
					$backToForm = true;
					$newCertPasswordRetypeError = $strings['110'];
					unset($newCertPassword);
					unset($newCertPasswordRetype);
				}
				
				$encryptCertificate = true;
				
				if($backToForm == true) {
					$backToAddCertForm = true;
					$showCerts = true;
					// echo 'NOK';
				} else {
					//All is good... process new certificate!
					$dn = array(
						"countryName" => utf8_encode("$_POST[newCertCountry]"),
						"stateOrProvinceName" => utf8_encode("$_POST[newCertState]"),
						"localityName" => utf8_encode("$_POST[newCertCity]"),
						"organizationName" => "None",
						"organizationalUnitName" => "None",
						"commonName" => utf8_encode("$_POST[newCertName]"),
						"emailAddress" => utf8_encode("$_POST[newCertEmail]"),
					);
					
					$certificate = generateNewCertificate($dn, $newCertPassword);
					
					if($certificate != false) {
						registerCertificate($certificate, $newCertPassword, $_SESSION['userId'], $encryptCertificate);
						$message = $strings['112'];
						$loadContent = true;
					} else {
						$backToForm = true;
						$message = $strings['113'];
						$loadContent = true;
					}
					
					
					// At the end, clear all variables.
					unset($newCertName);
					unset($newCertEmail);
					unset($newCertCountry);
					unset($newCertState);
					unset($newCertCity);
					unset($newCertPassword);
					unset($newCertPasswordRetype);
					
				}
			}
			
			if($_SERVER["REQUEST_METHOD"] == "GET" && ctype_digit($_GET['deleteEntry'])) {
				// Trying to delete en entry.
				$entryId = mysqli_real_escape_string($conn, $_GET['deleteEntry']);
				$sql = "SELECT id FROM entries WHERE userId='$_SESSION[userId]' AND id='$entryId'";
				$db_rawEntry = $conn->query($sql);
				if (mysqli_num_rows($db_rawEntry) == 1) {
					$sql = "DELETE FROM entries WHERE userId='$_SESSION[userId]' AND id='$entryId'";
					if ($conn->query($sql)) {
						$message = $strings['114'];
					} else {
						$message = $strings['115'];
						// $message .= '<br>' . $sql;
					}
				} else {
					$message = $strings['115'];
					// $message .= '<br>' . $sql;
				}
			}
			
			if($_SERVER["REQUEST_METHOD"] == "GET" && ctype_digit($_GET['editEntry'])) {
				// Trying to edit en entry.
				$entryId = mysqli_real_escape_string($conn, $_GET['editEntry']);
				$sql = "SELECT id FROM entries WHERE id='$entryId' AND userId='$_SESSION[userId]'";
				$db_rawEntry = $conn->query($sql);
				if(mysqli_num_rows($db_rawEntry) > 0) {
					$editEntry = $entryId;
				}
			}
			
			if($_SERVER["REQUEST_METHOD"] == "GET" && ctype_digit($_GET['editNoteEntry'])) {
				// Trying to edit en entry.
				$entryId = mysqli_real_escape_string($conn, $_GET['editNoteEntry']);
				$sql = "SELECT id FROM entries WHERE id='$entryId' AND userId='$_SESSION[userId]'";
				$db_rawEntry = $conn->query($sql);
				if(mysqli_num_rows($db_rawEntry) > 0) {
					$editNoteEntry = $entryId;
				}
			}
			
			if($_SERVER["REQUEST_METHOD"] == "GET" && ctype_digit($_GET['editFileEntry'])) {
				// Trying to edit en entry.
				$entryId = mysqli_real_escape_string($conn, $_GET['editFileEntry']);
				$sql = "SELECT id FROM entries WHERE id='$entryId' AND userId='$_SESSION[userId]'";
				$db_rawEntry = $conn->query($sql);
				if(mysqli_num_rows($db_rawEntry) > 0) {
					$editFileEntry = $entryId;
				}
			}
			
			if($_SERVER["REQUEST_METHOD"] == "GET" && ctype_digit($_GET['revokeCert'])) {
				// Trying to revoke a certificate.
				$certId = mysqli_real_escape_string($conn, $_GET['revokeCert']);
				if($certId == $_SESSION['certId']) {
					//The user is trying to re-instate the current certificate.
					$message = $strings['68'];
				} else {
					//Now make sure the user can revoke this certificate...
					$sql = "SELECT id FROM certs WHERE userId='$_SESSION[userId]' AND id='$certId' AND revoked='0'";
					$db_rawCerts = $conn->query($sql);
					if(mysqli_num_rows($db_rawCerts) > 0) {
						// OK, user is authorized. Go ahead and delete.
						$sql = "UPDATE certs SET revoked='1' WHERE userId='$_SESSION[userId]' AND id='$certId'";
						if ($conn->query($sql)) {
							$message = $strings['69'];
						} else {
							$message = $strings['70'];
						}
					} else {
						// Trying to revoke some other certificates. Mothafucka.
						$message = $strings['71'];
					}
				}
			}
			
			if($_SERVER["REQUEST_METHOD"] == "GET" && ctype_digit($_GET['unrevokeCert'])) {
				// Trying to re-instate a certificate.
				$certId = mysqli_real_escape_string($conn, $_GET['unrevokeCert']);
				if($certId == $_SESSION['certId']) {
					//The user is trying to revoke the current certificate.
					$message = $strings['72'];
				} else {
					//Now make sure the user can revoke this certificate...
					$sql = "SELECT id FROM certs WHERE userId='$_SESSION[userId]' AND id='$certId' AND revoked='1'";
					$db_rawCerts = $conn->query($sql);
					if(mysqli_num_rows($db_rawCerts) > 0) {
						// OK, user is authorized. Go ahead and delete.
						$sql = "UPDATE certs SET revoked='0' WHERE userId='$_SESSION[userId]' AND id='$certId'";
						if ($conn->query($sql)) {
							$message = $strings['73'];
						} else {
							$message = $strings['74'];
						}
					} else {
						// Trying to revoke some other certificates. Mothafucka.
						$message = $strings['75'];
					}
				}
			}
			
			if($_SERVER["REQUEST_METHOD"] == "GET" && ctype_digit($_GET['deleteCert'])) {
				// Trying to delete a certificate.
				$certId = mysqli_real_escape_string($conn, $_GET['deleteCert']);
				if($certId == $_SESSION['certId']) {
					//The user is trying to delete the current certificate.
					$message = $strings['64'];
				} else {
					//Now make sure the user can delete this certificate...
					$sql = "SELECT id FROM certs WHERE userId='$_SESSION[userId]' AND id='$certId'";
					$db_rawCerts = $conn->query($sql);
					if(mysqli_num_rows($db_rawCerts) > 0) {
						// OK, user is authorized. Go ahead and delete.
						$sql = "DELETE FROM certs WHERE userId='$_SESSION[userId]' AND id='$certId'";
						if ($conn->query($sql)) {
							$message = $strings['65'];
						} else {
							$message = $strings['66'];
						}
					} else {
						// Trying to delete some other certificates. Mothafucka.
						$message = $strings['67'];
					}
				}
			}
			
			if($_SERVER["REQUEST_METHOD"] == "GET" && ctype_digit($_GET['downloadFile'])) {
				// Trying to download a file
				$fileEntryId = mysqli_real_escape_string($conn, $_GET['downloadFile']);
				$sql = "SELECT id, cipherSuite, iv, entry, tag FROM entries WHERE id='$fileEntryId' AND userId='$_SESSION[userId]'";
				$db_rawEntry = $conn -> query($sql);
				if(mysqli_num_rows($db_rawEntry) == 1) {
					// Entry exists and is for the right user. Extract original filename.
					$db_entry = $db_rawEntry -> fetch_assoc();
					$clearEntryJsonData = decryptDataNextGen($db_entry['iv'], $_SESSION['encryptionKey'], $db_entry['entry'], $db_entry['cipherSuite'], $db_entry['tag']);
					$entry = json_decode($clearEntryJsonData, true);
					
					// Select file content
					$sql = "SELECT cipherSuite, iv, content, originalDataFormat, tag FROM files WHERE entryId='$fileEntryId'";
					$db_rawFile = $conn -> query($sql);
					if(mysqli_num_rows($db_rawFile) == 1) {
						// All seems good!
						$db_file = $db_rawFile -> fetch_assoc();
						$fileContent = decryptDataNextGen($db_file['iv'], $_SESSION['encryptionKey'], $db_file['content'], $db_file['cipherSuite'], $db_file['tag']);
						if($db_file['originalDataFormat'] == 'base64') {
							$fileContent = base64_decode($fileContent);
						}
						
						$fileType = $entry['fileType'];
						$fileName = $entry['fileName'];
						
						header("Content-type: $fileType");
						header("Content-Disposition: attachment; filename=$fileName");
						echo $fileContent;
						
						$loadContent = false;
						
					} else {
						// This entry is not a file. Cannot download it!
						$message = $strings['203'];
					}
				} else {
					// This entry may not exist or be someone else's file. Mothafucka.
					$message = $strings['203'];
				}
			}
			
			if($_SERVER["REQUEST_METHOD"] == "GET" && ctype_digit($_GET['downloadCert'])) {
				// Trying to download a certificate
				$certId = mysqli_real_escape_string($conn, $_GET['downloadCert']);
				$sql = "SELECT ivPkcs12File, encryptedPkcs12File, tagPkcs12File, cipherSuitePkcs12File, ivCertData, encryptedCertData, tagCertData, cipherSuiteCertData FROM certs WHERE id='$certId' AND userId='$_SESSION[userId]'";
				$db_rawCert = $conn->query($sql);
				if(mysqli_num_rows($db_rawCert) == 1) {
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
					$message = $strings['111'];
					// $message .= '<br>' . $sql;
				}
			}
			
			if($_SERVER["REQUEST_METHOD"] == "GET" && $_GET["download"] == "csvPasswords") {
				// Trying to download all password entries as CSV... Build file for user.
				$sql = "SELECT id, cipherSuite, iv, entry, tag FROM entries WHERE userId='$_SESSION[userId]'";
				$db_rawEntry = $conn->query($sql);
				$csv = "id','entryName','entryUsername','entryPassword','entryComment
";
				$id = 1;
				while ($row = $db_rawEntry->fetch_assoc()) {
					// Decrypt row first
					$iv = $row['iv'];
					$encryptedEntry = $row['entry'];
					$cipherSuite = $row['cipherSuite'];
					$tag = $row['tag'];
					$jsonEntry = decryptDataNextGen($iv, $_SESSION['encryptionKey'], $encryptedEntry, $cipherSuite, $tag);
					
					// Turn JSON to Array then extract data from array
					$entry = json_decode($jsonEntry, true);
					
					if($entry['type'] == 'password' || $entry['type'] == '') {
						$entryName = utf8_decode($entry['name']);
						$entryUsername = utf8_decode($entry['username']);
						$entryPassword = utf8_decode($entry['password']);
						$entryComment = utf8_decode($entry['comment']);
						$entryComment = str_replace("\r", "", $entryComment);
						$entryComment = str_replace("\n", " | ", $entryComment);
						// Build file from each password entry
						$csv .= $id . '\',\'' . $entryName . '\',\'' . $entryUsername . '\',\'' . $entryPassword . '\',\'' . $entryComment . '
';
						$id++;
					}
				}
				// echo file
				
				header("Content-type: text/plain");
				header("Content-Disposition: attachment; filename=" . $_SESSION['fullName'] . " - password entries.txt");
				echo $csv;
				
				$loadContent = false;
			}
			
			if($_SERVER["REQUEST_METHOD"] == "GET" && ctype_digit($_GET["logoutSession"])) {
				$sessionId = mysqli_real_escape_string($conn, $_GET["logoutSession"]);
				$sql = "SELECT id FROM sessions WHERE userId='$_SESSION[userId]' AND id='$sessionId'";
				$db_rawSession = $conn->query($sql);
				if(mysqli_num_rows($db_rawSession) === 1 && $sessionId != $_SESSION['sessionId']) {
					$sql = "DELETE FROM sessions WHERE userId='$_SESSION[userId]' AND id='$sessionId'";
					if($conn->query($sql)) {
						$message = $strings['240'];
					} else {
						$message = $strings['241'];
					}
				} else {
					$message = $strings['241'];
				}
			}
			
			if($_SERVER["REQUEST_METHOD"] == "GET" && $_GET['control'] == 'updateDataCipher') {
				// This is to update cipher suites in all users records. Can only be initiated by user.
				// Check certs first
				$sql = "SELECT id, ivPkcs12File, encryptedPkcs12File, tagPkcs12File, cipherSuitePkcs12File, ivCertData, encryptedCertData, tagCertData, cipherSuiteCertData FROM certs WHERE userId='$_SESSION[userId]'";
				$db_rawCerts = $conn->query($sql);
				while($row = $db_rawCerts->fetch_assoc()) {
					if($row['cipherSuitePkcs12File'] != $config['currentCipherSuite']) {
						$certFile = decryptDataNextGen($row['ivPkcs12File'], $_SESSION['encryptionKey'], $row['encryptedPkcs12File'], $row['cipherSuitePkcs12File'], $row['tagPkcs12File']);
						$encryptedCertFile = encryptDataNextGen($_SESSION['encryptionKey'], $certFile, $config['currentCipherSuite']);
						$sql = "UPDATE certs SET ivPkcs12File='$encryptedCertFile[iv]', encryptedPkcs12File='$encryptedCertFile[data]', tagPkcs12File='$encryptedCertFile[tag]', cipherSuitePkcs12File='$config[currentCipherSuite]' WHERE id='$row[id]'";
						$conn -> query($sql);
					}
					
					if($row['cipherSuiteCertData'] != $config['currentCipherSuite']) {
						$certData = decryptDataNextGen($row['ivCertData'], $_SESSION['encryptionKey'], $row['encryptedCertData'], $row['cipherSuiteCertData'], $row['tagCertData']);
						$encryptedCertData = encryptDataNextGen($_SESSION['encryptionKey'], $certData, $config['currentCipherSuite']);
						$sql = "UPDATE certs SET ivCertData='$encryptedCertData[iv]', encryptedCertData='$encryptedCertData[data]', tagCertData='$encryptedCertData[tag]', cipherSuiteCertData='$config[currentCipherSuite]' WHERE id='$row[id]'";
						$conn -> query($sql);
					}
				}
				
				// Check entries next
				$sql = "SELECT id, cipherSuite, iv, entry, tag FROM entries WHERE userId='$_SESSION[userId]'";
				$db_rawEntries = $conn->query($sql);
				while($row = $db_rawEntries -> fetch_assoc()) {
					if($row['cipherSuite'] != $config['currentCipherSuite']) {
						$entry = decryptDataNextGen($row['iv'], $_SESSION['encryptionKey'], $row['entry'], $row['cipherSuite'], $row['tag']);
						$encryptedEntry = encryptDataNextGen($_SESSION['encryptionKey'], $entry, $config['currentCipherSuite']);
						$sql = "UPDATE entries SET cipherSuite='$config[currentCipherSuite]', iv='$encryptedEntry[iv]', entry='$encryptedEntry[data]', tag='$encryptedEntry[tag]' WHERE id='$row[id]'";
						$conn -> query($sql);
					}
					
					// Check if there is a file related to this entry...
					$sql = "SELECT id, cipherSuite, iv, content, tag FROM files WHERE entryId='$row[id]'";
					$db_rawFiles = $conn->query($sql);
					if(mysqli_num_rows($db_rawFiles) === 1) {
						while($fileRow = $db_rawFiles -> fetch_assoc()) {
							if($fileRow['cipherSuite'] != $config['currentCipherSuite']) {
								$file = decryptDataNextGen($fileRow['iv'], $_SESSION['encryptionKey'], $fileRow['content'], $fileRow['cipherSuite'], $fileRow['tag']);
								$encryptedFile = encryptDataNextGen($_SESSION['encryptionKey'], $file, $config['currentCipherSuite']);
								$sql = "UPDATE files SET cipherSuite='$config[currentCipherSuite]', iv='$encryptedFile[iv]', content='$encryptedFile[data]', tag='$encryptedFile[tag]' WHERE id='$fileRow[id]'";
								$conn -> query($sql);
							}
						}
					}
				}
				$_SESSION['updateCipherSuites'] = false;
				$message = $strings['217'] . $config['currentCipherSuite'];
			}
			
			if($_SERVER["REQUEST_METHOD"] == "GET" && $_GET["control"] == "generateNewEncryptionKey") {
				// This is to generate a new encryption key and re-encrypt all the data and entries with it.
				
				$newRawEncryptionKey = generateEncryptionKeyNextGen();
				$newBase64EncryptionKey = base64_encode($newRawEncryptionKey);
				
				$queries = array();
				
				$sql = "SELECT id, ivPkcs12File, encryptedPkcs12File, tagPkcs12File, cipherSuitePkcs12File, ivCertData, encryptedCertData, tagCertData, cipherSuiteCertData FROM certs WHERE userId='$_SESSION[userId]'";
				if ($db_rawCerts = $conn -> query($sql)) {
					while($db_cert = $db_rawCerts -> fetch_assoc()) {
						// For each cert, get the public key...
						$decryptedCertData = decryptDataNextGen($db_cert['ivCertData'], $_SESSION['encryptionKey'], $db_cert['encryptedCertData'], $db_cert['cipherSuiteCertData'], $db_cert['tagCertData']);
						$decryptedCertFile = decryptDataNextGen($db_cert['ivPkcs12File'], $_SESSION['encryptionKey'], $db_cert['encryptedPkcs12File'], $db_cert['cipherSuitePkcs12File'], $db_cert['tagPkcs12File']);
						$certData = json_decode($decryptedCertData, true);
						$publicKey = $certData[publicKey];
						
						// encrypt cert data with new encryption key
						$encryptedCertData = encryptDataNextGen($newBase64EncryptionKey, $decryptedCertData, $config['currentCipherSuite']);
						$encryptedCertFile = encryptDataNextGen($newBase64EncryptionKey, $decryptedCertFile, $config['currentCipherSuite']);
						
						// encrypt the encryption key with the public keys.
						$encryptedEncryptionKey = encrypt($publicKey, $newRawEncryptionKey, $config['currentPadding']);
						
						if ($encryptedCertData['data'] == '' || $encryptedCertFile['data'] == '' || $decryptedCertData == '' || $decryptedCertFile == '' || $encryptedEncryptionKey == '' || $certData['publicKey'] == '') { $failRotateEncryptionKey = true; }
						
						$queries[] = "UPDATE encryptionKeys SET padding='$config[currentPaddingString]', encryptedKey='$encryptedEncryptionKey', version='2' WHERE certId='$db_cert[id]'";
						$queries[] = "UPDATE certs SET ivPkcs12File='$encryptedCertFile[iv]', encryptedPkcs12File='$encryptedCertFile[data]', tagPkcs12File='$encryptedCertFile[tag]', cipherSuitePkcs12File='$config[currentCipherSuite]', ivCertData='$encryptedCertData[iv]', encryptedCertData='$encryptedCertData[data]', tagCertData='$encryptedCertData[tag]', cipherSuiteCertData='$config[currentCipherSuite]' WHERE id='$db_cert[id]'";
						
					}
				} else {
					$failRotateEncryptionKey = true;
				}
				
				$sql = "SELECT id, cipherSuite, iv, entry, tag FROM entries WHERE userId='$_SESSION[userId]'";
				if ($db_rawEntries = $conn->query($sql)) {
					while($db_entry = $db_rawEntries -> fetch_assoc()) {
						$decryptedEntry = decryptDataNextGen($db_entry['iv'], $_SESSION['encryptionKey'], $db_entry['entry'], $db_entry['cipherSuite'], $db_entry['tag']);
						$encryptedEntry = encryptDataNextGen($newBase64EncryptionKey, $decryptedEntry, $config['currentCipherSuite']);
						
						$queries[] = "UPDATE entries SET cipherSuite='$config[currentCipherSuite]', iv='$encryptedEntry[iv]', entry='$encryptedEntry[data]', tag='$encryptedEntry[tag]' WHERE id='$db_entry[id]'";
						
						if ($encryptedEntry['data'] == '' || $decryptedEntry == '') { $failRotateEncryptionKey = true; }
						
						// for each entry, check if there is a file attached...
						$sql = "SELECT id, cipherSuite, iv, content, tag FROM files WHERE entryId='$db_entry[id]'";
						$db_rawFiles = $conn -> query($sql);
						if(mysqli_num_rows($db_rawFiles) == '1') {
							$db_file = $db_rawFiles -> fetch_assoc();
							// There is a file. Convert it too...
							$decryptedFile = decryptDataNextGen($db_file['iv'], $_SESSION['encryptionKey'], $db_file['content'], $db_file['cipherSuite'], $db_file['tag']);
							$encryptedFile = encryptDataNextGen($newBase64EncryptionKey, $decryptedFile, $config['currentCipherSuite']);
							
							if ($encryptedFile['data'] == '' || $decryptedFile == '') { $failRotateEncryptionKey = true; }
							
							$queries[] = "UPDATE files SET cipherSuite='$config[currentCipherSuite]', iv='$encryptedFile[iv]', content='$encryptedFile[data]', tag='$encryptedFile[tag]' WHERE id='$db_file[id]'";
						
						}
					}
				} else {
					$failRotateEncryptionKey = true;
				}
				
				$sql = "SELECT id, cipherSuite, iv, session, tag FROM sessions WHERE userId='$_SESSION[userId]'";
				if($db_rawSessions = $conn -> query($sql)) {
					while($db_session = $db_rawSessions -> fetch_assoc()) {
						$decryptedSession = decryptDataNextGen($db_session['iv'], $_SESSION['encryptionKey'], $db_session['session'], $db_session['cipherSuite'], $db_session['tag']);
						$encryptedSession = encryptDataNextGen($newBase64EncryptionKey, $decryptedSession, $config['currentCipherSuite']);
						
						$queries[] = "UPDATE sessions SET cipherSuite='$config[currentCipherSuite]', iv='$encryptedSession[iv]', session='$encryptedSession[data]', tag='$encryptedSession[tag]' WHERE id='$db_session[id]'";
					}
				} else {
					$failRotateEncryptionKey = true;
				}
				
				$sql = "SELECT id, userIv, userData, userTag, userCipherSuite FROM downloadLinks WHERE userId='$_SESSION[userId]'";
				if($db_rawLinks = $conn -> query($sql)) {
					while($db_link = $db_rawLinks -> fetch_assoc()) {
						$decryptedLink = decryptDataNextGen($db_link['userIv'], $_SESSION['encryptionKey'], $db_link['userData'], $db_link['userCipherSuite'], $db_link['userTag']);
						$encryptedLink = encryptDataNextGen($newBase64EncryptionKey, $decryptedLink, $config['currentCipherSuite']);
						
						$queries[] = "UPDATE downloadLinks SET userCipherSuite='$config[currentCipherSuite]', userIv='$encryptedLink[iv]', userData='$encryptedLink[data]', userTag='$encryptedLink[tag]' WHERE id='$db_link[id]'";
					}
				} else {
					$failRotateEncryptionKey = true;
				}
				
				if ($failRotateEncryptionKey === true) {
					$message = $strings['244'];
					//Something went wrong before we try to insert data in database.
				} else {
					
					foreach($queries as $sql) {
						if($conn -> query($sql)) {
							$success = true;
						} else {
							$fail = true;
						}
					}
					
					if($fail === true) {
						$message = $strings['245'];
						// Dammit, something went wrong while inserting data. Data may be corrupted...
					} else {
						// SUCCESS everywhere! Change $_SESSION['encryptionKey']
						$_SESSION['encryptionKey'] = $newBase64EncryptionKey;
						unset($_SESSION['updateEncryptionKey']);
						
						//Logout all other sessions to make sure we do not corrupt data!!!
						
						$sql = "SELECT id FROM sessions WHERE userId='$_SESSION[userId]'";
						$db_rawSessions = $conn->query($sql);
						while($db_session = $db_rawSessions -> fetch_assoc()) {
							if($db_session['id'] != $_SESSION['sessionId']) {
								$sql = "DELETE FROM sessions WHERE id='$db_session[id]'";
								$conn -> query($sql);
							}
						}
						
						
						$message = $strings['246'];
					}
				}
			}
			
			if($_SERVER["REQUEST_METHOD"] == "GET" && $_GET["control"] == "deleteAccount") {
				$_SESSION['deleteAccount'] = generateRandomString(8, 'aA1');
				$loadContent = false;
				header("HTTP/1.1 301 Moved Permanently");
				header("Location: $parentPage");
			}
			
			if($_SERVER["REQUEST_METHOD"] == "GET" && $_GET["control"] == "cancelAccountDeletion") {
				$loadContent = false;
				unset($_SESSION['deleteAccount']);
				header("HTTP/1.1 301 Moved Permanently");
				header("Location: $parentPage");
			}
			
			if($_SERVER["REQUEST_METHOD"] == "POST" && $_POST["formAction"] == "deleteAccount") {
				if($_POST['deleteAccount'] == $_SESSION['deleteAccount']) {
					$sql = "DELETE FROM users WHERE id='$_SESSION[userId]'";
					if ($conn -> query($sql)) {
						killCookie();
						$loadContent = false;
						header("HTTP/1.1 301 Moved Permanently");
						header("Location: index.php");
					} else {
						$message = '<span class="w3-text-red">' . $strings['276'] . '</span>';
					}
				} else {
					$deleteAccountError = $strings['275'];
				}
			}
			
			if($_SERVER["REQUEST_METHOD"] == "GET" && ctype_digit($_GET["sendByEmail"])) {
				// Validate the this cert belongs to this user...
				$recordId = mysqli_real_escape_string($conn, $_GET["sendByEmail"]);
				$recordType = $_GET["type"];
				if($recordType == 'certificate') {
					$sql = "SELECT id FROM certs WHERE userId='$_SESSION[userId]' AND id='$recordId'";
					$db_rawCert = $conn -> query($sql);
					if(mysqli_num_rows($db_rawCert) == 1) {
						$downloadInfo = array(
							'type' => $recordType,
							'id' => $recordId
						);
						$_SESSION['sendByEmail'] = $downloadInfo;
					} else {
						$_SESSION['message'] = $string['278'];
					}
				} elseif($recordType == 'password' || $recordType == 'note' || $recordType == 'file') {
					$sql = "SELECT id FROM entries WHERE userId='$_SESSION[userId]' AND id='$recordId'";
					$db_rawEntry = $conn -> query($sql);
					if(mysqli_num_rows($db_rawEntry) == 1) {
						$downloadInfo = array(
							'type' => $recordType,
							'id' => $recordId
						);
						$_SESSION['sendByEmail'] = $downloadInfo;
					} else {
						$_SESSION['message'] = $string['294'];
					}
				} else {
					// Invalid type
					$_SESSION['message'] = $string['293'];
				}
				
				$loadContent = false;
				header("HTTP/1.1 301 Moved Permanently");
				header("Location: $parentPage");
			}
			
			if($_SERVER["REQUEST_METHOD"] == "GET" && $_GET["control"] == "cancelSendByEmail") {
				$loadContent = false;
				unset($_SESSION['sendByEmail']);
				header("HTTP/1.1 301 Moved Permanently");
				header("Location: $parentPage");
			}
			
			if($_SERVER["REQUEST_METHOD"] == "POST" && $_POST["formAction"] == "sendByEmail") {
				
				$downloadRecipient = $_POST['emailAddress'];
				$downloadPassphrase = $_POST['downloadPassphrase'];
				
				if(!filter_var($downloadRecipient, FILTER_VALIDATE_EMAIL)) {
					unset($downloadRecipient);
					$backToForm = true;
					$downloadEmailError = $strings['102'];
				}
				
				if(strlen($downloadPassphrase) < 15) {
					unset($downloadPassphrase);
					$backToForm = true;
					$downloadPassphraseError = $strings['283'];
				}
				
				
				if($backToForm != true) {
					$recordId = $_SESSION['sendByEmail']['id'];
					$recordType = $_SESSION['sendByEmail']['type'];
					$keypair = generateKeypair();
					
					// GENERATE ENCRYPTION KEY FOR RECIPIENT
					$encryptionKey = generateEncryptionKeyNextGen();
					$encryptedEncryptionKey = encrypt($keypair['public'], $encryptionKey, $config['currentPadding']);
					$base64EncryptionKey = base64_encode($encryptionKey);
					
					
					
					if($recordType == 'certificate') {
						// Get certificate record
						$sql = "SELECT serial, ivPkcs12File, encryptedPkcs12File, tagPkcs12File, cipherSuitePkcs12File, ivCertData, encryptedCertData, tagCertData, cipherSuiteCertData FROM certs WHERE userId='$_SESSION[userId]' AND id='$recordId'";
						$db_rawCert = $conn -> query($sql);
						if(mysqli_num_rows($db_rawCert) == 1) {
							$db_cert = $db_rawCert -> fetch_assoc();
							
							// Extract user data from record
							$clearCertJsonData = decryptDataNextGen($db_cert['ivCertData'], $_SESSION['encryptionKey'], $db_cert['encryptedCertData'], $db_cert['cipherSuiteCertData'], $db_cert['tagCertData']);
							$clearCertData = json_decode($clearCertJsonData, true);
							$clearCertData['serial'] = utf8_encode($db_cert['serial']);
							$clearCertData['type'] = 'certificate';
							$clearCertData['recipient'] = utf8_encode($downloadRecipient);
							$clearCertJsonData = json_encode($clearCertData);
							
							// THIS DATA IS ENCRYPTED FOR THE USER
							$userEncryptedData = encryptDataNextGen($_SESSION['encryptionKey'], $clearCertJsonData, $config['currentCipherSuite']);

							// Create package for recipient
							$clearCertFile = decryptDataNextGen($db_cert['ivPkcs12File'], $_SESSION['encryptionKey'], $db_cert['encryptedPkcs12File'], $db_cert['cipherSuitePkcs12File'], $db_cert['tagPkcs12File']);
							$clearCertData['file'] = utf8_encode(base64_encode($clearCertFile));
							$package = json_encode($clearCertData);
							
						} else {
							$cannotFinish = true;
							$_SESSION['message'] = $string['278'];
						}
						
					} else {
						// Get entry record
						$sql = "SELECT cipherSuite, iv, entry, tag FROM entries WHERE userId='$_SESSION[userId]' AND id='$recordId'";
						$db_rawEntry = $conn -> query($sql);
						if(mysqli_num_rows($db_rawEntry) == 1) {
							$db_entry = $db_rawEntry -> fetch_assoc();
							
							// Extract user data from record
							$clearEntryJsonData = decryptDataNextGen($db_entry['iv'], $_SESSION['encryptionKey'], $db_entry['entry'], $db_entry['cipherSuite'], $db_entry['tag']);
							$clearEntryData = json_decode($clearEntryJsonData, true);
							$clearEntryData['recipient'] = utf8_encode($downloadRecipient);
							$clearEntryJsonData = json_encode($clearEntryData);
							
							// THIS DATA IS ENCRYPTED FOR THE USER
							$userEncryptedData = encryptDataNextGen($_SESSION['encryptionKey'], $clearEntryJsonData, $config['currentCipherSuite']);
							
							// Build file depending on entry type...
							if($recordType == 'password') {
								$clearEntryData['file'] = utf8_encode(base64_encode(html_entity_decode($strings['39'] . ': ' . utf8_decode($clearEntryData['name']) . '
' . $strings['40'] . ': ' . utf8_decode($clearEntryData['username']) . '
' . $strings['41'] . ': ' . utf8_decode($clearEntryData['password']) . '
' . $strings['265'] . ': ' . utf8_decode($clearEntryData['url']) . '
' . $strings['42'] . ': 
' . utf8_decode($clearEntryData['comment']) . '
')));
							} elseif ($recordType == 'note') {
								$clearEntryData['file'] = utf8_encode(base64_encode(html_entity_decode($strings['147'] . ': ' . utf8_decode($clearEntryData['name']) . '
' . $strings['146'] . ': 
' . utf8_decode($clearEntryData['content']) . '
')));
							} elseif ($recordType == 'file') {
								
								// Get file content from database
								$sql = "SELECT cipherSuite, iv, content, originalDataFormat, tag FROM files WHERE entryId='$recordId'";
								$db_rawFile = $conn -> query($sql);
								if(mysqli_num_rows($db_rawFile) == 1) {
									
									$db_file = $db_rawFile -> fetch_assoc();
									$clearFile = decryptDataNextGen($db_file['iv'], $_SESSION['encryptionKey'], $db_file['content'], $db_file['cipherSuite'], $db_file['tag']);
									if($db_file['originalDataFormat'] == 'raw') {
										// need to convert to base64 first
										$clearEntryData['file'] = utf8_encode(base64_encode($clearFile));
									} else {
										// already base64. Add string directly.
										$clearEntryData['file'] = utf8_encode($clearFile);
									}
									
								} else {
									$cannotFinish = true;
									$_SESSION['message'] = $string['294'];
								}
							} else {
								$cannotFinish = true;
								$_SESSION['message'] = $string['294'];
							}
							
							$package = json_encode($clearEntryData);
							
						} else {
							$cannotFinish = true;
							$_SESSION['message'] = $string['294'];
						}
					}
					
					if($cannotFinish != true) {
						// ENCRYPT PACKAGE WITH RECIPIENT ENCRYPTION KEY
						$encryptedpackage = encryptDataNextGen($base64EncryptionKey, $package, $config['currentCipherSuite']);
						$packageIv = $encryptedpackage['iv'];
						$packageData = $encryptedpackage['data'];
						$packageTag = $encryptedpackage['tag'];
						
						// ENCRYPT PRIVATE KEY THAT WILL BE SENT BY EMAIL
						$privateKeyExportOptions = array(encrypt_key => true, encrypt_key_cipher => OPENSSL_CIPHER_AES_256_CBC);
						openssl_pkey_export($keypair['private'], $securePrivateKey, $downloadPassphrase, $privateKeyExportOptions);
						// $securePrivateKey IS THE CONTENT OF THE .PEM CERTIFICATE FILE, PROTECTED BY THE PASSPHRASE
						
						// GENERATE SECURE HASH TO PERMIT ACCESS TO PAGE
						$nonce = generateRandomString(64, 'aA1');
						$secureHashString = $downloadRecipient . $nonce;
						$secureHash = hash('sha256', $secureHashString);
						
						// Write package and user data to database...
						$sql = "INSERT INTO downloadLinks (userId, userIv, userData, userTag, userCipherSuite, secureHash, downloadEncryptionKey, downloadIv, downloadData, downloadTag, downloadCipherSuite) VALUES ('$_SESSION[userId]', '$userEncryptedData[iv]', '$userEncryptedData[data]', '$userEncryptedData[tag]', '$config[currentCipherSuite]', '$secureHash', '$encryptedEncryptionKey', '$packageIv', '$packageData', '$packageTag', '$config[currentCipherSuite]')";
						$conn -> query($sql);
						
						
						// Send email to user
						sendFileByEmail($downloadRecipient, $securePrivateKey, $nonce, $recordType);
						
						$_SESSION['message'] = $strings['308'] . ' ' . $downloadRecipient . '<br><br>' . $strings['352'];
						
					}
					
					unset($_SESSION['sendByEmail']);
					
				}
			}
			
			if($_SERVER["REQUEST_METHOD"] == "GET" && ctype_digit($_GET["deleteDownloadLink"])) {
				$linkId = mysqli_real_escape_string($conn, $_GET["deleteDownloadLink"]);
				$sql = "SELECT id FROM downloadLinks WHERE id='$linkId' AND userId='$_SESSION[userId]'";
				$db_rawLink = $conn -> query($sql);
				if(mysqli_num_rows($db_rawLink) == 1) {
					$sql = "DELETE FROM downloadLinks WHERE id='$linkId'";
					if ($conn -> query($sql)) {
						$_SESSION['message'] = $strings['339'];
						$loadContent = false;
						header("HTTP/1.1 301 Moved Permanently");
						header("Location: profile.php");
					} else {
						$_SESSION['message'] = $strings['338'];
						$loadContent = false;
						header("HTTP/1.1 301 Moved Permanently");
						header("Location: profile.php");
					}
				} else {
					$_SESSION['message'] = $strings['338'];
					$loadContent = false;
					header("HTTP/1.1 301 Moved Permanently");
					header("Location: profile.php");
				}
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
	include 'profileContent.php';
}
?>