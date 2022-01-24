		<div style="height: 20px;"></div>
		<div class="w3-border-top w3-border-bottom w3-border-blue">
			<div style="height: 20px;"></div>
			<div class="w3-row">
			
				<div class="w3-half w3-small w3-center">
					<div class="w3-medium">Navigation</div>
					<ul class="footerUl">
						<li><a class="footerLinks" href="../index.php"><?php echo $strings['168'] ?></a></li>
						<li><a class="footerLinks" href="../faq.php"><?php echo $strings['132'] ?></a></li>
						<li><a class="footerLinks" href="../about.php"><?php echo $strings['133'] ?></a></li>
						<li><a class="footerLinks" href="../terms.php"><?php echo $strings['169'] ?></a></li>
					</ul>
					<div class="w3-hide-large" style="height: 20px;"></div>
				</div>
				
				<div class="w3-half w3-center">
					<div class="w3-xxlarge">
						<a style="color:#3578E5" href="https://www.facebook.com/jeanfrancois.courteau.10" target="_blank"><i class="fa fa-facebook-square"></i></a>
						<a style="color:rgb(29, 161, 242);" href="https://twitter.com/MySecureVault" target="_blank"><i class="fa fa-twitter-square"></i></a>
						<a class="w3-text-blue" href="mailto:info@mysecurevault.ca"><i class="fa fa-envelope"></i></a>
					</div>
					<div style="height: 20px;"></div>
					<a href="../index.php"><img class="footerLogo" src="<?php echo $logo ?>" alt="<?php echo $siteTitle; ?>"></a>
					<br>
					<span class="w3-tiny">
						&copy; Copyright 2020 Jean-Fran&ccedil;ois Courteau
						<br>
						<a href="mailto:info@mysecurevault.ca">info@mysecurevault.ca</a>
					</span>
				</div>
				<div style="height: 20px;"></div>	
			</div>
			<div style="height: 20px;"></div>
		</div>

		<?php $certInfo = getCertificateInfo(); ?>
		<div class="w3-border-bottom w3-border-blue w3-center">
			<div style="height: 20px;"></div>
			<span class="w3-small"><b><?php echo $strings['185'] ?></b><br><?php echo $strings['186'] ?></span><br>
			<span class="w3-tiny"><?php echo $strings['187'] ?> <?php echo $certInfo['serial']; ?><br><?php echo $strings['188'] ?> <?php echo $certInfo['sha1']; ?><br><?php echo $strings['189'] ?> <?php echo $certInfo['sha256']; ?></span>
			<div style="height: 20px;"></div>
		</div>
		<div style="height: 20px;"></div>
	</body>
</html>