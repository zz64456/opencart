<?php
/*
	This file was inherited by kamod.
	More information can be found at https://www.ka-station.com/kamod
	
	Original file: extension/ka_extensions/kamod/admin/controller/startup/permission.php
*/
/*
	$Project$
	$Author$

	$Version$ ($Revision$)

	We always allow to see the 'Ka Extensions' page. Probably it will be hidden later by the permissions.
*/

namespace extension\ka_extensions\startup;

require_once(__DIR__ . '/permission.1.kamod.php');

class Permission extends \Opencart\Admin\Controller\Startup\Permission_kamod  {

	public function index(): object|null {

		if (!empty($this->request->get['route'])) {
			if (strpos($this->request->get['route'], 'extension/ka_extensions/extensions') !== false) {
			
				// we always allow those users to see the ka extensions page who can grant access to 
				// that page ourselves
				//
				if ($this->user->hasPermission('access', 'user/user_permission')
				 && $this->user->hasPermission('modify', 'user/user_permission')
				) {
					return null;
				}
			}
		}
		
		return parent::index();
	}
}
