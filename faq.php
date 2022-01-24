<?php
	include "functions.php";
	$parentPage = 'faq.php';
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
		$pageTitle = $strings['132'];
		$pageDescription = $strings['252'];
		$pageKeyworkds = $strings['253'];
		$pageIndex = 'index';
		$pageFollow = 'nofollow';
		
		
		if(isset($_GET['section'])) {
			if (strpos($_GET['section'], 'generalInformation') !== false){
				$generalInformationSection = true;
				$sectionId = $_GET['section'];
			} elseif(strpos($_GET['section'], 'bestPractice') !== false) {
				$bestPracticeSection = true;
				$sectionId = $_GET['section'];
			} elseif(strpos($_GET['section'], 'security') !== false) {
				$securitySection = true;
				$sectionId = $_GET['section'];
			} elseif(strpos($_GET['section'], 'functions') !== false) {
				$functionsSection = true;
				$sectionId = $_GET['section'];
			}
		}
		
		
		
		
				
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
					<h1 class="w3-border-bottom w3-border-blue"><?php echo $strings['132'] ?></h1>
					
						
						<?php
						
						// SELECT all FAQ without parents.
						$sql = "SELECT id, enTitle, frTitle, enContent, frContent FROM articles WHERE page='faq.php' AND parent IS NULL";
						$db_rawFaq = $conn -> query($sql);
						while ($faq = $db_rawFaq -> fetch_assoc()) {
							if($_SESSION['language'] == 'en') {
								$title = $faq['enTitle'];
								$content = $faq['enContent'];
							} elseif($_SESSION['language'] == 'fr') {
								$title = $faq['frTitle'];
								$content = $faq['frContent'];
							} else {
								$title = $faq['enTitle'];
								$content = $faq['enContent'];
							}
							
							echo '
						<div class="w3-border-bottom w3-border-blue">
							<a href="javascript:showhidePlus(\'section' . $faq['id'] . '\', \'sectionIcon' . $faq['id'] . '\')" style="text-decoration: none;">
								<h2 class="w3-padding w3-hover-blue">
									<i class="fa fa-plus-square" id="sectionIcon' . $faq['id'] . '"></i> ' . $title . '
								</h2>
							</a>
							<div class="w3-padding" id="section' . $faq['id'] . '" style="display: none">';
								// SELECT all FAQ with this parent
								$sql = "SELECT id, enTitle, frTitle, enContent, frContent FROM articles WHERE page='faq.php' AND parent='$faq[id]'";
								$db_rawSubFaq = $conn -> query($sql);
								while ($subFaq = $db_rawSubFaq -> fetch_assoc()) {
									if($_SESSION['language'] == 'en') {
										$subTitle = $subFaq['enTitle'];
										$subContent = $subFaq['enContent'];
									} elseif($_SESSION['language'] == 'fr') {
										$subTitle = $subFaq['frTitle'];
										$subContent = $subFaq['frContent'];
									} else {
										$subTitle = $faq['enTitle'];
										$subContent = $faq['enContent'];
									}
									echo '<a href="javascript:showhidePlus(\'subSection' . $subFaq['id'] . '\', \'subSectionIcon' . $subFaq['id'] . '\')" style="text-decoration: none;">
										<h3 class="w3-padding w3-hover-blue">
											<i class="fa fa-plus-square" id="subSectionIcon' . $subFaq['id'] . '"></i> ' . $subTitle . '
										</h3>
									</a>
									<div class="w3-border-bottom w3-border-blue w3-padding" id="subSection' . $subFaq['id'] . '" style="display: none">
										' . $subContent . '
										<div style="height: 20px;"></div>
									</div>';
								}
								
							echo '</div>
						</div>';
						}
						?>
				</div>
			</div>
		</div>
		<?php include 'footer.php'; ?>
	</body>
</html>