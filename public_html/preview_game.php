<?php
/**
 * preview_game.php
 *
 * @package default
 */


if (isset($_GET["get"])) {
	$uid = $_GET["get"];
	header('Content-Type: application/zip');
	header('Content-Length: ' . filesize($file));
	header('Content-Disposition: attachment; filename="file.zip"');
	readfile($file);
}

require_once "config.php";
require_once "header.php";

if (isset($_GET["uid"])) {
	$uid = $conn->real_escape_string($_GET["uid"]);
	$sql_curr = "SELECT * from games where uid = '$uid' LIMIT 1";
	$curr = $conn->query($sql_curr)->fetch_assoc();
	$data = json_decode(base64_decode($curr["data"]));
	echo '<ul>
<li><b>Name:</b> '.$curr["name"].'
<li><b>Realeased in:</b> '.$curr['year'].'
<li><b>Brief:</b> '.$curr["brief"].'
<li>';
	foreach ($data->ImgUrls as $img) {
		echo '<img src="image.php?file='.$img.'"/>';
	}
	echo '</li>
<li>';
	foreach ($data->VideoUrls as $vid) {
		// if youtube
		if (stripos($vid, "youtube") !== false) {
			$id = substr($vid, strrpos($vid, '=') + 1);
			$vid = "https://www.youtube.com/embed/".$id;
		}
		echo '<iframe width="560" height="315" src="'.$vid.'" frameborder="0" allow="autoplay; encrypted-media" allowfullscreen></iframe>';
	}
	echo '</li>
<li><b>Game Genres:</b><ul>';
	foreach (GetGenresForGame($uid) as $genre) {
		echo '<li>'.$genre.'</li>';
	}
	echo '</ul><li><b>Game Modes:</b><ul>';
	foreach (GetModesForGame($uid) as $mode) {
		echo '<li>'.$mode.'</li>';
	}
	echo '
</ul>
</li>
<li><b>Description:</b> '.$data->Description.'
</ul>';

	require_once "footer.php";
}
