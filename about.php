<?php
	include "functions.php";
	$parentPage = 'about.php';
	$currentPage = htmlspecialchars($_SERVER["PHP_SELF"]);
	
	
	if (initiateSession()) {
		if($_GET["control"] == "logout") {
			killCookie();
			$loadContent = false;
			header("HTTP/1.1 301 Moved Permanently");
			header("Location: $currentPage");
		}
		
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
		$pageTitle = $strings['154'];
		$pageDescription = $strings['254'];
		$pageKeywords = $strings['255'];
		$pageIndex = 'index';
		$pageFollow = 'nofollow';
		
		
				
	} else {
	// invalid session. Initiate a new one by reloading the page.
	$loadContent = false;
	header("HTTP/1.1 301 Moved Permanently");
    header("Location: $currentPage");
}
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
			<div class="w3-twothird">
				<div class="w3-padding">
					<h1 class="w3-border-bottom w3-border-blue"><?php echo $strings['154'] ?></h1>
					<div class="w3-border-bottom w3-border-blue">
						<h2 class="w3-padding">
							<?php echo $strings['155'] ?>
						</h2>
						<div class="w3-padding"><?php echo $strings['157']; ?></div>
						<div class="w3-padding">
							<div class="w3-center w3-xlarge w3-circle bitcoinLogo"><i class="fa fa-btc"></i></div> Bitcoin: 13scHmSGwa8tNVpSueT1HFqSaQbaHwfuoN
								
						</div>
						<div class="w3-padding">
							<div class="w3-center w3-xlarge w3-circle paypalLogo"><i class="fa fa-paypal"></i></div> Paypal: 
							<?php echo $strings['158']; ?>
						</div>
						<div style="height: 20px;"></div>
					</div>
					
					<div class="w3-border-bottom w3-border-blue">
						<h2 class="w3-padding">
							<?php echo $strings['156'] ?>
						</h2>
						<div class="w3-padding">
							<?php echo $strings['159']; ?>
							<div style="height: 20px;"></div>
						</div>
					</div>
					
					<div class="w3-border-bottom w3-border-blue">
						<h2 class="w3-padding">
							<?php echo $strings['170'] ?>
						</h2>
						<div class="w3-padding w3-xlarge">
							<a style="color:#3578E5" href="https://www.facebook.com/mysecurevault.ca/" target="_blank"><i class="fa fa-facebook-square"> <span style="font-family: arial;">mysecurevault.ca</span></i></a><br>
							<a style="color:rgb(29, 161, 242);" href="https://twitter.com/MySecureVault" target="_blank"><i class="fa fa-twitter-square"> <span style="font-family: arial;">@MySecureVault</span></i></a><br>
							<a class="w3-text-blue" href="mailto:info@mysecurevault.ca"><i class="fa fa-envelope"> <span style="font-family: arial;">info@mysecurevault.ca</span></i></a><br>
							<div style="height: 20px;"></div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php include 'footer.php'; ?>
	</body>
</html>