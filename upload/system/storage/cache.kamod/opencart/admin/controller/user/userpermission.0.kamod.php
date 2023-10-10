<?php
/*
	This file was inherited by kamod.
	More information can be found at https://www.ka-station.com/kamod
	
	Original file: extension/ka_extensions/kamod/admin/controller/user/user_permission.php
*/
/*
	$Project$
	$Author$

	$Version$ ($Revision$)
	
	Hide any paths within kamod directories. They cannot be controlled by permissions and will only
	confuse the admin.
	
*/
namespace extension\ka_extensions\user;

require_once(__DIR__ . '/userpermission.1.kamod.php');

class ControllerUserPermission extends \Opencart\Admin\Controller\User\UserPermission_kamod  {

	use \extension\ka_extensions\TraitController;

	public function form(): void {
	
		$this->disableRender();
		parent::form();
		$this->enableRender();
		
		$data = $this->getRenderData();
		$template = $this->getRenderTemplate();
		
		$extensions = array();
		foreach ($data['extensions'] as $e) {
			if (preg_match('/extension\/[^\/]*\/kamod\//', $e, $matches)) {
				continue;
			}
		
			$extensions[] = $e;
		}
		$data['extensions'] = $extensions;
		
		$this->response->setOutput($this->load->view($template, $data));
	}
}