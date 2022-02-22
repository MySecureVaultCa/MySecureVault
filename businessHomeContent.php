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
		
		function searchGroup() {
			var searchString = document.getElementById("groupSearchField").value;
			var lowerCaseString = searchString.toLowerCase();
			// alert(searchString);
			
			var entries = document.getElementsByClassName('searchableGroup');
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
		
		function deleteCert(certId, certName) {
			var r = confirm("<?php echo $strings['61'] ?>:\n" + certName);
			if ( r == true ) {
				document.location = "usersAndGroups.php?deleteCert=" + certId;
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
					<h1 class="w3-border-bottom w3-border-blue"><i class="fa fa-home"></i> 
						<?php
						$hierarchy = buildHierarchy($currentFolder['id']);
						if($hierarchy[depth] === 0) {
							echo $strings['500'];
						} else {
							while(is_array($hierarchy['subfolder'])) {
								echo '/<a href="businessHome.php?folderId=' . $hierarchy['id'] . '">' . $hierarchy['name'] . '</a>';
								$hierarchy = $hierarchy['subfolder'];
							}
							echo '/' . $hierarchy['name'];
						}
						?>
					</h1>
					
						<?php 
						 if(strpos($effectiveFolderPermissions, 'w') !== false && $showAddFolderForm !== true) {
							 // User has write permission. Display buttons accordingly
							 echo '
							 <div class="w3-border-bottom w3-border-blue">
								 <div class="w3-padding-small" style="display:inline-block;">
									<a class="w3-btn w3-blue" href="businessHome.php?action=addFolder&parentFolder=' . $currentFolder['id'] . '"><i class="fa fa-folder" aria-hidden="true"></i> ' . $strings['499'] . '</a>
								 </div>
								 <div class="w3-padding-small" style="display:inline-block;">
									<a class="w3-btn w3-blue" href="businessHome.php?action=addPassword&parentFolder=' . $currentFolder['id'] . '"><i class="fa fa-key" aria-hidden="true"></i> ' . $strings['501'] . '</a>
								 </div>
								 <div class="w3-padding-small" style="display:inline-block;">
									<a class="w3-btn w3-blue" href="businessHome.php?action=addNote&parentFolder=' . $currentFolder['id'] . '"><i class="fa fa-sticky-note" aria-hidden="true"></i> ' . $strings['502'] . '</a>
								 </div>
								 <div class="w3-padding-small" style="display:inline-block;">
									<a class="w3-btn w3-blue" href="businessHome.php?action=addFile&parentFolder=' . $currentFolder['id'] . '"><i class="fa fa-file-o" aria-hidden="true"></i> ' . $strings['503'] . '</a>
								 </div>
								 <div style="height:10px;"></div>
							 </div>';
						 }
						 
						 if($showAddFolderForm === true) {
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
								</div>
								
								
								
								<div class="w3-padding-16">
									' . $strings['506'] . '
								</div>
								
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
											' . $strings['509'] . '
										</td>
										<td>
											<input type="checkbox" class="w3-check" name="u[r]" value="1"'; if($newFolderPermission['u']['r'] == '1') { echo 'checked'; } echo '>
										</td>
										<td>
											<input type="checkbox" class="w3-check" name="g[r]" value="1"'; if($newFolderPermission['g']['r'] == '1') { echo 'checked'; } echo '>
										</td>
										<td>
											<input type="checkbox" class="w3-check" name="o[r]" value="1"'; if($newFolderPermission['o']['r'] == '1') { echo 'checked'; } echo '>
										</td>
									</tr>
									<tr>
										<td>
											' . $strings['510'] . '
										</td>
										<td>
											<input type="checkbox" class="w3-check" name="u[w]" value="1"'; if($newFolderPermission['u']['w'] == '1') { echo 'checked'; } echo '>
										</td>
										<td>
											<input type="checkbox" class="w3-check" name="g[w]" value="1"'; if($newFolderPermission['g']['w'] == '1') { echo 'checked'; } echo '>
										</td>
										<td>
											<input type="checkbox" class="w3-check" name="o[w]" value="1"'; if($newFolderPermission['o']['w'] == '1') { echo 'checked'; } echo '>
										</td>
									</tr>
									<tr>
										<td>
											' . $strings['511'] . '
										</td>
										<td>
											<input type="checkbox" class="w3-check" name="u[s]" value="1"'; if($newFolderPermission['u']['s'] == '1') { echo 'checked'; } echo '>
										</td>
										<td>
											<input type="checkbox" class="w3-check" name="g[s]" value="1"'; if($newFolderPermission['g']['s'] == '1') { echo 'checked'; } echo '>
										</td>
										<td>
											<input type="checkbox" class="w3-check" name="o[s]" value="1"'; if($newFolderPermission['o']['s'] == '1') { echo 'checked'; } echo '>
										</td>
									</tr>
									<tr>
										<td>
											' . $strings['512'] . '
										</td>
										<td>
											<input type="checkbox" class="w3-check" name="u[b]" value="1"'; if($newFolderPermission['u']['b'] == '1') { echo 'checked'; } echo '>
										</td>
										<td>
											<input type="checkbox" class="w3-check" name="g[b]" value="1"'; if($newFolderPermission['g']['b'] == '1') { echo 'checked'; } echo '>
										</td>
										<td>
											<input type="checkbox" class="w3-check" name="o[b]" value="1"'; if($newFolderPermission['o']['b'] == '1') { echo 'checked'; } echo '>
										</td>
									</tr>
								</table>
							 
							 ';
						 }
						 
						 
						?>
					
					
					
					
				</div>
			</div>
		</div>
		<?php include 'footer.php'; ?>
	</body>
</html>