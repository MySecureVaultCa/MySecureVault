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
		
		function hidePlaceholder(field) {
			document.getElementById(field).placeholder = "";
		}
		
		function showPlaceholder(field,text) {
			document.getElementById(field).placeholder = text;
		}
		
		function searchUser() {
			var searchString = document.getElementById("userSearchField").value;
			var lowerCaseString = searchString.toLowerCase();
			// alert(searchString);
			
			var entries = document.getElementsByClassName('searchableUser');
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
		
		function revokeCert(certId, certName) {
			var r = confirm("<?php echo $strings['59'] ?>:\n" + certName);
			if ( r == true ) {
				document.location = "usersAndGroups.php?revokeCert=" + certId;
			}
		}
		
		function unrevokeCert(certId, certName) {
			var r = confirm("<?php echo $strings['60'] ?>:\n" + certName);
			if ( r == true ) {
				document.location = "usersAndGroups.php?unrevokeCert=" + certId;
			}
		}
		
		function deleteFolder(folderId, folderName) {
			var r = confirm("<?php echo $strings['519'] ?>:\n" + folderName);
			if ( r == true ) {
				document.location = "businessHome.php?deleteFolder=" + folderId;
			}
		}
		
		function deleteUser(userId, userName) {
			var r = confirm("<?php echo javascriptEscape($strings['428']); ?>:\n" + userName);
			if ( r == true ) {
				document.location = "usersAndGroups.php?deleteUser=" + userId;
			}
		}
		
		function changePasswordMethod() {
			if(document.getElementById('userPassphraseButton').checked) {
			  document.getElementById('downloadPassphrase').style.display = 'none';
			  document.getElementById('newUserDownloadPassphrase').value='';
			  document.getElementById('userPassphrase').style.display = 'block';
			}else if(document.getElementById('downloadPassphraseButton').checked) {
			  document.getElementById('downloadPassphrase').style.display = 'block';
			  document.getElementById('newUserPassphrase').value='';
			  document.getElementById('newUserPassphraseRetype').value='';
			  document.getElementById('userPassphrase').style.display = 'none';
			  
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
			<div class="w3-twothird">
				<div class="w3-padding">
					<h1 class="w3-xlarge w3-border-bottom w3-border-blue"> 
						<?php
						$hierarchy = buildHierarchy($currentFolder['id']);
						$rootFolder = getRootFolder();
						
						if(count(array_keys($hierarchy)) === 1) {
							echo '<i class="fa fa-home"></i> ' . $strings['500'];
						} else {
							array_shift($hierarchy);
							echo '<a href="businessHome.php?folderId=' . $rootFolder['id'] . '"><i class="fa fa-home"></i></a> ';
							foreach($hierarchy as $key => $folder) {
								echo '<i class="fa fa-angle-right"></i> <a href="businessHome.php?folderId=' . $folder['id'] . '">' . $folder['name'] . '</a> ';
							}
						}
						?>
					</h1>
					
					<?php 
					
					
					if((strpos($effectiveFolderPermission, 'w') !== false || isBusinessOwner($currentUser['id']) === true || isEnterpriseAdmin($currentUser['id'])) && $showAddFolderForm !== true && $showEditFolderForm !== true && $showAddPasswordForm !== true && $showAddNoteForm !== true && $showAddFileForm !== true && $showEditPasswordForm !== true && $showEditNoteForm !== true && $showEditFileForm !== true) {
						// User has write permission. Display buttons accordingly
						echo '
						<div class="w3-dropdown-hover w3-white">
							<button class="w3-medium w3-button w3-blue w3-padding"><i class="fa fa-plus"></i> ' . $strings['534'] . '</button>
							<div class="w3-dropdown-content w3-bar-block w3-card-4" id="menuDropdown" style="width: 300px;">';
								echo '
								<a style="padding: 15px!important;" class="w3-bar-item w3-button w3-large w3-border-top w3-hover-blue" href="businessHome.php?action=addFolder&parentFolder=' . $currentFolder['id'] . '">
									<i class="fa fa-folder w3-hover-blue">
										<span style="font-family: arial;"> ' . $strings['533'] . '</span>
									</i>
								</a>';
								echo '
								<a style="padding: 15px!important;" class="w3-bar-item w3-button w3-large w3-border-top w3-hover-blue" href="businessHome.php?action=addPassword&parentFolder=' . $currentFolder['id'] . '">
									<i class="fa fa-key w3-hover-blue">
										<span style="font-family: arial;"> ' . $strings['41'] . '</span>
									</i>
								</a>';
								echo '
								<a style="padding: 15px!important;" class="w3-bar-item w3-button w3-large w3-border-top w3-hover-blue" href="businessHome.php?action=addNote&parentFolder=' . $currentFolder['id'] . '">
									<i class="fa fa-sticky-note w3-hover-blue">
										<span style="font-family: arial;"> ' . $strings['535'] . '</span>
									</i>
								</a>';
								echo '
								<a style="padding: 15px!important;" class="w3-bar-item w3-button w3-large w3-border-top w3-hover-blue" href="businessHome.php?action=addFile&parentFolder=' . $currentFolder['id'] . '">
									<i class="fa fa-file-o w3-hover-blue">
										<span style="font-family: arial;"> ' . $strings['183'] . '</span>
									</i>
								</a>';
								echo '
							</div>
						</div>';
					}
					
					if($showAddFolderForm === true) {
						 //var_dump($parentFolderInfo);
						 echo '
						 <h2>' . $strings['499'] . ' <a href="businessHome.php?action=cancelAddFolder" class="w3-button w3-red w3-margin w3-medium">' . $strings['44'] . ' <i class="fa fa-times-circle"></i></a></h2>
						 <form method="POST" action="' . htmlspecialchars($_SERVER["PHP_SELF"]) .  '" autocomplete="off" class="w3-padding">
							<input type="hidden" name="formAction" value="addFolder">
							<input type="hidden" name="parentFolder" value="' . $parentFolderInfo['id'] . '">
							<input type="hidden" name="formUid" value="' . $_SESSION["secureFormCode"] . '">
							<div class="w3-padding-16">
								<label>' . $strings['505'] . '*</label>
								<input id="newFolderName" class="w3-input" type="text" name="newFolderName"'; if(isset($newFolderName)) { echo ' value="' . $newFolderName . '"'; } echo '>';
								if (isset($newFolderNameError)) { echo '<div class="w3-text-red">' . $newFolderNameError . '</div>';} echo '
							</div>';
							
							
							if($businessPermission['users'] == 'rw') {
								// Those who can manage users can manage and delegate folder permission
								// Display permission table
								echo '
								<div class="w3-padding-16">
									' . $strings['507'] . '
									<select name="newFolderOwner" class="w3-select">';
										if(!isset($newFolderOwner)) { $newFolderOwner = $currentUser['id']; }
										echo businessUsersList($newFolderOwner);
									echo '
									</select>';
									if (isset($newFolderOwnerError)) { echo '<div class="w3-text-red">' . $newFolderOwnerError . '</div>';}
									echo '
								</div>
								
								<div class="w3-padding-16">
									' . $strings['508'] . '
									<select name="newFolderOwningGroup" class="w3-select">';
										if(!isset($newFolderOwningGroup)) { $newFolderOwningGroup = $parentFolderInfo['owningGroup']; }
										echo businessGroupsList($newFolderOwningGroup);
									echo '
									</select>';
									if (isset($newFolderOwningGroupError)) { echo '<div class="w3-text-red">' . $newFolderOwningGroupError . '</div>';}
									echo '
								</div>
								
								<div class="w3-padding-16">
									' . $strings['506'] . '
								
								
									<table class="w3-center">
										<tr>
											<th class="w3-padding-small">
												&nbsp;
											</th>
											<th class="w3-padding-small">
												' . $strings['507'] . '
											</th>
											<th class="w3-padding-small">
												' . $strings['508'] . '
											</th>
											<th class="w3-padding-small">
												' . $strings['513'] . '
											</th>
										</tr>
										<tr>
											<td>
												' . $strings['512'] . '
											</td>
											<td>
												<input type="checkbox" class="w3-check" name="newFolderPermission[u][b]" value="1"'; if($newFolderPermission['u']['b'] == '1') { echo 'checked'; } echo '>
											</td>
											<td>
												<input type="checkbox" class="w3-check" name="newFolderPermission[g][b]" value="1"'; if($newFolderPermission['g']['b'] == '1') { echo 'checked'; } echo '>
											</td>
											<td>
												<input type="checkbox" class="w3-check" name="newFolderPermission[o][b]" value="1"'; if($newFolderPermission['o']['b'] == '1') { echo 'checked'; } echo '>
											</td>
										</tr>
										<tr>
											<td>
												' . $strings['509'] . '
											</td>
											<td>
												<input type="checkbox" class="w3-check" name="newFolderPermission[u][r]" value="1"'; if($newFolderPermission['u']['r'] == '1') { echo 'checked'; } echo '>
											</td>
											<td>
												<input type="checkbox" class="w3-check" name="newFolderPermission[g][r]" value="1"'; if($newFolderPermission['g']['r'] == '1') { echo 'checked'; } echo '>
											</td>
											<td>
												<input type="checkbox" class="w3-check" name="newFolderPermission[o][r]" value="1"'; if($newFolderPermission['o']['r'] == '1') { echo 'checked'; } echo '>
											</td>
										</tr>
										<tr>
											<td>
												' . $strings['510'] . '
											</td>
											<td>
												<input type="checkbox" class="w3-check" name="newFolderPermission[u][w]" value="1"'; if($newFolderPermission['u']['w'] == '1') { echo 'checked'; } echo '>
											</td>
											<td>
												<input type="checkbox" class="w3-check" name="newFolderPermission[g][w]" value="1"'; if($newFolderPermission['g']['w'] == '1') { echo 'checked'; } echo '>
											</td>
											<td>
												<input type="checkbox" class="w3-check" name="newFolderPermission[o][w]" value="1"'; if($newFolderPermission['o']['w'] == '1') { echo 'checked'; } echo '>
											</td>
										</tr>
										<tr>
											<td>
												' . $strings['511'] . '
											</td>
											<td>
												<input type="checkbox" class="w3-check" name="newFolderPermission[u][s]" value="1"'; if($newFolderPermission['u']['s'] == '1') { echo 'checked'; } echo '>
											</td>
											<td>
												<input type="checkbox" class="w3-check" name="newFolderPermission[g][s]" value="1"'; if($newFolderPermission['g']['s'] == '1') { echo 'checked'; } echo '>
											</td>
											<td>
												<input type="checkbox" class="w3-check" name="newFolderPermission[o][s]" value="1"'; if($newFolderPermission['o']['s'] == '1') { echo 'checked'; } echo '>
											</td>
										</tr>
									</table>';
									if (isset($newFolderPermissionError)) { echo '<div class="w3-text-red">' . $newFolderPermissionError . '</div>';}
									echo '
								</div>';
							}
						echo '
							<div style="height: 15px;"></div>
							<div class="w3-padding w3-center">
								<input class="w3-btn w3-blue w3-margin-bottom" type="submit" value="' . $strings['514'] . '" name="submit">
							</div>
						</form>';
					} elseif($showEditFolderForm === true) {
						echo '
						<h2 class="w3-xlarge">' . $strings['521'] . ' ' . $editFolder['name'] . ' <a href="businessHome.php?action=cancelEditFolder" class="w3-button w3-red w3-margin w3-medium">' . $strings['44'] . ' <i class="fa fa-times-circle"></i></a></h2>
						<form method="POST" action="' . htmlspecialchars($_SERVER["PHP_SELF"]) .  '" autocomplete="off" class="w3-padding">
							<input type="hidden" name="formAction" value="editFolder">
							<input type="hidden" name="editFolder" value="' . $editFolder['id'] . '">
							<input type="hidden" name="formUid" value="' . $_SESSION["secureFormCode"] . '">
							
							<div class="w3-padding-16">
								<label>' . $strings['505'] . '*</label>
								<input id="editFolderName" class="w3-input" type="text" name="editFolderName"'; if(isset($editFolderName)) { echo ' value="' . $editFolderName . '"'; } echo '>';
								if (isset($editFolderNameError)) { echo '<div class="w3-text-red">' . $editFolderNameError . '</div>';} echo '
							</div>';
							
							if($businessPermission['users'] == 'rw') {
								// Those who can manage users can manage and delegate folder permission
								// Display permission table
								echo '
								<div class="w3-padding-16">
									' . $strings['507'] . '
									<select name="editFolderOwner" class="w3-select">';
										if(!isset($editFolderOwner)) { $editFolderOwner = $editFolder['owner']; }
										echo businessUsersList($editFolderOwner);
									echo '
									</select>';
									if (isset($editFolderOwnerError)) { echo '<div class="w3-text-red">' . $editFolderOwnerError . '</div>';}
									echo '
								</div>
								
								<div class="w3-padding-16">
									' . $strings['508'] . '
									<select name="editFolderOwningGroup" class="w3-select">';
										if(!isset($editFolderOwningGroup)) { $editFolderOwningGroup = $editFolder['owningGroup']; }
										echo businessGroupsList($editFolderOwningGroup);
									echo '
									</select>';
									if (isset($editFolderOwningGroupError)) { echo '<div class="w3-text-red">' . $editFolderOwningGroupError . '</div>';}
									echo '
								</div>
								
								<div class="w3-padding-16">
									' . $strings['506'] . '
								
								
									<table class="w3-center">
										<tr>
											<th class="w3-padding-small">
												&nbsp;
											</th>
											<th class="w3-padding-small">
												' . $strings['507'] . '
											</th>
											<th class="w3-padding-small">
												' . $strings['508'] . '
											</th>
											<th class="w3-padding-small">
												' . $strings['513'] . '
											</th>
										</tr>
										<tr>
											<td>
												' . $strings['512'] . '
											</td>
											<td>
												<input type="checkbox" class="w3-check" name="editFolderPermission[u][b]" value="1"'; if($editFolderPermission['u']['b'] == '1') { echo 'checked'; } echo '>
											</td>
											<td>
												<input type="checkbox" class="w3-check" name="editFolderPermission[g][b]" value="1"'; if($editFolderPermission['g']['b'] == '1') { echo 'checked'; } echo '>
											</td>
											<td>
												<input type="checkbox" class="w3-check" name="editFolderPermission[o][b]" value="1"'; if($editFolderPermission['o']['b'] == '1') { echo 'checked'; } echo '>
											</td>
										</tr>
										<tr>
											<td>
												' . $strings['509'] . '
											</td>
											<td>
												<input type="checkbox" class="w3-check" name="editFolderPermission[u][r]" value="1"'; if($editFolderPermission['u']['r'] == '1') { echo 'checked'; } echo '>
											</td>
											<td>
												<input type="checkbox" class="w3-check" name="editFolderPermission[g][r]" value="1"'; if($editFolderPermission['g']['r'] == '1') { echo 'checked'; } echo '>
											</td>
											<td>
												<input type="checkbox" class="w3-check" name="editFolderPermission[o][r]" value="1"'; if($editFolderPermission['o']['r'] == '1') { echo 'checked'; } echo '>
											</td>
										</tr>
										<tr>
											<td>
												' . $strings['510'] . '
											</td>
											<td>
												<input type="checkbox" class="w3-check" name="editFolderPermission[u][w]" value="1"'; if($editFolderPermission['u']['w'] == '1') { echo 'checked'; } echo '>
											</td>
											<td>
												<input type="checkbox" class="w3-check" name="editFolderPermission[g][w]" value="1"'; if($editFolderPermission['g']['w'] == '1') { echo 'checked'; } echo '>
											</td>
											<td>
												<input type="checkbox" class="w3-check" name="editFolderPermission[o][w]" value="1"'; if($editFolderPermission['o']['w'] == '1') { echo 'checked'; } echo '>
											</td>
										</tr>
										<tr>
											<td>
												' . $strings['511'] . '
											</td>
											<td>
												<input type="checkbox" class="w3-check" name="editFolderPermission[u][s]" value="1"'; if($editFolderPermission['u']['s'] == '1') { echo 'checked'; } echo '>
											</td>
											<td>
												<input type="checkbox" class="w3-check" name="editFolderPermission[g][s]" value="1"'; if($editFolderPermission['g']['s'] == '1') { echo 'checked'; } echo '>
											</td>
											<td>
												<input type="checkbox" class="w3-check" name="editFolderPermission[o][s]" value="1"'; if($editFolderPermission['o']['s'] == '1') { echo 'checked'; } echo '>
											</td>
										</tr>
									</table>';
									if (isset($editFolderPermissionError)) { echo '<div class="w3-text-red">' . $editFolderPermissionError . '</div>';}
									echo '
								</div>';
							}
						echo '
							<div style="height: 15px;"></div>
							<div class="w3-padding w3-center">
								<input class="w3-btn w3-blue w3-margin-bottom" type="submit" value="' . $strings['459'] . '" name="submit">
							</div>';
							
							
						echo '							
						</form>
						';
					} elseif($showAddPasswordForm === true) {
						echo '
						<h2>' . $strings['37'] . ' <a href="businessHome.php?action=cancelAddPassword" class="w3-button w3-red w3-margin w3-medium">' . $strings['44'] . ' <i class="fa fa-times-circle"></i></a></h2>
						<form class="w3-container" method="POST" action="' . htmlspecialchars($_SERVER["PHP_SELF"]) . '">';
								echo '<input type="hidden" name="formUid" value="' . $_SESSION["secureFormCode"] . '">';
								echo '<input type="hidden" name="formAction" value="addPassword">';
								echo '<input type="hidden" name="parentFolder" value="' . $parentFolderInfo['id'] . '">';
								echo '
							<div class="w3-padding-16">
								<label>' . $strings['39'] . '*</label>
								<input class="w3-input" type="text" name="newEntryName"'; if(isset($newEntryName)) { echo ' value="' . $newEntryName . '"'; } echo '>';
								if (isset($newEntryNameError)) { echo '<div class="w3-text-red">' . $newEntryNameError . '</div>';} echo '
							</div>
							<div class="w3-padding-16">
								<label>' . $strings['40'] . '</label>
								<input class="w3-input" type="text" name="newEntryUsername"'; if(isset($newEntryUsername)) { echo ' value="' . $newEntryUsername . '"'; } echo '>';
								if (isset($newEntryUsernameError)) { echo '<div class="w3-text-red">' . $newEntryUsernameError . '</div>';} echo '
							</div>
							<div class="w3-padding-16">
								<label>' . $strings['41'] . '*</label>
								<input class="w3-input" type="password" name="newEntryPassword" id="newEntryPassword"'; if(isset($newEntryPassword)) { echo ' value="' . $newEntryPassword . '"'; } echo '>
								<a class="w3-btn w3-blue" href="javascript:showPass(\'newEntryPassword\', \'newEntryPasswordButton\', \'newEntryPasswordIcon\')"><i class="fa fa-eye" id="newEntryPasswordIcon"></i> <span id="newEntryPasswordButton">' . $strings['43'] . '</span></a>';
								if (isset($newEntryPasswordError)) { echo '<div class="w3-text-red">' . $newEntryPasswordError . '</div>';} echo '
							</div>
							<div class="w3-padding-16">
								<label>' . $strings['265'] . '</label>
								<input class="w3-input" type="url" name="newEntryUrl"'; if(isset($newEntryUrl)) { echo ' value="' . $newEntryUrl . '"'; } echo '>';
								if (isset($newEntryUrlError)) { echo '<div class="w3-text-red">' . $newEntryUrlError . '</div>';} echo '
							</div>
							<div class="w3-padding-16">
								<label>' . $strings['42'] . '</label>
								<textarea class="w3-input" name="newEntryComment">'; if(isset($newEntryComment)) { echo $newEntryComment; } echo '</textarea>
							</div>
							
							<div style="height: 15px;"></div>
							
							<div class="w3-padding w3-center">
								<input class="w3-btn w3-blue w3-margin-bottom" type="submit" value="' . $strings['45'] . '" name="submit">
							</div>
						</form>';
						
					} elseif($showEditPasswordForm === true) {
						echo 'Show edit password';
					} elseif($showAddNoteForm === true) {
						echo '
						<h2>' . $strings['148'] . ' <a href="businessHome.php?action=cancelAddNote" class="w3-button w3-red w3-margin w3-medium">' . $strings['44'] . ' <i class="fa fa-times-circle"></i></a></h2>
						<form class="w3-container" method="POST" action="' . htmlspecialchars($_SERVER["PHP_SELF"]) . '">';
								echo '<input type="hidden" name="formUid" value="' . $_SESSION["secureFormCode"] . '">';
								echo '<input type="hidden" name="formAction" value="addNote">';
								echo '<input type="hidden" name="parentFolder" value="' . $parentFolderInfo['id'] . '">';
								echo '
							<div class="w3-padding-16">
								<label>' . $strings['147'] . '*</label>
								<input class="w3-input" type="text" name="newNoteEntryName"'; if(isset($newNoteEntryName)) { echo ' value="' . $newNoteEntryName . '"'; } echo '>';
								if (isset($newNoteEntryNameError)) { echo '<div class="w3-text-red">' . $newNoteEntryNameError . '</div>';} echo '
							</div>
							<div class="w3-padding-16">
								<label>' . $strings['146'] . '</label>
								<textarea class="w3-input" name="newNoteEntryContent">'; if(isset($newNoteEntryContent)) { echo $newNoteEntryContent; } echo '</textarea>
							</div>
							<div style="height: 15px;"></div>
							
							<div class="w3-padding w3-center">
								<input class="w3-btn w3-blue w3-margin-bottom" type="submit" value="' . $strings['45'] . '" name="submit">
							</div>
						</form>';
					} elseif($showEditNoteForm === true) {
						echo 'Show edit note';
					} elseif($showAddFileForm === true) {
						echo '
						<h2>' . $strings['181'] . ' <a href="businessHome.php?action=cancelAddFile" class="w3-button w3-red w3-margin w3-medium">' . $strings['44'] . ' <i class="fa fa-times-circle"></i></a></h2>
						<form class="w3-container" method="POST" action="' . htmlspecialchars($_SERVER["PHP_SELF"]) . '" enctype="multipart/form-data">';
								echo '<input type="hidden" name="formUid" value="' . $_SESSION["secureFormCode"] . '">';
								echo '<input type="hidden" name="formAction" value="addFile">';
								echo '<input type="hidden" name="parentFolder" value="' . $parentFolderInfo['id'] . '">';
								echo '
							<div class="w3-padding-16">
								<label>' . $strings['182'] . '*</label>
								<input class="w3-input" type="text" name="newFileDescription"'; if(isset($newFileDescription)) { echo ' value="' . $newFileDescription . '"'; } echo '>';
								if (isset($newFileDescriptionError)) { echo '<div class="w3-text-red">' . $newFileDescriptionError . '</div>';} echo '
							</div>
							<div class="w3-padding-16">
								<label>' . $strings['183'] . '*</label>
								<input class="w3-input" type="file" name="newFile" onChange="javascript:fileInputNameEscape(\'newFileInput\')" id="newFileInput">';
								if (isset($newFileError)) { echo '<div class="w3-text-red">' . $newFileError . '</div>';} echo '
								<span class="w3-small">' .  $strings['184'] . '</span>
							</div>
							<div style="height: 15px;"></div>
							
							<div class="w3-padding w3-center">
								<input class="w3-btn w3-blue w3-margin-bottom" type="submit" value="' . $strings['45'] . '" name="submit">
							</div>
						</form>
						';
					} elseif($showEditFileForm === true) {
						echo 'Show edit file';
					} elseif($showEntry === true) {
						echo 'Show entry';
					} else {
						// No form to display. Just show current folder records.
						$folderObjects = getFolderObjects($currentFolder['id']);
						$leafObjects = getLeafObjects($currentFolder['id']);
						usort($folderObjects, function ($a, $b) {
							return strtolower($a['name']) <=> strtolower($b['name']);
						});
						usort($leafObjects['passwords'], function ($a, $b) {
							return strtolower($a['name']) <=> strtolower($b['name']);
						});
						usort($leafObjects['notes'], function ($a, $b) {
							return strtolower($a['name']) <=> strtolower($b['name']);
						});
						usort($leafObjects['files'], function ($a, $b) {
							return strtolower($a['name']) <=> strtolower($b['name']);
						});
						foreach($folderObjects as $object) {
							if($object['deleted'] !== '1') {
								$objectPermission = getFolderEffectivePermission($object['id']);
								if(strpos($objectPermission, 'w') !== false || isBusinessOwner($currentUser['id']) || isEnterpriseAdmin($currentUser['id'])) { $write = true; } else { $write = false; }
								echo '
								<div class="w3-row">
									<div class="w3-padding w3-border-bottom w3-border-light-grey" style="position:relative;">
										<h4>
											<a style="text-decoration:none;" href="businessHome.php?folderId=' . $object['id'] . '"><i class="w3-xlarge fa fa-folder" aria-hidden="true"></i> ' . $object['name'] . '</a>';
											$owner = getBusinessUserInfoFromId($object['owner']);
											$owningGroup = getGroupInfo($object['owningGroup']);
											echo '
											<div class="w3-dropdown-hover w3-white w3-right">
												<button class="w3-button w3-round w3-text-blue w3-padding-small"><i class="w3-xlarge fa fa-ellipsis-h"></i></button>
												<div class="w3-dropdown-content w3-bar-block w3-card-4" style="right: 0;" id="menuDropdown">';
													if($write) {
														echo '
														<a style="padding: 15px!important;" class="w3-bar-item w3-button w3-large w3-border-top w3-hover-blue" href="businessHome.php?editFolder=' . $object['id'] . '">
															<i class="fa fa-pencil-square-o w3-hover-blue">
																<span style="font-family: arial;"> ' . $strings['50'] . '</span>
															</i>
														</a>';
													}
													if(isBusinessOwner($currentUser['id']) || isEnterpriseAdmin($currentUser['id']) || $currentUser['id'] == $object['owner']) {
														//Only a business admin or the owner of a folder can delete it.
														echo '
														<a style="padding: 15px!important;" class="w3-bar-item w3-button w3-large w3-border-top w3-hover-blue" href="javascript:deleteFolder(\'' . $object['id'] . '\', \'' . $object['name'] . '\')">
															<i class="fa fa-trash-o w3-hover-blue">
																<span style="font-family: arial;"> ' . $strings['51'] . '</span>
															</i>
														</a>';
													}
													echo '<div class="w3-padding-small w3-medium w3-bar-item w3-border-top" style="width: 300px;">
														' . $strings['529'];
														
														echo '<div class="w3-padding-small"><i class="fa fa-user" aria-hidden="true"></i> ' . $owner['name'] . ' (' . $object['acl']['u'] . ')</div>';
														echo '<div class="w3-padding-small"><i class="fa fa-users" aria-hidden="true"></i> ' . $owningGroup['name'][$language] . ' (' . $object['acl']['g'] . ')</div>';
														echo '<div class="w3-padding-small"><i class="fa fa-globe" aria-hidden="true"></i> ' . $strings['513'] . ' (' . $object['acl']['o'] . ')</div>';
														echo '
													</div>
												</div>
											</div>
										</h4>
									</div>
								</div>';
							}
						}
						foreach($leafObjects['passwords'] as $password) {
							if($password['deleted'] !== '1') {
								$entryPermission = getEntryEffectivePermission($password['id']);
								if(strpos($entryPermission, 'w') !== false || isBusinessOwner($currentUser['id']) || isEnterpriseAdmin($currentUser['id'])) { $write = true; } else { $write = false; }
								echo '
								<div class="w3-row">
									<div class="w3-padding w3-border-bottom w3-border-light-grey" style="position:relative;">
										<h4>
											<a style="text-decoration:none;" href="businessHome.php?entryId=' . $password['id'] . '">';
											$owner = getBusinessUserInfoFromId($password['owner']);
											$owningGroup = getGroupInfo($password['owningGroup']);
											if($password['favicon'] != '') {
												echo '<img class="favicon" src="data:' . $password['faviconType'] . ';base64, ' . $password['favicon'] . '" /> ';
											} else {
												echo '<i class="w3-xlarge fa fa-key" aria-hidden="true"></i> ';
											}
											echo '' . htmlOutput(utf8_decode($password['name'])) . '</a>';
											
										echo '
											<div class="w3-dropdown-hover w3-white w3-right">
												<button class="w3-button w3-round w3-text-blue w3-padding-small"><i class="w3-xlarge fa fa-ellipsis-h"></i></button>
												<div class="w3-dropdown-content w3-bar-block w3-card-4" style="right: 0;" id="menuDropdown">';
													if($write) {
														echo '
														<a style="padding: 15px!important;" class="w3-bar-item w3-button w3-large w3-border-top w3-hover-blue" href="businessHome.php?editPassword=' . $password['id'] . '">
															<i class="fa fa-pencil-square-o w3-hover-blue">
																<span style="font-family: arial;"> ' . $strings['50'] . '</span>
															</i>
														</a>';
														echo '
														<a style="padding: 15px!important;" class="w3-bar-item w3-button w3-large w3-border-top w3-hover-blue" href="javascript:deletePassword(\'' . $password['id'] . '\', \'' . $password['name'] . '\')">
															<i class="fa fa-trash-o w3-hover-blue">
																<span style="font-family: arial;"> ' . $strings['51'] . '</span>
															</i>
														</a>';
													}
													echo '<div class="w3-padding-small w3-medium w3-bar-item w3-border-top" style="width: 300px;">
														' . $strings['529'];
														
														echo '<div class="w3-padding-small"><i class="fa fa-user" aria-hidden="true"></i> ' . $owner['name'] . ' (' . $password['acl']['u'] . ')</div>';
														echo '<div class="w3-padding-small"><i class="fa fa-users" aria-hidden="true"></i> ' . $owningGroup['name'][$language] . ' (' . $password['acl']['g'] . ')</div>';
														echo '<div class="w3-padding-small"><i class="fa fa-globe" aria-hidden="true"></i> ' . $strings['513'] . ' (' . $password['acl']['o'] . ')</div>';
														echo '
													</div>
												</div>
											</div>
										</h4>
									</div>
								</div>';
							}
						}
					}
					 
					 
					?>
				</div>
			</div>
		</div>
		<?php include 'footer.php'; ?>
	</body>
</html>