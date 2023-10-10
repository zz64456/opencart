<?php
/*
	$Project$
	$Author$

	$Version$ ($Revision$)

	here we come from system/vendor.php. That file is loaded at the very beginning of opencart startup.
*/

namespace extension\ka_extensions;

// safe mode, disable all kamod features for the current admin session
//
if (defined('APPLICATION') && APPLICATION == 'Admin') {
	if (!empty($_COOKIE['ka_safe_mode'])) {
		return;
	}

	if (!empty($_GET['route']) && $_GET['route'] == 'ka_safe_mode') {
		setcookie("ka_safe_mode", 1);
		return;
	}
}

// if the extension was included twice for some reason
if (class_exists('KamodLockedException')) {
	return;
}

class KamodLockedException extends \Exception {};
class KamodFailedException extends \Exception {};

// replace the original Opencart loader with our loader supporting kamod cache
//
try {

	include_once(__DIR__ . '/autoloader.php');
	$autoloader = new Autoloader($autoloader);

} catch (KamodLockedException $e) {

	if (defined('KAMOD_DEBUG')) {
		// it is ok to skip the locked cache in development
	} elseif (APPLICATION == 'Admin') {
		echo "Kamod cache is locked. The store is closed.";
	} else {
//		die('We are rebuilding the store cache. Please try again in several minutes.');
	}

} catch (KamodFailedException $e) {

	if (APPLICATION == 'Admin') {
		echo "Kamod malfunction: " . $e->getMessage();
	} else {
//		die('Sorry, the store is not operable at this moment.');
	}
	
} catch (\Throwable $e) {

	// record the failure event to a log file
	file_put_contents(DIR_LOGS . "kamod.log", date('Y-m-d G:i:s') . ": Ka Extensions autoloader failed (Error: " . $e->getMessage() . ")\n", FILE_APPEND);

	if (defined('KAMOD_DEBUG')) {
	
		die("KAMOD DEBUG: " . $e->getMessage());
		
	} elseif (APPLICATION == 'Admin') {	
		echo ("WARNING: The store is not operating properly. Something wrong. Kamod error: " . $e->getMessage());
	} else {
//		die('Sorry, the store is not operable at this moment.');
	}
}