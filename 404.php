<?php
	include "functions.php";
	$parentPage = '404.php';
	$currentPage = htmlspecialchars($_SERVER["PHP_SELF"]);
	
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
		$pageTitle = $strings['214'];
		$pageDescription = '';
		$pageKeyworkds = '';
		$pageIndex = 'noindex';
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
	</head>
	<body>
		<?php include 'header.php'; ?>
		
		<div class="w3-row">
			<div class="w3-third">
				<?php 
					include 'leftMenu.php';
				?>
			</div>
			<div class="w3-twothird">
				<h1 class="w3-center w3-padding">
					<?php echo $strings['214']; ?> <i class="fa fa-frown-o"></i>
				</h1>
				<div class="w3-center w3-padding">
					<?php echo $strings['215']; ?>
				</div>
			</div>
		</div>
		<?php include 'footer.php'; ?>
		
	</body>
</html>