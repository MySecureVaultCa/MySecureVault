<!DOCTYPE html>
<html lang="<?php echo $_SESSION['language']; ?>">
	<head>	
		<title><?php echo $siteTitle; ?></title>
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<link rel="stylesheet" type="text/css" href="../w3.css">
		<link rel="stylesheet" type="text/css" href="../css/font-awesome.css">

		<link rel="apple-touch-icon" sizes="180x180" href="../apple-touch-icon.png">
		<link rel="icon" type="image/png" sizes="32x32" href="../favicon-32x32.png">
		<link rel="icon" type="image/png" sizes="16x16" href="../favicon-16x16.png">
		<link rel="manifest" href="../site.webmanifest">
		<link rel="mask-icon" href="safari-pinned-tab.svg" color="#5bbad5">
		<meta name="msapplication-TileColor" content="#ffc40d">
		<meta name="theme-color" content="#ffffff">

		<?php if (isset($alert_message) && $alert_message != '') { echo "<script type=\"text/javascript\">alert(\"$alert_message\");</script>"; } ?>
		
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
			
		</script>
		
	</head>
	<body>