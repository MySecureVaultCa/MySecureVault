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
			<div class="w3-third">
				<?php
					if($activeSession) { echo 'User has an active session'; } else { echo 'User has no active session'; }
				?>
				
			</div>
			<div class="w3-third">
				
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
				
				
			</div>
		</div>
		<?php include 'footer.php'; ?>
	</body>
</html>