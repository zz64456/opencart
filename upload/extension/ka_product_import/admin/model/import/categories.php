<?php
/*
	$Project: CSV Product Import $
	$Author: karapuz team <support@ka-station.com> $

	$Version: 6.0.0.2 $ ($Revision: 572 $)
*/

namespace extension\ka_product_import\import;

class ModelCategories extends \extension\ka_extensions\Model {

	private $import;

	public function setImport($import) {
		$this->import = $import;
	}

	/*
		PARAMETERS:
			..
			$category_chain - encoded html string
			..
	*/
	protected function saveCategory($product_id, $category_chain, $clear_cache = false) {

		if (empty($category_chain)) {
			return false;
		}

		$category_chain = $this->import->kaformat->strip($category_chain, $this->import->params['cat_separator']);
		$category_names = explode($this->import->params['cat_separator'], $category_chain);
		
		$categories       = array();
		$saved_categories = array();

		$parent_id   = 0;
		if (!empty($this->import->params['parent_category_id'])) {
			$parent_id = (int)$this->import->params['parent_category_id'];
			$saved_categories[] = $parent_id;
		}
		$category_id = 0;
		
		if (empty($this->import->params['cfg_compare_as_is'])) {
			$name_comparison = "TRIM(CONVERT(name using utf8)) LIKE ";
		} else {
			$name_comparison = "name = ";
		}

		foreach ($category_names as $ck => $cv) {

			$cv = trim($cv);

			$new_category = false;
			
			// we use convert function here to make comparison case-insensitive
			// http://dev.mysql.com/doc/refman/5.0/en/cast-functions.html#function_convert
			//
			// http://dev.mysql.com/doc/refman/5.0/en/string-comparison-functions.html#operator_like
			//
			$sel = $this->db->query("SELECT c.category_id FROM " . DB_PREFIX . "category_description cd
				INNER JOIN " . DB_PREFIX . "category c ON cd.category_id=c.category_id
				WHERE 
					$name_comparison '". $this->db->escape($this->db->escape($cv)) . "'
					AND parent_id = '$parent_id'"
			);

			if (empty($sel->row)) {
				
				$this->db->query("INSERT INTO " . DB_PREFIX . "category SET 
					parent_id = '$parent_id',
					status ='1',
					image = '',
					date_modified = NOW(), date_added = NOW()
				");
				
				$category_id = $this->db->getLastId();
				$is_new      = true;

				foreach ($this->import->languages as $lang) {
					$rec = array(
						'category_id' => $category_id,
						'language_id' => $lang['language_id'],
						'meta_title'  => $cv,
						'name'        => $cv
					);
					$this->kadb->queryInsert('category_description', $rec);
				}
				
				$this->import->stat['categories_created']++;

				$level = 0;
				$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "category_path` WHERE category_id = '" . (int)$parent_id . "' ORDER BY `level` ASC");
				
				foreach ($query->rows as $result) {
					$this->db->query("REPLACE INTO `" . DB_PREFIX . "category_path` SET `category_id` = '" . (int)$category_id . "', `path_id` = '" . (int)$result['path_id'] . "', `level` = '" . (int)$level . "'");
					$level++;
				}
				$this->db->query("REPLACE INTO `" . DB_PREFIX . "category_path` SET `category_id` = '" . (int)$category_id . "', `path_id` = '" . (int)$category_id . "', `level` = '" . (int)$level . "'");
				
			} else {
				$category_id = $sel->row['category_id'];
			}

			// insert category to stores
			$this->import->insertToStores('category', $category_id, $this->import->params['store_ids']);

			$parent_id = $category_id;
			$saved_categories[] = $category_id;
		}

		if (empty($category_id)) {
			return false;
		}

		// add to each category of the category path
		//
		if (empty($this->import->params['add_to_each_category'])) {
			$saved_categories = array($category_id);
		}
		foreach ($saved_categories as $category_id) {
			$rec = array(
				'product_id'  => $product_id,
				'category_id' => $category_id,
			);
			$this->kadb->queryInsert('product_to_category', $rec, true);
		}
		
		if ($clear_cache) {
			$this->cache->delete('category');
		}
				
		return true;
	}

	
	/*
		Assign categories to product by category_id or by category name.
		
	*/
	public function saveCategories($row, $data, $product, $flags, &$updated) {

		$category_assigned = false;
		$product_id = $data['product_id'];
		
		if (!empty($data['category_id']) || !empty($data['category'])) {
			if ($flags['delete_old']) {
				$this->db->query("DELETE FROM " . DB_PREFIX . "product_to_category
					WHERE product_id = '$product_id'");
			}
			unset($updated['fields']['product_category']);
		}
		
		$multicat_sep = $this->import->params['multicat_sep'];
		$cats_list    = array();

		// assign categories by category_id
		//
		if (!empty($data['category_id'])) {

			if (!empty($multicat_sep)) {
				$cats_list = explode($multicat_sep, $data['category_id']);	
			} else {
				$cats_list = array($data['category_id']);
			}
			foreach ($cats_list as $cat) {
				$cat = (int)$cat;
				$qry = $this->db->query("SELECT category_id FROM " . DB_PREFIX . "category 
					WHERE category_id = '" . $cat . "'"
				);

				if (!empty($qry->row)) {
					$rec = array(
						'product_id'  => $product_id,
						'category_id' => $qry->row['category_id'],
					);
					
					$update['fields']['product_category'] = 1;
					$this->kadb->queryInsert('product_to_category', $rec, true);
					$category_assigned = true;
				} else {
					$this->import->addImportMessage("Category ID was not found '$cat'");
				}
			}
		}

		// assign categories by name
		//				
		if (!empty($data['category']) && empty($data['category_id'])) {

			// insert categories
			//
			if (!empty($data['category'])) {
				if (!empty($multicat_sep)) {
					$cats_list = explode($multicat_sep, $data['category']);
				} else {
					$cats_list = array($data['category']);
				}
				
				foreach ($cats_list as $cat) {
				
					if (!empty($data['sub-category'])) {
						$cat .= $this->import->params['cat_separator'] . $data['sub-category'];
					}
					
					if (!empty($data['sub-sub-category'])) {
						$cat .= $this->import->params['cat_separator'] . $data['sub-sub-category'];
					}
				
					if ($this->saveCategory($product_id, $cat)) {
						$category_assigned = true;
						$update['fields']['product_category'] = 1;						
					}
				}
			}
		}

		// assign the default category for new products if no categories were assigned
		//
		if (!$category_assigned && $flags['is_new']) {
			if (!empty($this->import->params['default_category_id'])) {
				$category_id = $this->import->params['default_category_id'];
				$rec = array(
					'product_id'  => $product_id,
					'category_id' => $category_id,
				);
				
				$updated['fields']['product_category'] = 1;
				$this->kadb->queryInsert('product_to_category', $rec, true);
			}
		}
		
		$this->cache->delete('category');
	}

}