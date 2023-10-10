<?php
/*
	$Project$
	$Author$

	$Version$ ($Revision$)
	
	Customized templates are stored on a disk because they are patched by kamod. We always return empty here
	to prevent replacing kamod twig loaders with "Array Loader" used for the code.
	
*/
namespace extension\ka_extensions;
class ModelDesign extends \Opencart\Catalog\Model\Design\Theme {

	public function getTheme(string $route): array {
		return array();
	}
}