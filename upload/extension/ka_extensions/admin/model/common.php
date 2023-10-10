<?php
/*
	$Project$
	$Author$

	$Version$ ($Revision$)
*/

namespace extension\ka_extensions;

class ModelCommon extends Model {

	protected $kacurl;

	protected function onLoad() {

		$this->kacurl = new Curl();
		
		$this->load->model('setting/setting');
		
		return true;
	}
	
	public function getExtensionInfo($extension) {
	
		$return = array();

		$ka_reg = $this->model_setting_setting->getSetting('kareg');

		$key = $this->getRegKey($extension);
		
		if (empty($ka_reg) || empty($ka_reg[$key])) {
			return $return;
		}
		
		return $ka_reg[$key];
	}

	
	public function getInstalledByType($type) {
	
		$extension_data = array();

		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "extension 
			WHERE `type` = '" . $this->db->escape($type) . "'
		");
		
		if (empty($query->rows)) {
			return $extension_data;
		}
		
		foreach ($query->rows as $row) {
			$extension_data[$row['code']] = $row;
		}
		
		return $extension_data;
	}

	
/*
	Returns

		$ka_st_data = array(
			'result' => 'OK'
		);

		$ka_st_data = array(
			'result'  => 'ERROR',
			'message' => 'The code is not valid.'
		);
	
*/	
	public function registerKey($key, $extension) {

		$ret = '';
		
		// send code to the remote server
		//
		$data = array(
			'key'       => $key,
			'url'       => HTTP_CATALOG,
			'extension' => $extension,
		);
		
		$request_url = KaGlobal::getKaStoreURL() . '?route=extension/register_key';

		$result = $this->kacurl->request($request_url, $data);

		// process the response from the remote server
		//
		if (empty($result)) {
			$this->lastError = 'A request to the license registration server failed with this error:' 
				. $this->kacurl->getLastError()
				. '<br />Please try again later.<br /><br />If you cannot activate the license key within 24 ' 
				. 'hours please contact us at support@ka-station.com.
			';
				
			return false;
		}
		
		$result = json_decode($result, true);		
		if (!empty($result['error'])) {
			$this->lastError = $result['error'];
			return false;
		}	

		
		if (empty($result['ext_code'])) {
			$this->lastError = 'Wrong request parameters:' . var_export($result, true);
			return false;
		}
		
		$data['is_registered'] = true;
		if (!$this->saveReg($result['ext_code'], $data)) {
			$this->lastError = 'saveReg failed';
			return false;
		}
		
		return true;
	}
	

	public function isRegistered($extension_code) {
		$ka_reg = $this->model_setting_setting->getSetting('kareg');

		if (empty($ka_reg)) {
			return false;
		}

		$key = $this->getRegKey($extension_code);
		
		if (isset($ka_reg[$key]['is_registered'])) {
			return true;
		}
		
		return false;
	}

	
	public function saveRegAll($data = array()) {

		if ($data === null) {
			return true;
		}
		
		// get installed extensions
		//
		$installed_extensions = $this->getInstalledByType('ka_extensions');
		$installed_extension_codes = array_keys($installed_extensions);
		
		$url = HTTP_CATALOG;
		$kareg = array(
			'kareg' => 1
		);
		
		// get existing registrations
		//
		$existing = $this->model_setting_setting->getSetting('kareg');
		
		if (!empty($data)) {
			foreach ($data as $k => $v) {
			
				$v['url']           = $url;
				$v['is_registered'] = 1;
			
				$key = $this->getRegKey($k);
				$kareg[$key] = $v;
				
				if (!empty($existing[$key])) {
					unset($existing[$key]);
				}
			}
		}
		
		// copy installed licenses to the new array
		//
		if (!empty($existing['kareg'])) {
			unset($existing['kareg']);
		}
		
		if (!empty($existing)) {
			foreach ($existing as $ek => $ev) {
				$ext_code = substr($ek, 5);
				if (in_array($ext_code, $installed_extension_codes)) {
					$ev['is_wrong_license'] = true;
					$kareg[$ek] = $ev;
				}
			}
		}

		$this->model_setting_setting->editSetting('kareg', $kareg);
		
		return true;
	}

	
	public function saveReg($ext_code, $data = array()) {

		$ka_reg = $this->model_setting_setting->getSetting('kareg');

		$url = HTTP_CATALOG;
		$key = $this->getRegKey($ext_code);
		
		$ka_reg[$key] = array();
		
		if (isset($data['is_registered'])) {
			$ka_reg[$key]['is_registered'] = 1;
			$ka_reg[$key]['url'] = $url;
		}
		
		$this->model_setting_setting->editSetting('kareg', $ka_reg);
		
		return true;
	}	

	
	/*
		Service function for updating or creating a new Opencart setting
	*/
	public function saveSetting($code, $key, $value) {
		$this->load->model('setting/setting');
		
		$setting = $this->model_setting_setting->getSetting($code);
		$setting[$key] = $value;
		$this->model_setting_setting->editSetting($code, $setting);
	}	
	
	
	protected function getRegKey($code) {
		$result = 'kareg' . $code;
		return $result;
	}
	
	
	public function loadManifest($ext_code) {
	
		$ini_file = DIR_OPENCART . 'extension/' . $ext_code . '/install.json';

		if (!file_exists($ini_file)) {
			return array();
		}

		$ini = file_get_contents($ini_file);		
		$ini = json_decode($ini, true);
		
		return $ini;
	}
}