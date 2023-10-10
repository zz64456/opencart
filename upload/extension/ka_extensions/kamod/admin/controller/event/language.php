<?php
/*
	$Project$
	$Author$

	$Version$ ($Revision$)
	
	We disable language variables injection to templates for our pages. All language variables
	should be requested with {{ t() }} twig function there.
	
*/

namespace extension\ka_extensions\event;

class Language extends \Opencart\Admin\Controller\Event\Language {

	// view/*/before
	// Dump all the language vars into the template.
	public function index(string &$route, array &$args): void {
		if (!empty($GLOBALS['ka_is_language_injection_disabled'])) {
			return;
		}

		parent::index($route, $args);
	}

	// controller/*/before
	// 1. Before controller load store all current loaded language data.
	public function before(string &$route, array &$args): void {
		if (!empty($GLOBALS['ka_is_language_injection_disabled'])) {
			return;
		}

		parent::before($route, $args);
	}

	public function after(string &$route, array &$args, mixed &$output): void {
		if (!empty($GLOBALS['ka_is_language_injection_disabled'])) {
			return;
		}

		parent::after($route, $args, $output);
	}
}