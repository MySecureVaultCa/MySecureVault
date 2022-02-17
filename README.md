# MySecureVault
MySecureVault is the most secure passwords, notes and files vault on the Internet. It has been developed with ultimate privacy and security in mind. It is in fact so secure that no one other than the owner of the certificate files can decrypt one's data. It is a multi user password manager developped in PHP, the good old procedural way. The code is easy to understand even for lambda users, and I documented it as best as I could.

My name is Jean-François Courteau, and I developped MySecureVault out of the personal need to have my passwords kept securely. I did not want a cloud company to be able to decrypt my data, I wanted to be able to access my passwords on any device I own, and I wanted to use certificate authentication. All this is here for you tu use freely.

This repo here is offered as-is, with no guarantee whatsoever. If you only want to USE MySecureVault, you can visit https://mysecurevault.ca, which is hosted on my servers.

## Installation
1. git clone https://github.com/MySecureVaultCa/MySecureVault/ to a folder you can share on your favorite php web server (Apache is preferred)
2. Import the database schemas from https://github.com/MySecureVaultCa/MySecureVault/tree/main/dbSchema in 2 different databases on your MySQL server(s)
3. Copy config.inc.php to config.php at the root, and modify the settings according to your setup
4. Configure the config.php file
5. Set $config['baseUrl'] to the URL with which you plan to access your MySecureVault instance, and make sure SSL is configured properly
6. Set your database connection strings, mail server configuration and make sure all settings are OK in the config.php file
7. Connect to the base URL, and create a first user certificate from the home page to start using MySecureVault.ca

## Enterprise functionalities
At first, I did not develop MySecureVault to have auditing, RBAC, or other fancy discovery functionalities. I wanted to keep MySecureVault as secure and simple as possible. However, in the coming months, I plan to develop some business features for paid subscribers, like full action logging, device / user management, and remote password access through an API similar to Thycotic Secret Server.

## Support
I do not offer official enterprise or paid support, but I guess this could come in the future.

You can get more info on our website at https://mysecurevault.ca
