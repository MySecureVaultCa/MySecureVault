<?php

include "../functions.php";


if (initiateSession()) {
	
	if($_GET["control"] == "logout") {
		killCookie();
		$loadContent = false;
		header("HTTP/1.1 301 Moved Permanently");
		header("Location: /index.php");
	}
	
	if(!databaseConnection()) {
		// echo 'Cannot connect to database';
		$loadContent = false;
		header("HTTP/1.1 301 Moved Permanently");
		header("Location: /index.php");
	}
	
	if(in_array($_SESSION['userId'], $admins)) {
		$isAdmin = true;
	} else {
		$isAdmin = false;
	}
	
	if($_POST["reload"] == "no") {
		// Do nothing...
	} else {
		if (!secureForm($_POST["formUid"])) {
			$backToForm = true;
			$alert_message = "Impossible de soumettre le formulaire. Valeur de POST incorrecte.";
			$loadContent = true;
		}
	}
	
	if(isset($_SESSION["token"])) {
		if (authenticateToken()) {
			$loadContent = true;
			
			if(isset($_GET["setLanguage"])) {
				if ($_GET["setLanguage"] == "en") {
					$_SESSION["language"] = "en";
				} elseif($_GET["setLanguage"] == "fr") {
					$_SESSION["language"] = "fr";
				} else {
					$_SESSION["language"] = "en";
				}
				// Update language in certificate
				changeCertLanguage($_SESSION['language']);
			}
			
			// Get language strings...
			if(!isset($_SESSION['language'])) { $_SESSION['language'] = 'en'; }
			$language = $_SESSION['language'];
			$sql = "SELECT id, $language FROM langStrings"; 
			$db_rawStrings = $conn->query($sql);
			$strings = array();
			while($row = $db_rawStrings->fetch_assoc()) {
				$stringId = $row['id'];
				$strings["$stringId"] = $row["$language"];
			}
			
			// Start accepting and validating POST and GET
			
			if($_SERVER["REQUEST_METHOD"] == "POST" && $_POST["formAction"] == "addSection" && $backToForm != true) {
				$enTitle = htmlData($_POST['enTitle']);
				$frTitle = htmlData($_POST['frTitle']);
				$enContent = htmlData($_POST['enContent']);
				$frContent = htmlData($_POST['frContent']);
				
				if($_POST['enTitle'] == '' || strlen($_POST['enTitle']) > 512) {
					$backToForm = true;
					$errorEnTitle = 'Vous devez saisir un titre anglais de moins de 512 caract&egrave;res';
				}
				
				if($_POST['frTitle'] == '' || strlen($_POST['frTitle']) > 512) {
					$backToForm = true;
					$errorFrTitle = 'Vous devez saisir un titre fran&ccedil;ais de moins de 512 caract&egrave;res';
				}
				
				if(strlen($_POST['enContent']) > 1000000) {
					$backToForm = true;
					$errorEnContent = 'Vous devez saisir un titre anglais de moins de 1 000 000 de caract&egrave;res';
				}
				
				if(strlen($_POST['frContent']) > 1000000) {
					$backToForm = true;
					$errorFrContent = 'Vous devez saisir un titre fran&ccedil;ais de moins de 1 000 000 de caract&egrave;res';
				}
				
				if($backToForm === true) {
					$backToAddSectionForm = true;
				} else {
					// Allright, add entry!
					
					$enTitle = mysqli_real_escape_string($conn, $enTitle);
					$frTitle = mysqli_real_escape_string($conn, $frTitle);
					$enContent = mysqli_real_escape_string($conn, $enContent);
					$frContent = mysqli_real_escape_string($conn, $frContent);
					
					$sql = "INSERT INTO articles (page, enTitle, frTitle, enContent, frContent) VALUES ('faq.php', '$enTitle', '$frTitle', '$enContent', '$frContent')";
					$conn -> query($sql);
				}
			}
			
			if($_SERVER["REQUEST_METHOD"] == "POST" && $_POST["formAction"] == "addSubSection" && ctype_digit($_POST['parentSection']) && $backToForm != true) {
				$enTitle = htmlData($_POST['enTitle']);
				$frTitle = htmlData($_POST['frTitle']);
				$enContent = htmlData($_POST['enContent']);
				$frContent = htmlData($_POST['frContent']);
				$parentSection = mysqli_real_escape_string($conn, $_POST['parentSection']);
				
				$sql = "SELECT id, parent FROM articles WHERE id='$parentSection'";
				$db_rawArticle=$conn->query($sql);
				if (mysqli_num_rows($db_rawArticle) == 1) {
					$db_article = $db_rawArticle -> fetch_assoc();
					if($db_article['parent'] == '') {
						$hasParent = true;
					} else {
						$backToForm = true;
					}
				} else {
					$backToForm = true;
				}
				
				if($_POST['enTitle'] == '' || strlen($_POST['enTitle']) > 512) {
					$backToForm = true;
					$errorSubEnTitle = 'Vous devez saisir un titre anglais de moins de 512 caract&egrave;res';
				}
				
				if($_POST['frTitle'] == '' || strlen($_POST['frTitle']) > 512) {
					$backToForm = true;
					$errorSubFrTitle = 'Vous devez saisir un titre fran&ccedil;ais de moins de 512 caract&egrave;res';
				}
				
				if(strlen($_POST['enContent']) > 1000000) {
					$backToForm = true;
					$errorSubEnContent = 'Vous devez saisir un titre anglais de moins de 1 000 000 de caract&egrave;res';
				}
				
				if(strlen($_POST['frContent']) > 1000000) {
					$backToForm = true;
					$errorSubFrContent = 'Vous devez saisir un titre fran&ccedil;ais de moins de 1 000 000 de caract&egrave;res';
				}
				
				if($backToForm == true) {
					$backToAddSubSectionForm = $parentSection;
				} else {
					// Allright, add entry!
					$enTitle = mysqli_real_escape_string($conn, $enTitle);
					$frTitle = mysqli_real_escape_string($conn, $frTitle);
					$enContent = mysqli_real_escape_string($conn, $enContent);
					$frContent = mysqli_real_escape_string($conn, $frContent);
					
					$sql = "INSERT INTO articles (page, parent, enTitle, frTitle, enContent, frContent) VALUES ('faq.php', '$parentSection', '$enTitle', '$frTitle', '$enContent', '$frContent')";
					$conn -> query($sql);
				}
			}
			
			if($_SERVER["REQUEST_METHOD"] == "POST" && $_POST["formAction"] == "editSection" && ctype_digit($_POST['sectionId']) && $backToForm != true) {
				$enTitle = htmlData($_POST['enTitle']);
				$frTitle = htmlData($_POST['frTitle']);
				$enContent = htmlData($_POST['enContent']);
				$frContent = htmlData($_POST['frContent']);
				$editSection = mysqli_real_escape_string($conn, $_POST['sectionId']);
				
				if($_POST['enTitle'] == '' || strlen($_POST['enTitle']) > 512) {
					$backToForm = true;
					$errorEnTitle = 'Vous devez saisir un titre anglais de moins de 512 caract&egrave;res';
				}
				
				if($_POST['frTitle'] == '' || strlen($_POST['frTitle']) > 512) {
					$backToForm = true;
					$errorFrTitle = 'Vous devez saisir un titre fran&ccedil;ais de moins de 512 caract&egrave;res';
				}
				
				if(strlen($_POST['enContent']) > 1000000) {
					$backToForm = true;
					$errorEnContent = 'Vous devez saisir un titre anglais de moins de 1 000 000 de caract&egrave;res';
				}
				
				if(strlen($_POST['frContent']) > 1000000) {
					$backToForm = true;
					$errorFrContent = 'Vous devez saisir un titre fran&ccedil;ais de moins de 1 000 000 de caract&egrave;res';
				}
				
				if($backToForm == true) {
					
				} else {
					// Allright, update entry!
					$currentDate = date('Y-m-d H:i:s');
					$enTitle = mysqli_real_escape_string($conn, $enTitle);
					$frTitle = mysqli_real_escape_string($conn, $frTitle);
					$enContent = mysqli_real_escape_string($conn, $enContent);
					$frContent = mysqli_real_escape_string($conn, $frContent);
					
					$sql = "UPDATE articles SET timestamp='$currentDate', enTitle='$enTitle', frTitle='$frTitle', enContent='$enContent', frContent='$frContent' WHERE id='$editSection'";
					if ($conn -> query($sql)) {
						echo 'Success';
					} else {
						echo $conn->error;
					}
					
					unset($editSection);
				}
			}
			
			if($_SERVER["REQUEST_METHOD"] == "POST" && $_POST["formAction"] == "addTermsSection" && $backToForm != true) {
				$enTitle = htmlData($_POST['enTitle']);
				$frTitle = htmlData($_POST['frTitle']);
				$enContent = htmlData($_POST['enContent']);
				$frContent = htmlData($_POST['frContent']);
				
				if($_POST['enTitle'] == '' || strlen($_POST['enTitle']) > 512) {
					$backToForm = true;
					$errorTermsEnTitle = 'Vous devez saisir un titre anglais de moins de 512 caract&egrave;res';
				}
				
				if($_POST['frTitle'] == '' || strlen($_POST['frTitle']) > 512) {
					$backToForm = true;
					$errorTermsFrTitle = 'Vous devez saisir un titre fran&ccedil;ais de moins de 512 caract&egrave;res';
				}
				
				if(strlen($_POST['enContent']) > 1000000) {
					$backToForm = true;
					$errorTermsEnContent = 'Vous devez saisir un titre anglais de moins de 1 000 000 de caract&egrave;res';
				}
				
				if(strlen($_POST['frContent']) > 1000000) {
					$backToForm = true;
					$errorTermsFrContent = 'Vous devez saisir un titre fran&ccedil;ais de moins de 1 000 000 de caract&egrave;res';
				}
				
				if($backToForm === true) {
					$backToAddTermsSectionForm = true;
				} else {
					// Allright, add entry!
					
					$enTitle = mysqli_real_escape_string($conn, $enTitle);
					$frTitle = mysqli_real_escape_string($conn, $frTitle);
					$enContent = mysqli_real_escape_string($conn, $enContent);
					$frContent = mysqli_real_escape_string($conn, $frContent);
					
					$sql = "INSERT INTO articles (page, enTitle, frTitle, enContent, frContent) VALUES ('terms.php', '$enTitle', '$frTitle', '$enContent', '$frContent')";
					$conn -> query($sql);
				}
			}
			
			if($_SERVER["REQUEST_METHOD"] == "POST" && $_POST["formAction"] == "addTermsSubSection" && ctype_digit($_POST['parentSection']) && $backToForm != true) {
				$enTitle = htmlData($_POST['enTitle']);
				$frTitle = htmlData($_POST['frTitle']);
				$enContent = htmlData($_POST['enContent']);
				$frContent = htmlData($_POST['frContent']);
				$parentSection = mysqli_real_escape_string($conn, $_POST['parentSection']);
				
				$sql = "SELECT id, parent FROM articles WHERE id='$parentSection'";
				$db_rawArticle=$conn->query($sql);
				if (mysqli_num_rows($db_rawArticle) == 1) {
					$db_article = $db_rawArticle -> fetch_assoc();
					if($db_article['parent'] == '') {
						$hasParent = true;
					} else {
						$backToForm = true;
					}
				} else {
					$backToForm = true;
				}
				
				if($_POST['enTitle'] == '' || strlen($_POST['enTitle']) > 512) {
					$backToForm = true;
					$errorTermsSubEnTitle = 'Vous devez saisir un titre anglais de moins de 512 caract&egrave;res';
				}
				
				if($_POST['frTitle'] == '' || strlen($_POST['frTitle']) > 512) {
					$backToForm = true;
					$errorTermsSubFrTitle = 'Vous devez saisir un titre fran&ccedil;ais de moins de 512 caract&egrave;res';
				}
				
				if(strlen($_POST['enContent']) > 1000000) {
					$backToForm = true;
					$errorTermsSubEnContent = 'Vous devez saisir un titre anglais de moins de 1 000 000 de caract&egrave;res';
				}
				
				if(strlen($_POST['frContent']) > 1000000) {
					$backToForm = true;
					$errorTermsSubFrContent = 'Vous devez saisir un titre fran&ccedil;ais de moins de 1 000 000 de caract&egrave;res';
				}
				
				if($backToForm == true) {
					$backToAddTermsSubSectionForm = $parentSection;
				} else {
					// Allright, add entry!
					$enTitle = mysqli_real_escape_string($conn, $enTitle);
					$frTitle = mysqli_real_escape_string($conn, $frTitle);
					$enContent = mysqli_real_escape_string($conn, $enContent);
					$frContent = mysqli_real_escape_string($conn, $frContent);
					
					$sql = "INSERT INTO articles (page, parent, enTitle, frTitle, enContent, frContent) VALUES ('terms.php', '$parentSection', '$enTitle', '$frTitle', '$enContent', '$frContent')";
					$conn -> query($sql);
				}
			}
			
			if($_SERVER["REQUEST_METHOD"] == "POST" && $_POST["formAction"] == "editTermsSection" && ctype_digit($_POST['sectionId']) && $backToForm != true) {
				$enTitle = htmlData($_POST['enTitle']);
				$frTitle = htmlData($_POST['frTitle']);
				$enContent = htmlData($_POST['enContent']);
				$frContent = htmlData($_POST['frContent']);
				$editTermsSection = mysqli_real_escape_string($conn, $_POST['sectionId']);
				
				if($_POST['enTitle'] == '' || strlen($_POST['enTitle']) > 512) {
					$backToForm = true;
					$errorEnTitle = 'Vous devez saisir un titre anglais de moins de 512 caract&egrave;res';
				}
				
				if($_POST['frTitle'] == '' || strlen($_POST['frTitle']) > 512) {
					$backToForm = true;
					$errorFrTitle = 'Vous devez saisir un titre fran&ccedil;ais de moins de 512 caract&egrave;res';
				}
				
				if(strlen($_POST['enContent']) > 1000000) {
					$backToForm = true;
					$errorEnContent = 'Vous devez saisir un titre anglais de moins de 1 000 000 de caract&egrave;res';
				}
				
				if(strlen($_POST['frContent']) > 1000000) {
					$backToForm = true;
					$errorFrContent = 'Vous devez saisir un titre fran&ccedil;ais de moins de 1 000 000 de caract&egrave;res';
				}
				
				if($backToForm == true) {
					
				} else {
					// Allright, update entry!
					$currentDate = date('Y-m-d H:i:s');
					$enTitle = mysqli_real_escape_string($conn, $enTitle);
					$frTitle = mysqli_real_escape_string($conn, $frTitle);
					$enContent = mysqli_real_escape_string($conn, $enContent);
					$frContent = mysqli_real_escape_string($conn, $frContent);
					
					$sql = "UPDATE articles SET timestamp='$currentDate', enTitle='$enTitle', frTitle='$frTitle', enContent='$enContent', frContent='$frContent' WHERE id='$editTermsSection'";
					$conn -> query($sql);
					
					unset($editTermsSection);
				}
			}
			
			if($_SERVER["REQUEST_METHOD"] == "GET" && ctype_digit($_GET['deleteSection'])) {
				$sectionId = mysqli_real_escape_string($conn, $_GET['deleteSection']);
				$sql = "DELETE FROM articles WHERE id='$sectionId'";
				$conn -> query($sql);
			}
			
			if($_SERVER["REQUEST_METHOD"] == "GET" && ctype_digit($_GET['editSection'])) {
				$editSection = mysqli_real_escape_string($conn, $_GET['editSection']);
				$sql = "SELECT id FROM articles WHERE id='$editSection'";
				$db_rawSection = $conn -> query($sql);
				if (mysqli_num_rows($db_rawSection) != 1) { unset($editSection); }
			}
			
			if($_SERVER["REQUEST_METHOD"] == "GET" && ctype_digit($_GET['editTermsSection'])) {
				$editTermsSection = mysqli_real_escape_string($conn, $_GET['editTermsSection']);
				$sql = "SELECT id FROM articles WHERE id='$editTermsSection'";
				$db_rawSection = $conn -> query($sql);
				if (mysqli_num_rows($db_rawSection) != 1) { unset($editTermsSection); }
			}
			
			// End accepting and validating POST and GET
		} else {
			killCookie();
			$loadContent = false;
			header("HTTP/1.1 301 Moved Permanently");
			header("Location: /index.php");
		}
	} else {
		killCookie();
		$loadContent = false;
		header("HTTP/1.1 301 Moved Permanently");
		header("Location: /index.php");
	}
} else {
	// invalid session. Back to the login page, and kill cookie...
	killCookie();
	$loadContent = false;
	header("HTTP/1.1 301 Moved Permanently");
    header("Location: /index.php");
}

if($loadContent) {
	if ($isAdmin) {
		
		$pageTitle = 'Content Management System';
		if($_SESSION['language'] == 'en'){
			$logo = '../img/weblogoen.png';
		} elseif($_SESSION['language'] == 'fr') {
			$logo = '../img/weblogofr.png';
		} else {
			$logo = '../img/weblogoen.png';
		}
		
		include "head.php";
		?>
		<h1 class="w3-center w3-border-bottom w3-border-blue w3-padding"><a href="index.php"><img class="websiteLogo" src="<?php echo $logo ?>"></a><br><?php echo $pageTitle; ?></h1>
		<div class="w3-padding">
			<h2><a href="javascript:showhide('faq')">Gestion des FAQ</a></h2>
			<a class="w3-btn w3-blue" href="javascript:showhide('addSection')">Ajouter une section principale</a><br>
			<div id="addSection" style="display: <?php if($backToAddSectionForm === true) { echo 'block'; } else { echo 'none'; } ?>">
				<h3>Ajouter une section principale</h3>
				<form class="w3-container" method="POST" action="<?php $_SERVER['PHP_SELF'] ?>">
					<?php
						echo '<input type="hidden" name="formUid" value="' . $_SESSION["secureFormCode"] . '">';
						echo '<input type="hidden" name="formAction" value="addSection">';
					?>
					<label>Titre EN</label>
					<input class="w3-input" type="text" name="enTitle">
					<?php if (isset($errorEnTitle)) { echo '<div class="w3-text-red">' . $errorEnTitle . '</div>';} ?>
					<br>
					<label>Titre FR</label>
					<input class="w3-input" type="text" name="frTitle">
					<?php if (isset($errorFrTitle)) { echo '<div class="w3-text-red">' . $errorFrTitle . '</div>';} ?>
					<br>
					<label>Contenu EN</label>
					<textarea class="w3-input" type="text" name="enContent"></textarea>
					<?php if (isset($errorEnContent)) { echo '<div class="w3-text-red">' . $errorEnContent . '</div>';} ?>
					<br>
					<label>Contenu FR</label>
					<textarea class="w3-input" type="text" name="frContent"></textarea>
					<?php if (isset($errorFrContent)) { echo '<div class="w3-text-red">' . $errorFrContent . '</div>';} ?>
					<div class="w3-padding">
						<input class="w3-btn w3-blue w3-right w3-margin" type="submit" value="Enregistrer" name="submit">
						<a class="w3-btn w3-red w3-right w3-margin" href="javascript:showhide('addSection')">Annuler <i class="fa fa-undo"></i></a>
					</div>
				</form>
			</div>
			<div class="w3-padding w3-border-bottom w3-border-blue" id="faq" style="display:block;">
				<?php
				if(!isset($editSection)) {
					$sql = "SELECT id, priority, parent, enTitle, frTitle, enContent, frContent FROM articles WHERE page='faq.php' AND parent IS NULL";
					$db_rawFaq = $conn -> query($sql);
					while ($faq = $db_rawFaq -> fetch_assoc()) {
						
						echo '<a href="javascript:showhide(\'section' . $faq['id'] . '\')"><h3>' . $faq['enTitle'] . ' <span class="w3-medium">(' . $faq['frTitle'] . ')</span></h3></a>
						<a class="w3-btn w3-blue" href="' . $_SERVER['PHP_SELF'] . '?editSection=' . $faq['id'] . '">Modifier</a>
						<a class="w3-btn w3-blue" href="javascript:showhide(\'addSubSection' . $faq['id'] . '\')">Ajouter une sous-section</a>
						<a class="w3-btn w3-red" href="' . $_SERVER['PHP_SELF'] . '?deleteSection=' . $faq['id'] . '">Supprimer la section</a>
						<div class="w3-padding" id="addSubSection' . $faq['id'] . '" style="display: ';
						if ($backToAddSubSectionForm == $faq['id']) { echo 'block'; } else { echo 'none'; }
						echo '">
							<h3>Ajouter une sous-section</h3>
							<form class="w3-container" method="POST" action="' . $_SERVER['PHP_SELF'] . '">
								<input type="hidden" name="formUid" value="' . $_SESSION["secureFormCode"] . '">
								<input type="hidden" name="formAction" value="addSubSection">
								<input type="hidden" name="parentSection" value="' . $faq['id'] . '">
								
								<label>Titre EN</label>
								<input class="w3-input" type="text" name="enTitle">';
								if (isset($errorSubEnTitle)) { echo '<div class="w3-text-red">' . $errorSubEnTitle . '</div>';}
								echo '<br>
								<label>Titre FR</label>
								<input class="w3-input" type="text" name="frTitle">';
								if (isset($errorSubFrTitle)) { echo '<div class="w3-text-red">' . $errorSubFrTitle . '</div>';}
								echo '<br>
								<label>Contenu EN</label>
								<textarea rows="15" class="w3-input" type="text" name="enContent"></textarea>';
								if (isset($errorSubEnContent)) { echo '<div class="w3-text-red">' . $errorSubEnContent . '</div>';}
								echo '<br>
								<label>Contenu FR</label>
								<textarea rows="15" class="w3-input" type="text" name="frContent"></textarea>';
								if (isset($errorSubFrContent)) { echo '<div class="w3-text-red">' . $errorSubFrContent . '</div>';}
								echo '<div class="w3-padding">
									<input class="w3-btn w3-blue w3-right w3-margin" type="submit" value="Enregistrer" name="submit">
									<a class="w3-btn w3-red w3-right w3-margin" href="javascript:showhide(\'addSubSection' . $faq['id'] . '\')">Annuler <i class="fa fa-undo"></i></a>
								</div>
							</form>
						</div>';
						
						$sql = "SELECT id, priority, parent, enTitle, frTitle, enContent, frContent FROM articles WHERE page='faq.php' AND parent='$faq[id]'";
						$db_rawFaqSub = $conn -> query($sql);
						echo '<div id="section' . $faq['id'] . '" style="display: none">';
						while ($faqSub = $db_rawFaqSub -> fetch_assoc()) {
							echo '<div class="w3-padding w3-border-top w3-border-bottom w3-border-blue w3-margin">';
							echo '<a href="javascript:showhide(\'section' . $faqSub['id'] . '\')"><h4>' . $faqSub['enTitle'] . '<br><span class="w3-small">' . $faqSub['frTitle'] . '</span></h4></a>
							<div id="section' . $faqSub['id'] . '" style="display:none">
								<div class="w3-padding w3-medium">
									' . $faqSub['enContent'] . '
								</div>
								<div class="w3-padding w3-small">
									' . $faqSub['frContent'] . '
								</div>
							</div>
							<a class="w3-btn w3-blue w3-margin" href="' . $_SERVER['PHP_SELF'] . '?editSection=' . $faqSub['id'] . '">Modifier</a>
							<a class="w3-btn w3-red w3-margin" href="' . $_SERVER['PHP_SELF'] . '?deleteSection=' . $faqSub['id'] . '">Supprimer</a>';
							echo '</div>';
						}
						echo '</div>';
					}
				} else {
					$sql = "SELECT id, priority, parent, enTitle, frTitle, enContent, frContent FROM articles WHERE page='faq.php' AND id='$editSection'";
					$db_rawFaq = $conn -> query($sql);
					
					while ($faq = $db_rawFaq -> fetch_assoc()) {
						if($backToForm == true) {
							// Get data from POST
							$enTitle = $_POST['enTitle'];
							$frTitle = $_POST['frTitle'];
							$enContent = $_POST['enContent'];
							$frContent = $_POST['frContent'];
						} else {
							// Get data from DB
							$enTitle = reverseHtmlData($faq['enTitle']);
							$frTitle = reverseHtmlData($faq['frTitle']);
							$enContent = reverseHtmlData($faq['enContent']);
							$frContent = reverseHtmlData($faq['frContent']);
						}
						
						echo '<h3>Modifier la section "' . $faq['enTitle'] . '"</h3>';
						echo '<form class="w3-container" method="POST" action="' . $_SERVER['PHP_SELF'] . '">
							<input type="hidden" name="formUid" value="' . $_SESSION["secureFormCode"] . '">
							<input type="hidden" name="formAction" value="editSection">
							<input type="hidden" name="sectionId" value="' . $faq['id'] . '">
							<label>Titre EN</label>
							<input class="w3-input" type="text" name="enTitle" value="' . htmlOutput($enTitle) . '">';
							if (isset($errorEnTitle)) { echo '<div class="w3-text-red">' . $errorEnTitle . '</div>';}
							echo '<br>
							<label>Titre FR</label>
							<input class="w3-input" type="text" name="frTitle" value="' . htmlOutput($frTitle) . '">';
							if (isset($errorFrTitle)) { echo '<div class="w3-text-red">' . $errorFrTitle . '</div>';}
							echo '<br>
							<label>Contenu EN</label>
							<textarea rows="15" class="w3-input" type="text" name="enContent">' . $enContent . '</textarea>';
							if (isset($errorEnContent)) { echo '<div class="w3-text-red">' . $errorEnContent . '</div>';}
							echo '<br>
							<label>Contenu FR</label>
							<textarea rows="15" class="w3-input" type="text" name="frContent">' . $frContent . '</textarea>';
							if (isset($errorFrContent)) { echo '<div class="w3-text-red">' . $errorFrContent . '</div>';}
							echo '<div class="w3-padding">
								<input class="w3-btn w3-blue w3-right w3-margin" type="submit" value="Enregistrer" name="submit">
								<a class="w3-btn w3-red w3-right w3-margin" href="' . $_SERVER['PHP_SELF'] . '">Annuler <i class="fa fa-undo"></i></a>
							</div>
						</form>';
					}
				}
					
				?>
			</div>
		</div>
		
		<div class="w3-padding">
			<h2><a href="javascript:showhide('terms')">Gestion des conditions d'utilisation</a></h2>
			<a class="w3-btn w3-blue" href="javascript:showhide('addTermsSection')">Ajouter une section principale</a><br>
			<div id="addTermsSection" style="display: <?php if($backToAddTermsSectionForm === true) { echo 'block'; } else { echo 'none'; } ?>">
				<h3>Ajouter une section principale</h3>
				<form class="w3-container" method="POST" action="<?php $_SERVER['PHP_SELF'] ?>">
					<?php
						echo '<input type="hidden" name="formUid" value="' . $_SESSION["secureFormCode"] . '">';
						echo '<input type="hidden" name="formAction" value="addTermsSection">';
					?>
					<label>Titre EN</label>
					<input class="w3-input" type="text" name="enTitle">
					<?php if (isset($errorTermsEnTitle)) { echo '<div class="w3-text-red">' . $errorTermsEnTitle . '</div>';} ?>
					<br>
					<label>Titre FR</label>
					<input class="w3-input" type="text" name="frTitle">
					<?php if (isset($errorTermsFrTitle)) { echo '<div class="w3-text-red">' . $errorTermsFrTitle . '</div>';} ?>
					<br>
					<label>Contenu EN</label>
					<textarea class="w3-input" type="text" name="enContent"></textarea>
					<?php if (isset($errorTermsEnContent)) { echo '<div class="w3-text-red">' . $errorTermsEnContent . '</div>';} ?>
					<br>
					<label>Contenu FR</label>
					<textarea class="w3-input" type="text" name="frContent"></textarea>
					<?php if (isset($errorTermsFrContent)) { echo '<div class="w3-text-red">' . $errorTermsFrContent . '</div>';} ?>
					<div class="w3-padding">
						<input class="w3-btn w3-blue w3-right w3-margin" type="submit" value="Enregistrer" name="submit">
						<a class="w3-btn w3-red w3-right w3-margin" href="javascript:showhide('addTermsSection')">Annuler <i class="fa fa-undo"></i></a>
					</div>
				</form>
			</div>
			<div class="w3-padding w3-border-bottom w3-border-blue" id="terms" style="display:block;">
			<?php
			
			if(!isset($editTermsSection)) {
				$sql = "SELECT id, priority, parent, enTitle, frTitle, enContent, frContent FROM articles WHERE page='terms.php' AND parent IS NULL";
				$db_rawTerms = $conn -> query($sql);
				while ($term = $db_rawTerms -> fetch_assoc()) {
					echo '<a href="javascript:showhide(\'section' . $term['id'] . '\')"><h3>' . $term['enTitle'] . ' <span class="w3-medium">(' . $term['frTitle'] . ')</span></h3></a>
					<a class="w3-btn w3-blue" href="' . $_SERVER['PHP_SELF'] . '?editTermsSection=' . $term['id'] . '">Modifier</a>
					<a class="w3-btn w3-blue" href="javascript:showhide(\'addTermsSubSection' . $term['id'] . '\')">Ajouter une sous-section</a>
					<a class="w3-btn w3-red" href="' . $_SERVER['PHP_SELF'] . '?deleteSection=' . $term['id'] . '">Supprimer la section</a>
					<div class="w3-padding" id="addTermsSubSection' . $term['id'] . '" style="display: ';
					if ($backToAddTermsSubSectionForm == $term['id']) { echo 'block'; } else { echo 'none'; }
					echo '">
						<h3>Ajouter une sous-section</h3>
						<form class="w3-container" method="POST" action="' . $_SERVER['PHP_SELF'] . '">
							<input type="hidden" name="formUid" value="' . $_SESSION["secureFormCode"] . '">
							<input type="hidden" name="formAction" value="addTermsSubSection">
							<input type="hidden" name="parentSection" value="' . $term['id'] . '">
							
							<label>Titre EN</label>
							<input class="w3-input" type="text" name="enTitle">';
							if (isset($errorTermsSubEnTitle)) { echo '<div class="w3-text-red">' . $errorTermsSubEnTitle . '</div>';}
							echo '<br>
							<label>Titre FR</label>
							<input class="w3-input" type="text" name="frTitle">';
							if (isset($errorTermsSubFrTitle)) { echo '<div class="w3-text-red">' . $errorTermsSubFrTitle . '</div>';}
							echo '<br>
							<label>Contenu EN</label>
							<textarea rows="15" class="w3-input" type="text" name="enContent"></textarea>';
							if (isset($errorTermsSubEnContent)) { echo '<div class="w3-text-red">' . $errorTermsSubEnContent . '</div>';}
							echo '<br>
							<label>Contenu FR</label>
							<textarea rows="15" class="w3-input" type="text" name="frContent"></textarea>';
							if (isset($errorTermsSubFrContent)) { echo '<div class="w3-text-red">' . $errorTermsSubFrContent . '</div>';}
							echo '<div class="w3-padding">
								<input class="w3-btn w3-blue w3-right w3-margin" type="submit" value="Enregistrer" name="submit">
								<a class="w3-btn w3-red w3-right w3-margin" href="javascript:showhide(\'addTermsSubSection' . $term['id'] . '\')">Annuler <i class="fa fa-undo"></i></a>
							</div>
						</form>
					</div>';
					
					$sql = "SELECT id, priority, parent, enTitle, frTitle, enContent, frContent FROM articles WHERE page='terms.php' AND parent='$term[id]'";
					$db_rawTermSub = $conn -> query($sql);
					echo '<div id="section' . $term['id'] . '" style="display: none">';
					while ($termSub = $db_rawTermSub -> fetch_assoc()) {
						echo '<div class="w3-padding w3-border-top w3-border-bottom w3-border-blue w3-margin">';
						echo '<a href="javascript:showhide(\'section' . $termSub['id'] . '\')"><h4>' . $termSub['enTitle'] . '<br><span class="w3-small">' . $termSub['frTitle'] . '</span></h4></a>
						<div id="section' . $termSub['id'] . '" style="display:none">
							<div class="w3-padding w3-medium">
								' . $termSub['enContent'] . '
							</div>
							<div class="w3-padding w3-small">
								' . $termSub['frContent'] . '
							</div>
						</div>
						<a class="w3-btn w3-blue w3-margin" href="' . $_SERVER['PHP_SELF'] . '?editTermsSection=' . $termSub['id'] . '">Modifier</a>
						<a class="w3-btn w3-red w3-margin" href="' . $_SERVER['PHP_SELF'] . '?deleteSection=' . $termSub['id'] . '">Supprimer</a>';
						echo '</div>';
					}
					echo '</div>';
					
				}
			} else {
				$sql = "SELECT id, priority, parent, enTitle, frTitle, enContent, frContent FROM articles WHERE page='terms.php' AND id='$editTermsSection'";
				$db_rawTerm = $conn -> query($sql);
				
				while ($term = $db_rawTerm -> fetch_assoc()) {
					if($backToForm == true) {
						// Get data from POST
						$enTitle = $_POST['enTitle'];
						$frTitle = $_POST['frTitle'];
						$enContent = $_POST['enContent'];
						$frContent = $_POST['frContent'];
					} else {
						// Get data from DB
						$enTitle = reverseHtmlData($term['enTitle']);
						$frTitle = reverseHtmlData($term['frTitle']);
						$enContent = reverseHtmlData($term['enContent']);
						$frContent = reverseHtmlData($term['frContent']);
					}
					
					echo '<h3>Modifier la section "' . $term['enTitle'] . '"</h3>';
					echo '<form class="w3-container" method="POST" action="' . $_SERVER['PHP_SELF'] . '">
						<input type="hidden" name="formUid" value="' . $_SESSION["secureFormCode"] . '">
						<input type="hidden" name="formAction" value="editTermsSection">
						<input type="hidden" name="sectionId" value="' . $term['id'] . '">
						<label>Titre EN</label>
						<input class="w3-input" type="text" name="enTitle" value="' . $enTitle . '">';
						if (isset($errorTermsEnTitle)) { echo '<div class="w3-text-red">' . $errorTermsEnTitle . '</div>';}
						echo '<br>
						<label>Titre FR</label>
						<input class="w3-input" type="text" name="frTitle" value="' . $frTitle . '">';
						if (isset($errorTermsFrTitle)) { echo '<div class="w3-text-red">' . $errorTermsFrTitle . '</div>';}
						echo '<br>
						<label>Contenu EN</label>
						<textarea rows="15" class="w3-input" type="text" name="enContent">' . $enContent . '</textarea>';
						if (isset($errorTermsEnContent)) { echo '<div class="w3-text-red">' . $errorTermsEnContent . '</div>';}
						echo '<br>
						<label>Contenu FR</label>
						<textarea rows="15" class="w3-input" type="text" name="frContent">' . $frContent . '</textarea>';
						if (isset($errorTermsFrContent)) { echo '<div class="w3-text-red">' . $errorTermsFrContent . '</div>';}
						echo '<div class="w3-padding">
							<input class="w3-btn w3-blue w3-right w3-margin" type="submit" value="Enregistrer" name="submit">
							<a class="w3-btn w3-red w3-right w3-margin" href="' . $_SERVER['PHP_SELF'] . '">Annuler <i class="fa fa-undo"></i></a>
						</div>
					</form>';
				}
			}
		?>
			
		</div>
		</div>
		
		
		
		<h2 class="w3-center">Zone de test de fonctions...</h2>
		<div class="w3-padding w3-border-bottom w3-border-blue">
		
		<?php
		
		if($_SERVER["REQUEST_METHOD"] == "POST" && $_POST["formAction"] == "testFiles") {
			$numFiles = count($_FILES['uploads']['tmp_name']);
			echo $numFiles . ' uploaded<br>';
			$fileIndex = 0;
			while($numFiles > $fileIndex) {
				echo $_FILES['uploads']['tmp_name'][$fileIndex] . '<br>';
				echo $_FILES['uploads']['name'][$fileIndex] . '<br><br>';
				$fileIndex ++;
			}
			echo $fileIndex;
		}
		
		?>
		
		
		
		<form class="w3-container" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST" enctype="multipart/form-data">
			<input type="hidden" name="formAction" value="testFiles">
			<input type="hidden" name="formUid" value="<?php echo $_SESSION["secureFormCode"]; ?>">
			<input type="file" multiple="multiple" name="uploads[]" class="w3-input">
			<input class="w3-btn w3-blue" type="submit" value="<?php echo $strings['45']; ?>" name="submit">
		</form>
		
		
		</div>
		
		<?php
		
		include "footer.php";
	} else {
		killCookie();
		$loadContent = false;
		header("HTTP/1.1 301 Moved Permanently");
		header("Location: /index.php");
	}
}



?>