<?php
/*
	This file was inherited by kamod.
	More information can be found at https://www.ka-station.com/kamod
	
	Original file: extension/ka_extensions/kamod/system/engine/loader.php
*/
/*
	$Project$
	$Author$

	$Version$ ($Revision$)
*/

namespace extension\ka_extensions\engine;

require_once(__DIR__ . '/loader.1.kamod.php');

class Loader extends \Opencart\System\Engine\Loader_kamod  {

	protected $ka_render_depth = array(0);
	protected $ka_tmp_view_data;
	protected $ka_tmp_view_route;

	public function __construct(\Opencart\System\Engine\Registry $registry) {
		\extension\ka_extensions\KaGlobal::init($registry);
		parent::__construct($registry);
	}
	
	public function controller(string $route, mixed ...$args): mixed {
		array_unshift($this->ka_render_depth, 0);
		
		array_unshift($args, $route);
		$result = call_user_func_array([parent::class, 'controller'], $args);
		
		array_shift($this->ka_render_depth);
		
		return $result;
	}
	
	
	public function getTmpViewData() {
		return $this->ka_tmp_view_data;
	}
	
	
	public function getTmpViewRoute() {
		return $this->ka_tmp_view_route;
	}	

	public function view(string $route, array $data = [], string $code = ''): string {

		if ($this->isRenderDisabled()) {
			$this->ka_tmp_view_data  = $data;
			$this->ka_tmp_view_route = $route;
			return '';
		}
		
		return parent::view($route, $data, $code);
	}
	
	
	/*
		$route - example: "extension/ka_extensions/vendor"
	*/
	public function kamodel($route) {

		// Sanitize the call
		
		$route = preg_replace('/[^a-zA-Z0-9_\/]/', '', (string)$route);
		
		$model_name = 'model_' . str_replace('/', '_', (string)$route);

		if (!$this->registry->has($model_name)) {
			$route = str_replace('/','\\', $route);
			$pos = strpos($route, '\\', 10); 
			$extension_path = substr($route, 0, $pos + 1);
			$pos_class      = strrpos($route, '\\');
			$class_path     = '';
			if (!empty($pos_class)) {
				$class_path = substr($route, $pos + 1, $pos_class - $pos);
				$pos = $pos_class;
			}
			$class_name = substr($route, $pos + 1);
			$class = $extension_path . $class_path . 'Model' . str_replace('_', '', $class_name);
			
			if (!$GLOBALS['autoloader']->ka_loadAreaClass($class)) {
				$file  = str_replace('\\', '/', $extension_path . strtolower($this->registry->get('config')->get('application')) . '\model\\' . $class_path . $class_name) . '.php';
			
				if (file_exists(DIR_OPENCART . $file)) {
					include_once(\VQModKa::modCheck(DIR_OPENCART . $file));
				}
			} else {
//				echo "File was not found (" . DIR_OPENCART . "$file)";
			}
				
			if (class_exists($class)) {
				$proxy = new $class ($this->registry);
				$this->registry->set($model_name, $proxy);
			} else {
//				echo "class was not found:$class\n";
			}
		}

		return $this->registry->get($model_name);
	}
	
	
	public function isRenderDisabled() {
		if (empty($this->ka_render_depth[0])) {
			return false;
		}
		return true;
	}
	
	public function disableRender() {
		$this->ka_render_depth[0]++;
		return;
	}

	public function enableRender() {
		$this->ka_render_depth[0]--;
		$this->ka_render_depth[0] = max($this->ka_render_depth[0], 0);
		return;		
	}
}