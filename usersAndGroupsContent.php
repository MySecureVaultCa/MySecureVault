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
					<h1 class="w3-border-bottom w3-border-blue"><i class="fa fa-users"></i> <?php echo $strings['416'] ?></h1>
					
					<!-- Users section -->
					<div class="w3-card-4 w3-margin">
						<div class="w3-blue w3-center" style="padding: 7px">
							<h3 style="margin: 0px;"><a href="javascript:showhidePlus('users', 'usersPlus')"><i class="fa <?php if(isset($editUser) || $showAddUserForm == true) { echo 'fa-minus-square'; } else { echo 'fa-plus-square'; } ?>" id="usersPlus"></i></a> <a href="javascript:showhidePlus('users', 'usersPlus')"><?php echo $strings['419']; ?></a></h3>
						</div>
						<div id="users" style="display:<?php if(isset($editUser) || $showAddUserForm == true) { echo 'block'; } else { echo 'none'; } ?>;">
							<div class="w3-container">
								<?php 
									if($showAddUserForm) {
										echo '<div class="w3-center"><h4 style="display:inline-block;">' . $strings['442'] . '</h4> <a href="usersAndGroups.php?action=cancelAddUser" class="w3-button w3-red w3-margin">' . $strings['44'] . ' <i class="fa fa-times-circle"></i></a></div>';
											
										echo '<form method="POST" action="' . htmlspecialchars($_SERVER["PHP_SELF"]) .  '" autocomplete="off">
											<input type="hidden" name="formAction" value="addUser">
											<input type="hidden" name="formUid" value="' . $_SESSION["secureFormCode"] . '">
											<div class="w3-padding-16">
												<label>' . $strings['429'] . '*</label>
												<input id="newUserName" class="w3-input" type="text" name="newUserName"'; if(isset($newUserName)) { echo ' value="' . $newUserName . '"'; } echo '>';
												if (isset($newUserNameError)) { echo '<div class="w3-text-red">' . $newUserNameError . '</div>';} echo '
											</div>
											
											<div class="w3-padding-16">
												<label title="' . $strings['435'] . '">' . $strings['434'] . ' <i class="fa fa-question-circle" aria-hidden="true"></i></label>
												<input id="newUserDevice" class="w3-input" type="text" name="newUserDevice"'; if(isset($newUserDevice)) { echo ' value="' . $newUserDevice . '"'; } echo '>';
												if (isset($newUserDeviceError)) { echo '<div class="w3-text-red">' . $newUserDeviceError . '</div>';} echo '
											</div>
											
											<div class="w3-padding-16">
												<label>' . $strings['12'] . '*</label>
												<input id="newUserEmail" class="w3-input" type="email" name="newUserEmail"'; if(isset($newUserEmail)) { echo ' value="' . $newUserEmail . '"'; } echo '>';
												if (isset($newUserEmailError)) { echo '<div class="w3-text-red">' . $newUserEmailError . '</div>';} echo '
											</div>
											
											<div class="w3-padding-16">
												<label>' . $strings['15'] . '*</label>
												<input id="newUserCity" class="w3-input" type="text" name="newUserCity"'; if(isset($newUserCity)) { echo ' value="' . $newUserCity . '"'; } else { echo ' value="' . $_SESSION["certCity"] . '"'; } echo '>';
												if (isset($newUserCityError)) { echo '<div class="w3-text-red">' . $newUserCityError . '</div>';} echo '
											</div>
											
											<div class="w3-padding-16">
												<label>' . $strings['14'] . '*</label>
												<input id="newUserState" class="w3-input" type="text" name="newUserState"'; if(isset($newUserState)) { echo ' value="' . $newUserState . '"'; } else { echo ' value="' . $_SESSION["certState"] . '"'; } echo '>';
												if (isset($newUserStateError)) { echo '<div class="w3-text-red">' . $newUserStateError . '</div>';} echo '
											</div>
											
											<div class="w3-padding-16">
												<label>' . $strings['13'] . '*</label>
												<select class="w3-select" name="newUserCountry">';
												if(isset($newUserCountry)) { echo countryList($newUserCountry); } else { echo countryList($_SESSION["certCountry"]); }
												echo '</select>';
												if (isset($newUserCountryError)) { echo '<div class="w3-text-red">' . $newUserCountryError . '</div>';} echo '
											</div>
											
											
											<div style="height: 20px;"></div>
											<label>' . $strings['454'] . '*</label>
											<div class="w3-row">
												<div class="w3-half w3-padding">
													<input onchange="changePasswordMethod()" id="downloadPassphraseButton" type="radio" name="newUserPassType" value="downloadPassphrase" class="w3-radio"'; if($newUserPassType == 'downloadPassphrase') { echo ' checked'; } echo '> ' . $strings['447'] . '
												</div>
												<div class="w3-half w3-padding">
													<input onchange="changePasswordMethod()" id="userPassphraseButton" type="radio" name="newUserPassType" value="userPassphrase" class="w3-radio"'; if($newUserPassType == 'userPassphrase') { echo ' checked'; } echo '> ' . $strings['444'] . '
												</div>
											</div>
											'; if (isset($newUserPassTypeError)) { echo '<div class="w3-text-red">' . $newUserPassTypeError . '</div>';} echo '
											<div id="userPassphrase" class="w3-padding w3-border w3-border-blue" style="display: '; if($newUserPassType == 'userPassphrase') { echo 'block'; } else { echo 'none'; } echo ';">
												<div>' . $strings['446'] . '</div>
												<div class="w3-padding-16">
													<label>' . $strings['444'] . '</label>
													<input id="newUserPassphrase" class="w3-input" type="password" name="newUserPassphrase"'; if(isset($newUserPassphrase)) { echo ' value="' . $newUserPassphrase . '"'; } echo '>';
													if (isset($newUserPassphraseError)) { echo '<div class="w3-text-red">' . $newUserPassphraseError . '</div>';} echo '
												</div>
												<div class="w3-padding-16">
													<label>' . $strings['445'] . '</label>
													<input id="newUserPassphraseRetype" class="w3-input" type="password" name="newUserPassphraseRetype"'; if(isset($newUserPassphraseRetype)) { echo ' value="' . $newUserPassphraseRetype . '"'; } echo '>';
													if (isset($newUserPassphraseRetypeError)) { echo '<div class="w3-text-red">' . $newUserPassphraseRetypeError . '</div>';} echo '
												</div>
											</div>
											<div id="downloadPassphrase" class="w3-padding w3-border w3-border-blue" style="display: '; if($newUserPassType == 'downloadPassphrase') { echo 'block'; } else { echo 'none'; } echo ';">
												<div>' . $strings['443'] . '</div>
												<div class="w3-padding-16">
													<label>' . $strings['290'] . '*</label>
													<input onclick="javascript:changePasswordMethod();" id="newUserDownloadPassphrase" class="w3-input" type="text" name="newUserDownloadPassphrase"'; if(isset($newUserDownloadPassphrase)) { echo ' value="' . $newUserDownloadPassphrase . '"'; } echo '>';
													if (isset($newUserDownloadPassphraseError)) { echo '<div class="w3-text-red">' . $newUserDownloadPassphraseError . '</div>';} echo '
												</div>
											</div>
											<div style="height: 20px;"></div>
											
											
											<div class="w3-padding-16">
												<label>' . $strings['430'] . '</label>
												<div id="group1" class="w3-padding">
													' . $strings['431'] . ' 1:
													<select class="w3-select" name="newUserGroup1">';
													echo businessGroupsList($newUserGroup1);
													echo '</select>';
													if (isset($newUserGroup1Error)) { echo '<div class="w3-text-red">' . $newUserGroup1Error . '</div>';} echo '
													<a href="javascript:showhide(\'group2\')"><i class="fa fa-plus-circle" aria-hidden="true"></i> ' . $strings['432'] . '</a>
												</div>
												<div id="group2" class="w3-padding" style="'; if (!ctype_digit($newUserGroup2)) { echo 'display:none;'; } else { echo 'display: block;'; } echo '">
													' . $strings['431'] . ' 2:
													<select class="w3-select" name="newUserGroup2">';
													echo businessGroupsList($newUserGroup2);
													echo '</select>';
													if (isset($newUserGroup2Error)) { echo '<div class="w3-text-red">' . $newUserGroup2Error . '</div>';} echo '
													<a href="javascript:showhide(\'group3\')"><i class="fa fa-plus-circle" aria-hidden="true"></i> ' . $strings['432'] . '</a>&nbsp;&nbsp;&nbsp;<a href="javascript:showhide(\'group2\')"><i class="fa fa-times-circle" aria-hidden="true"></i> ' . $strings['433'] . '</a>
												</div>
												<div id="group3" class="w3-padding" style="'; if (!ctype_digit($newUserGroup3)) { echo 'display:none;'; } else { echo 'display: block;'; } echo '">
													' . $strings['431'] . ' 3:
													<select class="w3-select" name="newUserGroup3">';
													echo businessGroupsList($newUserGroup3);
													echo '</select>';
													if (isset($newUserGroup3Error)) { echo '<div class="w3-text-red">' . $newUserGroup3Error . '</div>';} echo '
													<a href="javascript:showhide(\'group4\')"><i class="fa fa-plus-circle" aria-hidden="true"></i> ' . $strings['432'] . '</a>&nbsp;&nbsp;&nbsp;<a href="javascript:showhide(\'group3\')"><i class="fa fa-times-circle" aria-hidden="true"></i> ' . $strings['433'] . '</a>
												</div>
												<div id="group4" class="w3-padding" style="'; if (!ctype_digit($newUserGroup4)) { echo 'display:none;'; } else { echo 'display: block;'; } echo '">
													' . $strings['431'] . ' 4:
													<select class="w3-select" name="newUserGroup4">';
													echo businessGroupsList($newUserGroup4);
													echo '</select>';
													if (isset($newUserGroup4Error)) { echo '<div class="w3-text-red">' . $newUserGroup4Error . '</div>';} echo '
													<a href="javascript:showhide(\'group5\')"><i class="fa fa-plus-circle" aria-hidden="true"></i> ' . $strings['432'] . '</a>&nbsp;&nbsp;&nbsp;<a href="javascript:showhide(\'group4\')"><i class="fa fa-times-circle" aria-hidden="true"></i> ' . $strings['433'] . '</a>
												</div>
												<div id="group5" class="w3-padding" style="'; if (!ctype_digit($newUserGroup5)) { echo 'display:none;'; } else { echo 'display: block;'; } echo '">
													' . $strings['431'] . ' 5:
													<select class="w3-select" name="newUserGroup5">';
													echo businessGroupsList($newUserGroup5);
													echo '</select>';
													if (isset($newUserGroup5Error)) { echo '<div class="w3-text-red">' . $newUserGroup5Error . '</div>';} echo '
													<a href="javascript:showhide(\'group5\')"><i class="fa fa-times-circle" aria-hidden="true"></i> ' . $strings['433'] . '</a>
												</div>
												<div class="w3-padding w3-center">
													<input class="w3-btn w3-blue w3-margin-bottom" type="submit" value="' . $strings['438'] . '" name="submit">
												</div>
											</div>
											
										</form>';
									} elseif ($showEditUserForm){
										// Edit user form
										echo '<div class="w3-center"><h4 style="display:inline-block;">' . $strings['458'] . ' ' . $editUser['name'] . ' (' . $editUser['email'] . ')</h4> <a href="usersAndGroups.php?action=cancelEditUser" class="w3-button w3-red w3-margin">' . $strings['44'] . ' <i class="fa fa-times-circle"></i></a></div>';
										echo '<form method="POST" action="' . htmlspecialchars($_SERVER["PHP_SELF"]) .  '" autocomplete="off">
											<input type="hidden" name="formAction" value="editUser">
											<input type="hidden" name="editUser" value="' . $editUser['id'] . '">
											<input type="hidden" name="formUid" value="' . $_SESSION["secureFormCode"] . '">
											<div class="w3-padding-16">
												<label>' . $strings['429'] . '*</label>
												<input id="editUserName" class="w3-input" type="text" name="editUserName"'; if(isset($editUserName)) { echo ' value="' . $editUserName . '"'; } echo '>';
												if (isset($editUserNameError)) { echo '<div class="w3-text-red">' . $editUserNameError . '</div>';} echo '
											</div>
											
											<div class="w3-padding-16">
												<label>' . $strings['12'] . '*</label>
												<input id="editUserEmail" class="w3-input" type="email" name="editUserEmail"'; if(isset($editUserEmail)) { echo ' value="' . $editUserEmail . '"'; } echo '>';
												if (isset($editUserEmailError)) { echo '<div class="w3-text-red">' . $editUserEmailError . '</div>';} echo '
											</div>
											
											<div class="w3-padding-16">
												<label>' . $strings['15'] . '*</label>
												<input id="editUserCity" class="w3-input" type="text" name="editUserCity"'; if(isset($editUserCity)) { echo ' value="' . $editUserCity . '"'; } else { echo ' value="' . $_SESSION["certCity"] . '"'; } echo '>';
												if (isset($newUserCityError)) { echo '<div class="w3-text-red">' . $newUserCityError . '</div>';} echo '
											</div>
											
											<div class="w3-padding-16">
												<label>' . $strings['14'] . '*</label>
												<input id="editUserState" class="w3-input" type="text" name="editUserState"'; if(isset($editUserState)) { echo ' value="' . $editUserState . '"'; } else { echo ' value="' . $_SESSION["certState"] . '"'; } echo '>';
												if (isset($editUserStateError)) { echo '<div class="w3-text-red">' . $editUserStateError . '</div>';} echo '
											</div>
											
											<div class="w3-padding-16">
												<label>' . $strings['13'] . '*</label>
												<select class="w3-select" name="editUserCountry">';
												if(isset($editUserCountry)) { echo countryList($editUserCountry); } else { echo countryList($_SESSION["certCountry"]); }
												echo '</select>';
												if (isset($editUserCountryError)) { echo '<div class="w3-text-red">' . $editUserCountryError . '</div>';} echo '
											</div>
											
											<div class="w3-padding-16">
												<label>' . $strings['430'] . '</label>
												<div id="group1" class="w3-padding">
													' . $strings['431'] . ' 1:
													<select class="w3-select" name="editUserGroup1">';
													echo businessGroupsList($editUserGroup1);
													echo '</select>';
													if (isset($editUserGroup1Error)) { echo '<div class="w3-text-red">' . $editUserGroup1Error . '</div>';} echo '
													<a href="javascript:showhide(\'group2\')"><i class="fa fa-plus-circle" aria-hidden="true"></i> ' . $strings['432'] . '</a>
												</div>
												<div id="group2" class="w3-padding" style="'; if (!ctype_digit($editUserGroup2)) { echo 'display:none;'; } else { echo 'display: block;'; } echo '">
													' . $strings['431'] . ' 2:
													<select class="w3-select" name="editUserGroup2">';
													echo businessGroupsList($editUserGroup2);
													echo '</select>';
													if (isset($editUserGroup2Error)) { echo '<div class="w3-text-red">' . $editUserGroup2Error . '</div>';} echo '
													<a href="javascript:showhide(\'group3\')"><i class="fa fa-plus-circle" aria-hidden="true"></i> ' . $strings['432'] . '</a>&nbsp;&nbsp;&nbsp;<a href="javascript:showhide(\'group2\')"><i class="fa fa-times-circle" aria-hidden="true"></i> ' . $strings['433'] . '</a>
												</div>
												<div id="group3" class="w3-padding" style="'; if (!ctype_digit($editUserGroup3)) { echo 'display:none;'; } else { echo 'display: block;'; } echo '">
													' . $strings['431'] . ' 3:
													<select class="w3-select" name="editUserGroup3">';
													echo businessGroupsList($editUserGroup3);
													echo '</select>';
													if (isset($editUserGroup3Error)) { echo '<div class="w3-text-red">' . $editUserGroup3Error . '</div>';} echo '
													<a href="javascript:showhide(\'group4\')"><i class="fa fa-plus-circle" aria-hidden="true"></i> ' . $strings['432'] . '</a>&nbsp;&nbsp;&nbsp;<a href="javascript:showhide(\'group3\')"><i class="fa fa-times-circle" aria-hidden="true"></i> ' . $strings['433'] . '</a>
												</div>
												<div id="group4" class="w3-padding" style="'; if (!ctype_digit($editUserGroup4)) { echo 'display:none;'; } else { echo 'display: block;'; } echo '">
													' . $strings['431'] . ' 4:
													<select class="w3-select" name="editUserGroup4">';
													echo businessGroupsList($editUserGroup4);
													echo '</select>';
													if (isset($editUserGroup4Error)) { echo '<div class="w3-text-red">' . $editUserGroup4Error . '</div>';} echo '
													<a href="javascript:showhide(\'group5\')"><i class="fa fa-plus-circle" aria-hidden="true"></i> ' . $strings['432'] . '</a>&nbsp;&nbsp;&nbsp;<a href="javascript:showhide(\'group4\')"><i class="fa fa-times-circle" aria-hidden="true"></i> ' . $strings['433'] . '</a>
												</div>
												<div id="group5" class="w3-padding" style="'; if (!ctype_digit($editUserGroup5)) { echo 'display:none;'; } else { echo 'display: block;'; } echo '">
													' . $strings['431'] . ' 5:
													<select class="w3-select" name="editUserGroup5">';
													echo businessGroupsList($editUserGroup5);
													echo '</select>';
													if (isset($editUserGroup5)) { echo '<div class="w3-text-red">' . $editUserGroup5 . '</div>';} echo '
													<a href="javascript:showhide(\'group5\')"><i class="fa fa-times-circle" aria-hidden="true"></i> ' . $strings['433'] . '</a>
												</div>
												<div class="w3-padding w3-center">
													<input class="w3-btn w3-blue w3-margin-bottom" type="submit" value="' . $strings['459'] . '" name="submit">
												</div>
											</div>
											
										</form>';
									} else {
										// No action, just display the list of groups...
										echo '<a href="usersAndGroups.php?action=addUser" class="w3-button w3-blue w3-margin">' . $strings['421'] . ' <i class="fa fa-plus-circle"></i></a>';
										$effectivePermissions = getBusinessManagementPermissions();
										$businessUsers = getAllBusinessUsers();
										$currentUser = getBusinessUserInfo($_SESSION['certId']);
										$currentUserGroups = getBusinessUserGroups($currentUser['id']);
										foreach($businessUsers as $user) {
											$userGroups = getBusinessUserGroups($user['id']);
											echo '
											<div class="w3-padding w3-border-top w3-border-light-grey">
												<div class="w3-row">
													<div class="w3-half">
														<h4><a href="javascript:showhide(\'user' . $user['id'] . '\')">' . $user['name'] . ' (' . $user['email'] . ')</a></h4>
													</div>
													<div class="w3-half">';
														if (isEnterpriseAdmin($currentUser['id']) && isEnterpriseAdmin($user['id'])) {
															echo '<div class="w3-padding-small" style="display:inline-block;"><a class="w3-btn w3-blue" href="usersAndGroups.php?editUser=' . $user['id'] . '"><i class="fa fa-pencil-square-o" aria-hidden="true"></i> ' . $strings['50'] . '</a></div>';
															if($currentUser['id'] != $user['id']) { echo '<div class="w3-padding-small" style="display:inline-block;"><a class="w3-btn w3-red" href="javascript:deleteUser(\'' . $user['id'] . '\', \'' . $user['name'] . '\')"><i class="fa fa-trash-o" aria-hidden="true"></i> ' . $strings['51'] . '</a></div>'; }
														} elseif (isEnterpriseAdmin($user['id']) === false) {
															echo '<div class="w3-padding-small" style="display:inline-block;"><a class="w3-btn w3-blue" href="usersAndGroups.php?editUser=' . $user['id'] . '"><i class="fa fa-pencil-square-o" aria-hidden="true"></i> ' . $strings['50'] . '</a></div>';
															if($currentUser['id'] != $user['id']) { echo '<div class="w3-padding-small" style="display:inline-block;"><a class="w3-btn w3-red" href="javascript:deleteUser(\'' . $user['id'] . '\', \'' . $user['name'] . '\')"><i class="fa fa-trash-o" aria-hidden="true"></i> ' . $strings['51'] . '</a></div>'; }
														}
															
													echo '</div>
												</div>
												<div id="user' . $user['id'] . '" style="display:none" class="w3-padding">
													<div class="w3-small">' . $strings['453'] . ': ' . $user['id'] . '</div>
													<div style="height: 20px;"></div>
													<div class="w3-row">
														<div class="w3-half w3-padding">
															<div>' . $strings['436'] . ':<br>' . $user['lastLogin'] . '</div>
														</div>
														<div class="w3-half w3-padding">
															<div>' . $strings['437'] . ':<br>' . $user['lastActivity'] . '</div>
														</div>
													</div>
													<div class="w3-row">
														<div class="w3-half">
															<h5 style="margin-bottom: 0px;"><b>' . $strings['423'] . '</b></h5>
															<div class="w3-padding">';
															foreach($userGroups as $group) {
																$groupInfo = getGroupInfo($group);
																echo '<div title="' . $groupInfo['description'] . '">' . $groupInfo['name'] . '</div>';
															}
														echo '
															</div>
														</div>
														<div class="w3-half">
															<h5 style="margin-bottom: 0px;"><b>' . $strings['424'] . '</b></h5>
															<div class="w3-padding w3-card-4">';
															foreach($user['certs'] as $cert) {
																$certInfo = getBusinessUserCertInfo($cert);
																echo '<h4 style="border-top: 0px; border-bottom: 0px;" class="w3-text-'; if($certInfo['revoked'] == '1') { echo 'red'; } else { echo 'black'; } echo '"><i class="fa fa-id-card-o"></i> ' . utf8_decode($certInfo['fullName']) . ' (' . $certInfo['emailAddress'] . ')</h4>
																' . $strings['79'] . ': ' . $certInfo['serial'] . '<br>
																' . $strings['234'] . ':' . $certInfo['validTo'] . ' <span class="w3-small">(' . $certInfo['daysToExpire'] . ' ' . $strings['258'] . ')</span><br>
																' . $strings['426'] . ': ';
																if($certInfo['revoked'] == '0') {
																	// Certificate is active
																	echo '<span class="w3-text-green">' . $strings['425'] . '</span><br>';
																	if (isEnterpriseAdmin($currentUser['id']) && isEnterpriseAdmin($user['id'])) {
																		echo '<div class="w3-padding-small" style="display:inline-block;"><a class="w3-btn w3-blue" href="' . $_SERVER["PHP_SELF"] . '?downloadCert=' . $certInfo['id'] . '&reload=no">' . $strings['83'] . ' <i class="fa fa-download"></i></a></div>';
																	} elseif (isEnterpriseAdmin($user['id']) === false) {
																		echo '<div class="w3-padding-small" style="display:inline-block;"><a class="w3-btn w3-blue" href="' . $_SERVER["PHP_SELF"] . '?downloadCert=' . $certInfo['id'] . '&reload=no">' . $strings['83'] . ' <i class="fa fa-download"></i></a></div>';
																	}
																	echo '<div class="w3-padding-small" style="display:inline-block;"><a class="w3-btn w3-blue" href="' . $_SERVER["PHP_SELF"] . '?sendByEmail=' . $certInfo['id'] . '&type=certificate">' . $strings['277'] . ' <i class="fa fa-share-square-o"></i></a></div>';
																	if($certInfo['id'] != $_SESSION['certId']) {	
																		// Not current session's certificate, ok to show revoke option.
																		if (isEnterpriseAdmin($currentUser['id']) && isEnterpriseAdmin($user['id'])) {
																			echo '<div class="w3-padding-small" style="display:inline-block;"><a class="w3-btn w3-red" href="javascript:void(0)" onclick="revokeCert(\'' . $certInfo['id'] . '\', \'' . $certInfo['fullName'] . ' (' . $certInfo['serial'] . ')\')">' . $strings['84'] . ' <i class="fa fa-times"></i></a></div>';
																		} elseif (isEnterpriseAdmin($user['id']) === false) {
																			echo '<div class="w3-padding-small" style="display:inline-block;"><a class="w3-btn w3-red" href="javascript:void(0)" onclick="revokeCert(\'' . $certInfo['id'] . '\', \'' . $certInfo['fullName'] . ' (' . $certInfo['serial'] . ')\')">' . $strings['84'] . ' <i class="fa fa-times"></i></a></div>';
																		}
																	}
																} else {
																	// Certificate is revoked. Offer to reinstate or delete.
																	echo '<span class="w3-text-red">' . $strings['427'] . '</span><br>';
																	if (isEnterpriseAdmin($currentUser['id']) && isEnterpriseAdmin($user['id'])) {
																		echo '<div class="w3-padding-small" style="display:inline-block;"><a class="w3-btn w3-blue" href="javascript:void(0)" onclick="unrevokeCert(\'' . $certInfo['id'] . '\', \'' . $certInfo['fullName'] . ' (' . $certInfo['serial'] . ')\')">' . $strings['85'] . ' <i class="fa fa-repeat"></i></a></div>';
																		echo '<div class="w3-padding-small" style="display:inline-block;"><a class="w3-btn w3-red" href="javascript:void(0)" onclick="deleteCert(\'' . $certInfo['id'] . '\', \'' . javascriptEscape($certInfo['fullName']) . ' (' . $certInfo['serial'] . ')\')">' . $strings['86'] . ' <i class="fa fa-times"></i></a></div>';
																	} elseif (isEnterpriseAdmin($user['id']) === false) {
																		echo '<div class="w3-padding-small" style="display:inline-block;"><a class="w3-btn w3-blue" href="javascript:void(0)" onclick="unrevokeCert(\'' . $certInfo['id'] . '\', \'' . $certInfo['fullName'] . ' (' . $certInfo['serial'] . ')\')">' . $strings['85'] . ' <i class="fa fa-repeat"></i></a></div>';
																		echo '<div class="w3-padding-small" style="display:inline-block;"><a class="w3-btn w3-red" href="javascript:void(0)" onclick="deleteCert(\'' . $certInfo['id'] . '\', \'' . javascriptEscape($certInfo['fullName']) . ' (' . $certInfo['serial'] . ')\')">' . $strings['86'] . ' <i class="fa fa-times"></i></a></div>';
																	}
																	
																}
																echo '';
																// var_dump($certInfo);
															}
															
														echo '	
															</div>
														</div>
													</div>
													<div style="height: 20px;"></div>
												</div>
											</div>';
										}
									}
									
								?>
							</div>
						</div>
					</div>
					
					<!-- Groups section -->
					<div class="w3-card-4 w3-margin">
						<div class="w3-blue w3-center" style="padding: 7px">
							<h3 style="margin: 0px;"><a href="javascript:showhidePlus('groups', 'groupsPlus')"><i class="fa <?php if(isset($editGroup) || isset($backToAddGroupForm)) { echo 'fa-minus-square'; } else { echo 'fa-plus-square'; } ?>" id="groupsPlus"></i></a> <a href="javascript:showhidePlus('groups', 'groupsPlus')"><?php echo $strings['420']; ?></a></h3>
						</div>
						<div id="groups" style="display:<?php if(isset($editGroup) || $showAddGroupForm == true) { echo 'block'; } else { echo 'none'; } ?>;">
							<div class="w3-container">
								<?php 
									if($showAddGroupForm) {
										echo '<a href="usersAndGroups.php?action=cancelAddGroup" class="w3-button w3-red w3-margin">' . $strings['44'] . ' <i class="fa fa-plus-circle"></i></a>';
									} elseif ($showEditGroupForm){
										
									} else {
										echo '<a href="usersAndGroups.php?action=addGroup" class="w3-button w3-blue w3-margin">' . $strings['422'] . ' <i class="fa fa-plus-circle"></i></a>';
									}
									
								?>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php include 'footer.php'; ?>
	</body>
</html>