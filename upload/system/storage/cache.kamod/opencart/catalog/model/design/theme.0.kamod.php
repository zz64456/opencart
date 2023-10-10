<?php
/*
	This file was inherited by kamod.
	More information can be found at https://www.ka-station.com/kamod
	
	Original file: extension/ka_extensions/kamod/catalog/model/design/theme.php
*/
/*
	$Project$
	$Author$

	$Version$ ($Revision$)
	
	Customized templates are stored on a disk because they are patched by kamod. We always return empty here
	to prevent replacing kamod twig loaders with "Array Loader" used for the code.
	
*/
namespace extension\ka_extensions;
require_once(__DIR__ . '/theme.1.kamod.php');

class ModelDesign extends \Opencart\Catalog\Model\Design\Theme_kamod  {

	public function getTheme(string $route): array {
		return array();
	}
}