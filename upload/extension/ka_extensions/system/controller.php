<?php
/*
	$Project: Ka Extensions $
	$Author: karapuz team <support@ka-station.com> $

	$Version: 4.1.1.0 $ ($Revision: 287 $)
*/
	
namespace extension\ka_extensions;

abstract class Controller extends \Opencart\System\Engine\Controller {

	use TraitSession, TraitController;

	protected $kadb = null;
	
	function __construct($registry) {
		parent::__construct($registry);

		$this->kadb = new Db($this->db);

		if (KaGlobal::isAdminArea()) {
			
			$this->document->addStyle(HTTP_CATALOG . 'extension/ka_extensions/admin/view/stylesheet/stylesheet.css');
		}
		
		$this->onLoad();
	}

	
	protected function onLoad() {
		return true;
	}
}
