<?php
/*
  Project : Ka Extensions
  Author  : karapuz team <support@ka-station.com>

  Version : 4 ($Revision: 260 $)
  
*/

namespace extension\ka_extensions;

use \Twig\TwigFunction;

class TwigExtension extends \Twig\Extension\AbstractExtension
{
    public function getFunctions()
    {
        return array(
            new TwigFunction('dir', function($text) {
         		return dirname($text) . '/';
            }),
            new TwigFunction('t', function($text) {
         		return KaGlobal::t($text);
            }),
            new TwigFunction('html_entity_decode', function($text) {
        		return html_entity_decode($text);
            }),
            new TwigFunction('get_language_image', function($param) {
           		return KaGlobal::getLanguageImage($param);
            }),
            new TwigFunction('has_t', function($text) {
            	return KaGlobal::getRegistry()->get('language')->has($text);
            }),
            // getting links inside a template
            //
            new TwigFunction('linka', function($route, $params = '', $is_js = false) {
            	$registry = \KaGlobal::getRegistry();
            	$link = $registry->get('url')->linka($route, $params, true, $is_js);
        		return $link;
            }),
        );
    }
    
    public function getName()
    {
        return 'Ka Extensions';
    }
}