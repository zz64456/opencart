<?php
/*
	$Project$
	$Author$

	$Version$ ($Revision$)
*/
	
namespace extension\ka_extensions;

/*
	This is a service class for storing url parameters and building URLs

	// define available page parameters
	//
		$params = array(
			'path'    => '',
			'letters' => '', // letter
		'sort'    => 'name',
			'order'   => 'ASC', 
			'page'    => '1',
		);
	$url_params = new UrlParams($this->request, $params);
	$params = $url_params->getParams();
	if (strlen($params['letters']) > 1) {
  		$params['subletter'] = substr($params['letters'], 1, 1);
  	}

	
  	...
  	'href' => $this->url->link('product/category', $url_params->getUrl(['path' => $path]))
  	...
  	$pagination->url = $this->url->link('product/category', $url_params->getUrl(['page' => '{page}']));
	
*/
class UrlParams {
	protected $request;

	protected $default_params;
	protected $params;
	protected $url_array;
	
	protected $built_cache;
	
	function __construct($request, $default_params, $keep_original = false) {

		$this->request = $request;
		$this->default_params = $default_params;
	
  		// init page parameters
  		//
  		$params = array();
  		foreach ($default_params as $k => $v) {
			if (isset($this->request->get[$k])) {
				if ($keep_original) {
					$params[$k] = $this->request->get[$k];
				} else {
					$params[$k] = trim($this->request->get[$k]);
				}
	  		}
	  	}
	  	
	  	$this->params = $params;
	}
	
	
	public function getParams() {

		if (empty($this->default_params)) {
			return $this->params;
		}
		
		$params = array_merge($this->default_params, $this->params);
	
		return $params;
	}
	

	public function getParam($name) {
	
		if (isset($this->params[$name])) {
			return $this->params[$name];
		}
		
		if (isset($this->default_params[$name])) {
			return $this->default_params[$name];
		}
	
		return null;
	}
	
	protected function buildParams($params) {
	
		if (!empty($this->built_cache)) {
			return $this->built_cache;
		}
	
  		$url_array = array();
  		foreach ($params as $k => $v) {
			$url_array[$k] = $k . '=' . $params[$k];
	  	}
	  	
		$this->built_cache = $url = implode('&', $url_array);
		
		return $url;
	}
	
	
	/*
		Return url for sorting
	*/
	function getUrlSort($field) {
	
		$params = $this->params;
		
		if (!empty($params['sort']) && $params['sort'] == $field) {
			if (!empty($params['order'])) {
			  	if ($params['order'] == 'ASC') {
			  		$params['order'] = 'DESC';
			  	} else {
			  		$params['order'] = 'ASC';
			  	}
			} elseif (!empty($this->default_params['order'])) {
				$params['order'] = 'DESC';
			}
		}
		
		$params['sort'] = $field;
		
		$this->built_cache = null;
		$url = $this->buildParams($params);
		
		return $url;
	}
	
	function updateParams($params) {
		$this->built_cache = null;
		$this->params = array_merge($this->params, $params);
	}
	
	/*
		Returns URL parameters exploded by '&'. Parameters can be extended or overwritten with $params
		array.
		
		The parameter can be removed if it is set to null
		
	*/
	function getUrl($params = null) {
	
		if (!empty($params)) {
			$this->built_cache = null;
			$params = array_merge($this->params, $params);
			$params = array_filter($params, function($val) { return !is_null($val); });
		} else {
			$params = $this->params;
		}
		
		$url = $this->buildParams($params);
		
		return $url;
	}
}
