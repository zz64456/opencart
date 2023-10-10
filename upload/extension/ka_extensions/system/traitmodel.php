<?php
/*
	$Project: Ka Extensions $
	$Author: karapuz team <support@ka-station.com> $

	$Version: 4.1.1.0 $ ($Revision: 287 $)
*/
	
namespace extension\ka_extensions;

use extension\ka_extensions\Tables;

trait TraitModel {

	protected $lastError;
	
	public function getLastError() {
		return $this->lastError;
	}
}