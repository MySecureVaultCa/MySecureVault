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
			$currentUser = getBusinessUserInfo($_SESSION['certId']);
			$currentUserGroups = getBusinessUserGroups($currentUserInfo['id']);
			$businessInfo = getBusinessInfo($_SESSION['userId']);
			$language = $_SESSION['language'];
			
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
					$newFolderJustSet = true;
					if($changeFolder !== false) {
						if($changeFolder['deleted'] !== '1') {
							$_SESSION['currentFolderId'] = $changeFolder['id'];
							logAction('80', 'Folder ID: ' . $changeFolder['id'] . ', Name: ' . $changeFolder['name']);
						} else {
							// Trying to navigate to a deleted folder... not permitted!
							logAction('103', 'Requested Folder ID: ' . htmlOutput($_GET['folderId']));
						}
					} else {
						logAction('73', 'Requested Folder ID: ' . htmlOutput($_GET['folderId']));
						$message = $strings['523'];
					}
				}
				
				if(ctype_digit($_SESSION['currentFolderId'])) {
					$currentFolder = getFolderInfo($_SESSION['currentFolderId']);
					if($newFolderJustSet !== true) {
						logAction('81', 'Folder ID: ' . $currentFolder['id'] . ', Name: ' . $currentFolder['name']);
					}
				} else {
					$currentFolder = getRootFolder();
					$_SESSION['currentFolderId'] = $currentFolder['id'];
					logAction('82', 'Folder ID: ' . $currentFolder['id'] . ', Name: ' . $currentFolder['name']);
				}
				
				$effectiveFolderPermission = getFolderEffectivePermission($currentFolder['id']);
				
				if($_GET['action'] == 'addFolder' && ctype_digit($_GET['parentFolder']) && $newFolderJustSet !== true) {
					// trying to add a subfolder to a parent folder...
					$parentFolder = htmlOutput($_GET['parentFolder']);
					$parentFolderPermissions = getFolderEffectivePermission($parentFolder);
					if($parentFolderPermissions !== false) {
						if(strpos($parentFolderPermissions, 'w') !== false) {
							// All good, show add folder form
							$parentFolderInfo = getFolderInfo($parentFolder);
							if($parentFolderInfo['deleted'] !== '1') {
								$newFolderPermission = defaultFolderPermission($parentFolder);
								$showAddFolderForm = true;
								logAction('76', 'Parent folder ID: ' . $parentFolder . ', Parent folder name: ' . $parentFolderInfo['name']);
							} else {
								// Trying to add a folder under a deleted folder. Not permitted!
								logAction('96', 'Parent folder ID provided: ' . $parentFolder);
								$message = $strings['504'];
							}
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
				
				if($_GET['action'] == 'addPassword' && ctype_digit($_GET['parentFolder']) && $newFolderJustSet !== true) {
					// trying to add a subfolder to a parent folder...
					$parentFolder = htmlOutput($_GET['parentFolder']);
					$parentFolderPermissions = getFolderEffectivePermission($parentFolder);
					if($parentFolderPermissions !== false) {
						if(strpos($parentFolderPermissions, 'w') !== false) {
							$parentFolderInfo = getFolderInfo($parentFolder);
							if($parentFolderInfo['deleted'] !== '1') {
								$showAddPasswordForm = true;
								logAction('108', 'Parent folder ID: ' . $parentFolder . ', Parent folder name: ' . $parentFolderInfo['name']);
							} else {
								// Trying to add a password entry under a deleted folder. Not permitted!
								logAction('107', 'Parent folder ID provided: ' . $parentFolder);
								$message = $strings['530'];
							}
						} else {
							logAction('106', 'Parent folder ID provided: ' . $parentFolder);
							$message = $strings['530'];
						}
					} else {
						logAction('105', 'Parent folder ID provided: ' . $parentFolder);
						$message = $strings['530'];
					}
				}
				
				if($_GET['action'] == 'cancelAddPassword') {
					logAction('104');
				}
				
				if($_GET['action'] == 'addNote' && ctype_digit($_GET['parentFolder']) && $newFolderJustSet !== true) {
					// trying to add a subfolder to a parent folder...
					$parentFolder = htmlOutput($_GET['parentFolder']);
					$parentFolderPermissions = getFolderEffectivePermission($parentFolder);
					if($parentFolderPermissions !== false) {
						if(strpos($parentFolderPermissions, 'w') !== false) {
							$parentFolderInfo = getFolderInfo($parentFolder);
							if($parentFolderInfo['deleted'] !== '1') {
								$showAddNoteForm = true;
								logAction('118', 'Parent folder ID: ' . $parentFolder . ', Parent folder name: ' . $parentFolderInfo['name']);
							} else {
								// Trying to add a note entry under a deleted folder. Not permitted!
								logAction('117', 'Parent folder ID provided: ' . $parentFolder);
								$message = $strings['536'];
							}
						} else {
							logAction('116', 'Parent folder ID provided: ' . $parentFolder);
							$message = $strings['536'];
						}
					} else {
						logAction('115', 'Parent folder ID provided: ' . $parentFolder);
						$message = $strings['536'];
					}
				}
				
				if($_GET['action'] == 'cancelAddNote') {
					logAction('114');
				}
				
				if($_GET['action'] == 'addFile' && ctype_digit($_GET['parentFolder']) && $newFolderJustSet !== true) {
					// trying to add a subfolder to a parent folder...
					$parentFolder = htmlOutput($_GET['parentFolder']);
					$parentFolderPermissions = getFolderEffectivePermission($parentFolder);
					if($parentFolderPermissions !== false) {
						if(strpos($parentFolderPermissions, 'w') !== false) {
							$parentFolderInfo = getFolderInfo($parentFolder);
							if($parentFolderInfo['deleted'] !== '1') {
								$showAddFileForm = true;
								logAction('123', 'Parent folder ID: ' . $parentFolder . ', Parent folder name: ' . $parentFolderInfo['name']);
							} else {
								// Trying to add a file entry under a deleted folder. Not permitted!
								logAction('122', 'Parent folder ID provided: ' . $parentFolder);
								$message = $strings['537'];
							}
						} else {
							logAction('121', 'Parent folder ID provided: ' . $parentFolder);
							$message = $strings['537'];
						}
					} else {
						logAction('120', 'Parent folder ID provided: ' . $parentFolder);
						$message = $strings['537'];
					}
				}
				
				if($_GET['action'] == 'cancelAddFile') {
					logAction('119');
				}
				
				if(ctype_digit($_GET['editFolder']) && $newFolderJustSet !== true) {
					$editFolder = htmlOutput($_GET['editFolder']);
					$folderPermissions = getFolderEffectivePermission($editFolder);
					if($folderPermissions !== false) {
						if(strpos($folderPermissions, 'w') !== false || isBusinessOwner($currentUser['id']) || isEnterpriseAdmin($currentUser['id'])) {
							$editFolder = getFolderInfo($editFolder);
							$editFolderPermission = defaultFolderPermission($editFolder['id']);
							$showEditFolderForm = true;
							
							$editFolderName = $editFolder['name'];
							$editFolderOwner = $editFolder['owner'];
							$editFolderOwningGroup = $editFolder['owningGroup'];
							
							
							logAction('90', 'Folder ID: ' . $editFolder['id'] . ', Folder name: ' . $editFolder['name']);
						} else {
							logAction('89', 'Folder ID provided: ' . $editFolder);
							$message = $strings['520'];
						}
					} else {
						logAction('88', 'Folder ID provided: ' . $editFolder);
						$message = $strings['520'];
					}
				}
				
				if($_GET['action'] == 'cancelEditFolder') {
					logAction('91');
				}
				
				if(ctype_digit($_GET['deleteFolder'])) {
					$deleteFolder = htmlOutput($_GET['deleteFolder']);
					$folderPermissions = getFolderEffectivePermission($deleteFolder);
					$rootFolder = getRootFolder();
					if($folderPermissions !== false) {
						// Folder exists. Check if the requesting user is owner, enterprise admin or business owner...
						$deleteFolderInfo = getFolderInfo($deleteFolder);
						if(isBusinessOwner($currentUser['id']) || isEnterpriseAdmin($currentUser['id']) || $currentUser['id'] == $deleteFolderInfo['owner']) {
							if($deleteFolderInfo['deleted'] !== '1') {
								if($deleteFolder != $rootFolder['id']) {
									// OK, not trying to delete the root, and all permissions OK. Go ahead!
									// First, get all child objects ->
									$child = getAllChildObjects($deleteFolder);
									
									// Add deleted folder itself, since the function does not return it...
									$child['folders'][] = $deleteFolder;
									
									foreach($child['folders'] as $folderId) {
										$folder = getFolderInfo($folderId);
										$folder['lastModified'] = date('Y-m-d H:i:s');
										unset($folder['parentFolder']);
										$jsonFolder = json_encode($folder);
										
										$encryptedEntry = encryptDataNextGen($_SESSION['encryptionKey'], $jsonFolder, $config['currentCipherSuite']);
										$encryptedEntryIv = $encryptedEntry['iv'];
										$encryptedEntryData = $encryptedEntry['data'];
										$encryptedEntryTag = $encryptedEntry['tag'];
										
										$sql = "UPDATE businessFolders SET deleted='1', cipherSuite='$config[currentCipherSuite]', iv='$encryptedEntryIv', entry='$encryptedEntryData', tag='$encryptedEntryTag' WHERE id='$folder[id]'";
										// echo $sql . '<br>';
										$conn -> query($sql);
										$deletedFolder['id'] = $folder['id'];
										$deletedFolder['name'] = $folder['name'];
										$deletedFolders[] = $deletedFolder;
									}
									$deletedFoldersJson = json_encode($deletedFolders);
									logAction('102', 'The following folders were deleted: ' . $deletedFoldersJson);
									$message = $strings['528'];
								} else {
									// This is bad... trying to delete the root folder... You cannot do that, protect the user from itself!
									$message = $strings['525'];
									logAction('100', 'Folder ID provided: ' . $deleteFolder);
								}
							} else {
								// Tried to delete an already deleted folder. Cannot do that!
								$message = $strings['525'];
								logAction('101', 'Folder ID provided: ' . $deleteFolder);
							}
						} else {
							// User is neither enterprise admin, nor business owner, nor folder owner... cannot do that!
							$message = $strings['525'];
							logAction('99', 'Folder ID provided: ' . $deleteFolder);
						}
					} else {
						// Folder does not exist, SOAB.
						$message = $strings['525'];
						logAction('98', 'Folder ID provided: ' . $deleteFolder);
					}
				}
				
				if($_POST['formAction'] == 'addFolder' && ctype_digit($_POST['parentFolder']) && $newFolderJustSet !== true) {
					// Trying to add a folder...
					$parentFolder = htmlOutput($_POST['parentFolder']);
					$parentFolderPermissions = getFolderEffectivePermission($parentFolder);
					if($parentFolderPermissions !== false) {
						if(strpos($parentFolderPermissions, 'w') !== false || isBusinessOwner($currentUser['id']) || isEnterpriseAdmin($currentUser['id'])) {
							// All good, process form information.
							$parentFolderInfo = getFolderInfo($parentFolder);
							if($parentFolderInfo['deleted'] !== '1') {
								$newFolderPermission = defaultFolderPermission($parentFolderInfo['id']);
								$newFolderOwner = $currentUser['id'];
								$newFolderOwningGroup = $parentFolderInfo['owningGroup'];
								
								$newFolderName = htmlOutput($_POST['newFolderName']);
								
								if(strlen($newFolderName) < 1) {
									$backToForm = true;
									$newFolderNameError = $strings['515'];
								} elseif(strlen($newFolderName) > 64) {
									$backToForm = true;
									$newFolderNameError = $strings['516'];
								}
								
								if($businessPermission['users'] == 'rw'){
									// get owner, owningGroup and permission from POST
									// Otherwise, they've already been set...
									if(ctype_digit($_POST['newFolderOwner'])) {
										$newFolderOwner = getBusinessUserInfoFromId($_POST['newFolderOwner']);
										if($newFolderOwner !== false) {
											$newFolderOwner = $newFolderOwner['id'];
										} else {
											$backToForm = true;
											$newFolderOwner = $currentUser['id'];
											$newFolderOwnerError = $strings['518'];
											logAction('83', 'Owner user ID provided: ' . htmlOutput($_POST['newFolderOwner']));
										}
									} else {
										$backToForm = true;
										$newFolderOwnerError = $strings['518'];
									}
									
									if(ctype_digit($_POST['newFolderOwningGroup'])) {
										$newFolderOwningGroup = getGroupInfo($_POST['newFolderOwningGroup']);
										if($newFolderOwningGroup !== false) {
											$newFolderOwningGroup = $newFolderOwningGroup['id'];
											if($newFolderOwningGroup == $businessInfo['business']['owningGroup']){
												// Trying to set Enterprise admins as the owner of the folder. Must be enterprise admin.
												if(isEnterpriseAdmin($currentUser['id'])) {
													// OK, all good...
													
												} else {
													// Tried to set Enterprise admins manually. NOK
													$backToForm = true;
													$newFolderOwningGroup = $parentFolderInfo['owningGroup'];
													$newFolderOwningGroupError = $strings['517'];
													logAction('85', 'Owning group ID provided: ' . htmlOutput($_POST['newFolderOwningGroup']));
												}
											}
										} else {
											// Tried to set a non-existent group as the owner!
											$backToForm = true;
											$newFolderOwningGroup = $parentFolderInfo['owningGroup'];
											$newFolderOwningGroupError = $strings['517'];
											logAction('84', 'Owning group ID provided: ' . htmlOutput($_POST['newFolderOwningGroup']));
										}
									} else {
										// Nothing posted, or not a number!
										$backToForm = true;
										$newFolderOwningGroup = $parentFolderInfo['owningGroup'];
										$newFolderOwningGroupError = $strings['517'];
									}
									
									$newFolderPermission = makeCoherentFolderPermission($_POST['newFolderPermission']);
									
									if($newFolderPermission === false) {
										// There were errors in the permission posted, it was not an array!
										$backToForm = true;
										$newFolderPermissionError = $strings['522'];
									}
									
								}
								
								if($backToForm === true) {
									$showAddFolderForm = true;
									logAction('86');
								} else {
									// all good, encrypt and write to database!
									
									$newFolderComputedPermission = computePermission($newFolderPermission);
									
									$folder['name'] = $newFolderName;
									$folder['owner'] = $newFolderOwner;
									$folder['owningGroup'] = $newFolderOwningGroup;
									$folder['sharedWith'] = array();
									$folder['acl'] = $newFolderComputedPermission;
									$folder['createdOn'] = date('Y-m-d H:i:s');
									$folder['lastModified'] = '';
									$parentFolder = mysqli_real_escape_string($conn, $parentFolder);
									
									$jsonFolder = json_encode($folder);
									
									$encryptedEntry = encryptDataNextGen($_SESSION['encryptionKey'], $jsonFolder, $config['currentCipherSuite']);
									$encryptedEntryIv = $encryptedEntry['iv'];
									$encryptedEntryData = $encryptedEntry['data'];
									$encryptedEntryTag = $encryptedEntry['tag'];
									
									$sql = "INSERT INTO businessFolders (userId, parentFolder, cipherSuite, iv, entry, tag) VALUES ('$_SESSION[userId]', '$parentFolder', '$config[currentCipherSuite]', '$encryptedEntry[iv]', '$encryptedEntry[data]', '$encryptedEntry[tag]')";
									// echo $sql;
									$conn -> query($sql);
									$newFolderId = mysqli_insert_id($conn);
									logAction('87', 'Folder ID: ' . $newFolderId . ', Name: ' . $folder['name'] . ', Parent folder ID: ' . $folder['parentFolder']);
									$message = $strings['527'];
								}
							} else {
								// Trying to create a folder under a deleted folder... cannot do that!
								logAction('97', 'Parent folder ID provided: ' . $parentFolder);
								$message = $strings['504'];
							}
						} else {
							logAction('79', 'Parent folder ID provided: ' . $parentFolder);
							$message = $strings['504'];
						}
					} else {
						logAction('78', 'Parent folder ID provided: ' . $parentFolder);
						$message = $strings['504'];
					}
				}
				
				if($_POST['formAction'] == 'editFolder' && ctype_digit($_POST['editFolder']) && $newFolderJustSet !== true) {
					$editFolderId = htmlOutput($_POST['editFolder']);
					$folderPermissions = getFolderEffectivePermission($editFolderId);
					if($folderPermissions !== false) {
						if(strpos($folderPermissions, 'w') !== false || isBusinessOwner($currentUser['id']) || isEnterpriseAdmin($currentUser['id'])) {
							$editFolder = getFolderInfo($editFolderId);
							if($editFolder['deleted'] !== '1') {
								$editFolderPermission = defaultFolderPermission($editFolder['id']);
								
								$editFolderName = htmlOutput($_POST['editFolderName']);
								$editFolderOwner = $editFolder['owner'];
								$editFolderOwningGroup = $editFolder['owningGroup'];
								
								if(strlen($editFolderName) < 1) {
									$backToForm = true;
									$editFolderNameError = $strings['515'];
								} elseif(strlen($editFolderName) > 64) {
									$backToForm = true;
									$editFolderNameError = $strings['516'];
								}
								
								if($businessPermission['users'] == 'rw'){
									// Get owner, owning group and permissions from form ONLY if user manager. Else, we keep those from current folder.
									
									if(ctype_digit($_POST['editFolderOwner'])) {
										$editFolderOwner = getBusinessUserInfoFromId($_POST['editFolderOwner']);
										if($editFolderOwner !== false) {
											$editFolderOwner = $editFolderOwner['id'];
										} else {
											$backToForm = true;
											$editFolderOwner = $editFolder['owner'];
											$editFolderOwnerError = $strings['518'];
											logAction('83', 'Owner user ID provided: ' . htmlOutput($_POST['newFolderOwner']));
										}
									} else {
										$backToForm = true;
										$editFolderOwnerError = $strings['518'];
									}
									
									if(ctype_digit($_POST['editFolderOwningGroup'])) {
										$editFolderOwningGroup = getGroupInfo($_POST['editFolderOwningGroup']);
										if($editFolderOwningGroup !== false) {
											$editFolderOwningGroup = $editFolderOwningGroup['id'];
											if($editFolderOwningGroup == $businessInfo['business']['owningGroup']){
												// Trying to set Enterprise admins as the owner of the folder. Must be enterprise admin.
												if(isEnterpriseAdmin($currentUser['id'])) {
													// OK, all good...
													
												} else {
													// Tried to set Enterprise admins manually. NOK
													$backToForm = true;
													$editFolderOwningGroup = $editFolder['owningGroup'];
													$editFolderOwningGroupError = $strings['517'];
													logAction('85', 'Owning group ID provided: ' . htmlOutput($_POST['editFolderOwningGroup']));
												}
											}
										} else {
											// Tried to set a non-existent group as the owner!
											$backToForm = true;
											$editFolderOwningGroup = $parentFolderInfo['owningGroup'];
											$editFolderOwningGroupError = $strings['517'];
											logAction('84', 'Owning group ID provided: ' . htmlOutput($_POST['editFolderOwningGroup']));
										}
									} else {
										// Nothing posted, or not a number!
										$backToForm = true;
										$editFolderOwningGroup = $parentFolderInfo['owningGroup'];
										$editFolderOwningGroupError = $strings['517'];
									}
									
									$editFolderPermission = makeCoherentFolderPermission($_POST['editFolderPermission']);
									
									if($editFolderPermission === false) {
										// There were errors in the permission posted, it was not an array!
										$backToForm = true;
										$editFolderPermissionError = $strings['522'];
									}
								}
								
								if($backToForm == true) {
									$showEditFolderForm = true;
									logAction('94');
								} else {
									// Process, no errors
									$editFolderComputedPermission = computePermission($editFolderPermission);
									
									$editFolder['name'] = $editFolderName;
									$editFolder['owner'] = $editFolderOwner;
									$editFolder['owningGroup'] = $editFolderOwningGroup;
									$editFolder['acl'] = $editFolderComputedPermission;
									$editFolder['lastModified'] = date('Y-m-d H:i:s');
									$parentFolder = $editFolder['parentFolder'];
									unset($editFolder['parentFolder']);
									$jsonFolder = json_encode($editFolder);
									
									$encryptedEntry = encryptDataNextGen($_SESSION['encryptionKey'], $jsonFolder, $config['currentCipherSuite']);
									$encryptedEntryIv = $encryptedEntry['iv'];
									$encryptedEntryData = $encryptedEntry['data'];
									$encryptedEntryTag = $encryptedEntry['tag'];
									
									$sql = "UPDATE businessFolders SET cipherSuite='$config[currentCipherSuite]', iv='$encryptedEntryIv', entry='$encryptedEntryData', tag='$encryptedEntryTag' WHERE id='$editFolder[id]'";
									// echo $sql;
									$conn -> query($sql);
									$message = $strings['526'];
									logAction('95', 'Folder ID: ' . $editFolder['id'] . ', Name: ' . $editFolder['name'] . ', Parent folder ID: ' . $parentFolder);
								}
							} else {
								logAction('113', 'Folder ID provided: ' . $editFolderId);
								$message = $strings['524'];
							}
						} else {
							logAction('93', 'Folder ID provided: ' . $editFolderId);
							$message = $strings['524'];
						}
					} else {
						logAction('92', 'Folder ID provided: ' . $editFolderId);
						$message = $strings['524'];
					}
				}
				
				// ************** Post form ADD PASSWORD ENTRY **************
				if($_POST["formAction"] == "addPassword" && ctype_digit($_POST['parentFolder']) && $newFolderJustSet !== true) {
					$parentFolder = htmlOutput($_POST['parentFolder']);
					$parentFolderPermissions = getFolderEffectivePermission($parentFolder);
					if($parentFolderPermissions !== false) {
						if(strpos($parentFolderPermissions, 'w') !== false || isBusinessOwner($currentUser['id']) || isEnterpriseAdmin($currentUser['id'])) {
							// All good, process form information.
							$parentFolderInfo = getFolderInfo($parentFolder);
							if($parentFolderInfo['deleted'] !== '1') {
								$newEntryPermission = defaultFolderPermission($parentFolderInfo['id']);
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
								
								$newEntryPermission = makeCoherentFolderPermission($newEntryPermission);
								
								if($backToForm == true) {
									$showAddPasswordForm = true;
									logAction('112');
								} else {
									// Validation successful, encrypt entry then add it to database.
									$entry['type'] = utf8_encode('password');
									$entry['timestamp'] = date('Y-m-d H:i:s');
									$entry['name'] = utf8_encode($newEntryName);
									$entry['username'] = utf8_encode($newEntryUsername);
									$entry['password'] = utf8_encode($newEntryPassword);
									$entry['url'] = utf8_encode($newEntryUrl);
									$entry['comment'] = utf8_encode($newEntryComment);
									$entry['owner'] = $currentUser['id'];
									$entry['owningGroup'] = $parentFolderInfo['owningGroup'];
									$entry['acl'] = computePermission($newEntryPermission);
									
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
									
									$sql = "INSERT INTO businessEntries (userId, parentFolder, cipherSuite, iv, entry, tag) VALUES ('$_SESSION[userId]', '$parentFolderInfo[id]', '$config[currentCipherSuite]', '$iv', '$encryptedData', '$tag')";
									$conn -> query($sql);
									
								}
							} else {
								// Trying to add a password entry under a deleted folder. Not permitted!
								logAction('111', 'Parent folder ID provided: ' . $parentFolder);
								$message = $strings['530'];
							}
						} else {
							logAction('110', 'Parent folder ID provided: ' . $parentFolder);
							$message = $strings['530'];
						}
					} else {
						logAction('109', 'Parent folder ID provided: ' . $parentFolder);
						$message = $strings['530'];
					}
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