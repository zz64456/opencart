<?php
/*
	This file was inherited by kamod.
	More information can be found at https://www.ka-station.com/kamod
	
	Original file: extension/ka_extensions/kamod/system/library/response.php
*/
/*
	$Project$
	$Author$

	$Version$ ($Revision$)
*/

namespace extension\ka_extensions\library;

require_once(__DIR__ . '/response.1.kamod.php');

class Response extends \Opencart\System\Library\Response_kamod  {
	
	public function getHeaders(): array {
		return $this->headers;
	}
	
	public function setHeaders($headers) {
		$this->headers = $headers;
	}

}

