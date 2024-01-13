<?php
// String
function oc_strlen(string $string) {
	return mb_strlen($string);
}

function oc_strpos(string $string, string $needle, int $offset = 0) {
	return mb_strpos($string, $needle, $offset);
}

function oc_strrpos(string $string, string $needle, int $offset = 0) {
	return mb_strrpos($string, $needle, $offset);
}

function oc_substr(string $string, int $offset, ?int $length = null) {
	return mb_substr($string, $offset, $length);
}

function oc_strtoupper(string $string) {
	return mb_strtoupper($string);
}

function oc_strtolower(string $string) {
	return mb_strtolower($string);
}

// Other
function oc_token(int $length = 32): string {
	return substr(bin2hex(random_bytes($length)), 0, $length);
}

/**
 * test print all
 */
function dd($items, $items2 = [], $items3 = [], $items4 = [], $quit = true) {
    echo "<pre>";
    print_r($items);
    echo "</pre>";

    if (!empty($items2) || $items2 == 0 || $items2 == '0') {
        echo "<pre>";
        print_r($items2);
        echo "</pre>";
    }

    if (!empty($items3) || $items3 == 0 || $items3 == '0') {
        echo "<pre>";
        print_r($items3);
        echo "</pre>";
    }

    if (!empty($items4) || $items4 == 0 || $items4 == '0') {
        echo "<pre>";
        print_r($items4);
        echo "</pre>";
    }

    if ($quit):
        exit;
    endif;
}