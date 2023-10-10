<?php
/*
	$Project$
	$Author$

	$Version$ ($Revision$)
*/

namespace extension\ka_extensions\common;

class Footer extends Opencart\Admin\Controller\Common\Footer {

	use \extension\ka_extensions\TraitController;

	public function index(): string {

		$this->load->disableRender();
		parent::index();
		$this->load->enableRender();
		
		$data = $this->getRenderData();
		$template = $this->getRenderTemplate();		

		// bootstrap stops working when its script loads twice. We load it in the header and stop loading
		// it in the footer
		$data['bootstrap'] = '';
	
		return $this->load->view($template, $data);
	}
}