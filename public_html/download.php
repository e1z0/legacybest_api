<?php
/**
 * download.php
 *
 * @package default
 */


require_once "config.php";
if (isset($_GET["file"])) {
	$uid = $conn->real_escape_string($_GET["file"]);
	$sql_curr = "SELECT * from games where uid = '$uid' LIMIT 1";
	$curr = $conn->query($sql_curr)->fetch_assoc();
	$file = "../archive/".$curr["filename"];
	header('Content-Type: application/zip');
	header('Content-Length: ' . filesize($file));
	header('Content-Disposition: attachment; filename="file.zip"');
	readfile($file);
}

?>
