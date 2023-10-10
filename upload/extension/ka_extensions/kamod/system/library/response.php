<?php
/*
	$Project$
	$Author$

	$Version$ ($Revision$)
*/

namespace extension\ka_extensions\library;

class Response extends \Opencart\System\Library\Response {
	
	public function getHeaders(): array {
		return $this->headers;
	}
	
	public function setHeaders($headers) {
		$this->headers = $headers;
	}

}

