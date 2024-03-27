<?php

use Journal3\Opencart\ModuleController;
use Journal3\Options\Parser;
use Journal3\Utils\Arr;

class ControllerJournal3Catalog extends ModuleController {

	/**
	 * @param Parser $parser
	 * @param $index
	 * @return array
	 */
	protected function parseGeneralSettings($parser, $index) {
		$data = array(
			'edit'            => 'module_layout/' . $this->module_type . '/edit/' . $this->module_id,
			'name'            => $parser->getSetting('name'),
			'swiper_carousel' => $parser->getSetting('gridType') === 'ipr' && $parser->getSetting('carousel'),
			'classes'         => [
				'carousel-mode'    => $parser->getSetting('gridType') === 'ipr' && $parser->getSetting('carousel'),
				'image-on-hover'   => $parser->getSetting('changeImageOnHover'),
				'align-to-content' => $parser->getSetting('gridType') === 'auto' && $parser->getSetting('autoGridContainerAlignToContent'),
			],
			'carouselOptions' => $this->journal3->carousel($parser->getJs(), 'carouselStyle'),
		);

		if ($this->journal3->get('performanceLazyLoadImagesStatus')) {
			$data['dummy_image'] = $this->journal3_image->transparent($parser->getSetting('imageDimensions.width'), $parser->getSetting('imageDimensions.height'));
		}

		return $data;
	}

	/**
	 * @param Parser $parser
	 * @param $index
	 * @return array
	 */
	protected function parseItemSettings($parser, $index) {
		$this->load->model('catalog/category');
		$this->load->model('catalog/manufacturer');
		$this->load->model('journal3/category');
		$this->load->model('journal3/manufacturer');
		$this->load->model('journal3/filter');
		$this->load->model('journal3/product');

		$data = array(
			'classes' => array(
				'swiper-slide' => $this->settings['swiper_carousel'],
			),
			'items'   => array(),
			'image'   => '',
			'name'    => '',
			'href'    => '',
			'total'   => null,
		);

		$limit = (int)$parser->getSetting('limit');

		if (!$limit) {
			$limit = (int)$this->settings['limit'];
		}

		switch ($parser->getSetting('type')) {
			case 'category':
				$category_info = $this->model_journal3_category->getCategory((int)$parser->getSetting('category'));

				if (!$category_info) {
					return null;
				}

				$category_path = (string)$parser->getSetting('category');

				if ($category_prefix = (string)Arr::get($this->module_args, 'category_prefix')) {
					$category_prefix_info = $this->model_journal3_category->getCategory((int)$category_prefix);

					if ($category_prefix_info && ($category_path !== $category_prefix)) {
						$category_path = $category_prefix . '_' . $category_path;
					}
				}

				$data['name'] = $category_info['name'];
				$data['href'] = $this->journal3_url->link('product/category', 'path=' . $category_path);

				if ($this->settings['images']) {
					$data['image'] = $this->journal3_image->resize($category_info['image'], $this->settings['imageDimensions']['width'], $this->settings['imageDimensions']['height'], $this->settings['imageDimensions']['resize']);
					$data['image2x'] = $this->journal3_image->resize($category_info['image'], $this->settings['imageDimensions']['width'] * 2, $this->settings['imageDimensions']['height'] * 2, $this->settings['imageDimensions']['resize']);
				} else {
					$data['image'] = false;
					$data['image2x'] = false;
				}

				switch ($parser->getSetting('subtype')) {
					case 'category':
						$results = $this->model_journal3_category->getCategories($parser->getSetting('category'));

						$data['total'] = count($results);

						if ($limit) {
							$results = array_slice($results, 0, $limit);
						}

						foreach ($results as $result) {
							$data['items'][] = array(
								'name'    => $result['name'],
								'href'    => $this->journal3_url->link('product/category', 'path=' . $category_path . '_' . $result['category_id']),
								'image'   => $this->settings['images'] ? $this->journal3_image->resize($result['image'], $this->settings['imageDimensions']['width'], $this->settings['imageDimensions']['height'], $this->settings['imageDimensions']['resize']) : '',
								'image2x' => $this->settings['images'] ? $this->journal3_image->resize($result['image'], $this->settings['imageDimensions']['width'] * 2, $this->settings['imageDimensions']['height'] * 2, $this->settings['imageDimensions']['resize']) : '',
							);
						}

						break;

					case 'product':
						$filter_data = array(
							'filter_category_id' => $parser->getSetting('category'),
							'limit'              => $limit,
							'sort'               => 'p.sort_order',
						);

						$data['total'] = $this->model_journal3_filter->getTotalProducts($filter_data);

						$results = $this->model_journal3_filter->getProducts($filter_data);
						$results = $this->model_journal3_product->getProduct($results);

						foreach ($results as $result) {
							$data['items'][] = array(
								'name'    => $result['name'],
								'href'    => $this->journal3_url->link('product/product', 'path=' . $category_path . '&product_id=' . $result['product_id']),
								'image'   => $this->settings['images'] ? $this->journal3_image->resize($result['image'], $this->settings['imageDimensions']['width'], $this->settings['imageDimensions']['height'], $this->settings['imageDimensions']['resize']) : '',
								'image2x' => $this->settings['images'] ? $this->journal3_image->resize($result['image'], $this->settings['imageDimensions']['width'] * 2, $this->settings['imageDimensions']['height'] * 2, $this->settings['imageDimensions']['resize']) : '',
							);
						}

						break;

					default:
						return null;
				}

				break;

			case 'categories':
				$data['href'] = $parser->getSetting('link')['href'] ?: 'javascript:;';
				$data['name'] = $parser->getSetting('title');

				if ($this->settings['images'] && $parser->getSetting('image')) {
					$data['image'] = $this->journal3_image->resize($parser->getSetting('image'), $this->settings['imageDimensions']['width'], $this->settings['imageDimensions']['height'], $this->settings['imageDimensions']['resize']);
					$data['image2x'] = $this->journal3_image->resize($parser->getSetting('image'), $this->settings['imageDimensions']['width'] * 2, $this->settings['imageDimensions']['height'] * 2, $this->settings['imageDimensions']['resize']);
				}

				$results = $this->model_journal3_category->getCategories(0);

				$data['total'] = count($results);

				if ($limit) {
					$results = array_slice($results, 0, $limit);
				}

				foreach ($results as $result) {
					$data['items'][] = array(
						'name'    => $result['name'],
						'href'    => $this->journal3_url->link('product/category', 'path=' . $result['category_id']),
						'image'   => $this->settings['images'] ? $this->journal3_image->resize($result['image'], $this->settings['imageDimensions']['width'], $this->settings['imageDimensions']['height'], $this->settings['imageDimensions']['resize']) : '',
						'image2x' => $this->settings['images'] ? $this->journal3_image->resize($result['image'], $this->settings['imageDimensions']['width'] * 2, $this->settings['imageDimensions']['height'] * 2, $this->settings['imageDimensions']['resize']) : '',
					);
				}

				break;

			case 'manufacturer';
				$manufacturer_info = $this->model_journal3_manufacturer->getManufacturer($parser->getSetting('manufacturer'));

				if (!$manufacturer_info) {
					return null;
				}

				$data['href'] = $manufacturer_info['link']['href'];
				$data['name'] = $manufacturer_info['name'];

				if ($this->settings['images']) {
					$data['image'] = $this->journal3_image->resize($manufacturer_info['image'], $this->settings['imageDimensions']['width'], $this->settings['imageDimensions']['height'], $this->settings['imageDimensions']['resize']);
					$data['image2x'] = $this->journal3_image->resize($manufacturer_info['image'], $this->settings['imageDimensions']['width'] * 2, $this->settings['imageDimensions']['height'] * 2, $this->settings['imageDimensions']['resize']);
				}

				$filter_data = array(
					'filter_manufacturer_id' => $parser->getSetting('manufacturer'),
					'limit'                  => $limit,
					'sort'                   => 'p.sort_order',
				);

				$data['total'] = $this->model_journal3_filter->getTotalProducts($filter_data);

				$results = $this->model_journal3_filter->getProducts($filter_data);
				$results = $this->model_journal3_product->getProduct($results);

				foreach ($results as $result) {
					$data['items'][] = array(
						'name'    => $result['name'],
						'href'    => $this->journal3_url->link('product/product', 'manufacturer_id=' . $result['manufacturer_id'] . '&product_id=' . $result['product_id']),
						'image'   => $this->settings['images'] ? $this->journal3_image->resize($result['image'], $this->settings['imageDimensions']['width'], $this->settings['imageDimensions']['height'], $this->settings['imageDimensions']['resize']) : '',
						'image2x' => $this->settings['images'] ? $this->journal3_image->resize($result['image'], $this->settings['imageDimensions']['width'] * 2, $this->settings['imageDimensions']['height'] * 2, $this->settings['imageDimensions']['resize']) : '',
					);
				}

				break;

			case 'manufacturers':
				$data['href'] = $parser->getSetting('link')['href'] ?: 'javascript:;';
				$data['name'] = $parser->getSetting('title');

				if ($this->settings['images'] && $parser->getSetting('image')) {
					$data['image'] = $this->journal3_image->resize($parser->getSetting('image'), $this->settings['imageDimensions']['width'], $this->settings['imageDimensions']['height'], $this->settings['imageDimensions']['resize']);
					$data['image2x'] = $this->journal3_image->resize($parser->getSetting('image'), $this->settings['imageDimensions']['width'] * 2, $this->settings['imageDimensions']['height'] * 2, $this->settings['imageDimensions']['resize']);
				}

				$results = $this->model_catalog_manufacturer->getManufacturers(array(
					'sort'  => 'sort_order',
				));

				$results = array_map(function ($result) {
					return array_merge($result, $this->model_journal3_manufacturer->getManufacturer($result['manufacturer_id']));
				}, $results);

				$data['total'] = count($results);

				if ($limit) {
					$results = array_slice($results, 0, $limit);
				}

				foreach ($results as $result) {
					$data['items'][] = array(
						'name'    => $result['name'],
						'href'    => $result['link']['href'],
						'image'   => $this->settings['images'] ? $this->journal3_image->resize($result['image'], $this->settings['imageDimensions']['width'], $this->settings['imageDimensions']['height'], $this->settings['imageDimensions']['resize']) : '',
						'image2x' => $this->settings['images'] ? $this->journal3_image->resize($result['image'], $this->settings['imageDimensions']['width'] * 2, $this->settings['imageDimensions']['height'] * 2, $this->settings['imageDimensions']['resize']) : '',
					);
				}

				break;

			case 'custom':
				$data['href'] = $parser->getSetting('link')['href'] ?: 'javascript:;';
				$data['name'] = $parser->getSetting('title');
				$data['items'] = null;

				if ($this->settings['images'] && $parser->getSetting('image')) {
					$data['image'] = $this->journal3_image->resize($parser->getSetting('image'), $this->settings['imageDimensions']['width'], $this->settings['imageDimensions']['height'], $this->settings['imageDimensions']['resize']);
					$data['image2x'] = $this->journal3_image->resize($parser->getSetting('image'), $this->settings['imageDimensions']['width'] * 2, $this->settings['imageDimensions']['height'] * 2, $this->settings['imageDimensions']['resize']);
				}

				break;

			default:
				return null;
		}

		return $data;
	}

	/**
	 * @param Parser $parser
	 * @param $index
	 * @return array
	 */
	protected function parseSubitemSettings($parser, $index) {
		$data['href'] = $parser->getSetting('link')['href'];
		$data['name'] = $parser->getSetting('title');
		$data['total'] = null;

		if ($this->settings['images'] && $parser->getSetting('image')) {
			$data['image'] = $this->journal3_image->resize($parser->getSetting('image'), $this->settings['imageDimensions']['width'], $this->settings['imageDimensions']['height'], $this->settings['imageDimensions']['resize']);
			$data['image2x'] = $this->journal3_image->resize($parser->getSetting('image'), $this->settings['imageDimensions']['width'] * 2, $this->settings['imageDimensions']['height'] * 2, $this->settings['imageDimensions']['resize']);
		}

		return $data;
	}

	protected function afterRender() {
		$this->document->addScript('catalog/view/theme/journal3/js/carousel.js', 'js-defer');

		if ($this->settings['swiper_carousel']) {
			$this->document->addStyle('catalog/view/theme/journal3/lib/swiper/swiper-critical.min.css');
			$this->document->addScript('catalog/view/theme/journal3/lib/swiper/swiper.min.css', 'lib-swiper');
			$this->document->addScript('catalog/view/theme/journal3/lib/swiper/swiper.min.js', 'lib-swiper');
		}

		$this->document->addScript('catalog/view/theme/journal3/js/catalog.js', 'js-defer');
	}

}

class_alias('ControllerJournal3Catalog', '\Opencart\Catalog\Controller\Journal3\Catalog');
