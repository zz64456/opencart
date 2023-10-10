<?php
/*
	This file was inherited by kamod.
	More information can be found at https://www.ka-station.com/kamod
	
	Original file: extension/ka_extensions/kamod/admin/controller/marketplace/installer.php
*/
/*
	$Project$
	$Author$

	$Version$ ($Revision$)
*/

namespace extension\ka_extensions\marketplace;

use \extension\ka_extensions\Directory;
use \extension\ka_extensions\KaGlobal;

require_once(__DIR__ . '/installer.1.kamod.php');

class ControllerMarketplaceInstaller extends \Opencart\Admin\Controller\Marketplace\Installer_kamod  {

	use \extension\ka_extensions\TraitController;

	/*
		This method is called on uninstalling the extension at the 'Extension Installer' page.
		The vendor file has to be rebuilt after that operation. OC4000 does not do it.
	*/
	public function uninstall(): void {
	
		if (isset($this->request->get['extension_install_id'])) {
			$extension_install_id = (int)$this->request->get['extension_install_id'];
		} else {
			$extension_install_id = 0;
		}
	
		$this->load->model('setting/extension');
		$extension_install_info = $this->model_setting_extension->getInstall($extension_install_id);
	
		// let the default uninstaller handle any non-standard situations
		if (!$this->user->hasPermission('modify', 'marketplace/installer') && empty($extension_install_info)) {
			parent::uninstall();
			return;
		}
		
		// remove active.kamod file
		$this->model_setting_extension->deactivateKamodForExtension($extension_install_info['code']);

		parent::uninstall();
		
		// these operations are harmless and they don't need to know if the uninstall operation was
		// successful or not
		$output = $this->response->getOutput();
		$this->vendor();
		$this->response->setOutput($output);

		\extension\ka_extensions\KamodManager::getInstance()->markKamodCacheInvalid();
				
		if ($extension_install_info['code'] == 'ka_extensions') {
			$this->load->model('setting/setting');
			$this->model_setting_setting->deleteSetting('kamod', 0);
		}
	}
	
	
	public function install(): void {

		// get extension information
		//
		if (isset($this->request->get['extension_install_id'])) {
			$extension_install_id = (int)$this->request->get['extension_install_id'];
		} else {
			$extension_install_id = 0;
		}

		$this->load->model('setting/extension');
		$extension_install_info = $this->model_setting_extension->getInstall($extension_install_id);

		if (empty($extension_install_info) || !$this->user->hasPermission('modify', 'marketplace/installer')) {
			parent::install();
			return;
		}
	
		if (!empty($this->request->get['force_installation'])) {
			if (file_exists(DIR_EXTENSION . $extension_install_info['code'])) {
				Directory::deleteDirectory(DIR_EXTENSION . $extension_install_info['code']);
			}
		}

		parent::install();
		
		if (KaGlobal::isKaInstalled($extension_install_info['code'])) {
			$this->model_setting_extension->activateKamodForExtension($extension_install_info['code']);
		}

		\extension\ka_extensions\KamodManager::getInstance()->markKamodCacheInvalid();
	}
	
	
	public function extension(): void {
	
		$this->disableRender();
		parent::extension();
		$this->enableRender();
		
		$data     = $this->getRenderData();
		$template = $this->getRenderTemplate();

		if (!empty($data['extensions'])) {
			foreach ($data['extensions'] as &$ext) {
				$extension_id = preg_replace("/.*extension_install_id=(\d*).*/", "$1", $ext['install']);
				
				if (!empty($extension_id)) {
					$ext['extension_id'] = $extension_id;
					if (!$this->isSafeInstallation($extension_id)) {					
						$ext['force_install'] = html_entity_decode($ext['install']) . '&force_installation=1';
					}
				}
			}
		}
		
		$this->response->setOutput($this->load->view($template, $data));
	}
	
	
	protected function isSafeInstallation($extension_id) {
		
		$this->load->model('setting/extension');
		$extension = $this->model_setting_extension->getInstall($extension_id);

		if (empty($extension)) {
			return true;
		}
		
		if (file_exists(DIR_EXTENSION . $extension['code'])) {
			return false;
		}
		
		return true;
	}	
	
	/*
		We add a time marker of vendor.php rebuild to DB. It is checked to make sure that we have kamod changes
		in that file after possible rebuild in safe mode.
	*/	
	public function vendor(): void {

		$this->disableRender();
		parent::vendor();
		$this->enableRender();

		$output = $this->response->getOutput();

		$json = json_decode($output, true);
		
		if (!empty($json) && !empty($json['success'])) {

			$this->load->model('setting/setting');
			
			$kamod_setting = $this->model_setting_setting->getSetting('kamod', 0);
			$kamod_setting['kamod_vendor_patched_time'] = time();
			$this->model_setting_setting->editSetting('kamod', $kamod_setting);
		}	
	}
	

	/*
		OC4000 does not delete an existing file on upload because of a bug. We will delete it.
	*/
	public function upload(): void {
	
		// 1. Validate the file uploaded.
		if (!empty($this->request->files['file']['name'])) {
			$filename = basename($this->request->files['file']['name']);
			
			// 4. check if there is already a file
			$file = DIR_STORAGE . 'marketplace/' . $filename;

			if (is_file($file)) {
				unlink($file);
			}
		}
		
		parent::upload();
	}		
}