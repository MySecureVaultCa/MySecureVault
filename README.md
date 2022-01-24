# MySecureVault
MySecureVault is the most secure passwords, notes and files vault on the Internet. It has been developed with ultimate privacy and security in mind. It is in fact so secure that no one other than the owner of the certificate files can decrypt one's data. It is a multi user password manager developped in PHP, the good old procedural way. The code is easy to understand even for lambda users, and I documented it as best as I could.

My name is Jean-François Courteau, and I developped MySecureVault out of the personal need to have my passwords kept securely. I did not want a cloud company to be able to decrypt my data, I wanted to be able to access my passwords on any device I own, and I wanted to use certificate authentication. All this is here for you tu use freely.

This repo here is offered as-is, with no guarantee whatsoever. If you only want to USE MySecureVault, you can visit https://mysecurevault.ca, which is hosted on my servers.

## Installation
You can download the software and use it, but for the moment, the installation is undocumented. I can tell you that it is written in PHP with a MySQL 5.7 backend
The database connection info and a whole bunch of settings can be set in the config.php file. Eventually, I will document it better.

## Enterprise functionalities
At first, I did not develop MySecureVault to have auditing, RBAC, or other fancy discovery functionalities. I wanted to keep MySecureVault as secure and simple as possible. However, in the coming months, I plan to develop some business features for paid subscribers, like full action logging, device / user management, and remote password access through an API similar to Thycotic Secret Server.

## Support
I do not offer official enterprise or paid support, but I guess this could come in the future.

You can get more info on our website at https://mysecurevault.ca
