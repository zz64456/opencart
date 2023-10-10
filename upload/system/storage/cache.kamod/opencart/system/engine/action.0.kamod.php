<?php
/*
	This file was inherited by kamod.
	More information can be found at https://www.ka-station.com/kamod
	
	Original file: extension/ka_extensions/kamod/system/engine/action.php
*/
/*
	This file was generated by kamod.
	More information can be found at https://www.ka-station.com/kamod
	
	Original file: extension/ka_extensions/kamod/system/engine/action.php
*/

/*
	$Project$
	$Author$

	$Version$ ($Revision$)
*/

namespace extension\ka_extensions\engine;

require_once(__DIR__ . '/action.1.kamod.php');

class Action extends \Opencart\System\Engine\Action_kamod  {

	protected string $route;
	protected string $class_route;
	protected string $method;
	
	protected $parent_action = null;

	/*
		Here we detect the class route and method from the 'route'. Example:
		original route: extension/ka_extensions/extensions|install
		class route: extension/ka_extensions/extensions
		method: install
		
	*/
	public function __construct(string $route) {
	
		if (strpos($route, 'extension/') === 0) {
			if (version_compare(VERSION, '4.0.2.0', '>=')) {
				$this->route = preg_replace('/[^a-zA-Z0-9_\.\/]/', '', $route);
				$pos = strrpos($this->route, '.');
			} else {
				$this->route = preg_replace('/[^a-zA-Z0-9_|\/]/', '', $route);
				$pos = strrpos($this->route, '|');
			}

			if ($pos === false) {
				$this->class_route = $this->route;
				$this->method      = 'index';
			} else {
				$this->class_route = substr($this->route, 0, $pos);
				$this->method      = substr($this->route, $pos + 1);
			}
		}

		$parent_class = get_parent_class($this);
		$this->parent_action = new $parent_class($route);
	}
	
	
	public function getId(): string {
		return $this->parent_action->getId();
	}	
	
	
	public function execute(\Opencart\System\Engine\Registry $registry, array &$args = []): mixed {
	
		$route = $this->getId();
	
		if (strpos($route, 'extension/') === 0 && strpos($route, '__') === false) {

			// similar code is used in detecting the model
			$route = str_replace('/','\\', $this->class_route);
			$pos = strpos($route, '\\', 10); 
			$extension_path = substr($route, 0, $pos + 1);
			$pos_class      = strrpos($route, '\\');
			$class_path     = '';
			if (!empty($pos_class)) {
				$class_path = substr($route, $pos + 1, $pos_class - $pos);
				$pos = $pos_class;
			}
			$class_name = substr($route, $pos + 1);
			$class = $extension_path . $class_path . 'controller' . str_replace('_', '', $class_name);

			if (!$GLOBALS['autoloader']->ka_loadAreaClass($class)) {
				$file = str_replace('\\', '/', $extension_path . strtolower($registry->get('config')->get('application')) .'\controller\\' . $class_path . $class_name) . '.php';

				if (file_exists(DIR_OPENCART . $file)) {
					include_once(\VQModKa::modCheck(DIR_OPENCART . $file));
				}
			} else {
//				echo "File was not found (" . DIR_OPENCART . "$file)";
			}
				
			if (class_exists($class)) {
				$controller = new $class ($registry);
				
				if (is_callable([$controller, $this->method])) {
					return call_user_func_array([$controller, $this->method], $args);
				}
			} else {
//				echo "class was not found:$class\n";
			}
		}
		
		return $this->parent_action->execute($registry, $args);
	}
	
	function __call($name, $args) {
		return call_user_func_array(array($this->parent_action, $name), $args);
	}
}