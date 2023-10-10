<?php

namespace extension\ka_product_import;

class ModelCatalogProduct extends \Opencart\Admin\Model\Catalog\Product {

	protected $last_product_id;
	protected $disable_addProduct = false;
	protected $last_addProductData = array();

	public function addProduct(array $data): int {
	
		if ($this->disable_addProduct) {
			$this->last_addProductData = $data;
			return false;
		}
	
		$product_id = parent::addProduct($data);

		$this->last_product_id = $product_id;
		
		if (empty($product_id)) {
			return $product_id;
		}
		
		if (isset($data['skip_import'])) {
			$this->db->query("UPDATE " . DB_PREFIX . "product SET 
				skip_import = '" . (int)$data['skip_import'] . "' 
				WHERE product_id = '" . (int)$product_id . "'
			");
		}
		
		return $product_id;
	}

	
	public function editProduct(int $product_id, array $data): void {
	
		parent::editProduct($product_id, $data);

		if (isset($data['skip_import'])) {
			$this->db->query("UPDATE " . DB_PREFIX . "product SET 
				skip_import = '" . (int)$data['skip_import'] . "' 
				WHERE product_id = '" . (int)$product_id . "'
			");
		}
	}
		
	public function copyProductForImport(int $product_id) {

		$this->last_product_id = 0;
		
		parent::copyProduct($product_id);
		
		return $this->last_product_id;
	}
	
	
	
	
	public function getProductCopy($product_id) {
	
		$this->disable_addProduct = true;
	
		$this->copyProduct($product_id);
		
		$this->disable_addProduct = true;
	
		return $this->last_addProductData;
	}
	
}
