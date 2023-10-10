----------------------------------------
Ka Extensions library ver.5.0.0.x
----------------------------------------

Thank you for choosing our products! We hope you will enjoy it. If you have any questions or
notes feel free to contact us at support@ka-station.com. All messages are usually replied within
24 hours.


Purpose of this package
=======================

Ka Extensions is a set of files shared among other extensions. It includes 'kamod' engine as well as other useful
tools.


Requirements
=======================
- Opencart 4.0.0.0 - 4.0.2.x


How to install
=======================

1) Log in to your store back-end under an adminstrator account and upload the extension archive 
   at the 'Extension Installer' page.
   
2) On the 'Installed Extensions' list click the 'Install' button next to the 'Ka Extensions' extension. Once you click 
   the button a part of extension functionality will be activated. In case of any unexpected issues with the store, 
   please refer to the 'Safe Mode' section of this file.

3) Reload the page, you should see a new menu item 'Ka Extensions' under the 'Extensions' menu. All extensions 
   developed by our team will appear there. You can hide that menu with standard user permissions. The page always shows
   to administrators with a 'modify' access at the 'User Permissions' page.   
   
4) Make sure that the store front-end is available for customers and it is functional.  


Safe Mode
======================

In case of any fatal issues with our extensions (actually any extensions based on kamod engine), it is 
possible to Open the store in a safe mode. The safe mode is a default Opencart store without any changes
added by kamod engine. Please notice that other 3rd party code may still affect the store.

To open the store in the safe mode you have to specify 'route=ka_safe_mode' parameter in the store back-end url. 
For example, your store back-end URL looks like this:

https://www.mystore.com/admin-secret/index.php

in that case you need to open the following URL with the safe mode parameter:

https://www.mystore.com/admin-secret/index.php?route=ka_safe_mode

After defining the parameter, you will be prompted to login to the store under an administrator account. 
On successful login, you may see a 'page not found' notice, but it is correct because there is no real page 
at the specified route.

To exit the safe mode, you have to close the browser and open it again.

IMPORTANT: Do not uninstall the 'Ka Extensions' library under the safe mode. It may lead to an incorrect state
of the 'vendor.php' file which will result to an unrecoverable fatal error.


About
==============
 * @author karapuz team <support@ka-station.com>
 * @copyright (c) 2014-2023