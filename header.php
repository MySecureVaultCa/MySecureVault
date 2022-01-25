<?php
	if($_SESSION['language'] == 'en'){
		$logo = 'img/weblogoen.png';
	} elseif($_SESSION['language'] == 'fr') {
		$logo = 'img/weblogofr.png';
	} else {
		$logo = 'img/weblogoen.png';
	}
	
	if(is_null($parentPage)) {
		header("HTTP/1.1 301 Moved Permanently");
		header("Location: index.php");
	}
?>

<?php if($config['devMode']) { echo '<div class="w3-block"><p class="w3-center w3-red">MySecureVault is in DEV MODE! Do not type any production information!</p></div>'; } ?>

<div class="w3-bar">
	<?php if ($parentPage == 'index.php') { echo '<h1'; } else {echo '<div';} ?> class="w3-bar-item" style="padding-bottom: 0px; margin-bottom: 0px; margin-top:10px; display: inline-block;"><a href="index.php"><img class="websiteLogo" src="<?php echo $logo ?>" alt="<?php echo $pageTitle; ?>"></a><?php if ($parentPage == 'index.php') { echo '</h1>'; } else { echo '</div>';} ?>
	<?php if ($parentPage == 'index.php') { echo '<h2'; } else {echo '<div';} ?> class="w3-xlarge w3-text-blue w3-hide-medium w3-hide-small w3-right w3-margin" style="font-family:Segoe UI,Arial,sans-serif;"><?php echo $strings['136']; if ($parentPage == 'index.php') { echo '</h2>'; } else { echo '</div>';} ?>
	<!-- Hamburger Menu -->
	<div class="w3-dropdown-hover w3-white w3-right w3-hide-large">
		<button class="w3-button w3-hover-blue w3-round w3-text-blue" style="padding: 0px; margin: 5px;"><i class="w3-bar-item w3-xxlarge fa fa-bars"></i></button>
		<div class="w3-dropdown-content w3-bar-block w3-card-4" style="right: 0;" id="menuDropdown">
			<?php // Profile button
				if(!is_null($_SESSION['fullName'])) {
					echo '
						<a href="profile.php" class="w3-bar-item w3-button w3-large w3-border-top w3-hover-blue">
							<i class="fa fa-user">
								<span style="font-family: arial;"> ' . $_SESSION['fullName'] . '</span>
							</i>
						</a>
					';
					echo '
						<a class="w3-bar-item w3-button w3-hover-blue" style="margin-left: 30px;" href="profile.php?control=logout">' . $strings['38'] . '</a>
					';
				}
			?>
			
			<?php // Signup button
				if(is_null($_SESSION['fullName'])) {
					echo '
						<a style="padding: 15px!important;" class="w3-bar-item w3-button w3-large w3-border-top w3-hover-blue" href="';
						if($parentPage == 'index.php') { echo 'javascript:signUp(\'registration\', \'signupForm\')'; } else { echo 'index.php?control=signup'; }
						echo '">
							<i class="fa fa-pencil-square">
								<span style="font-family: arial;"> ' . $strings['131'] . '</span>
							</i>
						</a>
					';
				}
			?>
			<!-- FAQ Button -->
			<a style="padding: 15px!important;" class="w3-bar-item w3-button w3-large w3-border-top w3-hover-blue" href="faq.php">
				<i class="fa fa-question-circle w3-hover-blue">
					<span style="font-family: arial;"> <?php echo $strings['132'] ?></span>
				</i>
			</a>
			<!-- ABOUT Button-->
			<a style="padding: 15px!important;" class="w3-bar-item w3-button w3-large w3-border-top w3-hover-blue" href="about.php">
				<i class="fa fa-info-circle w3-hover-blue">
					<span style="font-family: arial;"> <?php echo $strings['133'] ?></span>
				</i>
			</a>
			<!-- BUSINESS Button-->
			<a style="padding: 15px!important;" class="w3-bar-item w3-button w3-large w3-border-top w3-hover-blue" href="business.php">
				<i class="fa fa-credit-card w3-hover-blue">
					<span style="font-family: arial;"> <?php echo $strings['353'] ?></span>
				</i>
			</a>
			<!-- LANGUAGE -->
			<div class="w3-bar-item w3-large w3-border-top">
				<i class="fa fa-globe">
					<span style="font-family: arial;"> <?php echo $strings['130'] ?></span>
				</i>
			</div> 
				<a class="w3-bar-item w3-button w3-hover-blue" style="margin-left: 30px;" href="<?php echo $parentPage; ?>?setLanguage=en">
					<span style="<?php if($_SESSION['language'] == 'en') { echo 'font-weight: bold;'; } ?>">English</span>
				</a>
				<a class="w3-bar-item w3-button w3-hover-blue" style="margin-left: 30px;" href="<?php echo $parentPage; ?>?setLanguage=fr">
					<span style="<?php if($_SESSION['language'] == 'fr') { echo 'font-weight: bold;'; } ?>">Fran&ccedil;ais</span>
				</a>
			<?php if($isAdmin) { ?>
			<a href="/cms/" style="padding:15px!important;" class="w3-bar-item w3-button w3-large w3-border-top w3-hover-blue">
				<i class="fa fa-id-badge w3-hover-blue">
					<span style="font-family: arial;"> CMS</span>
				</i>
			</a>
			<?php } ?>
		</div>
	</div>
</div>
<div style="margin-top: 18px;" class="w3-border-bottom w3-border-blue w3-hide-medium w3-hide-small"></div>
<?php if ($parentPage == 'index.php') { echo '<h2'; } else {echo '<div';} ?> class="w3-large w3-text-blue w3-hide-large w3-border-bottom w3-border-blue" style="font-family:Segoe UI,Arial,sans-serif; font-weight: 400; margin: 0px; padding: 0px; padding-left: 16px;"><?php echo $strings['136']; if ($parentPage == 'index.php') { echo '</h2>'; } else { echo '</div>';} ?>

