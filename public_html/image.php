<?php
/**
 * image.php
 *
 * @package default
 */


//$IMAGES = "../images";
require_once "config.php";


/**
 *
 * @param unknown $file
 * @return unknown
 */
function ReturnImageType($file) {
	switch (exif_imagetype($file)) {
	case 1:
		return "image/gif";
	case 2:
		return "image/jpeg";
	case 3:
		return "image/png";
	case 4:
		return "image/swf";
	case 5:
		return "image/psd";
	case 6:
		return "image/bmp";
	case 7:
		return "image/tiff";
	case 8:
		return "image/tiff";
	case 9:
		return "image/jpc";
	case 10:
		return "image/jp2";
	case 11:
		return "image/jpx";
	case 12:
		return "image/jb2";
	case 13:
		return "image/swc";
	case 14:
		return "image/iff";
	case 15:
		return "image/wbmp";
	case 16:
		return "image/xbm";
	case 17:
		return "image/ico";
	case 18:
		return "image/webp";
	default:
		return "image/jpeg";
	}
}


if (isset($_GET["file"])) {
	$file = $_GET["file"];
	$file = str_replace('/', '_', $file);
	$file = str_replace('..', '_', $file);
	$type = ReturnImageType($IMAGES."/".$file);
	// image/jpeg
	header('Content-Type: '.$type);
	readfile($IMAGES."/".$file);
}

?>
