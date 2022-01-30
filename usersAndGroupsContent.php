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
							<h3 style="margin: 0px;"><a href="javascript:showhidePlus('users', 'usersPlus')"><i class="fa <?php if(isset($editUser) || isset($backToAddUserForm)) { echo 'fa-minus-square'; } else { echo 'fa-plus-square'; } ?>" id="usersPlus"></i></a> <a href="javascript:showhidePlus('users', 'usersPlus')"><?php echo $strings['419']; ?></a></h3>
						</div>
						<div id="users" style="display:<?php if(isset($editUser) || isset($backToAddEntryForm)) { echo 'block'; } else { echo 'none'; } ?>;">
							<div class="w3-container">
								<?php 
									echo '<a href="javascript:showhide(\'addUser\')" class="w3-button w3-blue w3-margin">' . $strings['421'] . ' <i class="fa fa-plus-circle"></i></a>';
								?>
							</div>
						</div>
					</div>
					
					<!-- Groups section -->
					<div class="w3-card-4 w3-margin">
						<div class="w3-blue w3-center" style="padding: 7px">
							<h3 style="margin: 0px;"><a href="javascript:showhidePlus('groups', 'groupsPlus')"><i class="fa <?php if(isset($editGroup) || isset($backToAddGroupForm)) { echo 'fa-minus-square'; } else { echo 'fa-plus-square'; } ?>" id="groupsPlus"></i></a> <a href="javascript:showhidePlus('groups', 'groupsPlus')"><?php echo $strings['420']; ?></a></h3>
						</div>
						<div id="groups" style="display:<?php if(isset($editGroup) || isset($backToAddGroupForm)) { echo 'block'; } else { echo 'none'; } ?>;">
							<div class="w3-container">
								<?php 
									echo '<a href="javascript:showhide(\'addGroup\')" class="w3-button w3-blue w3-margin">' . $strings['422'] . ' <i class="fa fa-plus-circle"></i></a>';
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