<?php
/*
	$Project: CSV Product Import $
	$Author: karapuz team <support@ka-station.com> $

	$Version: 6.0.0.2 $ ($Revision: 581 $)
*/

namespace extension\ka_product_import;

class ControllerCatalogProduct extends \Opencart\Admin\Controller\Catalog\Product {

	use \extension\ka_extensions\TraitController;

	public function form(): void {

		$this->disableRender();
		parent::form();
		$this->enableRender();

		$template = $this->getRenderTemplate();
		$data = $this->getRenderData();
		
		$this->load->language('extension/csv_product_import/product');
		
		if (!empty($data['product_id'])) {
			$product_info = $this->model_catalog_product->getProduct($data['product_id']);
			if (!empty($product_info)) {
				$data['skip_import'] = (int) $product_info['skip_import'];
			}
		} else {
			$data['skip_import'] = 0;
		}
		
		$data['ka_product_import_link'] = $this->url->linka('extension/ka_product_import/extension');
		
		$this->response->setOutput($this->load->view($template, $data));
	}
}
