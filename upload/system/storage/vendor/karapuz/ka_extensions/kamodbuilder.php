<?php
/*
	$Project$
	$Author$

	$Version$ ($Revision$)
*/

namespace extension\ka_extensions;

if (class_exists('\VQMod')) {
	class_alias('\VQMod', '\VQModKa');
} else {
	include_once(__DIR__ . '/vqmodka.php');
}

include_once(__DIR__ . '/directory.php');
include_once(__DIR__ . '/kapatch.php');

class KamodBuilder {

	// format version for checking patches compatibility and other kamod-specific details
	const KAMOD_VERSION = '1.0'; // <backward compatibility degradation>.<improvements/fixes>
	const LOG_FILENAME  = 'kamod.log';
	const LOG_ERRORS_FILENAME = 'kamod.errors.log';
	
	// directories with a trailing slash
	//
	protected $root_dir;
	protected $module_dirs;
	protected $ka_cache_dir;

	// template-related directories
	//

	// short name of kamod templates cache directory
	protected $templates_dir = 'templates'; 

	// this directory is for patched/inherited templates. It is located inside ka_cache_dir (full dir)
	protected $ka_twig_cache_dir;

	// customized files saved from design theme (full dir)
	protected $theme_cache_dir;
	
	protected $kapatch;
	
	// these are directories with keys 'admin','catalog','system' and values of real directory names
	//
	protected $service_dirs;
	
	protected $mod_comment;
	
	public function log($msg, $type = 'I') {

		$msg = date("Y-m-n M:i:s: ") .  $msg . "\n";
		
		if ($type == 'E') {
			file_put_contents(DIR_LOGS . static::LOG_ERRORS_FILENAME, $msg, FILE_APPEND);	
		}
		
		file_put_contents(DIR_LOGS . static::LOG_FILENAME, $msg, FILE_APPEND);
	}

	/*
		$root_dir         - store root directory (absolute path. Ends with the slash, equals to DIR_OPENCART in OC4).
		$ka_cache_dir     - absolute path to the directory where the cache is stored. Ended with a slash.
		
		$templates_dir    - short directory name where we store templates (inside kamod cache dir).
		$theme_cache_dir  - full directory name where customized theme files are stored (with trail slash)
	*/
	function __construct($root_dir, $ka_cache_dir, $templates_dir, $theme_cache_dir) {
	
		$this->root_dir        = $root_dir;
		$this->ka_cache_dir    = $ka_cache_dir;

		$this->templates_dir   = $templates_dir;
		$this->theme_cache_dir = $theme_cache_dir;
		
		if (!file_exists($this->ka_cache_dir)) {
			if (!mkdir($this->ka_cache_dir, 0777)) {
				$error = "Cannot create kamod cache directory: " . $this->ka_cache_dir;
				$this->log($error);
				throw new \Exception($error);
			}
		}
		
		$this->ka_cache_dir = $ka_cache_dir;
		
		$this->kapatch = new KaPatch($this->root_dir, $this);
	}

	/*	
		$module_dirs      - list of directories where we will search for module overridings
		                    all paths are relative to $root dir. Starts without slash. Ends with slash
	*/
	function addModuleDirs($module_dirs) {
		$this->module_dirs = array_merge($this->module_dirs, $module_dirs);
	}
	
	function setModuleDirs($module_dirs) {
		$this->module_dirs = $module_dirs;
	}

	function getModuleDirs() {
		return $this->module_dirs;
	}
	
	/*
		$module_root_dir - where we should search for child classes. No slash in the end.
		
		Return main files from the current_directory
	*/
	protected function collectDirectoryMainFiles($module_root_dir, $current_dir, $file_ext) {
	
		if (empty($current_dir)) {
			$current_dir = $module_root_dir;
		}
	
		$main_files = array();

		// loop through files in catalog
		$files = glob($current_dir . '/*');

		// loop through a list of main files
		foreach ($files as $file) {
		
			if (is_dir($file)) {
				$main_files = array_merge($main_files, $this->collectDirectoryMainFiles($module_root_dir, $file, $file_ext));
				continue;
			}
			
			$pathinfo = pathinfo($file);
			$is_xml = false;
			if ($pathinfo['extension'] == 'xml') {
				$is_xml = true;
				$pathinfo = pathinfo($pathinfo['filename']);
			}			
			if (!in_array($pathinfo['extension'], $file_ext)) {
				continue;
			}
			
			$target_file = substr($file, strlen($module_root_dir . '/'));
			if ($is_xml) {
				// remove xml extension from the file path
				$target_file = substr($target_file, 0, -4);
			}			
			// parts
			$parts = explode('/', $target_file);
			
			if ($parts[0] == 'storage') {
				array_shift($parts);
				$real_target_file = DIR_STORAGE . implode('/', $parts);
			
			} else {
				if (!empty($this->service_dirs[$parts[0]])) {
					$parts[0] = $this->service_dirs[$parts[0]];
				}

				$real_target_file = $this->root_dir . implode('/', $parts);
			}

			if (!file_exists($real_target_file)) {
			
				// it is possible to have child twig files for non-existing main files. 
				// 
				if (!in_array('twig', $file_ext)) {
					$this->log("Target file was not found:" . $real_target_file);
					continue;
				}
			}
			
			if (empty($main_files[$target_file] )) {
				$main_files[$target_file] = array(
					'children' => array(),
					'patches' => array(),
				);
			}
			
			$mod_file = substr($file, strlen($this->root_dir));

			if ($is_xml) {
				$main_files[$target_file]['patches'][] = $mod_file;
			} else {
				$main_files[$target_file]['children'][] = $mod_file;
			}
		}
		
		return $main_files;
	}

	
	protected function markCacheValid() {
		$this->log('Cache marked valid at ' . date('c'));
		
		$contents = array();
		$contents[] = 'built at ' . date("c");
		$contents[] = "";
		$contents[] = "The kamod cache is required for working the store properly. Do not remove this directory.";
		$contents[] = "Please refer to https://www.ka-station.com/kamod for details";
		
		file_put_contents($this->ka_cache_dir . 'valid.kamod', implode("\n", $contents));
	}


	public function isCacheEmpty() {
	
		$files = glob($this->ka_cache_dir . '*.kamod');
		
		if (empty($files)) {
			return true;
		}
		
		return false;
	}

	
	public function emptyCache() {
	
		Directory::clearDirectory($this->ka_cache_dir, array('locked.kamod'));
		$this->log('Cache cleared at ' . date('c'));	
		
		if (file_exists(DIR_LOGS . static::LOG_ERRORS_FILENAME)) {
			unlink(DIR_LOGS . static::LOG_ERRORS_FILENAME);
		}
	}

	
	public function isCacheValid() {

		$marker_file = $this->ka_cache_dir . 'valid.kamod';
		
		if (!file_exists($marker_file)) {
			return false;
		}

		$installation_time = filemtime(DIR_EXTENSION . 'ka_extensions/install.json');
		$validation_time = filemtime($marker_file);
		if ($installation_time > $validation_time) {
			return false;
		}
		
		return true;
	}
	
	
	public function markCacheInvalid() {
	
		$this->log('Cache marked valid at ' . date('c'));
	
		// add an 'invalid' marker
		//
		$contents = <<<CNTTXT
WARNING: The kamod cache was marked as invalid. The entire modifications cache has to be rebuilt to operate properly.
Please refer to https://www.ka-station.com/kamod for details
CNTTXT;
		file_put_contents($this->ka_cache_dir . 'invalid.kamod', $contents);
	
		// remove the valid marker
		//
		$marker_file = $this->ka_cache_dir . 'valid.kamod';
		
		if (file_exists($marker_file)) {
			unlink($marker_file);
		}

		// remove the locker
		//
		$this->unlockCache();
	}
	
	
	protected function lockCache() {
	
		$marker_file = $this->ka_cache_dir . 'locked.kamod';
	
		$contents = <<<CNTTXT
WARNING: The kamod cache is being rebuilt.
Please refer to https://www.ka-station.com/kamod for details
CNTTXT;

		file_put_contents($marker_file, $contents);
	}
	
	
	protected function isCacheLocked() {
	
		$marker_file = $this->ka_cache_dir . 'locked.kamod';

		// when the lock is completely disabled, some pages like Theme Editor may show file writing/deleting failures
		// from kamod because two processes work concurrently
		//
		$lock_period = 30;
		if (defined('KAMOD_DEBUG')) {
			$lock_period = 3;
		}
		
		if (file_exists($marker_file)) {
		
			if (time() - filemtime($marker_file) > $lock_period) {
				$this->unlockCache();
				return false;
			}
			
			return true;
		}
		
		return false;
	}
	
	
	protected function unlockCache() {
		$marker_file = $this->ka_cache_dir . 'locked.kamod';
	
		if (file_exists($marker_file)) {
			unlink($marker_file);
		}
	}
	
	
	/*
		$module_dirs - array of module directories where we allow to search for child files.
	*/
	protected function collectModuleMainFiles($module_dirs) {
	
		$php_main_files  = array();
		$twig_main_files = array();
		
		if (empty($module_dirs)) {
			return array();
		}

		// this loop will generate a list of main_files and their children
		//
		foreach ($module_dirs as $dir) {
		
			$module_root_dir = $this->root_dir . $dir;

			$php_main_files = array_merge_recursive($php_main_files, $this->collectDirectoryMainFiles($module_root_dir, '', array('php')));
			$twig_main_files = array_merge_recursive($twig_main_files, $this->collectDirectoryMainFiles($module_root_dir, '', array('twig')));
		}
		
		$result = array(
			'php_main_files'  => $php_main_files,
			'twig_main_files' => $twig_main_files
		);
		
		return $result;
	}
	
	/*
		Throws an exception on failure
	*/
	public function buildCache() {

		$success = false;

		if ($this->isCacheLocked()) {
			$this->log('Kamod cache is locked. We cannot proceed with building.');
			throw new KamodLockedException();
		}
		$this->lockCache();

		$this->emptyCache();
	
		$this->log('Kamod rebuild started at ' . date('c'));

		try {
			// collect main files to inherit
			//
			$result = $this->collectModuleMainFiles($this->module_dirs);
			
			if (!empty($result)) {
			
				// generate php chain
				//
				$this->generatePhpChains($result['php_main_files']);
				
				// generate twig chain
				//
				$this->generateTwigChains($result['twig_main_files']);
				
				$this->markCacheValid();
			}
			
		} catch (\Exception $e) {
		
			$msg = "kamod cache build failed with error: " . $e->getMessage();
			$this->log($msg);
		
			$this->unlockCache();
			
			throw new KamodFailedException($msg);
		}
		
		$this->log('Cache rebuild finished at ' . date('c'));
		
		$this->unlockCache();
	}

	
	protected function generatePhpChains($main_files) {
	
		if (empty($main_files)) {
			return;
		}
		
$php_comment = <<<CMTXT
/*
	This stub file was generated by kamod. You can find parent files in the same directory.
	More information can be found at https://www.ka-station.com/kamod
*/

CMTXT;

$mod_comment = <<<CMTXT
/*
	This file was patched by kamod.
	More information can be found at https://www.ka-station.com/kamod
		
	Original file:
	%original_file%
	
	The following patches were applied:
	%file_list%
	
*/

CMTXT;

		foreach ($main_files as $file => $fdata) {

			// check if the main file has any children
			// or we need just to apply xml patches
			//
			if (empty($fdata['children'])) {

				// get main file information including its contents
				//
				$real_file = $this->getActualPhpFile($file);
				$main_info = $this->parsePhpFile($real_file);
				
				$file_path    = str_replace('\\', '/', $main_info['namespace']);
				$target_file  = strtolower($file_path . '/' . $main_info['class'] . '.php');

				// apply kapatch to that file
				//
				$contents = implode("", $main_info['file_lines']);
				$contents = $this->kapatch->applyXmlPatches($contents, $fdata['patches']);

				// save the file
				//
				$applied_patches = implode("\n\t", $fdata['patches']);
				
				$contents = "<?php\n" 
					. str_replace(['%file_list%', '%original_file%'], [$applied_patches, $file], $mod_comment) 
					. '?>' 
					. $contents;
				Directory::checkDirectory($this->ka_cache_dir . $target_file);
				file_put_contents($this->ka_cache_dir . $target_file, $contents);
				
				continue;
			}
			
			// sort classes according to their priorities
			//
			$fdata['children'] = $this->arrangeChildren($fdata['children']);
			$index = count($fdata['children']);
			
			// copy the main file
			//
			$main_info = $this->copyPhpFile($file, $index);

			// apply patch changes if they are available
			//
			if (!empty($fdata['patches'])) {
			
				$contents = file_get_contents(\VQModKa::modCheck($main_info['file']));
				
				$applied_patches = implode("\n\t", $fdata['patches']);
				
				$contents = $this->kapatch->applyXmlPatches($contents, $fdata['patches']);
				$contents = "<?php\n" 
					. str_replace(['%file_list%', '%original_file%'], [$applied_patches, $file], $mod_comment) 
					. '?>' 
					. $contents;
				
				file_put_contents($main_info['file'], $contents);
			}

			// copy children files
			//
			$parent_info = $main_info;
			foreach ($fdata['children'] as $child) {
				$index--;
				$parent_info = $this->copyPhpFile($child, $index, $parent_info);
			}
			
			// generate the last modificaiton file
			//
			$file_path      = str_replace('\\', '/', $main_info['namespace']);
			$main_file_info = pathinfo($main_info['main_file']);
			$parent_file    = basename($parent_info['file']);
			$file           = strtolower($file_path . '/' . $main_info['org_class'] . '.php');
			
			$contents = [];
			$contents[] = '<?php';
			
			$contents[] .= $php_comment;

			if (!empty($main_info['namespace'])) {
				$contents[] = "namespace " . $main_info['namespace'] . ';';
			}

			$contents[] = "require_once(__DIR__" . " . '/" . $parent_file . "');";
			
			$contents[] = "class $main_info[org_class] extends $parent_info[class] {";
			$contents[] = "}";

			if (!empty($main_info['area'])) {
				$file = $main_info['area'] . '/' . $file;
			}
			
			if (!empty($main_info['namespace'])) {
				if (preg_match("/^model|controller\/.*/i", $main_info['org_class'], $matches)) {
					$class_suffix = preg_replace("/model|controller/i", "", $main_info['org_class']);
					$class_prefix = preg_replace("/(model|controller)(.*)/i", "$1", $main_info['org_class']);
					$contents[] = "class_alias(__NAMESPACE__ . '\\" . $main_info['org_class'] . "', '\\" . $class_prefix . str_replace(['\\','_'],['',''], $main_info['namespace'] . $class_suffix) . "');";
				}
			}
			
			file_put_contents($this->ka_cache_dir . $file, implode("\n", $contents));
		}
	}
	

	protected function generateTwigChain($file, $fdata) {
	
		if (empty($fdata['children'])) {
			$real_file = $this->getActualTwigFile($file);

			$contents = file_get_contents(\VQModKa::modCheck($real_file));
			
			// apply a patch to that file
			//
			$contents = $this->kapatch->applyXmlPatches($contents, $fdata['patches']);

			// save the file
			//
			$applied_patches = implode("\n\t", $fdata['patches']);
			
			$contents = str_replace(['%file_list%', '%original_file%'], [$applied_patches, $file], $this->mod_comment) . $contents;
			
			$file = $this->backFromServiceDir($file);
			Directory::checkDirectory($this->ka_twig_cache_dir . $file);
			file_put_contents($this->ka_twig_cache_dir . $file, $contents);
			
			return;
		}
			
		// sort classes according to their priorities
		//
		$fdata['children'] = $this->arrangeChildren($fdata['children']);

		$index = count($fdata['children']);

		// copy the main file
		//
		$main_info = $this->copyTwigFile($file, $index);
		
		// copy children files
		//
		$parent_info = $main_info;
		foreach ($fdata['children'] as $child) {
			$index--;
			$parent_info = $this->copyTwigFile($child, $index, $parent_info);
		}
	
	}
	
	protected function getStores() {
		
		$stores = array();
	
		$dirs = glob($this->theme_cache_dir . '*', GLOB_ONLYDIR);
	
		if (empty($dirs)) {
			return $stores;
		}
		
		foreach ($dirs as $d) {
			$d = basename($d);		
			$stores[$d] = $d;
		}
		
		return $stores;		
	}
	
	
	protected function generateTwigChains($main_files) {

		if (empty($main_files)) {
			return;
		}

		$this->mod_comment = <<<SMTXT
{#
	The file was patched by kamod
	More information can be found at https://www.ka-station.com/kamod
	
	Original file:
	%original_file%
	
	The following patches were applied:
	%file_list%
	
#}
SMTXT;

		$stores = $this->getStores();
		
		$root_dir          = $this->root_dir;
		$ka_twig_cache_dir = $this->ka_cache_dir . $this->templates_dir . '.default/';
		
		foreach ($main_files as $file => $fdata) {
			
			$this->root_dir          = $root_dir;
			$this->ka_twig_cache_dir = $ka_twig_cache_dir;
			
			$this->generateTwigChain($file, $fdata);		

			// generate cache for store-specific templates
			// for now they can occur from admin theme modififcations
			//
			if (!empty($stores)) {
				foreach ($stores as $store) {
					$this->root_dir = $this->theme_cache_dir . $store . '/';
					
					if (!file_exists($this->root_dir . $file)) {
						continue;
					}
					
					$this->ka_twig_cache_dir = $this->ka_cache_dir . $this->templates_dir . '.' . $store . '/';
					
					$this->generateTwigChain($file, $fdata);		
				}
			}
		}
		
		$this->root_dir          = $root_dir;
		$this->ka_twig_cache_dir = $ka_twig_cache_dir;
	}
	
	
	/*
		Creates a copy of file in ka_cache directory
		
		$module_file - Example: admin/controller/catalog/attribute_group.php.
		               The base is standard directory admin/catalog/etc. (may differ from the real one)
		
		Changes namespace, parent class path and other contents accordingly
	*/
	protected function copyPhpFile($module_file, $index = 0, $parent_info = []) {

		$mod_module_file = $this->getActualPhpFile($module_file);

		// get a module file information
		//
		$mod_file_info = $this->parsePhpFile($mod_module_file);	
		if (empty($mod_file_info['class'])) {
			$this->log("Class was not found in: " . $mod_module_file);
			return false;
		}
		$file_lines = $mod_file_info['file_lines'];
		
		// find the source file (when the parent is not set, the mod file is the same as the source file)
		//
		if (empty($parent_info)) {
			$main_file = $module_file;
		} else {
			$main_file = $parent_info['main_file'];
		}

		// get detailed path information about the main_file
		//
		$main_file_info = pathinfo($main_file);

		$dirparts = explode('/', $main_file);
		if ($dirparts[0] == 'extension') {
			$main_file_info['area'] = $dirparts[2];
		}
		
		// set class
		//
		if (empty($parent_info)) {
			$new_class = $mod_file_info['class'] . "_kamod";
		} else {
			$new_class = $mod_file_info['class'];
		}
		
		$parent_class = '';
		if (!empty($parent_info) && !empty($mod_file_info['parent_class'])) {
			$parent_class = $parent_info['class'];
		} elseif (!empty($mod_file_info['parent_class'])) {
			$parent_class = $mod_file_info['parent_class']; 
		}

		if (!empty($parent_class) && !empty($parent_class)) {
			$class = 'class ' . $new_class . ' extends ' . $parent_class . " " . $mod_file_info['tail'];
		} else {
			$class = 'class ' . $new_class . " " . $mod_file_info['tail'];
		}

		$file_lines[$mod_file_info['class_row']] = $class;

		if (!empty($parent_info['file'])) {
			$new_lines = array_slice($file_lines, 0, $mod_file_info['class_row']);
			$new_lines[] = "require_once(__DIR__" . " . '/" . basename($parent_info['file']) . "');\n\n";
			$new_lines = array_merge($new_lines, array_slice($file_lines, $mod_file_info['class_row']));
			$file_lines = $new_lines;
		}
		$file_lines[0] .= <<<CMTXT
/*
	This file was inherited by kamod.
	More information can be found at https://www.ka-station.com/kamod
	
	Original file: $module_file
*/

CMTXT;

		if (empty($parent_info)) {
			$main_namespace = $mod_file_info['namespace'];
			$main_class     = $mod_file_info['class'];
		} else {
			$main_namespace = $parent_info['main_namespace'];
			$main_class     = $parent_info['org_class'];
		}
		
		$target_file = strtolower(str_replace('\\', '/', $main_namespace) . '/' . $main_class . '.' . $index . '.kamod.php');
		
		if (!empty($main_file_info['area'])) {
			$target_file = $main_file_info['area'] . '/' . $target_file;
		}
		
		$target_file = $this->ka_cache_dir . $target_file;
		Directory::checkDirectory($target_file);
		
		file_put_contents($target_file, $file_lines);
		
		$file_info = array(
			'org_class'      => $main_class,
			'file'           => $target_file,
			'namespace'      => $mod_file_info['namespace'],
			'main_file'      => $main_file,
			'main_namespace' => $main_namespace,
		);
		$file_info['class'] = '\\';
		if (!empty($mod_file_info['namespace'])) {
			$file_info['class'] .= $mod_file_info['namespace'] . '\\';
		}
		$file_info['class'] .= $new_class;
		
		if (!empty($main_file_info['area'])) {
			$file_info['area'] = $main_file_info['area'];
		}
		
		return $file_info;
	}
	

	/*
		Returns array of file information and its contents
	*/
	protected function parsePhpFile($file) {

		if (!file_exists($file)) {
			return false;
		}
	
		$file_lines = file(\VQModKa::modCheck($file));
		
		// update namespace
		//
		$search = array(
			'namespace' => true, // anything before a code
			'class'     => true  // class or function
		);
		
		$found = array(
			'namespace' => '',
			'class'     => '',
			'tail'      => '',
		);

		// find lines with namespace and classes
		//
		foreach ($file_lines as $flk => $fline) {

			if (preg_match("/class_alias\(/", $fline, $matches)) {
				$file_lines[$flk] = "";
			}
		
			if ($search['namespace']) {
				if (preg_match("/^\s*namespace (.*);/", $fline, $matches)) {
					$search['namespace'] = false;
					$found['namespace'] = trim($matches[1]);
					$found['namespace_row'] = $flk;
				}
			}
			
			if ($search['class']) {
				if (preg_match("/^\s*class ([^\s\{]*)(.*)/s", $fline, $matches)) {
					$search['namespace'] = false;
					$search['class']     = false;
					$found['class']      = trim($matches[1]);
					$found['tail']       = $matches[2];

					if (preg_match('/class(.*) extends ([^\s\{]*)(.*)/s', $fline, $submatches)) {
						$found['class']        = trim($submatches[1]);
						$found['parent_class'] = trim($submatches[2]);
						$found['tail']         = $submatches[3];
					}
					$found['class_row'] = $flk;
					break;
				}
				
				if (preg_match("/^\s*function /", $fline, $matches)) {
					$search['namespace'] = false;
					$search['class']     = false;
					break;
				}
			}
		}

		$found['file_lines'] = $file_lines;
		
		return $found;
	}
	

	/*
		$file - a short file name with the service directory name
		
		returns a file path to the real file, it can be in the cache directory already.
	*/
	protected function getActualPhpFile($file) {
	
		// find the file to apply and store it as $mod_module_file
		//
		$parts = explode('/', $file);
		$area = $parts[0];

		if ($area == 'storage') {
			unset($parts[0]);
			$real_file = DIR_STORAGE . implode('/', $parts);
		
		} elseif (!empty($this->service_dirs[$area])) {
			unset($parts[0]);
			$real_file = $this->root_dir . $this->service_dirs[$area] . '/' . implode('/', $parts);
		} else {
			$real_file = $this->root_dir . $file;
		}

		if (file_exists($this->ka_cache_dir . $file)) {
			$real_file = $this->ka_cache_dir . $file;
		}
		
		return $real_file;
	}
	
	
	protected function getActualTwigFile($file) {

		// get the full path to the module file
		//
		if (file_exists($this->ka_cache_dir . $file)) {
			$real_file = $this->ka_cache_dir . $file;
			return $real_file;
		}
	
		// this path to a theme cache where we keep user modified template files
		//
		if (file_exists($this->theme_cache_dir . $file)) {
			$real_file = $this->theme_cache_dir . $file;
			return $real_file;
		}
		
		// find the file to apply and store it as $mod_module_file
		//
		$parts = explode('/', $file);
		$area = $parts[0];

		if (!empty($this->service_dirs[$area])) {
			unset($parts[0]);
			$real_file = $this->root_dir . $this->service_dirs[$area] . '/' . implode('/', $parts);
		} else {
			$real_file = $this->root_dir . $file;
		}

		return $real_file;
	}
		
	
	/*
		Creates a modified copy of the file in the modification directory
		
		$module_file - a relative path to the source file
		
	*/
	protected function copyTwigFile($module_file, $index = 0, $parent_info = []) {

		$mod_module_file = $this->getActualTwigFile($module_file);
		
		if (empty($parent_info)) {
			$main_file = $module_file;
		} else {
			$main_file = $parent_info['main_file'];
		}
		
		// get the main twig file name
		//
		$main_file_info = pathinfo($main_file);

		if (!empty($index)) {
			$target_file = $main_file_info['dirname'] . '/' . $main_file_info['filename'] . '.' . $index . '.kamod.twig';
		} else {
			$target_file = $main_file;
		}
		
		// read the file to an array
		//
		if (file_exists($mod_module_file)) {
			$file_contents = file_get_contents(\VQModKa::modCheck($mod_module_file));
		} else {
			$file_contents = <<<CNT
{# 
	The original file does not exist, but we have to generate it as a base for children 
	that can use parent() function
#}
CNT;
		}

		// collect properties of the file by walking through the lines
		//
		$is_block_found = false;
		
		if (preg_match("/\{%\s*endblock[\s]*%\}/mis", $file_contents, $matches)) {		
			$is_block_found = true;
		}
		
		// generate new lines for the copy
		//
		$new_lines = array();
		
		$new_lines[] = <<<SMTXT
{#
	The file was generated by kamod
	More information can be found at https://www.ka-station.com/kamod
	
	Original file: $module_file	
#}
SMTXT;
		if (!empty($parent_info['file'])) {
		
			$dirname = $main_file_info['dirname'];
			$parent_twig_file = $dirname . '/' . $main_file_info['filename'] . '.' . ($index+1) . '.kamod.twig';
			$parent_twig_file = $this->backFromServiceDir($parent_twig_file);
			
			$new_lines[] = '{% extends "' . $parent_twig_file . '" %}';
			$new_lines[] = '';
		}

		if (!$is_block_found) {
			$new_lines[] = '{% block parent_template %}';
			$new_lines[] = $file_contents;
			$new_lines[] = '{% endblock %}';
		} else {
			$new_lines[] = $file_contents;
		}

		if (!empty($index)) {
			$target_file = $main_file_info['dirname'] . '/' . $main_file_info['filename'] . '.' . $index . '.kamod.twig';			
		} else {
			$target_file = $main_file;
		}

		$target_file = $this->backFromServiceDir($target_file);
		
		Directory::checkDirectory($this->ka_twig_cache_dir . $target_file);
		
		$file_lines = implode("\n", $new_lines);
		file_put_contents($this->ka_twig_cache_dir . $target_file, $file_lines);
		
		$file_info = array(
			'file'      => $target_file,
			'main_file' => $main_file,
		);
		
		return $file_info;
	}
	
	
	/*
		Arrange class inheritances according to their preferences
		returned via tags in the module class code.
	*/
	protected function arrangeChildren($files) {

		// get 'new' array with all files
		//
		$new = array();
		foreach ($files as $file) {
			$module_code = $this->getModuleCode($file);
			$new[$module_code] = $file;
		}
		
		if (count($new) < 2) {
			return $new;
		}

		// walk through all items and sort them by 'after' position
		//
		$all_keys = array_keys($new);
		
		foreach ($all_keys as $ak) {
		
 			$after = $this->getModulesOrder($ak, $new[$ak], 'after');
			if (empty($after)) {
				continue;
			}

			$file = $new[$ak];
			unset($new[$ak]);
			
			// insert the file after a specific module code
			//
			$best = 0;
			$new_keys = array_keys($new);
			foreach ($after as $v) {
				$pos = array_search($v, $new_keys);
				if ($pos === false) {
					continue;
				}
				if ($pos > $best) {
					$best = $pos;
				}
			}
			$element = array(
				$ak => $file,
			);
			$new = Arrays::insertAfterKey($new, $new_keys[$best], $element);
		}
		
		// walk through all items and sort them by 'before' position
		//
		foreach ($all_keys as $ak) {

			$before = $this->getModulesOrder($ak, $new[$ak], 'before');
			
			if (empty($before)) {
				continue;
			}

			$file = $new[$ak];
			unset($new[$ak]);

			// insert the file before a specific module code
			//
			$best = count($new);
			$new_keys = array_keys($new);
			foreach ($before as $v) {
				$pos = array_search($v, $new_keys);
				if ($pos === false) {
					continue;
				}
				if ($pos < $best) {
					$best = $pos;
				}
			}
			
			$element = array(
				$ak => $file,
			);
			$new = Arrays::insertBeforeKey($new, $new_keys[$best], $element);
		}
		
		return $new;
	}
	
	/*
		Return module code for the file
	*/
	protected function getModuleCode($file) {
	
		$code = preg_replace("/^[\/]*extension\/([^\/]*)\/.*/is", "$1", $file);
		
		return $code;
	}
	

	/*
		Returns an array of module codes taken from the extension manifest file.
		
		Parameters:
			$file  - a path to the class file
			$order - 'before' or 'after'
		
		The following tags are supported in files:
			install_before
			install_after
	*/
	protected function getModulesOrder($code, $file, $order = '') {

		$result = array();
		
		// read the module manifest file
		//
		$manifest = DIR_EXTENSION . $code . '/install.json';
		if (!file_exists($manifest)) {
			$this->log("Manifest file was not found: " . $manifest);
			return $result;
		}
		
		// parse ini settings
		//
		$ini = json_decode($manifest, true);

		$value = '';
		if ($order == 'before') {
			if (!empty($ini['install_before'])) {
				$value = $ini['install_before'];
			}
		} elseif ($order == 'after') {
			if (!empty($ini['install_after'])) {
				$value = $ini['install_after'];
			}
		}		
		if (empty($value)) {
			return $result;
		}

		// parse the inheritance priority string
		//
		$modules = explode(",", $value);
		
		foreach ($modules as $m) {
			$m = trim($m);
			if (empty($m)) {
				continue;
			}
			
			$result[] = $m;
		}
		
		return $result;
	}


	public function setServiceDirs($dirs) {
		$this->service_dirs = $dirs;
	}
	
	
	protected function backFromServiceDir($file) {
	
		$parts = explode('/', $file);
		
		if (!empty($this->service_dirs[$parts[0]])) {
			$parts[0] = $this->service_dirs[$parts[0]];
			$file = implode('/', $parts);
		}
		
		return $file;
	}
}