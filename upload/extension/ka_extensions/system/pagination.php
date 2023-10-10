<?php
/*
	$Project: Ka Extensions $
	$Author: karapuz team <support@ka-station.com> $

	$Version: 4.1.1.0 $ ($Revision: 269 $)
*/
	
namespace extension\ka_extensions;

class Pagination {

	public $total = 0;
	public $page  = 1;
	public $limit = 20;
	public $url   = '';
	protected $registry;
	protected $load;
	protected $config;
	protected $language;

	public function __construct($params) {
		$this->registry = KaGlobal::getRegistry();
		$this->load     = $this->registry->get('load');
		$this->language = $this->registry->get('language');
		$this->config   = $this->registry->get('config');
		
		$this->total = $params['total'];
		$this->page  = $params['page'];

		if (!empty($params['limit'])) {
			$this->limit = $params['limit'];
		} else {
			$store_limit = $this->config->get('config_pagination_admin');
		 	if (!empty($store_limit)) {
		 		$this->limit = $store_limit;
		 	}
		}
		
		if (!empty($params['url'])) {
			$this->url = $params['url'];
		}
	}

	public function getPagination() {
		$pagination = $this->load->controller('common/pagination', [
			'total' => $this->total,
			'page'  => $this->page,
			'limit' => $this->limit,
			'url'   => $this->url
		]);
		
		return $pagination;
	}
	
	public function getResults($text = '') {
	
		if (empty($text)) {
			$text = $this->language->get('text_pagination');
		}
	
		$from  = ($this->total) ? (($this->page - 1) * $this->limit) + 1 : 0;
		$to    = ((($this->page - 1) * $this->limit) > ($this->total - $this->limit)) ? $this->total : ((($this->page - 1) * $this->limit) + $this->limit);
		if ($this->limit <= 0) {
			$pages = 1;
		} else {
			$pages = ceil($this->total / $this->limit);
		}
	
		// 'Showing %d to %d of %d (%d Pages)'
		//
		$str = sprintf($text, $from, $to, $this->total, $pages);
		
		return $str;
	}
}