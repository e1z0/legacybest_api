<?php
/**
 * add.php
 *
 * @package default
 */


require_once "config.php";
require_once "header.php";


/**
 *
 * @param unknown $game
 * @param unknown $directory
 * @param unknown $uid
 * @return unknown
 */
function BuildGamePackage($game, $directory, $uid) {
	global $ARCHIVE, $TMP;
	$filename = $directory."_".$uid.".abw";
	$rootPath = $TMP."/".$directory;
	file_put_contents($rootPath."/package.json", json_encode($game));
	Zip($rootPath, $ARCHIVE."/".$filename);
	deleteDir($TMP."/".$directory);
	return $filename;
}


/**
 *
 * @param unknown $dir
 * @return unknown
 */
function deleteDir($dir) {
	$files = array_diff(scandir($dir), array('.', '..'));

	foreach ($files as $file) {
		(is_dir("$dir/$file")) ? deleteDir("$dir/$file") : unlink("$dir/$file");
	}

	return rmdir($dir);
}


/**
 *
 * @param unknown $game
 * @param unknown $directory
 */
function SubmitGame($game, $directory) {
	global $conn, $IMAGES, $redis;
	$uid = uniqid();
	$game->UID = $uid;
	$img_count = 0;
	$fakefiles = array();
	foreach ($game->ImgUrls as $img) {
		// download image
		$img_count++;
		$fakename = $uid."_".$img_count;
		if (@getimagesize($img)) {
			$data = file_get_contents($img);
			$fakefiles[] = $fakename;
			file_put_contents($IMAGES."/".$fakename, $data);
			$sqlimg = "INSERT INTO games_images (game_uid,url) VALUES('$uid','$fakename')";
			$conn->query($sqlimg);
		}
	}
	$game->ImgUrls = $fakefiles;
	$filename = BuildGamePackage($game, $directory, $uid);
	// some escape fixed
	$name = $conn->real_escape_string($game->Name);
	$year = $conn->real_escape_string($game->Year);
	$brief = $conn->real_escape_string($game->Brief);
	$data = base64_encode(json_encode($game));
	$sqlgame = "INSERT INTO games (uid,name,filename,year,exe,brief,data) VALUES ('$uid','$name','$filename','$year','$game->Exe','$brief','$data')";
	if (!$result = $conn->query($sqlgame)) {
		error_log("Error description: " . $conn -> error, 0);
	}
	$redis->hset("abandonware_games", $uid, json_encode($game));
	//$conn->query($sqlgame);


	foreach ($game->Modes as $mode) {
		$sqlmodes = "INSERT INTO games_modes (game_uid,mode_id) VALUES('$uid',$mode)";
		$conn->query($sqlmodes);
	}
	foreach ($game->Genres as $genre) {
		$sqlgenre = "INSERT INTO games_genres (game_uid,genre_id) VALUES('$uid',$genre)";
		$conn->query($sqlgenre);
	}

	foreach ($game->VideoUrls as $vid) {
		$sqlvid = "INSERT INTO games_videos (game_uid,url) VALUES('$uid','$vid')";
		$conn->query($sqlvid);
	}

}


/**
 *
 * @param unknown $file
 * @return unknown
 */
function ExtractZipFiles($file) {
	global $TMP;
	$zip = new ZipArchive;
	$files = array();
	$dirname = "";
	// create files array here
	$res = $zip->open($file);
	if ($res === TRUE) {
		$dirname = pathinfo($file, PATHINFO_FILENAME);
		for ( $i = 0; $i < $zip->numFiles; $i++ ) {
			$stat = $zip->statIndex($i);
			$files[] = $stat['name'];
			//    print_r( basename( $stat['name'] ) . PHP_EOL );
		}


		if (!file_exists($TMP."/".$dirname)) {
			mkdir($TMP."/".$dirname, 0755, true);
		}
		$zip->extractTo($TMP."/".$dirname);
		$zip->close();
		//echo "Archive extracted";
	} else {
		showerror("Unable to extract the zip archive!");
		@deleteDir($TMP."/".$dirname);
		unlink($file);
		exit;
	}
	return array($dirname, $files);
}


/**
 *
 * @param unknown $fname
 */
function ShowAddBanner($fname) {
	global $UPLOADS, $TMP;
	list($directory, $filelist) = ExtractZipFiles($UPLOADS ."/".$fname);
	$exefiles = array();
	$has_directory = 0;
	$invalid_path = 0;
	$logfile = "";
	if ($directory != "") {
		foreach ($filelist as $failas) {
			if (stripos($failas, "exe") !== false) {
				if (stripos($failas, "/") !== false) $has_directory =1;
				if (strlen(basename($failas)) > 12) {
					$invalid_path = 1;
					$longfile = basename($failas);
				}
				$exefiles[] = $failas;
			}
		}

		if ($invalid_path > 0) {
			showerror("The zipped game contains long file name: ".$longfile.", please read rules, before uploading!");
			// clear temp files
			@deleteDir($TMP."/".$directory);
			@unlink($UPLOADS."/".$fname);
			exit;
		}

		if ($has_directory == 0) {
			showerror("The zipped game does not have directory, read the damn rules, before uploading!");
			// clear temp files
			@deleteDir($TMP."/".$directory);
			@unlink($UPLOADS."/".$fname);
			exit;
		}
		if (count($exefiles) > 0) {
?>
<script>
var imgurlcount = 0;
var videourlcount = 0;
var modescount = 1;
var genrescount = 1;
function addimageurl() {
imgurlcount++;
$('#imageplace').append("<input type='text' name='img-url[]' class='form-control' placeholder='paste game image url here'>");
}
function addvideourl() {
videourlcount++;
$('#video-place').append("<input type='text' name='video-url[]' class='form-control' placeholder='paste game youtube video url here'>");
}
function addgenre() {
genrescount++;
var genr = $('#GenreSelect').clone();
$('#genreplace').append(genr);
}

function addmodes() {
modescount++;
var mds = $('#ModesSelect').clone();
$('#modesplace').append(mds);
}
</script>
<?php
			echo '<form method="post">';
			echo "Select game exe file:";
			$count = 0;
			foreach ($exefiles as $exe) {
				$count++;
				echo '<div class="form-check">'.
					'<input class="form-check-input" type="radio" id="exe'.$count.'" name="exe" value="'.$exe.'">'.
					'<label class="form-check-label" for="exe'.$count.'">'.$exe.'</label>'.
					'</div>';
			}
			echo '
 <div class="form-group">
    <label for="InputName">Name</label>
    <input type="text" class="form-control" id="InputName" name="name" aria-describedby="nameHelp" placeholder="Enter name" value="'.$directory.'">
    <small id="nameHelp" class="form-text text-muted">This will be name of the uploaded game.</small>
  </div>
  <div class="form-group">
    <label for="InputYear">Released in</label>
    <input type="text" class="form-control" id="InputYear" name="year" placeholder="Year, ex.: 1990">
  </div>

<div class="form-group">
    <label for="GenreSelect">Select Genre</label>
    <select class="form-control" id="GenreSelect" name="GenreSelect[]">';
			foreach (GameGenres() as $genre) {
				echo '<option value="'.$genre["id"].'">'.$genre["name"].'</option>';
			}

			echo '</select></div>
<div id="genreplace"></div>
<button type="button" onclick="addgenre();">Add one more genre</button>

<div class="form-group">
    <label for="ModesSelect">Select game mode</label>
    <select class="form-control" id="ModesSelect" name="ModesSelect[]">';
			foreach (GameModes() as $mode) {
				echo '<option value="'.$mode["id"].'">'.$mode["name"].'</option>';
			}
			echo '</select></div>

<div id="modesplace"></div>
<button type="button" onclick="addmodes();">Add one more mode</button>


 <div class="form-group">
    <label for="Producer">Producer</label>
    <input type="text" class="form-control" id="Producer" name="producer" placeholder="Game producer">
  </div>

 <div class="form-group">
    <label for="Publisher">Publisher</label>
    <input type="text" class="form-control" id="Publisher" name="publisher" placeholder="Publisher">
  </div>

 <div class="form-group">
    <label for="Tags">Tags</label>
    <input type="text" class="form-control" id="Tags" name="tags" placeholder="game of 1991, gold edition, super gfx">
  </div>


 <div class="form-group">
    <label for="ShortDescription">Short description</label>
    <input type="text" class="form-control" id="ShortDescription" name="shortdescr" placeholder="Short description of the game">
 </div>

<div class="form-group">
    <label for="Textarea1">Full description</label>
    <textarea class="form-control" id="Textarea1" name="description" rows="3"></textarea>
  </div>

 <input type="hidden" name="directory" value="'.$directory.'">

<div id="imageplace"></div>
<button type="button" onclick="addimageurl();">Add image url</button>
<div id="video-place"></div>
<button type="button" onclick="addvideourl();">Add video url</button>
  <div class="form-check">
    <input type="checkbox" class="form-check-input" id="Check1" name="check">
    <label class="form-check-label" for="Check1">Agree with <a href="rules.php" target="_blank">rules</a></label>
  </div>
  <button type="submit" class="btn btn-primary" name="submit_2">Submit</button>';
			echo "</form>";
		} else {
			showerror("We did not detect any of exe files on this zip archive! The game cannot run without executable! Read rules...");
			@deleteDir($TMP."/".$directory);
			@deleteDir($UPLOADS."/".$directory);
			@unlink($UPLOADS."/".$fname);
			exit;
		}
	}
}


if (isset($_POST["submit_2"])) {
	$game=new stdClass();
	if ($_POST["check"]) {
		showok("Your submission have been completed, let it pass 24hours, the moderators will either approve or deny your request!<br>Until now you can play any other games from our database! :-)");
		$game->Exe = $_POST["exe"];
		$game->Name = $_POST["name"];
		$game->Year = $_POST["year"];
		$game->Genres = $_POST["GenreSelect"];
		$game->Modes = $_POST["ModesSelect"];
		$game->Producer = $_POST["producer"];
		$game->Publisher = $_POST["publisher"];
		$game->Tags = $_POST["tags"];
		$game->Brief = $_POST["shortdescr"];
		$game->Description = $_POST["description"];
		$game->ImgUrls = $_POST["img-url"];
		$game->VideoUrls = $_POST["video-url"];
		echo "<br>";
		SubmitGame($game, $_POST["directory"]);
	} else {
		echo "You do not want to agree with our rules... Sorry then..";
	}
	exit;
}

if (isset($_POST["submit_1"])) {
	$fname = basename($_FILES["fileToUpload"]["name"]);
	$check = isZipFile($_FILES["fileToUpload"]["tmp_name"]);
	if ($check !== false) {
		//    echo "File is an zip - " . $check . ".";
		$uploadOk = 1;
	} else {
		showerror("File is not an zip archive.");
		@unlink($_FILES["fileToUpload"]["tmp_name"]);
		exit;
		$uploadOk = 0;
	}

	if (!file_exists($_FILES["fileToUpload"]["tmp_name"])) {
		echo "File is gone :(";
		$uploadOk = 0;
	}

	if (file_exists($UPLOADS ."/".$fname)) {
		showerror("File already exists!");
		exit;
		$uploadOk = 0;
	}

	if ($uploadOk > 0) {
		//echo "We got good file, processing...<br> filename $fname";
		if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $UPLOADS ."/".$fname)) {
			//echo "file was uploaded!";
			ShowAddBanner($fname);
			@unlink($UPLOADS ."/".$fname);
		} else {
			echo "there was an error then moving file to the right path...";
		}
	}
} else {


?>
<!-- <img src="images/addnew.png"/>-->
<h1>Upload game form</h1>
<h3>
<span class="glyphicon glyphicon-hand-right" aria-hidden="true"></span>
Rules</h3>
<h3>PLEASE READ THESE TERMS OF USE CAREFULLY</h3>
<h4>Before uploading, you must accept the following rules, not accepting the rules can lead you to unexpected errors!</h4>
<ul class="list-group">
<li class="list-group-item"><b>1.</b> Do not upload files zipped without directory with only compressed file mess, we have no time to deal with waste...</li>
<li class="list-group-item"><b>2.</b> Do not upload files zipped with long file format!</li>
<li class="list-group-item"><b>3.</b> Do not upload virusas, we haven't any virus scanner at this time, to check if they are not infected!</li>
<li class="list-group-item"><b>4.</b> Do not upload files if you do not have their full descriptions and full details about the game, such as release year, pictures, videos etc...</li>
<li class="list-group-item"><b>5.</b> Do not upload zipped files that are longer than 8.3 filename specification <a href="https://en.wikipedia.org/wiki/8.3_filename" target="_blank">read here about it</a>.</li>
<li class="list-group-item"><b>6.</b> If you are compressing files using MacOS please exclude the MacOS resource forks. More about it you can read <a href="https://apple.stackexchange.com/a/264924">here</a>.</li>
</ul>
ALWAYS CHECK YOUR ZIP FILES BEFORE UPLOADING!!!
<form action="add.php" method="post" enctype="multipart/form-data">
<div class="form-group">
  <input type="file" name="fileToUpload" id="fileToUpload" class="btn btn-default">
  <span class="glyphicon glyphicon-cloud-upload"></span>
  <input type="submit" value="Upload Game" name="submit_1" class="btn btn-default">
</div>
</form>


<?php
}
require_once "footer.php";
?>
