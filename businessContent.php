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
				<?php
				// logAction('1');
					if($activeSession) { 
						// User has an active session
						if($_SESSION["businessAccount"]) {
							// Business account... is it initialized?
							$businessInfo = getBusinessInfo($_SESSION['userId']);
							if($businessInfo['status'] == 'uninitialized') {
								// Display business information form!
				?>
					<div class="w3-padding">
						<h1 class="w3-border-bottom w3-border-blue"><?php echo $strings['379'] ?></h1>
						<div><?php echo $strings['380'] ?></div>
						<form class="" method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" autocomplete="off">
						<?php
							echo '<input type="hidden" name="formUid" value="' . $_SESSION["secureFormCode"] . '">';
							echo '<input type="hidden" name="formAction" value="saveBusinessInfo">';
						?>
							<div class="w3-row">
								<div class="w3-half">
									<div class="w3-card-4 w3-margin w3-border-blue">
										<h2 class="w3-center w3-blue w3-padding"><i class="fa fa-building"></i> <?php echo $strings['381']; ?></h2>
										<div class="w3-padding w3-left-align">
											
											<div class="w3-padding-16">
												<label><?php echo $strings['383']; ?></label>
												<input autocapitalize="words" inputmode="autocapitalized" class="w3-input" type="text" name="businessName"<?php if(isset($businessName)) { echo ' value="' . $businessName . '"'; } if ($backToForm === true) { echo ' autofocus'; } ?>>
												<?php if (isset($businessNameError)) { echo '<div class="w3-text-red">' . $businessNameError . '</div>';} ?>
											</div>
											
											<div class="w3-padding-16">
												<label><?php echo $strings['384']; ?></label>
												<input autocapitalize="words" inputmode="autocapitalized" class="w3-input" type="text" name="businessAddress"<?php if(isset($businessAddress)) { echo ' value="' . $businessAddress . '"'; } ?>>
												<?php if (isset($businessAddressError)) { echo '<div class="w3-text-red">' . $businessAddressError . '</div>';} ?>
											</div>
											
											<div class="w3-padding-16">
												<label><?php echo $strings['15']; ?></label>
												<input autocapitalize="words" inputmode="autocapitalized" class="w3-input" type="text" name="businessCity"<?php if(isset($businessCity)) { echo ' value="' . $businessCity . '"'; } ?>>
												<?php if (isset($businessCityError)) { echo '<div class="w3-text-red">' . $businessCityError . '</div>';} ?>
											</div>
											
											<div class="w3-padding-16">
												<label><?php echo $strings['14']; ?></label>
												<input autocapitalize="words" inputmode="autocapitalized" class="w3-input" type="text" name="businessState"<?php if(isset($businessState)) { echo ' value="' . $businessState . '"'; } ?>>
												<?php if (isset($businessStateError)) { echo '<div class="w3-text-red">' . $businessStateError . '</div>';} ?>
											</div>
											
											<div class="w3-padding-16">
												<label><?php echo $strings['13']; ?></label>
												<select class="w3-select" name="businessCountry">
												<?php echo countryList($businessCountry); ?>
												</select>
												<?php if (isset($businessCountryError)) { echo '<div class="w3-text-red">' . $businessCountryError . '</div>';} ?>
											</div>
											
											<div class="w3-padding-16">
												<label><?php echo $strings['386']; ?></label>
												<input class="w3-input" type="email" name="businessEmail"<?php if(isset($businessEmail)) { echo ' value="' . $businessEmail . '"'; } ?>>
												<?php if (isset($businessEmailError)) { echo '<div class="w3-text-red">' . $businessEmailError . '</div>';} ?>
											</div>
											
											<div class="w3-padding-16">
												<label><?php echo $strings['385']; ?></label>
												<input class="w3-input" type="tel" name="businessPhone"<?php if(isset($businessPhone)) { echo ' value="' . $businessPhone . '"'; } ?>>
												<?php if (isset($businessPhoneError)) { echo '<div class="w3-text-red">' . $businessPhoneError . '</div>';} ?>
											</div>
											
										</div>
									</div>
								</div>
								<div class="w3-half">
									<div class="w3-card-4 w3-margin w3-border-blue">
										<h2 class="w3-center w3-blue w3-padding"><i class="fa fa-credit-card"></i> <?php echo $strings['382']; ?></h2>
										<div class="w3-padding w3-center w3-border-bottom w3-border-blue"><b><?php echo $strings['389']; ?></b></div>
										
										<div class="w3-padding w3-left-align">
										
											<div class="w3-padding-16">
												<label><?php echo $strings['387']; ?></label>
												<input placeholder="<?php echo $strings['388']; ?>" onclick="javascript:hidePlaceholder('billingName')" onBlur="javascript:showPlaceholder('billingName','<?php echo javascriptEscape($strings['388']); ?>')" id="billingName" autocapitalize="words" inputmode="autocapitalized" class="w3-input" type="text" name="billingName"<?php if(isset($billingName)) { echo ' value="' . $billingName . '"'; } ?>>
												<?php if (isset($billingNameError)) { echo '<div class="w3-text-red">' . $billingNameError . '</div>';} ?>
											</div>
											
											<div class="w3-padding-16">
												<label><?php echo $strings['390']; ?></label>
												<input placeholder="<?php echo $strings['391']; ?>" onclick="javascript:hidePlaceholder('billingAddress')" onBlur="javascript:showPlaceholder('billingAddress','<?php echo javascriptEscape($strings['388']); ?>')" id="billingAddress" autocapitalize="words" inputmode="autocapitalized" class="w3-input" type="text" name="billingAddress"<?php if(isset($billingAddress)) { echo ' value="' . $billingAddress . '"'; } ?>>
												<?php if (isset($billingAddressError)) { echo '<div class="w3-text-red">' . $billingAddressError . '</div>';} ?>
											</div>
											
											<div class="w3-padding-16">
												<label><?php echo $strings['15']; ?></label>
												<input placeholder="<?php echo $strings['392']; ?>" onclick="javascript:hidePlaceholder('billingCity')" onBlur="javascript:showPlaceholder('billingCity','<?php echo javascriptEscape($strings['388']); ?>')" id="billingCity" autocapitalize="words" inputmode="autocapitalized" class="w3-input" type="text" name="billingCity"<?php if(isset($billingCity)) { echo ' value="' . $billingCity . '"'; } ?>>
												<?php if (isset($billingCityError)) { echo '<div class="w3-text-red">' . $billingCityError . '</div>';} ?>
											</div>
											
											<div class="w3-padding-16">
												<label><?php echo $strings['14']; ?></label>
												<input placeholder="<?php echo $strings['393']; ?>" onclick="javascript:hidePlaceholder('billingState')" onBlur="javascript:showPlaceholder('billingState','<?php echo javascriptEscape($strings['388']); ?>')" id="billingState" autocapitalize="words" inputmode="autocapitalized" class="w3-input" type="text" name="billingState"<?php if(isset($billingState)) { echo ' value="' . $billingState . '"'; } ?>>
												<?php if (isset($billingStateError)) { echo '<div class="w3-text-red">' . $billingStateError . '</div>';} ?>
											</div>
											
											<div class="w3-padding-16">
												<label><?php echo $strings['13']; ?></label>
												<select class="w3-select" name="billingCountry">
												<?php echo countryList($billingCountry); ?>
												</select>
												<?php if (isset($billingCountryError)) { echo '<div class="w3-text-red">' . $billingCountryError . '</div>';} ?>
											</div>
											
											<div class="w3-padding-16">
												<label><?php echo $strings['394']; ?></label>
												<input placeholder="<?php echo $strings['395']; ?>" onclick="javascript:hidePlaceholder('billingEmail')" onBlur="javascript:showPlaceholder('billingEmail','<?php echo javascriptEscape($strings['388']); ?>')" id="billingEmail" class="w3-input" type="email" name="billingEmail"<?php if(isset($billingEmail)) { echo ' value="' . $billingEmail . '"'; } ?>>
												<?php if (isset($billingEmailError)) { echo '<div class="w3-text-red">' . $billingEmailError . '</div>';} ?>
											</div>
											
										</div>
									</div>
								</div>
							</div>
							<div class="w3-padding w3-center">
								<h3><?php echo $strings['169']; ?></h3>
								<div class="w3-show-inline-block">
									<input class="w3-check" type="checkbox" value="accept" name="businessTerms"<?php if($businessTerms == 'accept') { echo ' checked'; } ?>>
								</div>
								<div class="w3-show-inline-block">
									<?php echo $strings['396']; ?>
								</div>
								<?php if (isset($businessTermsError)) { echo '<div class="w3-text-red">' . $businessTermsError . '</div>';} ?>
							</div>
							<div style="height:30px;"></div>
							<div class="w3-padding w3-center">
								<input class="w3-btn w3-blue w3-margin-bottom" type="submit" value="<?php echo $strings['397']; ?>" name="submit">
							</div>
						</form>
					</div>
				<?php			
							} else {
								// Business account is initialized. For the moment, this is a testing zone!
								
								$businessInfo = getBusinessInfo($_SESSION['userId']);
								$userInfo = getBusinessUserInfo($_SESSION['certId']);
								$userGroups = getBusinessUserGroups($userInfo['id']);
								$userBusinessPermissions = getBusinessManagementPermissions();
								
								echo '<pre>';
									var_dump($businessInfo);
									var_dump($userInfo);
									var_dump($userGroups);
									var_dump($userBusinessPermissions);
								echo '</pre>';
							}
						} else {
							echo 'Standard account';
						}
						
					} else {
						/* Show marketing stuff */
				?>
					
					<div class="w3-padding">
						<h1 class="w3-border-bottom w3-border-blue"><?php echo $strings['354'] ?></h1>
						<div class="w3-row">
							<div class="w3-half">
								<div class="w3-card-4 w3-margin w3-border-blue">
									<h2 class="w3-center w3-blue w3-padding"><i class="fa fa-users"></i> <?php echo $strings['357']; ?></h2>
									<div class="w3-padding w3-center">
									<h3 class="w3-xxlarge"><?php echo $strings['359']; ?></h3>
									<div class="w3-small w3-border-bottom w3-border-blue">
										<?php echo $strings['360']; ?><br>
										<a class="w3-btn w3-blue w3-xlarge w3-margin" href="index.php?control=signup"><i class="fa fa-pencil-square-o"></i> <?php echo $strings['131']; ?></a>
									</div>
										<ul class="w3-ul w3-left-align w3-border-bottom w3-border-blue">
											<li><i class="fa fa-id-card-o w3-text-blue" aria-hidden="true"></i> <?php echo $strings['362']; ?></li>
											<li><i class="fa fa-line-chart w3-text-blue" aria-hidden="true"></i> <?php echo $strings['363']; ?></li>
											<li><i class="fa fa-tablet w3-text-blue" aria-hidden="true"></i><i class="fa fa-desktop w3-text-blue" aria-hidden="true"></i><i class="fa fa-mobile w3-text-blue" aria-hidden="true"></i> <?php echo $strings['364']; ?></li>
											<li><i class="fa fa-globe w3-text-blue" aria-hidden="true"></i> <?php echo $strings['365']; ?></li>
											<li><i class="fa fa-share-alt w3-text-blue" aria-hidden="true"></i> <?php echo $strings['366']; ?></li>
											<li><i class="fa fa-lock w3-text-blue" aria-hidden="true"></i> <?php echo $strings['367']; ?></li>
											<li><i class="fa fa-database w3-text-blue" aria-hidden="true"></i> <?php echo $strings['368']; ?></li>
										</ul>
										<div class="w3-tiny w3-padding w3-left-align">* <?php echo $strings['374']; ?></div>
									</div>
								</div>
							</div>
							<div class="w3-half">
								<div class="w3-card-4 w3-margin w3-border-blue">
									<h2 class="w3-center w3-blue w3-padding"><i class="fa fa-building"></i> <?php echo $strings['358']; ?></h2>
									<div class="w3-padding w3-center">
									<h3 class="w3-xxlarge">3$</h3>
									<div class="w3-small w3-border-bottom w3-border-blue">
										<?php echo $strings['361']; ?><br>
										<a class="w3-btn w3-blue w3-xlarge w3-margin" href="index.php?control=businessSignup"><i class="fa fa-pencil-square-o"></i> <?php echo $strings['131']; ?></a>
									</div>
										<ul class="w3-ul w3-left-align w3-border-bottom w3-border-blue">
											<li class="w3-large"><i class="fa fa-plus-circle w3-text-blue" aria-hidden="true"></i> <?php echo $strings['369']; ?></li>
											<li><i class="fa fa-users w3-text-blue" aria-hidden="true"></i> <?php echo $strings['370']; ?></li>
											<li><i class="fa fa-folder-open w3-text-blue" aria-hidden="true"></i><i class="fa fa-tags w3-text-blue" aria-hidden="true"></i> <?php echo $strings['371']; ?></li>
											<li><i class="fa fa-life-ring w3-text-blue" aria-hidden="true"></i> <?php echo $strings['372']; ?></li>
											<li><i class="fa fa-database w3-text-blue" aria-hidden="true"></i> <?php echo $strings['373']; ?></li>
											<li><i class="fa fa-user w3-text-blue" aria-hidden="true"></i> <?php echo $strings['376']; ?></li>
										</ul>
										<div class="w3-tiny w3-padding w3-left-align">** <?php echo $strings['375']; ?></div>
									</div>
								</div>
							</div>
							
						</div>
					</div>
					
				<?php
					}
				?>
				
			</div>
		</div>
		<?php include 'footer.php'; ?>
	</body>
</html>