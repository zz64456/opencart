<?php
/*
	$Project$
	$Author$

	$Version$ ($Revision$)
*/

namespace extension\ka_extensions\common;

class Header extends Opencart\Admin\Controller\Common\Header {

	use \extension\ka_extensions\TraitController;

	private $kamodel_kamod;
	
	public function index(): string {

		// default OC common.js may use bootstrap functions before the bootstrap is loaded in the footer
		// we have to include this script to the header
		$this->document->addScript('view/javascript/bootstrap/js/bootstrap.bundle.min.js');
		
		// our scripts
		$this->document->addScript('../extension/ka_extensions/admin/view/javascript/common.js');	
	
		$this->disableRender();
		parent::index();
		$this->enableRender();
		
		$data     = $this->getRenderData();
		$template = $this->getRenderTemplate();
	
		$this->kamodel_kamod = $this->load->kamodel('extension/ka_extensions/kamod');
		
		$last_errors_total = $this->kamodel_kamod->getLastErrorsTotal();
		$data['kalog_errors_total'] = $last_errors_total;
		$data['kalog_errors_link'] = $this->url->linka('tool/log');
	
		return $this->load->view($template, $data);
	}
}