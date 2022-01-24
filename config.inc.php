<?php
$config = array();
	// Security settings
	$config['salt'] = 'CHANGE ME';													// The salt is a text string that serves to scramble some data. Generate a random string of at least 25 characters upon first usage of the software!
	$config['currentCipherSuite'] = 'aes-256-gcm';									// Current cipher suite. CHANGING THIS CIPHER SUITE REQUIRES A THOROUGH ANALYSIS OF THE CODE. DO NOT CHANGE ON YOUR OWN!
	$config['currentPadding'] = OPENSSL_PKCS1_OAEP_PADDING;							// Current key encryption padding. CHANGING THIS PADDING ALGORITHM REQUIRES A THOROUGH ANALYSIS OF THE CODE. DO NOT CHANGE ON YOUR OWN!
	$config['currentPaddingString'] = 'OPENSSL_PKCS1_OAEP_PADDING';					// This value must be a text string identical to the above padding algorithm. REQUIRES A THOROUGH ANALYSIS OF THE CODE. DO NOT CHANGE ON YOUR OWN!
	
	// Main database connection string	
	$config['dbServerName'] = 'change.me.local';									// MySQL server name or IP address
	$config['dbUsername'] = 'mySecureVault';										// MySQL username
	$config['dbPassword'] = 'CHANGE ME';											// MySQL password for user
	$config['dbName'] = 'mySecureVault';											// MySQL database name
	
	
	// Logging database connection string
	$config['loggingDbServerName'] = 'change.me.local';								// MySQL server name or IP address
	$config['loggingDbUsername'] = 'mySecureVaultPrivate';							// MySQL username
	$config['loggingDbPassword'] = 'CHANGE ME';										// MySQL password for user
	$config['loggingDbName'] = 'mySecureVaultPrivate';								// MySQL database name
	
	// Application context
	$config['baseUrl'] = "https://change.me.local";									// Complete URL where your application is installed. *** HTTPS IS MANDATORY! ***
	$config['sessionCookieName'] = "MYSECUREVAULT";									// Session cookie name
	$config['sessionCookieHashName'] = "msv_sch";									// Secure session hash cookie name
	$config['devMode'] = true;														// Set to true if you want "dev" mode on, false for production mode.

	// Outgoing email configuration:
	$config['mailServer'] = 'mail.server.local';									// Name or IP address of mail server
	$config['mailServerPort'] = '25';												// TCP Port for outgoing emails
	$config['mailServerAuth'] = 'LOGIN';											// Mail server authentication type (LOGIN, PLAIN)
	$config['mailServerUsername'] = 'info@change.me.local';							// Username for outgoing emails (can be the email address itself, or else, depending on your server configuration)
	$config['mailServerPassword'] = 'CHANGE ME';									// Password for above username
	$config['mailServerFrom'] = 'info@change.me.local';								// The email address associated with your username, that is authorized by your email account
	$config['mailServerSender'] = '"CHANGE ME" <info@change.me.local>';				// The sender information that will appear in the recipient's inbox


?>