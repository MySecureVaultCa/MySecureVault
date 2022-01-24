<?php
	include "functions.php";
	$parentPage = 'terms.php';
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
		$pageTitle = $strings['247'];
		$pageDescription = $strings['256'];
		$pageKeyworkds = '';
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
					<h1 class="w3-border-bottom w3-border-blue"><?php echo $strings['247'] ?></h1>
					
						
						<?php
						// SELECT timestamp of all terms to get last update date:
						$timestampArray = array();
						$sql = "SELECT id, timestamp FROM articles WHERE page='terms.php'";
						$db_rawTimestamp = $conn->query($sql);
						while($timestamp = $db_rawTimestamp->fetch_assoc()) {
							$timestampArray[] = $timestamp['timestamp'];
						}
						rsort($timestampArray);
						echo $strings['248'] . ': ' . $timestampArray['0'];
						
						
						// SELECT all terms without parents.
						$sql = "SELECT id, enTitle, frTitle, enContent, frContent FROM articles WHERE page='terms.php' AND parent IS NULL";
						$db_rawTerms = $conn -> query($sql);
						$mainSectionNumber = 1;
						
						while ($terms = $db_rawTerms -> fetch_assoc()) {
							
							if($_SESSION['language'] == 'en') {
								$title = $terms['enTitle'];
								$content = $terms['enContent'];
							} elseif($_SESSION['language'] == 'fr') {
								$title = $terms['frTitle'];
								$content = $terms['frContent'];
							} else {
								$title = $terms['enTitle'];
								$content = $terms['enContent'];
							}
							
							echo '
							<div>
							
								<h2>
									' . $mainSectionNumber . '. ' . $title . '
								</h2>
								' . $content . '
								<div class="w3-padding">';
									// SELECT all Subterms with this parent
									$sql = "SELECT id, enTitle, frTitle, enContent, frContent FROM articles WHERE page='terms.php' AND parent='$terms[id]'";
									$db_rawSubTerm = $conn -> query($sql);
									$subSectionNumber = 1;
									while ($subTerm = $db_rawSubTerm -> fetch_assoc()) {
										if($_SESSION['language'] == 'en') {
											$subTitle = $subTerm['enTitle'];
											$subContent = $subTerm['enContent'];
										} elseif($_SESSION['language'] == 'fr') {
											$subTitle = $subTerm['frTitle'];
											$subContent = $subTerm['frContent'];
										} else {
											$subTitle = $terms['enTitle'];
											$subContent = $terms['enContent'];
										}
										echo '
										<h3>
											' . $mainSectionNumber . '.' . $subSectionNumber . ' ' . $subTitle . '
										</h3>
										<div class="w3-padding">
											' . $subContent . '
											<div style="height: 20px;"></div>
										</div>';
										$subSectionNumber++;
									}
									
								echo '</div>
							</div>';
							$mainSectionNumber ++;
						}
						?>
				</div>
			</div>
		</div>
		<?php include 'footer.php'; ?>
	</body>
</html>