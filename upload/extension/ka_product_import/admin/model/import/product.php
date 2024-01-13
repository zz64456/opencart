<?php
/*
	$Project: CSV Product Import $
	$Author: karapuz team <support@ka-station.com> $

	$Version: 6.0.0.2 $ ($Revision: 581 $)
*/

namespace extension\ka_product_import\import;

use \extension\ka_extensions\KaGlobal;
use \extension\ka_extensions\Format;
use \extension\ka_product_import\ModelImport;
use \extension\ka_extensions\QB;

class ModelProduct extends \extension\ka_extensions\Model {

	protected $lastError;
	protected $params;

	protected $field_lengths;

	// main import model
	protected $import;

	private $import_options;
	private $import_categories;

	// these fields will be affected by the 'treat as plain text' option.
	//
	protected $plain_text_fields = array('description');

	protected function onLoad() {

		$this->load->model('catalog/product');

		$this->field_lengths = $this->kadb->getFieldLengths(DB_PREFIX . 'product', array('model', 'sku', 'upc', 'ean'));

		$this->import_options    = $this->load->kamodel('extension/ka_product_import/import/options');
		$this->import_categories = $this->load->kamodel('extension/ka_product_import/import/categories');
	}

	public function setImport($import) {
		$this->import = $import;
		$this->import_options->setImport($import);
		$this->import_categories->setImport($import);
	}

	public function setParams(&$params) {
		$this->params = &$params;
	}

    /**
     * Copy 下方 getKeyFields()
     */
    public function getKeyFieldsShopee() {

        $key_fields = ['et_title_parent_sku'];

        return $key_fields;
    }

	public function getKeyFields() {

		$key_fields = $this->config->get('ka_product_import_key_fields');
		if (!is_array($key_fields) || empty($key_fields)) {
			$key_fields = array('model');
		}
		
		$key_fields = array_combine($key_fields, $key_fields);

		return $key_fields;
	}

	protected function getLanguageValues() {

		static $lang_values = array();

		if (!empty($lang_values)) {
			return $lang_values;
		}

		// prepare a language array for fields where multi-language value is used
		//
		$this->load->model('localisation/language');
		$languages = $this->model_localisation_language->getLanguages();

		foreach ($languages as $lang) {

			$code_parts = explode('-', $lang['code']);
			$code = strtolower($code_parts[0]);

			$v = array(
				'language_id' => $lang['language_id'],
				'code'        => $code,
				'image'       => KaGlobal::getLanguageImage($lang),
			);
			if ($this->config->get('config_language_id') == $lang['language_id']) {
				$v['is_default'] = true;
			}
			
			$lang_values[$lang['language_id']] = $v;
		}

		return $lang_values;
	}

    /**
     * 修改 getFieldSets
     */
    public function getShopeeFieldSets($params) {
        $lang_values = $this->getLanguageValues();

        // define product fields
        //
        $fields = array(
            'et_title_parent_sku' => array(
                'field' => 'et_title_parent_sku',
                'required' => false,
                'copy'  => true,
                'name'  => $this->language->get('Model'),
//				'descr' => $this->language->get('A unique product code requi...'),
                'descr' => $this->language->get('請選 et_title_parent_sku'),
                'section' => $this->language->get('Product Identifiers'),
            ),
            'master_model' => array(
                'field' => 'master_model',
                'name'  => $this->language->get('Master Model'),
            ),
            'sku' => array(
                'field' => 'sku',
                'copy'  => true,
                'name'  => $this->language->get('SKU'),
                'descr' => ''
            ),
            'upc' => array(
                'field' => 'upc',
                'copy'  => true,
                'name'  => $this->language->get('UPC'),
                'descr' => $this->language->get('Universal Product Code'),
            ),
            'ean' => array(
                'field' => 'ean',
                'copy'  => true,
                'name'  => $this->language->get('EAN'),
                'descr' => $this->language->get('European Article Number'),
            ),
            'jan' => array(
                'field' => 'jan',
                'copy'  => true,
                'name'  => $this->language->get('JAN'),
                'descr' => $this->language->get('Japanese Article Number'),
            ),
            'isbn' => array(
                'field' => 'isbn',
                'copy'  => true,
                'name'  => $this->language->get('ISBN'),
                'descr' => $this->language->get('International Standard Book...'),
            ),
            'mpn' => array(
                'field' => 'mpn',
                'copy'  => true,
                'name'  => $this->language->get('MPN'),
                'descr' => $this->language->get('Manufacturer Part Number'),
            ),
            'et_title_product_name' => array(
                'field' => 'et_title_product_name',
                'name'  => $this->language->get('Name'),
                'is_product_description' => true,
//				'descr' => $this->language->get('Product name'),
                'descr' => $this->language->get('請選 et_title_product_name'),
                'section'  => 'Main Properties',
                'values' => $lang_values,
            ),
            'et_title_product_description' => array(
                'field'    => 'et_title_product_description',
                'is_product_description' => true,
                'name'     => $this->language->get('Description'),
//				'descr'    => $this->language->get('Product description'),
                'descr' => $this->language->get('請選 et_title_product_description'),
                'values'   => $lang_values,
            ),
            'category_id' => array(
                'field'    => 'category_id',
                'name'     => $this->language->get('Category ID'),
                'descr'    => $this->language->get("If this field is specified ..."),
            ),
            'category' => array(
                'field'    => 'category',
                'name'     => $this->language->get('Category Name'),
                'descr'    => str_replace("%cat_separator%", $params['cat_separator'], $this->language->get('Full category path. Example...')),
                'tip'      => $this->language->get('This product import extensi...'),
            ),
            'sub-category' => array(
                'field'    => 'sub-category',
                'name'     => $this->language->get('Sub-Category Name'),
                'descr'    => $this->language->get('This is another way to defi...'),
                'tip'      => '',
            ),
            'subsbucategory' => array(
                'field'    => 'sub-sub-category',
                'name'     => $this->language->get('Sub-Sub-Category Name'),
                'descr'    => '',
                'tip'      => '',
            ),
            'image' => array(
                'field' => 'image',
                'name'  => $this->language->get('Main Product Image'),
                'descr' => $this->language->get("A relative path to the imag..."),
                'tip'   => $this->language->get('If you have a column combin...'),
            ),
            'additional_image' => array(
                'field' => 'additional_image',
                'can_be_cloned' => true,
                'name'  => $this->language->get('Additional Product Image'),
                'descr' => $this->language->get("Relative paths to image fil..."),
                'tip'   => str_replace("%settings_url%", $this->url->linka('extension/ka_extensions/csv_product_import', 'user_token=' . $this->session->data['user_token'], true), $this->language->get('Multiple additional images ...'))
            ),
            'manufacturer' => array(
                'field' => 'manufacturer',
                'name'  => $this->language->get('Manufacturer'),
                'descr' => $this->language->get('Manufacturer name')
            ),
            'et_title_variation_price' => array(
                'field' => 'et_title_variation_price',
                'name'  => $this->language->get('Price'),
//				'descr' => str_replace('%currency', $this->config->get('config_currency'), $this->language->get('Regular product price in pr...')),
                'descr'  => $this->language->get('請選 et_title_variation_price'),
                'section' => $this->language->get('Price & Inventory'),
            ),
            'tax_class' => array(
                'field' => 'tax_class',
                'name'  => $this->language->get('Tax class'),
                'has_default' => true,
                'descr' => str_replace("%taxes_url%", $this->url->linka('localisation/tax_class'), $this->language->get('Existing tax class name fro...'))
            ),
            'et_title_variation_stock' => array(
                'field' => 'et_title_variation_stock',
                'copy'  => true,
                'name'  => $this->language->get('Quantity'),
//				'descr' => ''
                'descr'  => $this->language->get('請選 et_title_variation_stock'),
            ),
            'minimum' => array(
                'field' => 'minimum',
                'copy'  => true,
                'name'  => $this->language->get('Minimum Quantity'),
                'descr' => ''
            ),
            'subtract' => array(
                'field' => 'subtract',
                'copy'  => true,
                'name'  => $this->language->get('Subtract Stock'),
                'descr' => $this->language->get("1 - Yes, 0 - No.")
            ),
            'stock_status' => array(
                'field' => 'stock_status',
                'name'  => $this->language->get('Out of Stock Status'),
                'descr' => str_replace("%stock_statuses_url%", $this->url->linka('localisation/stock_status'), $this->language->get('Existing stock status name ...'))
            ),
            'status' => array(
                'field' => 'status',
                'name'  => $this->language->get('Status'),
                'descr' => $this->language->get("Status Enabled can be def..."),
                'has_default'  => true,
            ),
            'date_available' => array(
                'field' => 'date_available',
                'name'  => $this->language->get('Date Available'),
                'descr' => $this->language->get('Format YYYY-MM-DD Example...')
            ),

            'shipping' => array(
                'field' => 'shipping',
                'copy'  => true,
                'name'  => $this->language->get('Requires Shipping'),
                'descr' => $this->language->get('1 - Yes, 0 - No.'),
                'section' => $this->language->get('Shipping'),
            ),
            'length' => array(
                'field' => 'length',
                'name'  => $this->language->get('Length'),
                'descr' => $this->language->get('Length class units declare...')
            ),
            'width' => array(
                'field' => 'width',
                'name'  => $this->language->get('Width'),
                'descr' => $this->language->get('Length class units declare...')
            ),
            'height' => array(
                'field' => 'height',
                'name'  => $this->language->get('Height'),
                'descr' => $this->language->get('Length class units declare...')
            ),
            'length_class' => array(
                'field' => 'length_class',
                'name'  => $this->language->get('Length Class'),
                'descr' => ''
            ),

            'weight' => array(
                'field' => 'weight',
                'name'  => $this->language->get('Weight'),
                'descr' => $this->language->get('Weight class units declare...'),
            ),
            'weight_class' => array(
                'field' => 'weight_class',
                'name'  => $this->language->get('Weight Class'),
                'descr' => '',
            ),

            'meta_keyword' => array(
                'field' => 'meta_keyword',
                'name'  => $this->language->get('Meta Tag Keywords'),
                'is_product_description' => true,
                'descr' => '',
                'section' => $this->language->get('SEO and Search'),
                'values' => $lang_values,
            ),
            'meta_title' => array(
                'field' => 'meta_title',
                'name'  => $this->language->get('Meta Title'),
                'is_product_description' => true,
                'values' => $lang_values,
                'descr' => ''
            ),
            'meta_description' => array(
                'field' => 'meta_description',
                'name'  => $this->language->get('Meta Tag Description'),
                'is_product_description' => true,
                'values' => $lang_values,
                'descr' => ''
            ),
            'sort_order' => array(
                'field' => 'sort_order',
                'copy'  => true,
                'name'  => $this->language->get('Sort Order'),
                'descr' => ''
            ),
            'seo_keyword' => array(
                'field' => 'seo_keyword',
                'name'  => $this->language->get('SEO Keyword'),
                'values' => $lang_values,
                'descr' => $this->language->get('SEO friendly URL for the pr...')
            ),
            'tag' => array(
                'field' => 'tag',
                'name'  => $this->language->get('Product Tags'),
                'is_product_description' => true,
                'values' => $lang_values,
                'descr' => $this->language->get('List of product tags separa...')
            ),
            'related_product' => array(
                'field' => 'related_product',
                'name'  => $this->language->get('Related Product'),
                'descr' => $this->language->get('model identifier of the rel...'),
            ),
            'downloads' => array(
                'field' => 'downloads',
                'name'  => $this->language->get('Downloads'),
                'descr' => $this->language->get('Downloadable file(s)'),
                'section' => $this->language->get('Miscellaneous'),
            ),
            'location' => array(
                'field' => 'location',
                'copy'  => true,
                'name'  => $this->language->get('Location'),
                'descr' => $this->language->get('This field is not used in f...'),
            ),
            'layout'    => array(
                'field' => 'layout',
                'name'  => $this->language->get('Layout'),
                'descr' => $this->language->get('Product layout')
            ),
            'points' => array(
                'field' => 'points',
                'copy'  => true,
                'name'  => $this->language->get('Points Required'),
                'descr' => $this->language->get('Number of reward points req...')
            ),
        );

        $enable_delete_flag = true;
        if ($enable_delete_flag) {
            $fields[] = array(
                'field' => 'delete_product_flag',
                'name'  => $this->language->get('"Delete Product" Flag'),
                'descr' => $this->language->get('Any non-empty value will be...'),
            );
            $fields[] = array(
                'field' => 'remove_from_store',
                'name'  => $this->language->get('Remove from Store'),
                'descr' => $this->language->get("Set this flag to a non..."),
            );
        }

        if ($this->config->get('ka_product_import_enable_product_id')) {
            $product_id_field = array(
                'field' => 'product_id',
                'name'  => $this->language->get('product_id'),
                'descr' => $this->language->get('You import this value at yo...')
            );
            array_unshift($fields, $product_id_field);
        }

        // add custom fields from 'product' and 'product_description' tables
        //
        $tmp_fields = $this->getCustomProductFields();
        $custom_fields = array();
        if (!empty($tmp_fields)) {
            $custom_fields = array_merge($custom_fields, $tmp_fields);
        }
        $tmp_fields = $this->getCustomProductDescriptionFields();
        if (!empty($tmp_fields)) {
            $custom_fields = array_merge($custom_fields, $tmp_fields);
        }
        if (!empty($custom_fields)) {
            reset($custom_fields);
            $custom_fields[key($custom_fields)]['section'] = $this->language->get('Custom Fields');
            $fields = array_merge($fields, $custom_fields);
        }

        /**
         * 只是最後把主要商品欄位 加上 required
         */
        foreach ($this->getKeyFieldsShopee() as $kfk => $kfv) {
            $fields[$kfv]['required'] = true;
        }

//        foreach ($this->getKeyFields() as $kfk => $kfv) {
//            $fields[$kfv]['required'] = true;
//        }

        $specials = array(
            array(
                'field' => 'customer_group',
                'name'  => $this->language->get('Customer Group'),
                'descr' => ''
            ),
            array(
                'field' => 'priority',
                'name'  => $this->language->get('Prioirity'),
                'descr' => ''
            ),
            array(
                'field'    => 'price',
                'name'     => $this->language->get('Price'),
                'descr'    => ''
            ),
            array(
                'field' => 'date_start',
                'name'  => $this->language->get('Date Start'),
                'descr' => $this->language->get('Format YYYY-MM-DD Example...')
            ),
            array(
                'field' => 'date_end',
                'name'  => $this->language->get('Date End'),
                'descr' => $this->language->get('Format YYYY-MM-DD Example...')
            ),
        );

        $discounts = array(
            array(
                'field' => 'customer_group',
                'name'  => $this->language->get('Customer Group'),
                'descr' => ''
            ),
            'quantity' => array(
                'field' => 'quantity',
                'name'  => $this->language->get('Quantity'),
                'descr' => ''
            ),
            'priority' => array(
                'field' => 'priority',
                'name'  => $this->language->get('Prioirity'),
                'descr' => ''
            ),
            'price' => array(
                'field' => 'price',
                'name'  => $this->language->get('Price'),
                'descr' => ''
            ),
            'date_start' => array(
                'field' => 'date_start',
                'name'  => $this->language->get('Date Start'),
                'descr' => $this->language->get('Format YYYY-MM-DD Example...')
            ),
            'date_end' => array(
                'field' => 'date_end',
                'name'  => $this->language->get('Date End'),
                'descr' => $this->language->get('Format YYYY-MM-DD Example...')
            ),
        );

        $reward_points = array(
            'customer_group' => array(
                'field' => 'customer_group',
                'name'  => $this->language->get('Customer Group'),
                'descr' => '',
            ),
            'points' => array(
                'field'    => 'points',
                'name'     => $this->language->get('Reward Points'),
                'descr'    => '',
            ),
        );

        $ext_options = array(
            'name' => array(
                'field' => 'name',
                'name'  => $this->language->get('Option Name'),
                'descr' => $this->language->get('required')
            ),
            'type' => array(
                'field' => 'type',
                'name'  => $this->language->get('Option Type'),
                'descr' => ''
            ),
            'value' => array(
                'field' => 'value',
                'name'  => $this->language->get('Option Value'),
                'descr' => $this->language->get('required')
            ),
            'required' => array(
                'field' => 'required',
                'name'  => $this->language->get('Option Required'),
                'descr' => ''
            ),
            'image' => array(
                'field' => 'image',
                'name'  => $this->language->get('Option Image'),
                'descr' => ''
            ),
            'sort_order' => array(
                'field' => 'sort_order',
                'name'  => $this->language->get('Value Sort Order'),
                'descr' => $this->language->get('Sort order for option values')
            ),
            'group_sort_order' => array(
                'field' => 'group_sort_order',
                'name'  => $this->language->get('Group Sort Order'),
                'descr' => $this->language->get('Sort order for option group...')
            ),
            'quantity' => array(
                'field' => 'quantity',
                'name'  => $this->language->get('Option Quantity'),
                'descr' => ''
            ),
            'subtract' => array(
                'field' => 'subtract',
                'name'  => $this->language->get('Option Subtract'),
                'descr' => ''
            ),
            'price' => array(
                'field' => 'price',
                'name'  => $this->language->get('Option Price'),
                'descr' => str_replace('%currency%', $this->config->get('config_currency'), $this->language->get('Product price modifier in...')),
            ),
            'points' => array(
                'field' => 'points',
                'name'  => $this->language->get('Option Points'),
                'descr' => ''
            ),
            'weight' => array(
                'field' => 'weight',
                'name'  => $this->language->get('Option Weight'),
                'descr' => ''
            ),
        );

        $reviews = array(
            'author' => array(
                'field' => 'author',
                'name'  => $this->language->get('Author'),
                'descr' => $this->language->get('Text field. Mandatory.')
            ),
            'text' => array(
                'field' => 'text',
                'name'  => $this->language->get('Review Text'),
                'descr' => $this->language->get('Mandatory.')
            ),
            'rating' => array(
                'field' => 'rating',
                'name'  => $this->language->get('Rating'),
                'descr' => $this->language->get('Mandatory. number (1 - 5)')
            ),
            'status' => array(
                'field' => 'status',
                'name'  => $this->language->get('Status'),
                'descr' => $this->language->get('1 - enabled default value...')
            ),
            'date_added' => array(
                'field' => 'date_added',
                'name'  => $this->language->get('Date Added'),
                'descr' => $this->language->get('Recommended formatYYYY-MM-...')
            ),
            'date_modified' => array(
                'field' => 'date_modified',
                'name'  => $this->language->get('Date Modified'),
                'descr' => $this->language->get('Recommended formatYYYY-MM-...')
            ),
        );

        $sets = array(
            'fields'        => $fields,
            'discounts'     => $discounts,
            'specials'      => $specials,
            'reward_points' => $reward_points,
            'ext_options'   => $ext_options,
            'reviews'       => $reviews,
        );

        if (KaGlobal::isKaInstalled('ka_variants')) {
            $sets['variants'] = $this->getVariantColumns();
        }

        $this->load->model('catalog/attribute');
        $sets['attributes'] = $this->model_catalog_attribute->getAttributes();
        if (!empty($sets['attributes'])) {
            foreach ($sets['attributes'] as $atk => $atv) {
                $sets['attributes'][$atk]['values'] = $lang_values;
            }
        } else {
            $sets['attributes'] = array();
        }

        $sets['options'] = $this->getOptions();

        $this->load->model('catalog/filter');
        $sets['filter_groups'] = $this->model_catalog_filter->getGroups();
        if (empty($sets['filter_groups'])) {
            $sets['filter_groups'] = array();
        }

        $sets['subscriptions'] = array(
            'name' => array(
                'field'    => 'name',
                'name'     => $this->language->get('Profile name'),
                'descr'    => '',
            ),
            'customer_group' => array(
                'field' => 'customer_group',
                'name'  => $this->language->get('Customer Group'),
                'descr' => '',
            ),
        );

        return $sets;
    }
		

	public function getFieldSets($params) {

		$lang_values = $this->getLanguageValues();
		
		// define product fields
		//
		$fields = array(
			'model' => array(
				'field' => 'model',
				'required' => false,
				'copy'  => true,
				'name'  => $this->language->get('Model'),
				'descr' => $this->language->get('A unique product code requi...'),
				'section' => $this->language->get('Product Identifiers'),
			),
			'master_model' => array(
				'field' => 'master_model',
				'name'  => $this->language->get('Master Model'),
			),
			'sku' => array(
				'field' => 'sku',
				'copy'  => true,
				'name'  => $this->language->get('SKU'),
				'descr' => ''
			),
			'upc' => array(
				'field' => 'upc',
				'copy'  => true,
				'name'  => $this->language->get('UPC'),
				'descr' => $this->language->get('Universal Product Code'),
			),			
			'ean' => array(
				'field' => 'ean',
				'copy'  => true,
				'name'  => $this->language->get('EAN'),
				'descr' => $this->language->get('European Article Number'),
			),
			'jan' => array(
				'field' => 'jan',
				'copy'  => true,
				'name'  => $this->language->get('JAN'),
				'descr' => $this->language->get('Japanese Article Number'),
			),
			'isbn' => array(
				'field' => 'isbn',
				'copy'  => true,
				'name'  => $this->language->get('ISBN'),
				'descr' => $this->language->get('International Standard Book...'),
			),
			'mpn' => array(
				'field' => 'mpn',
				'copy'  => true,
				'name'  => $this->language->get('MPN'),
				'descr' => $this->language->get('Manufacturer Part Number'),
			),
			'name' => array(
				'field' => 'name',
				'name'  => $this->language->get('Name'),
				'is_product_description' => true,
				'descr' => $this->language->get('Product name'),
				'section'  => 'Main Properties',
				'values' => $lang_values,
			),
			'description' => array(
				'field'    => 'description',
				'is_product_description' => true,
				'name'     => $this->language->get('Description'),
				'descr'    => $this->language->get('Product description'),
				'values'   => $lang_values,
			),
			'category_id' => array(
				'field'    => 'category_id',
				'name'     => $this->language->get('Category ID'),
				'descr'    => $this->language->get("If this field is specified ..."),
			),
			'category' => array(
				'field'    => 'category',
				'name'     => $this->language->get('Category Name'),
				'descr'    => str_replace("%cat_separator%", $params['cat_separator'], $this->language->get('Full category path. Example...')),
				'tip'      => $this->language->get('This product import extensi...'),
			),
			'sub-category' => array(
				'field'    => 'sub-category',
				'name'     => $this->language->get('Sub-Category Name'),
				'descr'    => $this->language->get('This is another way to defi...'),
				'tip'      => '',
			),
			'subsbucategory' => array(
				'field'    => 'sub-sub-category',
				'name'     => $this->language->get('Sub-Sub-Category Name'),
				'descr'    => '',
				'tip'      => '',
			),			
			'image' => array(
				'field' => 'image',
				'name'  => $this->language->get('Main Product Image'),
				'descr' => $this->language->get("A relative path to the imag..."),
				'tip'   => $this->language->get('If you have a column combin...'),
			),
			'additional_image' => array(
				'field' => 'additional_image',
				'can_be_cloned' => true,
				'name'  => $this->language->get('Additional Product Image'),
				'descr' => $this->language->get("Relative paths to image fil..."),
				'tip'   => str_replace("%settings_url%", $this->url->linka('extension/ka_extensions/csv_product_import', 'user_token=' . $this->session->data['user_token'], true), $this->language->get('Multiple additional images ...'))
			),
			'manufacturer' => array(				
				'field' => 'manufacturer',
				'name'  => $this->language->get('Manufacturer'),
				'descr' => $this->language->get('Manufacturer name')
			),			
			'price' => array(
				'field' => 'price',
				'name'  => $this->language->get('Price'),
				'descr' => str_replace('%currency', $this->config->get('config_currency'), $this->language->get('Regular product price in pr...')),
				'section' => $this->language->get('Price & Inventory'),
			),
			'tax_class' => array(
				'field' => 'tax_class',
				'name'  => $this->language->get('Tax class'),
				'has_default' => true,
				'descr' => str_replace("%taxes_url%", $this->url->linka('localisation/tax_class'), $this->language->get('Existing tax class name fro...'))
			),
			'quantity' => array(
				'field' => 'quantity',
				'copy'  => true,
				'name'  => $this->language->get('Quantity'),
				'descr' => ''
			),			
			'minimum' => array(
				'field' => 'minimum',
				'copy'  => true,
				'name'  => $this->language->get('Minimum Quantity'),
				'descr' => ''
			),
			'subtract' => array(
				'field' => 'subtract',
				'copy'  => true,
				'name'  => $this->language->get('Subtract Stock'),
				'descr' => $this->language->get("1 - Yes, 0 - No.")
			),
			'stock_status' => array(
				'field' => 'stock_status',
				'name'  => $this->language->get('Out of Stock Status'),
				'descr' => str_replace("%stock_statuses_url%", $this->url->linka('localisation/stock_status'), $this->language->get('Existing stock status name ...'))
			),
			'status' => array(
				'field' => 'status',
				'name'  => $this->language->get('Status'),
				'descr' => $this->language->get("Status Enabled can be def..."),
				'has_default'  => true,
			),
			'date_available' => array(
				'field' => 'date_available',
				'name'  => $this->language->get('Date Available'),
				'descr' => $this->language->get('Format YYYY-MM-DD Example...')
			),

			'shipping' => array(
				'field' => 'shipping',
				'copy'  => true,
				'name'  => $this->language->get('Requires Shipping'),
				'descr' => $this->language->get('1 - Yes, 0 - No.'),
				'section' => $this->language->get('Shipping'),
			),
			'length' => array(
				'field' => 'length',
				'name'  => $this->language->get('Length'),
				'descr' => $this->language->get('Length class units declare...')
			),
			'width' => array(
				'field' => 'width',
				'name'  => $this->language->get('Width'),
				'descr' => $this->language->get('Length class units declare...')
			),
			'height' => array(
				'field' => 'height',
				'name'  => $this->language->get('Height'),
				'descr' => $this->language->get('Length class units declare...')
			),
			'length_class' => array(
				'field' => 'length_class',
				'name'  => $this->language->get('Length Class'),
				'descr' => ''
			),
			
			'weight' => array(
				'field' => 'weight',
				'name'  => $this->language->get('Weight'),
				'descr' => $this->language->get('Weight class units declare...'),
			),
			'weight_class' => array(
				'field' => 'weight_class',
				'name'  => $this->language->get('Weight Class'),
				'descr' => '',
			),
			
			'meta_keyword' => array(
				'field' => 'meta_keyword',
				'name'  => $this->language->get('Meta Tag Keywords'),
				'is_product_description' => true,
				'descr' => '',
				'section' => $this->language->get('SEO and Search'),
				'values' => $lang_values,
			),
			'meta_title' => array(
				'field' => 'meta_title',
				'name'  => $this->language->get('Meta Title'),
				'is_product_description' => true,
				'values' => $lang_values,
				'descr' => ''
			),
			'meta_description' => array(
				'field' => 'meta_description',
				'name'  => $this->language->get('Meta Tag Description'),
				'is_product_description' => true,
				'values' => $lang_values,
				'descr' => ''
			),
			'sort_order' => array(
				'field' => 'sort_order',
				'copy'  => true,
				'name'  => $this->language->get('Sort Order'),
				'descr' => ''
			),
			'seo_keyword' => array(
				'field' => 'seo_keyword',
				'name'  => $this->language->get('SEO Keyword'),
				'values' => $lang_values,
				'descr' => $this->language->get('SEO friendly URL for the pr...')
			),
			'tag' => array(
				'field' => 'tag',
				'name'  => $this->language->get('Product Tags'),
				'is_product_description' => true,
				'values' => $lang_values,
				'descr' => $this->language->get('List of product tags separa...')
			),
			'related_product' => array(
				'field' => 'related_product',
				'name'  => $this->language->get('Related Product'),
				'descr' => $this->language->get('model identifier of the rel...'),
			),
			'downloads' => array(
				'field' => 'downloads',
				'name'  => $this->language->get('Downloads'),
				'descr' => $this->language->get('Downloadable file(s)'),
				'section' => $this->language->get('Miscellaneous'),
			),
			'location' => array(
				'field' => 'location',
				'copy'  => true,
				'name'  => $this->language->get('Location'),
				'descr' => $this->language->get('This field is not used in f...'),
			),
			'layout'    => array(
				'field' => 'layout',
				'name'  => $this->language->get('Layout'),
				'descr' => $this->language->get('Product layout')
			),
			'points' => array(
				'field' => 'points',
				'copy'  => true,
				'name'  => $this->language->get('Points Required'),
				'descr' => $this->language->get('Number of reward points req...')
			),
		);
		
		$enable_delete_flag = true;
		if ($enable_delete_flag) {
			$fields[] = array(
				'field' => 'delete_product_flag',
				'name'  => $this->language->get('"Delete Product" Flag'),
				'descr' => $this->language->get('Any non-empty value will be...'),
			);
			$fields[] = array(
				'field' => 'remove_from_store',
				'name'  => $this->language->get('Remove from Store'),
				'descr' => $this->language->get("Set this flag to a non..."),
			);
		}
		
		if ($this->config->get('ka_product_import_enable_product_id')) {
			$product_id_field = array(
				'field' => 'product_id',
				'name'  => $this->language->get('product_id'),
				'descr' => $this->language->get('You import this value at yo...')
			);
			array_unshift($fields, $product_id_field);
		}

		// add custom fields from 'product' and 'product_description' tables
		//
		$tmp_fields = $this->getCustomProductFields();
		$custom_fields = array();
		if (!empty($tmp_fields)) {
			$custom_fields = array_merge($custom_fields, $tmp_fields);
		}		
		$tmp_fields = $this->getCustomProductDescriptionFields();
		if (!empty($tmp_fields)) {
			$custom_fields = array_merge($custom_fields, $tmp_fields);
		}
		if (!empty($custom_fields)) {
			reset($custom_fields);
			$custom_fields[key($custom_fields)]['section'] = $this->language->get('Custom Fields');
			$fields = array_merge($fields, $custom_fields);
		}
		
		foreach ($this->getKeyFields() as $kfk => $kfv) {
			$fields[$kfv]['required'] = true;
		}
		
		$specials = array(
			array(
				'field' => 'customer_group',
				'name'  => $this->language->get('Customer Group'),
				'descr' => ''
			),
			array(
				'field' => 'priority',
				'name'  => $this->language->get('Prioirity'),
				'descr' => ''
			),
			array(
				'field'    => 'price',
				'name'     => $this->language->get('Price'),
				'descr'    => ''
			),
			array(
				'field' => 'date_start',
				'name'  => $this->language->get('Date Start'),
				'descr' => $this->language->get('Format YYYY-MM-DD Example...')
			),
			array(
				'field' => 'date_end',
				'name'  => $this->language->get('Date End'),
				'descr' => $this->language->get('Format YYYY-MM-DD Example...')
			),
		);			

		$discounts = array(
			array(
				'field' => 'customer_group',
				'name'  => $this->language->get('Customer Group'),
				'descr' => ''
			),
			'quantity' => array(
				'field' => 'quantity',
				'name'  => $this->language->get('Quantity'),
				'descr' => ''
			),
			'priority' => array(
				'field' => 'priority',
				'name'  => $this->language->get('Prioirity'),
				'descr' => ''
			),
			'price' => array(
				'field' => 'price',
				'name'  => $this->language->get('Price'),
				'descr' => ''
			),
			'date_start' => array(
				'field' => 'date_start',
				'name'  => $this->language->get('Date Start'),
				'descr' => $this->language->get('Format YYYY-MM-DD Example...')
			),
			'date_end' => array(
				'field' => 'date_end',
				'name'  => $this->language->get('Date End'),
				'descr' => $this->language->get('Format YYYY-MM-DD Example...')
			),
		);
		
		$reward_points = array(
			'customer_group' => array(
				'field' => 'customer_group',
				'name'  => $this->language->get('Customer Group'),
				'descr' => '',
			),
			'points' => array(
				'field'    => 'points',
				'name'     => $this->language->get('Reward Points'),
				'descr'    => '',
			),
		);

		$ext_options = array(
			'name' => array(
				'field' => 'name',
				'name'  => $this->language->get('Option Name'),
				'descr' => $this->language->get('required')
			),
			'type' => array(
				'field' => 'type',
				'name'  => $this->language->get('Option Type'),
				'descr' => ''
			),
			'value' => array(
				'field' => 'value',
				'name'  => $this->language->get('Option Value'),
				'descr' => $this->language->get('required')
			),
			'required' => array(
				'field' => 'required',
				'name'  => $this->language->get('Option Required'),
				'descr' => ''
			),
			'image' => array(
				'field' => 'image',
				'name'  => $this->language->get('Option Image'),
				'descr' => ''
			),
			'sort_order' => array(
				'field' => 'sort_order',
				'name'  => $this->language->get('Value Sort Order'),
				'descr' => $this->language->get('Sort order for option values')
			),
			'group_sort_order' => array(
				'field' => 'group_sort_order',
				'name'  => $this->language->get('Group Sort Order'),
				'descr' => $this->language->get('Sort order for option group...')
			),
			'quantity' => array(
				'field' => 'quantity',
				'name'  => $this->language->get('Option Quantity'),
				'descr' => ''
			),
			'subtract' => array(
				'field' => 'subtract',
				'name'  => $this->language->get('Option Subtract'),
				'descr' => ''
			),
			'price' => array(
				'field' => 'price',
				'name'  => $this->language->get('Option Price'),
				'descr' => str_replace('%currency%', $this->config->get('config_currency'), $this->language->get('Product price modifier in...')),
			),
			'points' => array(
				'field' => 'points',
				'name'  => $this->language->get('Option Points'),
				'descr' => ''
			),
			'weight' => array(
				'field' => 'weight',
				'name'  => $this->language->get('Option Weight'),
				'descr' => ''
			),
		);

		$reviews = array(
			'author' => array(
				'field' => 'author',
				'name'  => $this->language->get('Author'),
				'descr' => $this->language->get('Text field. Mandatory.')
			),
			'text' => array(
				'field' => 'text',
				'name'  => $this->language->get('Review Text'),
				'descr' => $this->language->get('Mandatory.')
			),
			'rating' => array(
				'field' => 'rating',
				'name'  => $this->language->get('Rating'),
				'descr' => $this->language->get('Mandatory. number (1 - 5)')
			),
			'status' => array(
				'field' => 'status',
				'name'  => $this->language->get('Status'),
				'descr' => $this->language->get('1 - enabled default value...')
			),
			'date_added' => array(
				'field' => 'date_added',
				'name'  => $this->language->get('Date Added'),
				'descr' => $this->language->get('Recommended formatYYYY-MM-...')
			),
			'date_modified' => array(
				'field' => 'date_modified',
				'name'  => $this->language->get('Date Modified'),
				'descr' => $this->language->get('Recommended formatYYYY-MM-...')
			),
		);

		$sets = array(
			'fields'        => $fields,
			'discounts'     => $discounts,
			'specials'      => $specials,
			'reward_points' => $reward_points,
			'ext_options'   => $ext_options,
			'reviews'       => $reviews,
		);

		if (KaGlobal::isKaInstalled('ka_variants')) {
			$sets['variants'] = $this->getVariantColumns();
		}
		
		$this->load->model('catalog/attribute');
		$sets['attributes'] = $this->model_catalog_attribute->getAttributes();
		if (!empty($sets['attributes'])) {
			foreach ($sets['attributes'] as $atk => $atv) {
				$sets['attributes'][$atk]['values'] = $lang_values;
			}
		} else {
			$sets['attributes'] = array();
		}
		
		$sets['options'] = $this->getOptions();
		
		$this->load->model('catalog/filter');
		$sets['filter_groups'] = $this->model_catalog_filter->getGroups();
		if (empty($sets['filter_groups'])) {
			$sets['filter_groups'] = array();
		}
		
		$sets['subscriptions'] = array(
			'name' => array(
				'field'    => 'name',
				'name'     => $this->language->get('Profile name'),
				'descr'    => '',
			),
			'customer_group' => array(
				'field' => 'customer_group',
				'name'  => $this->language->get('Customer Group'),
				'descr' => '',
			),
		);
				
		return $sets;
	}


	protected function getCustomProductFields() {
		$standard_fields = array('product_id', 'model', 'sku', 'upc', 'ean', 'jan', 'isbn', 'mpn',
			'location', 'quantity', 'stock_status_id', 'image', 'manufacturer_id', 'shipping',
			'price', 'points', 'tax_class_id', 'date_available', 'weight', 'weight_class_id',
			'length', 'width', 'height', 'length_class_id', 'subtract', 'minimum', 'sort_order', 'skip_import',
			'master_id', 'variant', 'override',
			'status', 'date_added', 'date_modified', 'viewed'
		);

		$qry = $this->db->query('SHOW FIELDS FROM ' . DB_PREFIX . 'product');
		if (empty($qry->rows)) {
			return false;
		}

		$fields = array();
		foreach ($qry->rows as $row) {
			if (in_array($row['Field'], $standard_fields)) {
				continue;
			}

			$field = array(
				'field' => $row['Field'],
				'copy'  => true,
				'name'  => $row['Field'],
				'descr' => sprintf($this->language->get('Custom field. Type: %s'), $row['Type'])
			);

			$fields[] = $field;
		}

		return $fields;
	}


	protected function getCustomProductDescriptionFields() {
		$standard_fields = array('product_id', 'language_id', 'name', 'description', 'tag',
			'meta_title', 'meta_description', 'meta_keyword'
		);

		$qry = $this->db->query('SHOW FIELDS FROM ' . DB_PREFIX . 'product_description');
		if (empty($qry->rows)) {
			return false;
		}

		$lang_values = $this->getLanguageValues();

		$fields = array();
		foreach ($qry->rows as $row) {
			if (in_array($row['Field'], $standard_fields)) {
				continue;
			}

			$field = array(
				'field'  => $row['Field'],
				'name'   => $row['Field'],
				'descr'  => $this->language->get('Custom multi-language field...') . ': ' . $row['Type'],
				'values' => $lang_values,
				'is_product_description' => true,
			);

			$fields[] = $field;
		}

		return $fields;
	}


	protected function getVariantColumns() {

		$columns = array();

		$qry = $this->db->query("SELECT o.*, od.* FROM `" . DB_PREFIX . "option` o
			INNER JOIN " . DB_PREFIX . "option_description od ON o.option_id = od.option_id
			WHERE is_variant = 1 AND language_id = " . (int) $this->config->get('config_language_id') . "
		");


		if (empty($qry->rows)) {
			return $columns;
		}

		foreach ($qry->rows as $option) {
			$columns[$option['option_id']] = array(
				'is_option' => true,
				'field' => $option['option_id'],
				'name'  => $option['name'],
				'descr' => $this->language->get('Variant option value')
			);
		}

		$columns = array_merge($columns, $variant_columns);

		return $columns;
	}

	protected function getOptions() {
		$this->load->model('catalog/option');

		$options = $this->model_catalog_option->getOptions();

		if (empty($options)) {
			return array();
		}

		$tpl_fields = array(
			'value' => array(
				'field' => 'value',
				'name'  => $this->language->get('Value'),
				'descr' => ''
			),
			'quantity' => array(
				'field' => 'quantity',
				'name'  => $this->language->get('Quantity'),
				'descr' => ''
			),
			'price' => array(
				'field' => 'price',
				'name'  => $this->language->get('Price'),
				'descr' => str_replace('%currency%', $this->config->get('config_currency'), $this->language->get('Product price modifier in...')),
			),
			'weight' => array(
				'field' => 'weight',
				'name'  => $this->language->get('Weight'),
				'descr' => $this->language->get('Option weight')
			),
		);

		foreach ($options as $ok => $ov) {
			$options[$ok]['fields'] = $tpl_fields;
		}

		return $options;
	}


	public function getDefaultProduct($product_id) {

		$product = $this->model_catalog_product->getProduct($product_id);
		if (empty($product)) {
			return false;
		}

		//set a tax class name
		//
		if (!empty($product['tax_class_id'])) {
			$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "tax_class	WHERE
				tax_class_id = '$product[tax_class_id]'
			");
			$product['tax_class'] = $query->row['title'];

		} else {
			$product['tax_class'] = '';
		}

		return $product;
	}



	/*
		get product information from the row.

		RETURNS
			array [<product_id>] - on success. Product_id can be empty if it was not found.
			false - on error

	    $lastError is set on error
	*/
	public function getEntityId($data) {

		$return = array(0);

		$this->lastError = '';

		// get product_id. It finds an existing product or creates a new one.

		$where = array();

		if (!empty($data['product_id'])) {

			if (!ctype_digit($data['product_id']) && !is_int($data['product_id'])) {
				$this->lastError = 'product_id value must be a number:' . $data['product_id'];
				return $return;
			}

			$where[] = "product_id = '" . $this->db->escape($data['product_id']) . "'";

		} else {

            /**
             * 只要是 setImportModel 設定的是 shopee
             * 底下3行只是為了掠過最下面的 <if (empty($where))>
             */
            if (isset($data['et_title_parent_sku']) && in_array('et_title_parent_sku', $this->import->key_fields)) {
                $where[] = "model='" . $this->db->escape($data['et_title_parent_sku']) . "'";
            }

			if (isset($data['model']) && in_array('model', $this->import->key_fields)) {
				if (!empty($this->params['field_lengths']['model'])) {
					if (mb_strlen($data['model']) > $this->params['field_lengths']['model']) {
						$this->lastError = 'Model field (' . $data['model'] . ') exceeds the maximum field size(' .
							$this->params['field_lengths']['model'] ."). Product is skipped.";
							return false;
					};
				}
				$where[] = "model='" . $this->db->escape($data['model']) . "'";
			}

			if (isset($data['sku']) && in_array('sku', $this->import->key_fields)) {
				if (!empty($this->params['field_lengths']['sku'])) {
					if (mb_strlen($data['sku']) > $this->params['field_lengths']['sku']) {
						$this->lastError = 'SKU field (' . $data['sku'] . ') exceeds the maximum field size(' .
							$this->params['field_lengths']['sku'] ."). Product is skipped.";
							return false;
					};
				}
				$where[] = "sku='" . $this->db->escape($data['sku']) . "'";
			}

			if (isset($data['upc']) && in_array('upc', $this->import->key_fields)) {
				if (!empty($this->params['field_lengths']['upc'])) {
					if (mb_strlen($data['upc']) > $this->params['field_lengths']['upc']) {
						$this->lastError = 'UPC field (' . $data['upc'] . ') exceeds the maximum field size(' .
							$this->params['field_lengths']['upc'] ."). Product is skipped.";
						return false;
					};
				}
				$where[] = "upc='" . $this->db->escape($data['upc']) . "'";
			}

			if (isset($data['ean']) && in_array('ean', $this->import->key_fields)) {
				if (!empty($this->params['field_lengths']['ean'])) {
					if (mb_strlen($data['ean']) > $this->params['field_lengths']['ean']) {
						$this->lastError = 'EAN field (' . $data['ean'] . ') exceeds the maximum field size(' .
							$this->params['field_lengths']['ean'] ."). Product is skipped.";
						return false;
					};
				}
				$where[] = "ean='" . $this->db->escape($data['ean']) . "'";
			}
		}

		if (empty($where)) {
			$this->lastError = 'key fields are empty';
			return false;
		}

		$sel = $this->db->query("SELECT product_id FROM " . DB_PREFIX . "product AS p 
			WHERE " . implode(" AND ", $where));

		$product_id = (isset($sel->row['product_id'])) ?$sel->row['product_id'] : 0;

		$return[0] = $product_id;

		return $return;
	}


	public function isImportable($product_id) {

		if (empty($product_id)) {
			return true;
		}

		$qry = $this->db->query("SELECT skip_import FROM " . DB_PREFIX . "product
			WHERE
				product_id = '" . (int) $product_id . "'
				AND skip_import = 1"
		);

		if (!empty($qry->row)) {
			return false;
		}

		return true;
	}


	public function createNewEntity($data) {

		// create a new product if possible
		//
		if (!empty($this->params['tpl_product_id'])) {
			if (!empty($data['product_id'])) {
				$this->import->addImportMessage('Product template is not used when product_id is specified in the file');
			} else {
				$product_id = $this->model_catalog_product->copyProductForImport($this->params['tpl_product_id']);
			}
		}

		if (empty($product_id)) {

			if (empty($data['master_model']) && empty($data['name']) && empty($data['et_title_product_name'])) {
				$this->import->addImportMessage("Product name is not specified for a new product. Line is skipped: " . $this->import->stat['lines_processed']);
				return false;
			}

			if (empty($data['product_id'])) {

				$rec = array_intersect_key($data, $this->import->key_fields);
				$rec[] = 'date_modified = NOW()';
				$rec[] = 'date_added = NOW()';

				$product_id = $this->kadb->queryInsert('product', $rec);

			} else {
				$this->db->query("REPLACE INTO " . DB_PREFIX . "product SET date_modified = NOW(), date_added = NOW(), product_id = '" . $this->db->escape($data['product_id']) . "'");
				$product_id = $data['product_id'];
			}

			if (empty($product_id)) {
				$this->import->addImportMessage("Insert operation failed.");
				return false;
			}
		}

		return $product_id;
	}


	public function processImageFields(&$data) {

		if (!empty($data['image']) && !empty($data['additional_image'])) {

			// some image associations might be skipped by the user
			// we prevent an index error by rearranging the image values
			//
			$data['additional_image'] = array_values($data['additional_image']);

			if ($data['additional_image'][0] == $data['image']) {

				$images = $this->import->splitImages($data['additional_image'][0]);

				if (!empty($images)) {
					$data['image'] = $images[0];
					array_shift($images);

					$data['additional_image'][0] = implode($this->params['cfg_image_separator'], $images);
				}
			}
		}
	}


	/*
		Update existing product. The product record should be created earlier for new products.
	*/
	public function updateProduct($row, $data, $org_product, $flags, &$updated) {

		if (empty($data['product_id'])) {
			throw new \Exception("Product_id is empty");
		}

		$not_updated_fields = array('master_id');

		$product_id = $data['product_id'];

		$product = array();

		// copy fields marked with 'copy' property
		foreach ($this->params['matches']['fields'] as $fk => $fv) {
			if (!empty($fv['copy'])) {
				$product[$fv['field']] = $data[$fv['field']];
			}
		}

		if (!empty($data['master_id'])) {
			$product['master_id'] = $data['master_id'];
		}

		// set the product status
		//
		if (isset($data['status']) && strlen($data['status'])) {
			$product['status'] = (in_array($data['status'], $this->import::ANSWER_POSITIVE)) ? 1 : 0;
		}

		// get a manufacturer id
		//
		if (isset($data['manufacturer'])) {
			$sel = $this->db->query("SELECT manufacturer_id FROM " . DB_PREFIX . "manufacturer AS m
				WHERE name='" . $this->db->escape($data['manufacturer']) . "'");

			if (!empty($sel->row['manufacturer_id'])) {
				$manufacturer_id = $sel->row['manufacturer_id'];
			} elseif (!empty($data['manufacturer'])) {
				$rec = array(
					'name' => $data['manufacturer'],
				);
				$manufacturer_id = $this->kadb->queryInsert("manufacturer", $rec);
			} else {
				$manufacturer_id = 0;
			}
			$product['manufacturer_id'] = $manufacturer_id;

			// insert a new manufacturer to the stores
			//
			if (!empty($manufacturer_id)) {
				$this->import->insertToStores('manufacturer', $manufacturer_id, $this->params['store_ids']);
			}
		}

		// get a tax class id
		//
		if (!empty($data['tax_class'])) {
			$sel = $this->db->query("SELECT tax_class_id FROM " . DB_PREFIX . "tax_class AS t
				WHERE title = '" . $this->db->escape($data['tax_class']) . "'");

			if (!empty($sel->row)) {
				$product['tax_class_id'] = $sel->row['tax_class_id'];
			} else {
				$this->import->addImportMessage("Tax class name '$data[tax_class]' not found");
			}
		}

		// Weight. Sample value: 10.000Kg
		//
		$weight_class_id = 0;
		if (!empty($data['weight'])) {
			$pair = $this->import->kaformat->parseWeight($data['weight']);
			if (empty($pair)) {
				$this->import->addImportMessage("Weight value cannot be parsed '" . $data['weight'] . "'");
			} else {
				$product['weight'] = $pair['value'];
				if (!empty($pair['weight_class_id'])) {
					$weight_class_id = $pair['weight_class_id'];
				}
			}
		}

		if (!empty($data['weight_class'])) {
			$class_id = $this->import->kaformat->getWeightClassIdByUnit($data['weight_class']);
			if (empty($class_id)) {
				$this->import->addImportMessage("Weight unit was not found:" . $data['weight_class']);
			} else {
				$weight_class_id = $class_id;
			}
		}

		if (empty($weight_class_id)) {
			if ($flags['is_new']) {
				$product['weight_class_id'] = $this->config->get('config_weight_class_id');
			}
			$not_updated_fields[] = 'weight_class_id';
		} else {
			$product['weight_class_id'] = $weight_class_id;
		}

		// Dimensions
		//
		$length_class_id = 0;

		$length_params = array('length', 'height', 'width');
		foreach ($length_params as $lv) {
			if (!empty($data[$lv])) {
				$pair = $this->import->kaformat->parseLength($data[$lv]);
				if (empty($pair)) {
					$this->import->addImportMessage($lv . " cannot be parsed '" . $data[$lv] . "'");
				} else {
					$product[$lv]    = $pair['value'];
					if (!empty($length_class_id)) {
						$length_class_id = $pair['length_class_id'];
					}
				}
			}
		}

		if (!empty($data['length_class'])) {
			$class_id = $this->import->kaformat->getLengthClassIdByUnit($data['length_class']);
			if (empty($class_id)) {
				$this->import->addImportMessage("Length unit was not found:" . $data['length_class']);
			} else {
				$length_class_id = $class_id;
			}
		}

		if (empty($length_class_id)) {
			if ($flags['is_new']) {
				$product['length_class_id'] = $this->config->get('config_length_class_id');
			}
			$not_updated_fields[] = 'length_class_id';

		} else {
			$product['length_class_id'] = $length_class_id;
		}

		// insert the product to the selected store
		//
	    $this->import->insertToStores('product', $product_id, $this->params['store_ids']);

		$product_descriptions = $this->updateProductDescription($product_id, $data, $updated);

		// stock status
		//
		if (!empty($data['stock_status'])) {
			$qry = $this->db->query("SELECT stock_status_id FROM " . DB_PREFIX . "stock_status
				WHERE '" . $this->db->escape(mb_strtolower($data['stock_status'], 'utf-8')) . "' LIKE LOWER(name)
			");

			if (empty($qry->row)) {
				$this->import->addImportMessage($this->language->get("stock status not found") . " '$data[stock_status]'");
				if ($flags['is_new']) {
					$product['stock_status_id'] = $this->config->get('ka_product_import_default_out_of_stock_status_id');
				}
			} else {
				$product['stock_status_id'] = $qry->row['stock_status_id'];
			}
		} elseif ($flags['is_new']) {
			$product['stock_status_id'] = $this->config->get('ka_product_import_default_out_of_stock_status_id');
			$not_updated_fields[] = 'stock_status_id';
		}

		// update price
		//
		if (isset($data['price']) && strlen($data['price'])) {
			$product['price'] = Format::parsePrice($data['price']);
			if (!empty($this->import->price_multiplier)) {
				$product['price'] = $product['price'] * $this->import->price_multiplier;
			}
		}

		// insert an image
		//
		if (isset($data['image'])) {
			if (!empty($data['image'])) {
				$file = $this->import->getImageFile($data['image']);
				if ($file === false) {
					if (!empty($this->lastError)) {
						$this->import->addImportMessage($this->language->get("main image cannot be saved -") . $this->import->lastError);
					}
				} elseif (!empty($file)) {
					$product['image'] = $file;
				}
			} else {
				$product['image'] = '';
			}
		}

		// insert date available
		//
		if (!empty($data['date_available'])) {
			if (!$this->import->kaformat->formatDate($data['date_available'])) {
				$this->import->addImportMessage($this->language->get("Wrong date format in date ..."));
				if ($flags['is_new']) {
					$product['date_available'] = '2000-01-01';
				}
			} else {
				if ($data['date_available'] != '0000-00-00') {
					$product['date_available'] = $data['date_available'];
				} else {
					$product['date_available'] = '2000-01-01';
				}
			}
		} elseif ($flags['is_new']) {
			$product['date_available'] = '2000-01-01';
			$not_updated_fields[] = 'date_available';
		}

		$updated['fields'] = array_merge($updated['fields'], array_fill_keys(array_diff(array_keys($product), $not_updated_fields), 1));

		$this->kadb->queryUpdate('product', $product, "product_id='$product_id'");

		// insert seo keyword
		//
		if (isset($data['seo_keyword']) || !empty($this->params['opt_generate_seo_keyword'])) {

			foreach ($this->params['store_ids'] as $store_id) {
				$seo_keywords = array();
				if (!empty($data['seo_keyword'])) {
					$seo_keywords = $data['seo_keyword'];
				}

				$this->saveSeoKeywords($store_id, $product_id, $seo_keywords, $product_descriptions);
			}
		}

		// produt layout
		//
		if (isset($data['layout'])) {
			$qry = $this->db->query('SELECT * FROM ' . DB_PREFIX . "layout WHERE
				name = '" . $this->db->escape($data['layout']) . "'"
			);

			if (!empty($qry->row['layout_id'])) {
				$layout_id = $qry->row['layout_id'];
			} else {
				$layout_id = 0;
			}

			foreach ($this->params['store_ids'] as $store_id) {
				$this->db->query("REPLACE INTO " . DB_PREFIX . "product_to_layout SET
					product_id = '" . (int)$product_id . "',
					store_id   = '" . (int)$store_id . "',
					layout_id  = '" . (int)$layout_id . "'"
				);
			}
		}

		return true;
	}


	public function saveDiscounts($row, $data, $product, $flags, &$updated) {

		if (empty($this->params['matches']['discounts'])) {
			return true;
		}

		if ($flags['delete_old']) {
			$this->db->query("DELETE FROM " . DB_PREFIX . "product_discount WHERE product_id = '$product[product_id]'");
			$updated['fields']['product_discount'] = 1;
		}

		$data = array();

		$record_valid = false;
		foreach ($this->params['matches']['discounts'] as $ak => $av) {

			if (!isset($av['column']))
				continue;

			$val = trim($row[$av['column']]);

			if ($av['field'] == 'price') {
				if (strlen($val) > 0) {
					$record_valid = true;
				}
				$val = format::parsePrice($val);

			} elseif (in_array($av['field'], array('date_start', 'date_end'))) {

				if (!$this->import->kaformat->formatDate($val)) {
					if (!empty($val)) {
						$this->import->addImportMessage("Wrong date format in 'discount' record. product_id = $product[product_id]");
					}
					$val = '0000-00-00';
				}

			} elseif ($av['field'] == 'customer_group') {
				$data['customer_group_id'] = $this->import->getCustomerGroupByName($val);
				continue;
			}

			$data[$av['field']] = $val;
		}

		if (!$record_valid) {
			return false;
		}

		if (empty($data['customer_group_id'])) {
			$data['customer_group_id'] = $this->config->get('config_customer_group_id');
		}

		if (!(isset($data['customer_group_id']) && isset($data['quantity']) &&
			isset($data['price']))
		) {
			return false;
		}

		if ($this->config->get('ka_product_import_save_max_date')) {
			if (empty($data['date_end']) || $data['date_end'] == '0000-00-00') {
				$data['date_end'] = '2099-01-01';
			}
		}

		$data['product_id'] = $product['product_id'];

		// key fields: product_id, customer_group, quantity, date_start, date_end
		//
		$where = "product_id = '$data[product_id]'
			AND customer_group_id = '$data[customer_group_id]'
			AND quantity = '$data[quantity]'
		";

		if (!empty($data['date_start'])) {
			$where .= " AND date_start = '$data[date_start]'";
		}
		if (!empty($data['date_end'])) {
			$where .= " AND date_end = '$data[date_end]'";
		}

		$qry = $this->db->query("SELECT product_discount_id FROM " . DB_PREFIX . "product_discount
			WHERE $where
		");

		if (!empty($qry->row)) {
			$this->kadb->queryUpdate('product_discount', $data, "product_discount_id = '" . $qry->row['product_discount_id'] . "'");
			$discount_id = $qry->row['product_discount_id'];
		} else {
			$discount_id = $this->kadb->queryInsert('product_discount', $data);
		}

		$updated['fields']['product_discount'] = 1;

		return true;
	}


	public function saveSpecials($row, $data, $product, $flags, &$updated) {

		if (empty($this->params['matches']['specials'])) {
			return true;
		}

		if ($flags['delete_old']) {
			$this->db->query("DELETE FROM " . DB_PREFIX . "product_special WHERE product_id = '$product[product_id]'");
			unset($updated['fields']['product_discount']);
		}

		$data = array();

		foreach ($this->params['matches']['specials'] as $ak => $av) {
			if (!isset($av['column']))
				continue;

			$val = trim($row[$av['column']]);

			if ($av['field'] == 'price') {
				if (strlen($val) == 0) {
					return false;
				}
				$val = format::parsePrice($val);
				if (!empty($this->import->price_multiplier)) {
					$val = $val * $this->import->price_multiplier;
				}

			} elseif (in_array($av['field'], array('date_start', 'date_end'))) {

				if (!$this->import->kaformat->formatDate($val)) {
					if (!empty($val)) {
						$this->import->addImportMessage("Wrong date format in 'special' record. product_id = $product[product_id]");
					}
					$val = '0000-00-00';
				}

			} elseif ($av['field'] == 'customer_group') {
				if (!empty($val)) {
					$customer_group_id = $this->import->getCustomerGroupByName($val);
					if (empty($customer_group_id)) {
						$this->import->addImportMessage("Customer group not found: $val");
						return false;
					}
				} else {
					$customer_group_id = $this->config->get('config_customer_group_id');
				}
				$data['customer_group_id'] = $customer_group_id;
				continue;
			}

			$data[$av['field']] = $val;
		}

		if (empty($data['customer_group_id']) && empty($data['price'])) {
			return true;
		}

		$data['product_id'] = $product['product_id'];

		if (empty($data['customer_group_id'])) {
			$data['customer_group_id'] = $this->config->get('config_customer_group_id');
		}

		if (!isset($data['priority'])) {
			$data['priority'] = 1;
		}

		if (!(isset($data['customer_group_id']) && isset($data['price']))) {
			return false;
		}

		if ($this->config->get('ka_product_import_save_max_date')) {
			if (empty($data['date_end']) || $data['date_end'] = '0000-00-00') {
				$data['date_end'] = '2099-01-01';
			}
		}

		// key fields: customer_group, date_start, date_end
		//
		$where = " product_id = '$data[product_id]'
			AND customer_group_id = '$data[customer_group_id]'
		";

		if (!empty($data['date_start'])) {
			$where .= " AND date_start = '$data[date_start]'";
		}
		if (!empty($data['date_end'])) {
			$where .= " AND date_end = '$data[date_end]'";
		}

		$qry = $this->db->query("SELECT product_special_id FROM " . DB_PREFIX . "product_special
			WHERE $where
		");

		if (!empty($qry->row)) {
			$this->kadb->queryUpdate('product_special', $data, "product_special_id = '" . $qry->row['product_special_id'] . "'");
			$special_id = $qry->row['product_special_id'];
		} else {
			$special_id = $this->kadb->queryInsert('product_special', $data);
		}

		return true;
	}


	protected function saveRewardPoints($row, $data, $product, $flags, &$updated) {

		if (empty($this->params['matches']['reward_points'])) {
			return true;
		}

		if ($flags['delete_old']) {
			$this->db->query("DELETE FROM " . DB_PREFIX . "product_reward WHERE product_id = '$product[product_id]'");
			unset($updated['fields']['product_reward']);
		}

		$data = array();
		foreach ($this->params['matches']['reward_points'] as $ak => $av) {
			if (!isset($av['column']))
				continue;

			$val = $row[$av['column']];

			if ($av['field'] == 'customer_group') {
				$data['customer_group_id'] = $this->import->getCustomerGroupByName($val);
				continue;
			}

			$data[$av['field']] = $val;
		}

		if (empty($data['points'])) {
			return false;
		}

		$data['product_id'] = $product['product_id'];

		if (empty($data['customer_group_id'])) {
			$data['customer_group_id'] = $this->config->get('config_customer_group_id');
		}

		$this->db->query("DELETE FROM " . DB_PREFIX . "product_reward WHERE
			product_id = '$product[product_id]'
			AND customer_group_id = '$data[customer_group_id]'
		");


		$this->kadb->queryInsert('product_reward', $data);
		$updated['fields']['product_reward'] = 1;

		return true;
	}


	/*
		$data     - array of file data split by columns
		$product  - product data array
	*/
	protected function saveRelatedProducts($row, $data, $product, $flags, &$updated) {

		if (empty($data['related_product'])) {
			return true;
		}

		if ($flags['delete_old']) {
			$this->db->query("DELETE FROM " . DB_PREFIX . "product_related WHERE product_id = '$product[product_id]'");
			unset($updated['fields']['product_related']);
		}

		// get the array of related models
		//
		$related = array();
		$sep     = $this->config->get('ka_product_import_related_products_separator');
		if (!empty($sep)) {
			$related = explode($sep, $data['related_product']);
		} else {
			$related = array($data['related_product']);
		}

		foreach ($related as $rv) {
			if (empty($rv)) {
				continue;
			}

			$qry = $this->db->query("SELECT product_id FROM " . DB_PREFIX . "product
				WHERE model = '" . $this->db->escape($rv) . "'");

			if (empty($qry->row)) {
				continue;
			}

			// link to all products with found model regardless of their number
			//
			foreach ($qry->rows as $row) {

				$rec = array(
					'product_id' => $product['product_id'],
					'related_id' => $row['product_id']
				);
				$this->kadb->queryInsert('product_related', $rec, true);

				$rec = array(
					'product_id' => $row['product_id'],
					'related_id' => $product['product_id']
				);

				$updated['fields']['product_related'] = 1;
				$this->kadb->queryInsert('product_related', $rec, true);
			}
		}

		return true;
	}



	protected function saveDownloads($row, $data, $product, $flags, $updated) {

		if (empty($data['downloads'])) {
			return true;
		}

		if ($flags['delete_old']) {
			$this->db->query("DELETE FROM " . DB_PREFIX . "product_to_download WHERE product_id = '$product[product_id]'");
			unset($updated['fields']['product_download']);
		}

		$downloads = array();
		$sep = $this->config->get('ka_product_import_general_separator');
		if (!empty($sep)) {
			$downloads = explode($sep, $data['downloads']);
		} else {
			$downloads = array($data['downloads']);
		}

		foreach ($downloads as $dv) {
			if (empty($dv)) {
				continue;
			}

			// 1) detect file parts from the file name
			//
			$ext = $dest_filename = $mask = '';
			$info = pathinfo($dv);

			if ($this->params['file_name_postfix'] == 'generate') {
				$mask = $dv;
				$ext  = md5(mt_rand());

			} elseif ($this->params['file_name_postfix'] == 'detect') {
				$mask = $info['filename'];
				$ext  = $info['extension'];

			} else {
				$mask = $dv;
			}

			$filename = $mask . (!empty($ext) ? '.'.$ext : '');

			// 2) find this file in downloads
			//
			$qry = $this->db->query('SELECT * FROM ' . DB_PREFIX . "download WHERE
				mask = '" . $this->db->escape($mask) . "'"
			);

			if (!empty($qry->row)) {
				$download_id = $qry->row['download_id'];
			} else {

				$data = array(
					'src_file'  => $dv,
					'filename'  => $filename,
					'mask'      => $mask,
				);
				$download_id = $this->addDownload($data);
			}

			// 3) connect product and download record
			//
			if (!empty($download_id)) {
				$rec = array(
					'product_id'  => $product['product_id'],
					'download_id' => $download_id
				);

				$updated['fields']['product_download'] = 1;
				$this->kadb->queryInsert('product_to_download', $rec, true);
			}
		}

		return true;
	}


	protected function addDownload($data) {

		$src_file = $this->params['download_source_dir'] . $this->import->kaformat->strip($data['src_file'], array("\\", "/"));

		// 1) copy the file to downloads directory
		//
		if (!file_exists($src_file)) {
			$this->import->addImportMessage("File does not exist: $src_file");
			return false;
		}

		$dest_file = DIR_DOWNLOAD . $data['filename'];
		if (!copy($src_file, $dest_file)) {
			$this->import->addImportMessage("Cannot copy file from $src_file to $dest_file.");
			return false;
		}

		// 2) add a new record to the database
		//
      	$this->db->query("INSERT INTO " . DB_PREFIX . "download SET
      		filename   = '" . $this->db->escape($data['filename']) . "',
      		mask       = '" . $this->db->escape($data['mask']) . "',
      		date_added = NOW()"
      	);

      	$download_id = $this->db->getLastId();

		foreach ($this->import->languages as $lang) {
	       	$this->db->query("INSERT INTO " . DB_PREFIX . "download_description SET
	       		download_id = '" . (int)$download_id . "',
	       		language_id = '" . (int)$lang['language_id'] . "',
	       		name = '" . $this->db->escape($data['mask']) . "'"
	       	);
		}
		return $download_id;
	}



	protected function disableNotImportedProducts() {

		$where = '';

		if (!empty($this->params['key_field_prefix'])) {
			foreach ($this->import->key_fields as $kf) {
				$where .= " AND p.`$kf` LIKE '" . $this->db->escape($this->params['key_field_prefix']) . "%' ";
			}
		}

		$this->db->query("UPDATE " . DB_PREFIX . "product p INNER JOIN
			" . DB_PREFIX. "product_to_store pts ON p.product_id = pts.product_id
			SET p.status='0'
			WHERE
				p.product_id NOT IN (
					SELECT product_id FROM " . DB_PREFIX . "ka_product_import
						WHERE token = '" . $this->session->data['ka_token'] . "'
				)
				AND p.skip_import = 0
				AND pts.store_id IN ('" . implode("','", $this->params['store_ids']) . "')
				$where
		");


		$qry = $this->db->query("SELECT ROW_COUNT() as affected");
		$this->import->stat['products_disabled'] += $qry->row['affected'];
	}


	protected function generateProductUrl($id, $name, $store_id = 0, $language_id = 0) {

		if (empty($name) || empty($id)) {
			$this->kalog->write(__METHOD__ . ": empty parameters");
			return false;
		}

		$url = \extension\ka_extensions\KaUrlify::filter($name);

		if (empty($url)) {
			$this->kalog->write(__METHOD__ . ": filter returned an empty string");
			return false;
		}

		$qry = $this->db->query("SELECT seo_url_id FROM " . DB_PREFIX . "seo_url WHERE
			store_id = '$store_id'
			AND keyword = '" . $this->db->escape($url) . "'
		");

		if (empty($qry->row)) {
			return $url;
		}

		if (count($this->import->languages) > 1 && !empty($language_id)) {
			$url = $url . "-p-" . $id . '-' . $language_id;
		} else {
			$url = $url . "-p-" . $id;
		}
		$qry = $this->db->query("SELECT seo_url_id FROM " . DB_PREFIX . "seo_url WHERE
			store_id = '$store_id'
			AND keyword = '" . $this->db->escape($url) . "'
		");

		if (empty($qry->row)) {
			return $url;
		}

		$this->kalog->write(__METHOD__ . ": cannot find a suitable string");

		return false;
	}


	protected function getProductDescriptionFields() {

		static $fields = array();

		if (!empty($fields)) {
			return $fields;
		}

		foreach ($this->params['matches']['fields'] as $field) {
			if (!empty($field['is_product_description'])) {
				$fields[] = $field;
			}
		}

		return $fields;
	}

	/*
		returns
			$product_descriptions - array
	*/
	protected function updateProductDescription($product_id, $data, &$updated) {

		$fields = $this->getProductDescriptionFields();

		$descriptions = array();
		foreach ($this->import->languages as $lang) {

			// collect language-specific data for the product_description table
			//
			$rec = array(
				'product_id'  => $product_id,
				'language_id' => $lang['language_id'],
			);

			foreach ($fields as $field) {

				if (!isset($data[$field['field']][$lang['language_id']])) {
					continue;
				}

				$val = $data[$field['field']][$lang['language_id']];

				if (!empty($this->params['import_as_plain_text'])
				  && in_array($field['field'], $this->plain_text_fields)
				) {
					$val = nl2br($val);
					$val = $this->request->clean($val);
				}

				$rec[$field['field']] = trim($val);
			}

			$updated['fields']['product_description'][$lang['language_id']] = array_fill_keys(array_diff(
				array_keys($rec),
				array('product_id', 'language_id')
			), 1);

			// update product description or insert a new one
			//
			$qry = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_description
				WHERE
					product_id = '" . $product_id . "'
					AND language_id = '" . $rec['language_id'] . "'"
			);

			if (!empty($qry->row)) {
				$rec = array_merge($qry->row, $rec);
			}

			if (empty($rec['meta_title']) && !empty($rec['name'])) {
				$rec['meta_title'] = $rec['name'];
			}

			$this->kadb->queryInsert('product_description', $rec, true);

			$descriptions[$lang['language_id']] = $rec;
		}

		return $descriptions;
	}


	/*
		store_id   - store_id
		product_id - product_id
		keywords   - array of keywords from the file
		$names     - array of product names to generate keywords

	*/
	protected function saveSeoKeywords($store_id, $product_id, $keywords, $product_descriptions) {

		foreach ($this->import->languages as $language_id => $lang) {

			$where_condition = "
				`key` = 'product_id'
				AND value = '" . (int)$product_id . "'
				AND store_id = '$store_id'
				AND language_id = '" . $lang['language_id'] . "'
			";

			$seo_url = $this->db->query("SELECT * FROM " . DB_PREFIX . "seo_url WHERE $where_condition")->row;

			$keyword = '';
			if (!empty($keywords[$lang['language_id']])) {

				$keyword = $keywords[$lang['language_id']];

			} elseif (!empty($this->params['opt_generate_seo_keyword']) && empty($seo_url)) {
				if (!empty($product_descriptions[$lang['language_id']]) && !empty($product_descriptions[$lang['language_id']]['name'])) {
					$keyword = $this->generateProductUrl($product_id, $product_descriptions[$lang['language_id']]['name'], $store_id, $lang['language_id']);
					if (empty($keyword)) {
						$this->import->addImportMessage($this->language->get("Unable to generate SEO frie...") . " (" . $product_descriptions[$lang['language_id']]['name'] . ")");
						continue;
					}
				}
			}

			if (!empty($keyword)) {
				$rec = array(
					'store_id'    => $store_id,
					'language_id' => $lang['language_id'],
					'key'         => 'product_id',
					'value'       => $product_id,
					'keyword'     => $keyword
				);

				if (empty($seo_url)) {
					$this->kadb->queryInsert('seo_url', $rec);
				} elseif ($seo_url['keyword'] != $rec['keyword']) {
					$this->kadb->queryUpdate('seo_url', $rec, "seo_url_id = '" . $seo_url['seo_url_id'] . "'");
				}
			}
		}
	}

	/*

	*/
	protected function saveAdditionalImage($product_id, $additional_image, $sort_order) {

		$file = $this->import->getImageFile($additional_image);
		if (empty($file)) {
			$this->import->addImportMessage("Additional image cannot be saved - " . $this->import->lastError);
			return false;

		}

		$qry = $this->db->query("SELECT product_image_id FROM " . DB_PREFIX . "product_image
			WHERE image = '" . $this->db->escape($file) . "' AND product_id = '$product_id'");

		if (empty($qry->row)) {
			$this->db->query("INSERT INTO " . DB_PREFIX . "product_image SET
				product_id = '" . $product_id . "',
				sort_order = " . $sort_order . ",
				image = '" . $this->db->escape($file) . "'"
			);
		}

		return true;
	}


	protected function saveAdditionalImages($row, $data, $product, $flags, &$updated) {

		if (empty($data['additional_image'])) {
			return true;
		}

		if ($flags['delete_old']) {
			$this->db->query("DELETE FROM " . DB_PREFIX . "product_image
				WHERE product_id = '$product[product_id]'"
			);
			unset($updated['fields']['product_image']);
		}

		if (!is_array($data['additional_image'])) {
			$data['additional_image'] = array($data['additional_image']);
		}

		// get the latest sort order
		$sort_order = 0;
		$qry = $this->db->query("SELECT sort_order FROM " . DB_PREFIX . "product_image
			WHERE product_id = '" . (int)$product['product_id'] . "' ORDER BY sort_order DESC LIMIT 1"
		);
		if (!empty($qry->row['sort_order'])) {
			$sort_order = $qry->row['sort_order'];
		}

		foreach ($data['additional_image'] as $additional_image) {
			$updated['fields']['product_image'] = 1;

			$images = $this->import->splitImages($additional_image);

			foreach ($images as $image) {

				if (empty($image)) {
					continue;
				}

				// insert an additional product image
				//
				if ($this->saveAdditionalImage($product['product_id'], $image, $sort_order)) {
					$sort_order += 5;
				}
			}
		}

		return true;
	}


	/*
		NOTE: Attributes are saved from the first product row only
	*/
	protected function saveAttributes($row, $data, $product, $flags, &$updated) {

		if (!$flags['is_first']) {
			return;
		}

		if (empty($this->params['matches']['attributes'])) {
			return true;
		}

		if ($flags['delete_old']) {
			$this->db->query("DELETE FROM " . DB_PREFIX . "product_attribute
				WHERE
					product_id = '$product[product_id]'
			");
			unset($updated['fields']['product_attribute']);
		}

		$data = array();
		foreach ($this->params['matches']['attributes'] as $ak => $av) {

			// the column has to be an array here
			//
			if (empty($av['column'])) {
				continue;
			}

			// collect existing attribute values to an array
			//
			$_attr_values = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_attribute
				WHERE
					product_id = '" . $product['product_id'] . "'
					AND attribute_id = '" . $av['attribute_id'] . "'
			")->rows;

			$attr_values = array();
			if (!empty($_attr_values)) {
				foreach ($_attr_values as $attr_value) {
					$attr_values[$attr_value['language_id']] = $attr_value['text'];
				}
			}

			// loop through all languages and set the attribute for the language
			//
			$all_empty = true;
			foreach ($this->import->languages as $lang) {
				if (isset($av['column'][$lang['language_id']])) {
					$val = trim($row[$av['column'][$lang['language_id']]]);

					// we support deleting an attribute by the '[DELETE]' value in a csv file
					//
					if (strcasecmp($val, '[DELETE]') == 0) {
						$this->db->query("DELETE FROM " . DB_PREFIX . "product_attribute
							WHERE
								product_id = '$product[product_id]'
								AND	attribute_id = '" .$av['attribute_id'] . "'"
						);

						$attr_values = array();
						break;
					}

				} elseif (isset($attr_values[$lang['language_id']])) {
					$val = $attr_values[$lang['language_id']];
				} else {
					$val = '';
				}

				$attr_values[$lang['language_id']] = $val;

				if (!empty($val)) {
					$all_empty = false;
				}
			}

			// insert new attribute value only if one of the values is not empty
			// we skip empty values
			//
			if (!empty($attr_values) && !$all_empty) {
				foreach ($attr_values as $avk => $avv) {
					$rec = array(
						'product_id'   => $product['product_id'],
						'attribute_id' => $av['attribute_id'],
						'language_id'  => $avk,
						'text'         => $avv
					);

					$updated['fields']['product_attribute'] = 1;
					$this->kadb->queryInsert('product_attribute', $rec, true);
				}
			}
		}

		return true;
	}


	protected function saveFilters($row, $data, $product, $flags, &$updated) {

		if (empty($this->params['matches']['filter_groups'])) {
			return true;
		}

		if ($flags['delete_old']) {
			$this->db->query("DELETE FROM " . DB_PREFIX . "product_filter
				WHERE
					product_id = '$product[product_id]'"
			);
			unset($updated['fields']['product_filter']);
		}

		if (empty($this->params['cfg_compare_as_is'])) {
			$name_comparison = "TRIM(CONVERT(name using utf8)) LIKE ";
		} else {
			$name_comparison = "name = ";
		}

		$data = array();
		foreach ($this->params['matches']['filter_groups'] as $ak => $av) {
			if (!isset($av['column'])) {
				continue;
			}

			$val = $row[$av['column']];

			$sep = $this->config->get('ka_product_import_general_separator');
			if (!empty($sep)) {
				$filter_values = explode($sep, $val);
			} else {
				$filter_values = array($val);
			}

			foreach ($filter_values as $fv) {

				if (empty($fv)) {
					continue;
				}

				// find the filter_id
				//
				$filter_id = false;
				$filter_group_id = $av['filter_group_id'];
				$fv = trim($fv);

				$qry = $this->db->query("SELECT filter_id FROM " . DB_PREFIX . "filter_description WHERE
					$name_comparison '". $this->db->escape($this->db->escape($fv)) . "'
					AND filter_group_id = '$filter_group_id'"
				);

				// create a new filter if required
				//
				if (empty($qry->row)) {

					// add a new filter value
					//
					$this->db->query("INSERT INTO " . DB_PREFIX . "filter SET
						filter_group_id = '" . (int)$filter_group_id . "',
						sort_order = 0"
					);
					$filter_id = $this->db->getLastId();

					if (empty($filter_id)) {
						$this->report('filter was not created');
						continue;
					}

					foreach ($this->import->languages as $lang) {
						$rec = array(
							'filter_id'       => $filter_id,
							'filter_group_id' => $filter_group_id,
							'language_id'     => $lang['language_id'],
							'name'            => $fv
						);
						$this->kadb->queryInsert('filter_description', $rec);
					}

				} else {
					$filter_id = $qry->row['filter_id'];
				}

				// assign the filter
				//
				$this->db->query("REPLACE INTO " . DB_PREFIX . "product_filter SET
					product_id = '" . (int)$product['product_id'] . "',
					filter_id = '" . (int)$filter_id . "'"
				);
				$updated['fields']['product_filter'] = 1;
			}
		}

		return true;
	}


	protected function saveReviews($row, $data, $product, $flags, &$updated) {

		if (empty($this->params['matches']['reviews'])) {
			return true;
		}

		if ($flags['delete_old']) {
			$this->db->query("DELETE FROM " . DB_PREFIX . "review
				WHERE
					product_id = '$product[product_id]'"
			);
		}

		$rec = array();

		foreach ($this->params['matches']['reviews'] as $ak => $av) {

			if (!isset($av['column']) || strlen($av['column']) == 0)
				continue;

			$val = $row[$av['column']];
			$rec[$av['field']] = $val;
		}

		if (empty($rec['author']) || empty($rec['text'])) {
			return false;
		}

		$rec['product_id'] = $product['product_id'];

		if (empty($rec['date_added'])) {
			if (!empty($rec['date_modified'])) {
				$rec['date_added'] = $rec['date_modified'];
			}
		}

		if (!empty($rec['date_added'])) {
			$rec['date_added'] = $this->import->kaformat->parseDate($rec['date_added']);
		}

		if (!empty($rec['date_modified'])) {
			$rec['date_modified'] = $this->import->kaformat->parseDate($rec['date_modified']);
		}

		$qry = $this->db->query("SELECT * FROM " . DB_PREFIX . "review WHERE
			author = '" . $rec['author'] . "' AND
			product_id = '" . $rec['product_id'] . "'
		");

		if (!empty($qry->rows)) {

			if (isset($rec['status']) && strlen($rec['status']) == 0) {
				unset($rec['status']);
			}

			$rec = array_merge($qry->rows[0], $rec);
			unset($rec['review_id']);

			$this->kadb->queryUpdate("review", $rec, "review_id = '" . $qry->row['review_id'] . "'");

		} else {
			if (empty($rec['status'])) {
				$rec['status'] = 1;
			}
			$this->kadb->queryInsert('review', $rec);
		}

		return true;
	}


	public function updateProductStatuses() {

		// update new products
		//
		if ($this->params['status_for_new_products'] == 'enabled') {

			$this->db->query("UPDATE " . DB_PREFIX . "product p INNER JOIN
				" . DB_PREFIX. "ka_product_import pi ON p.product_id = pi.product_id
				SET p.status = 1
				WHERE
					token = '" . $this->db->escape($this->session->data['ka_token']) . "'
					AND pi.is_new = 1
			");

		} elseif ($this->params['status_for_new_products'] == 'disabled') {

			$this->db->query("UPDATE " . DB_PREFIX . "product p INNER JOIN
				" . DB_PREFIX. "ka_product_import pi ON p.product_id = pi.product_id
				SET p.status = 0
				WHERE
					token = '" . $this->db->escape($this->session->data['ka_token']) . "'
					AND pi.is_new = 1
			");

		} else {

			$this->db->query("UPDATE " . DB_PREFIX . "product p INNER JOIN
				" . DB_PREFIX. "ka_product_import pi ON p.product_id = pi.product_id
				SET p.status = IF (p.quantity > 0, 1, 0)
				WHERE
					token = '" . $this->session->data['ka_token'] . "'
					AND pi.is_new = 1
			");
		}

		// update existing products
		//
		if ($this->params['status_for_existing_products'] == 'enabled') {

			$this->db->query("UPDATE " . DB_PREFIX . "product p INNER JOIN
				" . DB_PREFIX. "ka_product_import pi ON p.product_id = pi.product_id
				SET p.status = 1
				WHERE
					token = '" . $this->db->escape($this->session->data['ka_token']) . "'
					AND pi.is_new = 0
			");

		} elseif ($this->params['status_for_existing_products'] == 'disabled') {

			$this->db->query("UPDATE " . DB_PREFIX . "product p INNER JOIN
				" . DB_PREFIX. "ka_product_import pi ON p.product_id = pi.product_id
				SET p.status = 0
				WHERE
					token = '" . $this->db->escape($this->session->data['ka_token']) . "'
					AND pi.is_new = 0
			");

			$this->import->stat['products_disabled'] = $this->db->countAffected();

		} elseif ($this->params['status_for_existing_products'] == 'enabled_gt_0') {

			// enable products
			//
			$this->db->query("UPDATE " . DB_PREFIX . "product p INNER JOIN
				" . DB_PREFIX. "ka_product_import pi ON p.product_id = pi.product_id
				SET p.status = 1
				WHERE
					token = '" . $this->session->data['ka_token'] . "'
					AND quantity > 0
			");

			// disable products
			//
			$this->db->query("UPDATE " . DB_PREFIX . "product p INNER JOIN
				" . DB_PREFIX. "ka_product_import pi ON p.product_id = pi.product_id
				SET p.status = 0
				WHERE
					token = '" . $this->session->data['ka_token'] . "'
					AND quantity <= 0
			");
			$this->import->stat['products_disabled'] = $this->db->countAffected();
		}
	}

	/*
		$is_first - the first record for the product in the file
		$is_new   - the product was created from this record
	*/
	public function updateEntity($row, $data, $is_first, $is_new) {

		$data['product_id'] = $data['entity_id'];

		// this array will store field names updated by the product. They will be used for OC variants in the product.override field.
		//
		$updated = array(
			// we have to add 'fields' here because there are no functions in php to quickly merge an array by reference
			'fields' => array()
		);

		if (!empty($data['master_model'])) {
			if ($data['master_model'] == '[DELETE]') {
				$master_id = 0;
			} else {
				$master_id = $this->fetchMasterProductId($data);
			}
			$data['master_id'] = $master_id;

			if ($this->params['update_mode'] == 'replace' && !empty($master_id)) {
				if (!$this->import->isRecordRegistered($master_id, ModelImport::RECORD_TYPE_FIRST_VARIANT_PROCESSED)) {
					$this->emptyVariants($master_id);
					$this->import->registerRecord($master_id, ModelImport::RECORD_TYPE_FIRST_VARIANT_PROCESSED, 1);
				}
			}
		}

		// sets if we need to delete previous records of the product
		// it works for the first row of the product only
		//
		// delete previous product records like 'specials', 'discounts' etc.
		//
		$delete_old = false;

		if (($this->params['update_mode'] == 'replace') && $is_first) {
			$delete_old = true;
		}

		$flags = array(
			'delete_old' => $delete_old,
			'is_new'     => $is_new,
			'is_first'   => $is_first,
		);

		$product = $this->getProduct($data['product_id']);

		if ($is_first) {

			// extract the main product image from the additional images when the main
			// image is imported from the same column as the additional images
			//
			$this->processImageFields($data);

			$this->updateProduct($row, $data, $product, $flags, $updated);

			if (!$is_new) {
				$this->import->stat['products_updated']++;
			}
		}

		$product = $this->getProduct($data['product_id']);

		// get the override information for the variant product
		if (!empty($product['master_id']) && !$flags['is_new']) {
			$updated['fields'] = array_merge($updated['fields'], $this->getVariantFields($product));
		}

		// $row     - raw data read from the file, numeric indexes
		// $data    - data from the file mapped to basic product fields
		// $product - current product data
		// $updated - array of updated fields (for OC variant overriding)
		//
		$this->import_options->saveOptions($row, $data, $product, $flags, $updated);

		$this->import_categories->saveCategories($row, $data, $product, $flags, $updated);

		$this->saveAdditionalImages($row, $data, $product, $flags, $updated);

		$this->saveAttributes($row, $data, $product, $flags, $updated);

		$this->saveFilters($row, $data, $product, $flags, $updated);

		$this->saveDiscounts($row, $data, $product, $flags, $updated);

		$this->saveSpecials($row, $data, $product, $flags, $updated);

		$this->saveRewardPoints($row, $data, $product, $flags, $updated);

		$this->saveRelatedProducts($row, $data, $product, $flags, $updated);

		$this->saveSubscriptions($row, $data, $product, $flags, $updated);

		$this->saveDownloads($row, $data, $product, $flags, $updated);

		$this->saveReviews($row, $data, $product, $flags, $updated);

		$this->saveVariantFields($product, $updated);
	}

	protected function getProduct($product_id) {

		$product = $this->db->query("SELECT * FROM " . DB_PREFIX . "product
			WHERE product_id = '" . $product_id . "'
		")->row;

		return $product;
	}


	protected function getVariantFields($product) {

		// Variant field example: {"404":[254]}
		// where
		//   404 - product_option_id of the master product
		//   254 - product_option_value_id of the master product
		//
		$variants = json_decode($product['variant'], true);
		if (empty($variants)) {
			return array();
		}

		// override field example:
		// {"product_description":{"1":{"name":1}},"model":1,"price":1,"variant":{"404":1}}
		//
		$override = json_decode($product['override'], true);
		if (empty($override)) {
			return array();
		}

		$variants = array_intersect_key($variants, $override);

		return $variants;
	}

	/*
		Saves updated fields to 'product.variant'  and 'product.override' values.
		$updated - array
			['price'] => ...
			['product_description'][<lng>] => ...
			['options'] - !

	*/
	protected function saveVariantFields($product, $updated) {

		if (empty($product['master_id'])) {
			return;
		}

		unset($updated['fields']['quantity']);

		// request available options from the master product because
		// variant products cannot define options. All options are taken from the master product.
		//
		$override = $updated['fields'];

		$rec = array();
		if (!empty($updated['fields']['variant'])) {
			$variant = $updated['fields']['variant'];

			$_override_variant = array();
			foreach ($variant as $k => $v) {
				$_override_variant[$k] = 1;
			}
			$rec['variant'] = json_encode($variant) ?? '';
			$override['variant'] = $_override_variant;
		}
		$rec['override'] = json_encode($override) ?? '';

		$this->kadb->queryUpdate("product", $rec, "product_id='" . $product['product_id'] . "'");
	}


	public function getStages() {

		$stages = array(
			array(
				'code'     => 'update_variants',
				'title'    => $this->language->get('Checking variants'),
				'function' => array($this, 'runStageUpdateVariants'),
			),
			array(
				'code'     => 'check_products',
				'title'    => $this->language->get('Checking products and statuses'),
				'function' => array($this, 'runStageFinishImport'),
			),
			array(
				'code'     => 'delete_records',
				'title'    => $this->language->get('Deleting temporary records'),
				'function' => array($this, 'runStageDeleteRecords'),
			),
		);

		return $stages;
	}


	/*
		Returns true when it should be executed again
		false - the function finished all jobs
	*/
	public function runStageFinishImport() {

		$this->cache->delete('product');

		if (!empty($this->params['disable_not_imported_products'])) {
			$this->disableNotImportedProducts();
		}

		// update product statuses of imported products according to the global extension settings
		//
		if (empty($this->params['matches']['fields']['status'])) {
			$this->updateProductStatuses();
		}
	}


	/*
		true  - needs another call
		false - finished
	*/
	public function runStageUpdateVariants() {

		if (!isset($this->import->stat['last_master_id'])) {
			$this->import->stat['last_master_id'] = 0;
		}

		while (1) {

			if ($this->import->isTimeout()) {
				return true;
			}

			$product_ids = $this->db->query("SELECT kpi.product_id FROM " . DB_PREFIX . "ka_product_import kpi
				INNER JOIN " . DB_PREFIX . "product p ON kpi.product_id = p.master_id
				WHERE token = '" . $this->session->data['ka_token'] . "'
					AND kpi.product_id > " . $this->import->stat['last_master_id'] . "
				ORDER BY kpi.product_id
				LIMIT 50
			")->rows;
			if (empty($product_ids)) {
				break;
			}

			foreach ($product_ids as $product) {
				$this->syncProductVariants($product['product_id']);
				$this->import->stat['last_master_id'] = $product['product_id'];
			}
		}

		return false;
	}


	/*
		Here we use a standard Opencart function to update variant fields for all variants
	*/
	protected function syncProductVariants($product_id) {

		$master = $this->model_catalog_product->getProductCopy($product_id);
		if (empty($master)) {
			return;
		}

		// In the 'replace old records' mode we delete variants not participated in the last import
		//
		if ($this->params['update_mode'] == 'replace') {
			$this->deleteNotUpdatedVariants($master['product_id']);
		}

		$this->model_catalog_product->editVariants($master['product_id'], $master);
	}


	public function runStageDeleteRecords() {

		$this->import_options->deleteRecords();

		$this->db->query("DELETE FROM " . DB_PREFIX . "ka_import_records
			WHERE
				token = '" . $this->db->escape($this->session->data['ka_token']) . "'
				OR TIMESTAMPDIFF(HOUR, added_at, NOW()) > 168
				OR added_at = '0000-00-00 00:00:00'
		");
	}


	protected function saveSubscriptions($row, $data, $product, $flags, &$updated) {

		if (empty($this->params['matches']['subscriptions'])) {
			return true;
		}

		if ($flags['delete_old']) {
			$this->db->query("DELETE FROM " . DB_PREFIX . "product_subscription
				WHERE product_id = '$product[product_id]'
			");
		}

		$data = array();
		foreach ($this->params['matches']['subscriptions'] as $ak => $av) {

			if (!isset($av['column']))
				continue;

			$val = $row[$av['column']];

			if ($av['field'] == 'customer_group') {
				$data['customer_group_id'] = $this->import->getCustomerGroupByName($val);
				if (empty($data['customer_group_id'])) {
					return false;
				}
				continue;

			} elseif ($av['field'] == 'name') {
				$qry = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_subscription");
				if (empty($qry->row)) {
					return false;
				}
				$data['subscription_plan_id'] = $qry->row['subscription_plan_id'];
				continue;
			}

			$data[$av['field']] = $val;
		}

		$data['product_id'] = $product['product_id'];

		if (empty($data['customer_group_id'])) {
			$data['customer_group_id'] = $this->config->get('config_customer_group_id');
		}

		$this->db->query("DELETE FROM " . DB_PREFIX . "product_subscription WHERE
			product_id = '$product[product_id]'
			AND customer_group_id = '$data[customer_group_id]'
		");
		$this->kadb->queryInsert('product_subscription', $data);
	}


	/*
		$data - array including:
			master_model
	*/
	protected function fetchMasterProductId($data) {

		$product = $this->db->query("SELECT product_id FROM " . DB_PREFIX . "product
			WHERE model = '" . $this->db->escape($data['master_model']) . "'
		")->row;

		if (!empty($product)) {
			return $product['product_id'];
		}

		$new = array(
			'model' => $data['master_model'],
		);
		$product_id = $this->kadb->query("product", $new);

		return $product_id;
	}


	public function deleteEntity($entity_id) {
		$this->model_catalog_product->deleteProduct($entity_id);
	}


	/*
		Empty product variant field overriding
	*/
	public function emptyVariants($master_id) {

		$products = $this->db->query("SELECT product_id FROM " . DB_PREFIX . "product
			WHERE master_id = '" . $this->db->escape($master_id) . "'
		")->rows;

		if (empty($products)) {
			return;
		}

		foreach ($products as $p) {
			$this->emptyVariant($p['product_id']);
		}
	}


	protected function emptyVariant($product_id) {
		$this->db->query("UPDATE " . DB_PREFIX . "product
			SET variant = '', override = ''
			WHERE product_id = '$product_id'
		");
	}


	protected function deleteNotUpdatedVariantsQB($product_id) {

		$qb = new QB();

		$qb->select('p.product_id', 'ka_product_import', 'kpi');
		$qb->innerJoin('product', 'p', 'kpi.product_id = p.master_id');
		$qb->where('kpi.token', $this->session->data['ka_token']);
		$qb->where('p.master_id', $product_id);
		$qb->where("p.product_id NOT IN (
			SELECT product_id FROM " . DB_PREFIX . "ka_product_import
				WHERE token = '" . $this->session->data['ka_token'] . "'
			)
		");

		return $qb;
	}

	// delete variants not participated in the last import
	//
	protected function deleteNotUpdatedVariants($product_id) {

		$products = $this->deleteNotUpdatedVariantsQB($product_id)->query()->rows;

		if (empty($products)) {
			return;
		}

		foreach ($products as $p) {
			$this->model_catalog_product->deleteProduct($p['product_id']);
		}

	}
}
