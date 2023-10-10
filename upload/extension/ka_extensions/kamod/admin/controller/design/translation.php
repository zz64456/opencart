<?php
/*
	$Project$
	$Author$

	$Version$ ($Revision$)
	
	Here we add the root code to the list of routes. It allows the user to modify root language values.
	We have plans to add many language variables with standard human-readable text keys.
	
*/

namespace extension\ka_extensions\design;

class ControllerTranslation extends Opencart\Admin\Controller\Design\Translation {

	public function path(): void {

		if (isset($this->request->get['language_id'])) {
			$language_id = (int)$this->request->get['language_id'];
		} else {
			$language_id = 0;
		}
		$this->load->model('localisation/language');
		$language_info = $this->model_localisation_language->getLanguage($language_id);
	
		parent::path();
		
		$output = $this->response->getOutput();
		
		$json = json_decode($output, true);
		array_unshift($json, $language_info['code']);
		
		$this->response->setOutput(json_encode($json));
	}
}
