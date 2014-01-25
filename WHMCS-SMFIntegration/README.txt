WHMCS-SMF 2.0 Integration
========================================
Licensed under the BSD License
Version: 1.0
By: Anthony Calandra/Anthony`
========================================
Thanks for choosing this WHMCS module! This module integrates WHMCS registrants into
the SMF member system, and allows for easy customization for custom features you may
wish to add in yourself.

Features:
	- Registered clients are registered into SMF
	- Combines client first and lastnames and client ID to form SMF username (to
		 avoid duplications)
	- Updated client data is updated into SMF (includes username and email)
	- Deleted clients are deleted in SMF
	- Creates a link on profile and posts linking to WHMCS client profile

What this module configures (SMF-side):
	- Disables registration page to help avoid duplications (can be switched back 
		on through SMF)
	- DOES NOT modify SMF password when client data is modified
	- DOES NOT delete custom fields after module is disabled! You must do this in
		the admin CP yourself. I do this because if the data in the table is
		deleted, it cannot be retrieved incase this module is installed again.

