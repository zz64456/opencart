<?php
/*
	$Project$
	$Author$

	$Version$ ($Revision$)
*/

namespace extension\ka_extensions;

trait TraitController {

	protected $template = '';

	protected $data     = array();
	protected $children = array();

	protected $is_render_disabled = false;

	protected function getRenderData() {
		$data = $this->load->getTmpViewData();
		if (!empty($data)) {
			$this->data = array_merge($this->data, $data);
		}
		return $this->data;
	}

	protected function getRenderTemplate() {
		return $this->load->getTmpViewRoute();
	}

	/*
		These two methods are added to unify the interface with our "KaController" 
	*/
	protected function disableRender() {
		$this->is_render_disabled = true;
		$this->load->disableRender();
	}

	protected function enableRender() {
		$this->is_render_disabled = false;
		$this->load->enableRender();
	}
	
	
	protected function addTopMessage($msg, $type = 'S') {
	
		if (!is_array($msg)) {
			$msg = array($msg);
		}

		foreach ($msg as $text) {
			$this->session->data['ka_top_messages'][] = array(
				'type'    => $type,
				'content' => $text
			);
		}
	}

	
	protected function getTopMessages($clear = true) {

		if (isset($this->session->data['ka_top_messages'])) {
			$top = $this->session->data['ka_top_messages'];
		} else {
			$top = null;
		}

		if ($clear) {		
			$this->session->data['ka_top_messages'] = null;
		}
		return $top;
	}
	
	/*
		Rendering can be disabled for parent classes thus child classes may change the data
		or template file before output. Example:
		
		public function index() {
			$this->disableRender();
			parent::index();
			$this->enableRender();
			$this->response->setOutput($this->render());
		}
		
	*/
	protected function render($tpl = '', $data = array()) {

		if (!empty($data)) {
			$this->data = array_merge($this->data, $data);
		}
		
		if (!empty($tpl)) {
			$this->template = $tpl;
		}
		
		if ($this->is_render_disabled) {
			return '';
		}
	
		if (!empty($this->children)) {
			foreach ($this->children as $child) {
				$this->data[basename($child)] = $this->load->controller($child);
			}
		} else {
			if (KaGlobal::isAdminArea()) {
				$this->loadAdminPageBlocks();
			} else {
				$this->loadCustomerPageBlocks();
			}
		}
	
		$this->data['top_messages'] = $this->getTopMessages();

		$GLOBALS['ka_is_language_injection_disabled'] = true;

		$result = $this->load->view($this->template, $this->data);
		$GLOBALS['ka_is_language_injection_disabled'] = false;
		
		return $result;
	}
	

	/*
		$template - name of the template inside the module directory (when namespace is available)
		$data     - array of template variables
	*/		
	protected function showPage($template = '', $data = array()) {

		if (!empty($data)) {
			$this->data = $data;
		}
		
		if (!empty($template)) {
			$this->template = $template;
		}
		
		if (empty($this->data['heading_title'])) {
			$this->data['heading_title'] = $this->document->getTitle();
		}
		
		// the page id is generated from the template name when it is not defined explicitly
		//
		if (empty($this->data['page_id'])) {
			if (!empty($this->request->get['route'])) {
				$this->data['page_id'] = $this->request->get['route'];
			} else {
				$this->data['page_id'] = 'home';
			}
		}

		$this->document->addStyle('../extension/ka_extensions/admin/view/stylesheet/stylesheet.css');
		$this->document->addScript('../extension/ka_extensions/admin/view/javascript/common.js');

		$this->response->setOutput($this->render($this->template));
	}
	
	
	protected function loadAdminPageBlocks() {
		if (!empty($this->session->data['user_token'])) {
			$this->data['user_token'] = $this->session->data['user_token'];
		}
		$this->data['header'] = $this->load->controller('common/header');
		$this->data['column_left'] = $this->load->controller('common/column_left');
		$this->data['footer'] = $this->load->controller('common/footer');
	}

	
	protected function loadCustomerPageBlocks() {
		$this->data['column_left'] = $this->load->controller('common/column_left');
		$this->data['column_right'] = $this->load->controller('common/column_right');
		$this->data['content_top'] = $this->load->controller('common/content_top');
		$this->data['content_bottom'] = $this->load->controller('common/content_bottom');
		$this->data['footer'] = $this->load->controller('common/footer');
		$this->data['header'] = $this->load->controller('common/header');
	}	
	
	protected $lastError;
	
	public function getLastError() {
		return $this->lastError;
	}
}