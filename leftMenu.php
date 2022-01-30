<?php

	if(!defined($parentPage)) {
		header("HTTP/1.1 301 Moved Permanently");
		header("Location: index.php");
	}

?>

<div class="w3-bar-block w3-white w3-hide-medium w3-hide-small w3-card-4 w3-margin">
	<div style="margin: 0px!important;" class="w3-center w3-blue w3-padding"><i class="w3-xxlarge fa fa-bars"></i></div>
	<?php 
		if(!is_null($_SESSION['fullName'])) {
			if(strlen($_SESSION['fullName']) > 25) { $accountDisplayName = substr($_SESSION['fullName'], 0, 22) . '...'; } else { $accountDisplayName = $_SESSION['fullName']; }
			echo '
			<div class="w3-dropdown-hover">
				<a href="profile.php" class="w3-hover-blue w3-button w3-large w3-white w3-border-top" style="padding:15px!important;">
					<i class="fa fa-user">
						<span style="font-family: arial;"> ' . $accountDisplayName . '</span>
					</i>
				</a>
				<div class="w3-dropdown-content w3-bar-block w3-card-4" style="left: 30px;">
					<a class="w3-hover-blue w3-bar-item w3-button w3-large" href="profile.php?control=logout">
						<i class="fa fa-times-circle"></i> ' . $strings['38'] . '
					</a>
				</div>
			</div>';
		} else {
			echo '
			<a style="padding:15px!important;" class="w3-hover-blue w3-bar-item w3-button w3-large w3-border-top" href="';
					echo 'index.php?control=signup">
				<i class="fa fa-pencil-square">
					<span style="font-family: arial;"> ' . $strings['131'] . '</span>
				</i>
			</a>';
		}
	?>
	<!-- Business -->
	<?php
		$businessInfo = getBusinessInfo($_SESSION['userId']);
		if($businessInfo['status'] != 'initialized') {
	?>
	<a href="business.php" style="padding:15px!important;" class="w3-hover-blue w3-bar-item w3-button w3-large w3-border-top">
		<i class="fa fa-credit-card">
			<span style="font-family: arial;"> <?php echo $strings['353'] ?></span>
		</i>
	</a>
	<?php
		} else {
			// Business is initialized... Check user rights on this business!
			$effectivePermission = getBusinessManagementPermissions();
			if($effectivePermission['billing'] == 'rw') {
				// User can manage billing and licenses. Show button accordingly!
	?>
	<a href="billing.php" style="padding:15px!important;" class="w3-hover-blue w3-bar-item w3-button w3-large w3-border-top">
		<i class="fa fa-barcode">
			<span style="font-family: arial;"> <?php echo $strings['414'] ?></span>
		</i>
	</a>
	<?php
			}
			if($effectivePermission['business'] == 'rw') {
				// User can manage business information. Show button accordingly!
	?>
	<a href="business.php" style="padding:15px!important;" class="w3-hover-blue w3-bar-item w3-button w3-large w3-border-top">
		<i class="fa fa-cogs">
			<span style="font-family: arial;"> <?php echo $strings['415'] ?></span>
		</i>
	</a>
	<a href="usersAndGroups.php" style="padding:15px!important;" class="w3-hover-blue w3-bar-item w3-button w3-large w3-border-top">
		<i class="fa fa-users">
			<span style="font-family: arial;"> <?php echo $strings['416'] ?></span>
		</i>
	</a>
	<?php
			}
		}
	?>
	
	
	<!-- FAQ -->
	<a href="faq.php" style="padding:15px!important;" class="w3-hover-blue w3-bar-item w3-button w3-large w3-border-top">
		<i class="fa fa-question-circle">
			<span style="font-family: arial;"> <?php echo $strings['132'] ?></span>
		</i>
	</a>
	<!-- About -->
	<a href="about.php" style="padding:15px!important;" class="w3-hover-blue w3-bar-item w3-button w3-large w3-border-top">
		<i class="fa fa-info-circle">
			<span style="font-family: arial;"> <?php echo $strings['133'] ?></span>
		</i>
	</a>
	<div class="w3-dropdown-hover">
		<button class="w3-hover-blue w3-button w3-large w3-border-top" style="padding:15px!important;">
			<i class="fa fa-globe">
				<span style="font-family: arial;"> <?php echo $strings['130'] ?></span>
			</i>
		</button>
		<div class="w3-dropdown-content w3-bar-block w3-card-4" style="left: 30px;">
			<a class="w3-hover-blue w3-bar-item w3-button w3-large w3-border-top" href="<?php echo $parentPage; ?>?setLanguage=en">
				<span style="<?php if($_SESSION['language'] == 'en') { echo 'font-weight: bold;'; } ?>">English</span>
			</a>
			<a class="w3-hover-blue w3-bar-item w3-button w3-large w3-border-top" href="<?php echo $parentPage; ?>?setLanguage=fr">
				<span style="<?php if($_SESSION['language'] == 'fr') { echo 'font-weight: bold;'; } ?>">Fran&ccedil;ais</span>
			</a>
		</div>
	</div>
	<?php if($isAdmin) { ?>
	<a href="/cms/" style="padding:15px!important;" class="w3-hover-blue w3-bar-item w3-button w3-large w3-border-top">
		<i class="fa fa-id-badge">
			<span style="font-family: arial;"> CMS</span>
		</i>
	</a>
	<?php } ?>
</div> 