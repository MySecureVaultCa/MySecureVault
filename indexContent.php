<?php
if(!isset($loadContent) || $loadContent === false) {
	header("HTTP/1.1 301 Moved Permanently");
	header("Location: $parentPage");
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
			
			function signUp(id, formFocus){
				var e = document.getElementById(id);
				
				if (e.style.display == 'none') {
					e.style.display = 'block';
				}
				
				document.getElementById(formFocus).focus();
			}
		</script>
	</head>
	<body>
		<?php
			if (isIos() && '' == '1') {
			// Displays warning message because the device is iOS based.
		?>
			<div class="w3-red w3-center w3-padding" style="position:fixed; top:0; left:0; width:100%; display: block;" id="iosWarning">
				<?php echo $strings['264']; ?> <a style="margin-right: 6px;" class="w3-right" href="javascript:showhide('iosWarning')"><i class="fa fa-times-circle"></i></a>
			</div>
		<?php
			}
		include 'header.php';
		?>
		<div class="w3-row">
			<div class="w3-third">
				<?php include 'leftMenu.php'; ?>
				<?php
					if(queryFromTor()){ ?>
						<div class="w3-card-4 w3-margin" id="torAccess" style="display:block;">
							<h4 class="w3-center w3-padding"><a href="javascript:showhide('torInfo')"><i class="fa fa-check-circle-o w3-text-green w3-xlarge"></i></a> <a href="javascript:showhide('torInfo')"><?php echo $strings['327']; ?></a></h4>
							<div class="w3-padding w3-border-blue w3-border-top" id="torInfo" style="display:none;">
								<?php echo $strings['330']; ?>
							</div>
						</div>
					<?php } else { ?>
						<div class="w3-card-4 w3-margin" id="torAccess" style="display:block;">
							<h4 class="w3-center w3-padding"><a href="javascript:showhide('torInfo')"><i class="fa fa-info-circle w3-text-blue w3-xlarge"></i></a> <a href="javascript:showhide('torInfo')"><?php echo $strings['328']; ?></a></h4>
							<div class="w3-padding w3-border-blue w3-border-top" id="torInfo" style="display:none;">
								<?php echo $strings['329']; ?>
							</div>
						</div>
				<?php } ?>
			</div>
			<div class="w3-third">
				<?php if ($_SESSION['certificateFile'] != '') { ?>
				<div class="w3-card-4 w3-margin w3-border-green">	
					<h3 class="w3-center w3-green w3-padding"><i class="fa fa-certificate"></i> <?php echo $strings['268']; ?></h3>
					<div class="w3-padding w3-center">
						<?php 
						echo '<div class="w3-padding-small w3-border w3-border-red">' . $strings['326'] . '</div>
						<br>
						<a class="w3-btn w3-blue w3-margin-bottom" href="' . $_SERVER["PHP_SELF"] . '?control=downloadCert&reload=no"><i class="fa fa-certificate"></i> ' . $strings['83'] . '</a><br>
						<a href="javascript:showhide(\'certDetail\')">' . $strings['269'] . '</a>
						<div style="text-align:left;">
							<div id="certDetail" class="w3-padding" style="display:none;">
								<ul class="w3-ul">
									<li class="wrapLongText">' . $strings['196'] . ': ' . $_SESSION['certificateFilename'] . '.certificate</li>
									<li>' . $strings['197'] . ': ' . round(strlen($_SESSION['certificateFile']) / 1024, 2) . $strings['200'] . '</li>
									<li class="wrapLongText">' . $strings['261'] . ': ' . hash('md5', $_SESSION['certificateFile']) . '</li>
									<li class="wrapLongText">' . $strings['262'] . ': ' . hash('sha1', $_SESSION['certificateFile']) . '</li>
									<li class="wrapLongText">' . $strings['263'] . ': ' . hash('sha256', $_SESSION['certificateFile']) . '</li>
								</ul>
							</div>
						</div>
						'; ?>
						
					</div>
				</div>
				<?php } ?>
				<div class="w3-card-4 w3-margin">
					<div class="w3-container w3-blue w3-center">
						<h3><?php echo $strings['2']; ?></h3>
					</div>
					
					<form class="w3-container" method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" enctype="multipart/form-data" autocomplete="off">
						<?php
							echo '<input type="hidden" name="formUid" value="' . $_SESSION["secureFormCode"] . '">';
							echo '<input type="hidden" name="formAction" value="login">';
						?>
						<div class="w3-padding-16">
							<label><?php echo $strings['3']; ?></label>
							<input class="w3-input" type="file" name="cert">
						</div>
						<div class="w3-padding-16">
							<label><?php echo $strings['4']; ?></label>
							<input class="w3-input" type="password" name="certPass">
						</div>
						<div class="w3-padding-16">
							<input class="w3-check" type="checkbox" name="public" value="yes">
							<label><?php echo $strings['230']; ?></label>
						</div>
						<?php if (isset($certError)) { echo '<div class="w3-text-red">' . $certError . '</div>';} ?>
						<div class="w3-padding-16 w3-center">
							<input class="w3-btn w3-blue" type="submit" value="<?php echo $strings['5']; ?>" name="submit">
						</div>
					</form>
				</div>
				<?php if ($_SESSION['certificateFile'] == '') { ?>
				<div class="w3-card-4 w3-margin">
					<h3 class="w3-center w3-green w3-padding" style="margin-bottom: 0px;"><a href="javascript:showhide('registration')"><?php echo $strings['9']; ?></a> <i class="fa fa-pencil"></i></h3>
					<div id="registration" style="display:<?php if($backToRegisterForm == true || $signup === true) { echo 'block'; } else {echo 'none';}  ?>">
						<div class="w3-row">
						<?php if($businessSignup !== true) { /* Buttons when registering for free account */?>
							<div class="w3-half w3-center w3-padding">
								<div class="w3-btn w3-disabled"><i class="fa fa-users"></i> <?php echo $strings['357']; ?></div>
							</div>
							<div class="w3-half w3-center w3-light-grey w3-btn">
								<a href="/index.php?control=businessSignup" class="w3-block w3-padding"><i class="fa fa-building"></i> <?php echo $strings['358']; ?></a>
							</div>
						<?php } else { /* Buttons when registering for business account */ ?>
							<div class="w3-half w3-center w3-light-grey w3-btn">
								<a href="/index.php?control=signup" class="w3-block w3-padding"><i class="fa fa-users"></i> <?php echo $strings['357']; ?></a>
							</div>
							<div class="w3-half w3-center w3-padding">
								<div class="w3-btn w3-disabled"><i class="fa fa-building"></i> <?php echo $strings['358']; ?></div>
							</div>
						<?php } ?>
						</div>
						
						<?php if($businessSignup !== true) { ?>
							<div class="w3-center w3-margin-top w3-margin-bottom">
								<h4 class="w3-xlarge w3-margin-top w3-margin-bottom"><i class="fa fa-users"></i> <?php echo $strings['398']; ?></h4>
								<a class="w3-btn w3-blue" href="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>?control=anonymousRegistration"><i class="fa fa-user-secret w3-xlarge"></i> <?php echo $strings['331']; ?></a>
								<div class="w3-small w3-padding w3-margin-top">
									<?php echo $strings['332']; ?>
								</div>
							</div>
						<?php } else { ?>
							<div class="w3-center w3-margin-top w3-margin-bottom">
								<h4 class="w3-xlarge w3-margin-top w3-margin-bottom"><i class="fa fa-building"></i> <?php echo $strings['399']; ?></h4>
								<div class="w3-margin-bottom">
									<?php echo $strings['400']; ?> <span class="w3-text-red"><b>3$</b></span> <?php echo $strings['361']; ?>
								</div>
								<div class="w3-small w3-padding">
									<?php echo $strings['377']; ?>
								</div>
							</div>
						<?php } ?>
						<hr>
						<form class="w3-container" method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" autocomplete="off">
							<?php
							echo '<input type="hidden" name="formUid" value="' . $_SESSION["secureFormCode"] . '">';
							echo '<input type="hidden" name="formAction" value="register">';
							if($businessSignup === true) { echo '<input type="hidden" name="businessSignup" value="yes">'; }
							?>
							<div class="w3-padding-16">
								<label><?php echo $strings['11']; ?></label>
								<input autocapitalize="words" inputmode="autocapitalized" id="signupForm" class="w3-input" type="text" name="certName"<?php if(isset($certName)) { echo ' value="' . $certName . '"'; } if ($signup === true) { echo ' autofocus'; } ?>>
								<?php if (isset($certNameError)) { echo '<div class="w3-text-red">' . $certNameError . '</div>';} ?>
							</div>
							<div class="w3-padding-16">
								<label><?php echo $strings['12']; ?></label>
								<input class="w3-input" type="email" name="certEmail"<?php if(isset($certEmail)) { echo ' value="' . $certEmail . '"'; } ?>>
								<?php if (isset($certEmailError)) { echo '<div class="w3-text-red">' . $certEmailError . '</div>';} ?>
							</div>
							<div class="w3-padding-16">
								<label><?php echo $strings['13']; ?></label>
								<select class="w3-select" name="certCountry">
								<?php echo countryList($certCountry); ?>
								</select>
								<?php if (isset($certCountryError)) { echo '<div class="w3-text-red">' . $certCountryError . '</div>';} ?>
							</div>
							<div class="w3-padding-16">
								<label><?php echo $strings['14']; ?></label>
								<input autocapitalize="words" inputmode="autocapitalized" class="w3-input" type="text" name="certState"<?php if(isset($certState)) { echo ' value="' . $certState . '"'; } ?>>
								<?php if (isset($certStateError)) { echo '<div class="w3-text-red">' . $certStateError . '</div>';} ?>
							</div>
							<div class="w3-padding-16">
								<label><?php echo $strings['15']; ?></label>
								<input autocapitalize="words" inputmode="autocapitalized" class="w3-input" type="text" name="certCity"<?php if(isset($certCity)) { echo ' value="' . $certCity . '"'; } ?>>
								<?php if (isset($certCityError)) { echo '<div class="w3-text-red">' . $certCityError . '</div>';} ?>
							</div>
							<div class="w3-padding-small w3-border w3-border-green">
							<b><?php echo $strings['325']; ?></b>
								<div class="w3-padding-16">
									<label><?php echo $strings['4']; ?></label>
									<input class="w3-input" type="password" name="certPassword" id="certPassword"<?php if(isset($certPassword)) { echo ' value="' . $certPassword . '"'; } if($anonymousRegistration == true) {echo ' autofocus';}?>>
									<?php if (isset($certPasswordError)) { echo '<div class="w3-text-red">' . $certPasswordError . '</div>';} ?>
									<span class="w3-small"><?php echo $strings['178']; ?></span>
								</div>
								<div class="w3-padding-16">
									<label><?php echo $strings['16']; ?></label>
									<input class="w3-input" type="password" name="certPasswordRetype"<?php if(isset($certPasswordRetype)) { echo ' value="' . $certPasswordRetype . '"'; } ?>>
									<?php if (isset($certPasswordRetypeError)) { echo '<div class="w3-text-red">' . $certPasswordRetypeError . '</div>';} ?>
								</div>
							</div>
							
							<div class="w3-padding-16 w3-right">
								<input class="w3-btn w3-blue w3-margin-bottom" type="submit" value="<?php echo $strings['17']; ?>" name="submit">
							</div>
						</form>
						<div class="w3-row w3-section w3-padding">
							<div class="w3-col" style="width:50px">
								<i class="w3-xxlarge fa fa-pencil-square-o"></i>
							</div>
							<div class="w3-rest w3-padding">
								<?php echo $strings['18']; ?>
							</div>
						</div>
						<hr>
						<div class="w3-row w3-section w3-padding">
							<div class="w3-col" style="width:50px">
								<i class="w3-xxlarge fa fa-download"></i>
							</div>
							<div class="w3-rest w3-padding">
								<?php echo $strings['19']; ?>
							</div>
						</div>
						<hr>
						<div class="w3-row w3-section w3-padding">
							<div class="w3-col" style="width:50px">
								<i class="w3-xxlarge fa fa-user"></i>
							</div>
							<div class="w3-rest w3-padding">
								<?php echo $strings['20']; ?>
							</div>
						</div>
						<hr>
						<div class="w3-row w3-section w3-padding">
							<div class="w3-col" style="width:50px">
								<i class="w3-xxlarge fa fa-exclamation-circle"></i>
							</div>
							<div class="w3-rest w3-padding">
								<?php echo $strings['21']; ?>
							</div>
						</div>
					</div>
				</div>
				<?php } ?>
			</div>
			<div class="w3-third">
				
				<div class="w3-card-4 w3-margin">
					<div class="w3-center">
						<h3 class="w3-padding w3-blue"><?php echo $strings['151']; ?></h3>
						<pre><?php echo generateRandomString(25, 'aA1!') ?></pre>
						<pre><?php echo generateRandomString(25, 'aA1') ?></pre>
						<p class="w3-padding w3-small"><?php echo $strings['153']; ?></p>
					</div>
				</div>
			</div>
		</div>
		<div class="indexBackground">&nbsp;</div>
		<div class="w3-center w3-border-bottom w3-border-blue w3-margin-bottom">
			<h2 class="w3-padding w3-border-bottom w3-border-blue"><?php echo $strings['116']; ?></h2>
			<div>
				<div class="indexBoxes w3-border-blue">
					<div class="w3-blue w3-xxxlarge">*******</div>
					<div class="w3-padding">
						<h3 class=""><?php echo $strings['125']; ?></h3>
						<?php echo $strings['204']; ?>
					</div>
				</div>
				<div class="indexBoxes w3-border-blue">
					<div class="w3-blue w3-xxxlarge"><i class="fa fa-key"></i></div>
					<div class="w3-padding">
						<h3 class=""><?php echo $strings['117']; ?></h3>
						<?php echo $strings['118']; ?>
					</div>
				</div>
				<div class="indexBoxes w3-border-blue">
					<div class="w3-blue w3-xxxlarge"><i class="fa fa-user-secret"></i></div>
					<div class="w3-padding">
						<h3 class=""><?php echo $strings['121']; ?></h3>
						<?php echo $strings['122']; ?>
					</div>
				</div>
				<div class="indexBoxes w3-border-blue">
					<div class="w3-blue w3-xxxlarge"><i class="fa fa-certificate"></i></div>
					<div class="w3-padding">
						<h3 class=""><?php echo $strings['119']; ?></h3>
						<?php echo $strings['120']; ?>
					</div>
				</div>
				<div class="indexBoxes w3-border-blue">
					<div class="w3-blue w3-xxxlarge"><i class="fa fa-cog"></i></div>
					<div class="w3-padding">
						<h3 class=""><?php echo $strings['123']; ?></h3>
						<?php echo $strings['124']; ?>
					</div>
				</div> 
			</div>
		</div>
<!-- 		<div class="indexBackground">&nbsp;</div> -->
<!--		<div class="w3-center w3-border-bottom w3-border-blue w3-margin-bottom">
			<h2 class="w3-padding w3-border-bottom w3-border-blue"><i class="fa fa-gamepad"></i> <?php echo $strings['205']; ?></h2>
			<div class="w3-padding">
				<?php echo $strings['206']; ?>
				<?php
					// Get encrypted record for the game...
					$sql = "SELECT encryptedKey FROM encryptionKeys WHERE id='77'";
					$db_rawKey = $conn->query($sql);
					$db_key = $db_rawKey -> fetch_assoc();
					$gameEncryptionKey = $db_key['encryptedKey'];
					
					$sql = "SELECT iv, entry FROM entries WHERE id='198'";
					$db_rawEntry = $conn->query($sql);
					$db_entry = $db_rawEntry -> fetch_assoc();
					$gameIv = $db_entry['iv'];
					$gameRecord = $db_entry['entry'];
				?>
				<ul class="w3-ul" style="text-align: left;">
					<li><b><?php echo $strings['207']; ?>:</b><br><pre class="wrapLongText" style="white-space: pre-wrap;"><?php echo $gameEncryptionKey; ?></pre></li>
					<li><b><?php echo $strings['208']; ?>:</b><br><pre class="wrapLongText" style="white-space: pre-wrap;"><?php echo $gameIv; ?></pre></li>
					<li><b><?php echo $strings['209']; ?></b><br><pre class="wrapLongText" style="white-space: pre-wrap;"><?php echo $gameRecord; ?></pre></li>
				</ul>
			</div>
		</div>
-->
<!--		<div class="indexBackground">&nbsp;</div> -->
		<?php
		include 'footer.php'; 
		?>
	</body>
</html>
