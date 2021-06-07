<?php
/**
 * api.php
 *
 * @package default
 */


require_once "config.php";


// find by genre (finds all games that belongs to specified genre id)
if (isset($_GET["findbygenre"])) {
	$id = $conn->real_escape_string($_GET["findbygenre"]);
	$sql = "select games.uid,games.name,games.year,games.exe,games.brief,genres.id from games inner join games_genres on games.id = games_genres.game_id inner join genres on games_genres.genre_id = genres.id  where genres.id = $id";
	$games = array();
	if (!$result = $conn->query($sql)) {
		error_log("Error description: " . $conn -> error, 0);
	}
	if ($result->num_rows > 0) {
		while ($row = $result->fetch_assoc()) {
			$games[] = $row;
		}
	}
	header('Content-type: application/json');
	echo json_encode($games);
}

// find by game mode (finds all games that belongs to specified game mode id)
if (isset($_GET["findbymode"])) {
	$id = $conn->real_escape_string($_GET["findbymode"]);
	$sql = "select games.uid,games.name,games.year,games.exe,games.brief,modes.id from games inner join games_modes on games.id = games_modes.game_id inner join modes on games_modes.mode_id = modes.id where modes.id = $id";
	$games = array();
	if (!$result = $conn->query($sql)) {
		error_log("Error description: " . $conn -> error, 0);
	}
	if ($result->num_rows > 0) {
		while ($row = $result->fetch_assoc()) {
			$games[] = $row;
		}
	}
	header('Content-type: application/json');
	echo json_encode($games);
}

// list games, genres, modes, pictures, videos
if (isset($_GET["list"])) {
	$list = $_GET["list"];
	///////// games list
	if ($list == "games") {
		$sql = "select uid,name,year,exe,brief from games where approved IS NOT NULL";
		$games = array();
		if (!$result = $conn->query($sql)) {
			error_log("Error description: " . $conn -> error, 0);
		}
		if ($result->num_rows > 0) {
			while ($row = $result->fetch_assoc()) {
				$row["name"] = mysql_unreal_escape_string($row["name"]);
				$row["brief"] = mysql_unreal_escape_string($row["brief"]);
				$games[] = $row;
			}
		}
		header('Content-type: application/json');
		echo json_encode($games);
	}
	/////// genres list
	if ($list == "genres") {
		$sql = "select id,name from genres";
		$genres = array();
		if (!$result = $conn->query($sql)) {
			error_log("Error description: " . $conn -> error, 0);
		}
		if ($result->num_rows > 0) {
			while ($row = $result->fetch_assoc()) {
				$genres[] = $row;
			}
		}
		header('Content-type: application/json');
		echo json_encode($genres);
	}
	////// modes list
	if ($list == "modes") {
		$sql = "select id,name from modes";
		$modes = array();
		if (!$result = $conn->query($sql)) {
			error_log("Error description: " . $conn -> error, 0);
		}
		if ($result->num_rows > 0) {
			while ($row = $result->fetch_assoc()) {
				$modes[] = $row;
			}
		}
		header('Content-type: application/json');
		echo json_encode($modes);
	}
}
//// GAME ALL DETAILS
if (isset($_GET["gamedetails"])) {
	$uid = $conn->real_escape_string($_GET["gamedetails"]);
	$game = array();
	if ($redis->hexists("abandonware_games", $uid)) {
		$tmp = json_decode($redis->hget("abandonware_games", $uid));
		$game = $tmp;
		$game->Name = mysql_unreal_escape_string($game->Name);
		$game->Producer = mysql_unreal_escape_string($game->Producer);
		$game->Publisher = mysql_unreal_escape_string($game->Publisher);
		$game->Tags = mysql_unreal_escape_string($game->Tags);
		$game->Brief = mysql_unreal_escape_string($game->Brief);
		$game->Description = mysql_unreal_escape_string($game->Description);
	} else {
		$game=new stdClass();
		$sql_curr = "SELECT * from games where uid = '$uid' LIMIT 1";
		$curr = $conn->query($sql_curr)->fetch_assoc();
		$game->UID = $curr["uid"];
		$game->Exe = $curr["exe"];
		$game->Name = mysql_unreal_escape_string($curr["name"]);
		$game->Year = $curr["year"];
		$tmp = json_decode(base64_decode($curr["data"]));
		$game->Genres = $tmp->Genres;
		$game->Modes = $tmp->Modes;
		$game->Producer = mysql_unreal_escape_string($tmp->Producer);
		$game->Publisher = mysql_unreal_escape_string($tmp->Publisher);
		$game->Tags = mysql_unreal_escape_string($tmp->Tags);
		$game->Brief = mysql_unreal_escape_string($curr["brief"]);
		$game->Description = mysql_unreal_escape_string($tmp->Description);
		$game->ImgUrls = $tmp->ImgUrls;
		$game->VideoUrls = $tmp->VideoUrls;
	}
	$game->Genres = GetGenresForGame($uid);
	$game->Modes = GetModesForGame($uid);
	header('Content-type: application/json');
	echo json_encode($game);
}



?>
