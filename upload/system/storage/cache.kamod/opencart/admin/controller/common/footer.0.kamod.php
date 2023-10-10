<?php
/*
	This file was inherited by kamod.
	More information can be found at https://www.ka-station.com/kamod
	
	Original file: extension/ka_extensions/kamod/admin/controller/common/footer.php
*/
/*
	$Project$
	$Author$

	$Version$ ($Revision$)
*/

namespace extension\ka_extensions\common;

require_once(__DIR__ . '/footer.1.kamod.php');

class Footer extends \Opencart\Admin\Controller\Common\Footer_kamod  {

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