<?php

use WebPConvert\WebPConvert;

require_once __DIR__ . '/../../../../config.php';
require_once DIR_SYSTEM . 'library/image.php';
require_once DIR_SYSTEM . 'library/journal3/vendor/autoload.php';

// input
$input = explode('/', trim($_SERVER['PATH_INFO'] ?? '', '/'), 3);
$params = explode('-', $input[1] ?? '');
$width = $params[0] ?? '';
$height = $params[1] ?? '';
$resize_type = $params[2] ?? '';
$filename = $input[2] ?? '';

// check input
if (!is_numeric($width)) {
	$width = '';
}

if (!is_numeric($height)) {
	$height = '';
}

$error = false;

if (!$width || !$height || !in_array($resize_type, ['', 'w', 'h']) || !$filename) {
	$error = true;
}

if (!is_file(DIR_IMAGE . $filename)) {
	$error = true;
}

if ($error) {
	header("HTTP/1.0 404 Not Found");
	echo "Invalid input";
	exit(0);
}

// perform resize
$extension = pathinfo($filename, PATHINFO_EXTENSION);

$image_old = $filename;
$image_new = 'cache/' . mb_substr($filename, 0, mb_strrpos($filename, '.')) . '-' . $width . 'x' . $height . $resize_type . '.' . $extension;

$image_resize_params = "{$width}-{$height}" . ($resize_type ? "-" . $resize_type : "");
$image_resize_hash = $image_new . '.' . md5($image_resize_params);
$image_resize_path = "catalog/view/theme/journal3/image.php/{$image_resize_params}/{$filename}";

if (!is_file(DIR_IMAGE . $image_resize_hash)) {
	header("HTTP/1.0 404 Not Found");
	echo "Invalid input";
	exit(0);
} else if (file_get_contents(DIR_IMAGE . $image_resize_hash) !== md5($image_resize_params)) {
	header("HTTP/1.0 404 Not Found");
	echo "Invalid input";
	exit(0);
}

// if image is already resized, skip resizing
if (!is_file(DIR_IMAGE . $image_new) || (filemtime(DIR_IMAGE . $image_old) > filemtime(DIR_IMAGE . $image_new))) {
	list($width_orig, $height_orig, $image_type) = getimagesize(DIR_IMAGE . $image_old);

	if (!in_array($image_type, [IMAGETYPE_PNG, IMAGETYPE_JPEG, IMAGETYPE_GIF])) {
		$this->load->model('tool/image');

		return $this->model_tool_image->resize($filename, $width, $height);
	}

	$path = '';

	$directories = explode('/', dirname($image_new));

	foreach ($directories as $directory) {
		$path = $path . '/' . $directory;

		if (!is_dir(DIR_IMAGE . $path)) {
			@mkdir(DIR_IMAGE . $path, 0777);
		}
	}

	if ($width_orig != $width || $height_orig != $height) {
		if (class_exists('\Image')) {
			$image = new \Image(DIR_IMAGE . $image_old);
		} else {
			$image = new \Opencart\System\Library\Image(DIR_IMAGE . $image_old);
		}
		$image->resize($width, $height, $resize_type);
		$image->save(DIR_IMAGE . $image_new);
	} else {
		copy(DIR_IMAGE . $image_old, DIR_IMAGE . $image_new);
	}
}

$image_new_webp = $image_new . '.webp';

$error = false;

if (!is_file(DIR_IMAGE . $image_new_webp) || (filemtime(DIR_IMAGE . $image_old) > filemtime(DIR_IMAGE . $image_new_webp))) {
	try {
		WebPConvert::convert(DIR_IMAGE . $image_new, DIR_IMAGE . $image_new_webp, [
			'encoding'      => 'lossy',
			'near-lossless' => 100,
			'quality'       => 85,
			'method'        => 4,
		]);
	} catch (\Exception $e) {
		$error = true;
	}
}

if (!$error && (strpos($_SERVER['HTTP_ACCEPT'], 'image/webp') !== false)) {
	header("Content-Type: image/webp");
	$img = DIR_IMAGE . $image_new_webp;
} else {
	header("Content-Type: image/" . $extension);
	$img = DIR_IMAGE . $image_new;
}

header('Expires: ' . gmdate('D, d M Y H:i:s \G\M\T', time() + (60 * 60 * 24 * 365)));
header('Vary: Accept');
header('WebP-Converted', (int)$error);

ob_start();
readfile($img);

$size = ob_get_length();

header("Content-length: " . $size);

ob_flush();
