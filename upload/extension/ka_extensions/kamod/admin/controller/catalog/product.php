<?php
/*
	$Project$
	$Author$

	$Version$ ($Revision$)
	
	We add more information about the error to the top message. Usually it is hard to know what field
	has an error on multiple tabs.
*/

namespace extension\ka_extensions;
class ControllerCatalogProduct extends \Opencart\Admin\Controller\Catalog\Product {

	public function save(): void {

		parent::save();	
	
		$output = $this->response->getOutput();
		$json = json_decode($output, true) ?? array();
		
		if (!empty($json['error'])) {
		
			$errors = $json['error'];
			unset($errors['warning']);
			
			if (!empty($errors)) {
				$json['error']['warning'] = $json['error']['warning'] . ' <br> ' . implode('<br>', $errors);
			}
		}

		$this->response->setOutput(json_encode($json));
	}
}