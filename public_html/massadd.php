<?php
/**
 * massadd.php
 *
 * @package default
 */


if ($_SERVER["REMOTE_ADDR"] != "xxxxxxxxxx") {
	echo "Access denied!";
	die;
}
require_once "config.php";


/**
 *
 * @param unknown $dir
 * @return unknown
 */
function deleteDir($dir) {
	return;
	$files = array_diff(scandir($dir), array('.', '..'));

	foreach ($files as $file) {
		(is_dir("$dir/$file")) ? deleteDir("$dir/$file") : unlink("$dir/$file");
	}

	return rmdir($dir);
}


/**
 *
 * @param unknown $filename
 * @return unknown
 */
function CheckArchiveConsistency($filename) {
	global $TMP;
	$base = basename($filename);
	$dir = pathinfo($base, PATHINFO_FILENAME);
	$tmpdir = $TMP."/".$dir;
	$files = array();
	$exefiles = array();

	if (!file_exists($filename)) {
		return array("err", "File does not exist at all!", $exefiles);
		WriteLog("File: ".$filename." does not exist");
	}

	//if (isZipFile($filename)) {
	//return array("err","File is not zip file",$exefiles);
	//}

	$base = basename($filename);
	$dir = pathinfo($base, PATHINFO_FILENAME);
	$tmpdir = $TMP."/".$dir;

	if (!file_exists($tmpdir)) {
		mkdir($tmpdir, 0755, true);
	}
	$zip = new ZipArchive;
	$res = $zip->open($filename);
	$files = array();
	$exefiles = array();
	$has_directory = 0;
	$invalid_path = 0;
	$longfile = "";
	if ($res === TRUE) {
		WriteLog("File opened: ".$filename);
		$dirname = pathinfo($filename, PATHINFO_FILENAME);
		for ( $i = 0; $i < $zip->numFiles; $i++ ) {
			$stat = $zip->statIndex($i);
			$failas = $stat['name'];
			$files[] = $failas;
			if (stripos($failas, "exe") !== false) {
				$exefiles[] = $failas;
			}
			if (stripos($failas, "/") !== false) $has_directory =1;
			if (strlen(basename($failas)) > 12) {
				$invalid_path = 1;
				$longfile = basename($failas);
			}
		}
		$zip->extractTo($tmpdir);
	} else {
		//@deleteDir($TMP."/".$dirname);
		return array("err", "Cannot open zip archive", $exefiles);
	}
	//if ($has_directory == 0) {
	//@deleteDir($tmpdir);
	//return array("err","The zipped game does not contain any directory!",$exefiles);
	//}
	//if ($invalid_path > 0) {
	//return array("err","The zipped game contains long file name: ".$longfile.", please read rules, before uploading!",$exefiles);
	//@deleteDir($tmpdir);
	//}
	//if ($invalid_path > 0) {
	//return array("err","The zipped game contains long file name: ".$longfile.", please read rules, before uploading!",$exefiles);
	//@deleteDir($tmpdir);
	//}

	if (count($files) >0 && count($exefiles) >0) {
		return array("ok", "Zip files are consistent and ok", $exefiles);
	}
	//@deleteDir($tmpdir);
	return array("err", "Total fail of zip archive", $exefiles);
}


///// check zip
if (isset($_GET["checkzip"])) {
	$filename = $conn->real_escape_string($_GET["checkzip"]);
	WriteLog("checking zip: ".$filename);
	list($state, $msg, $files) = CheckArchiveConsistency($filename);
	WriteLog(print_r($files, true));
	header('Content-Type: application/json');
	echo json_encode(array('info'=>$state, 'message'=>$msg, 'files'=>$files));
	exit;
}

//// check zip end

/// upload file
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_GET["uploadfile"]) && $_GET["uploadfile"]== "1") {
	/* Getting file name */
	$filename = $_FILES['file']['name'];

	/* Location */
	$location = "tmp/".$filename;
	$uploadOk = 1;
	$imageFileType = pathinfo($location, PATHINFO_EXTENSION);

	/* Valid Extensions */
	$valid_extensions = array("zip", "7z", "rar");
	/* Check file extension */
	if ( !in_array(strtolower($imageFileType), $valid_extensions) ) {
		$uploadOk = 0;
	}

	if ($uploadOk == 0) {
		echo 0;
	}else {
		/* Upload file */
		if (move_uploaded_file($_FILES['file']['tmp_name'], $location)) {
			echo $location;
		}else {
			echo 0;
		}
	}

}

/// end of upload file

//// GAME mark as INVALID
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_GET["invalid"]) && $_GET["invalid"] > 0) {
	header('Content-Type: application/json');
	$game = $conn->real_escape_string($_GET["invalid"]);
	$state = "ok";
	$msg = "Game marked as invalid";
	$sql = "UPDATE uploads set invalid = now() where id = $game";
	if (!$result = $conn->query($sql)) {
		error_log("Error description: " . $conn -> error, 0);
		$state = "err";
		$msg = "unable to set the game as invalid";
	}
	echo json_encode(array('info'=>$state, 'message'=>$msg));
	exit;
}
//// END OF INVALID

//// GAME DELETE
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_GET["delgame"]) && $_GET["delgame"] > 0) {
	header('Content-Type: application/json');
	$game = $conn->real_escape_string($_GET["delgame"]);
	$state = "ok";
	$msg = "Upload deleted";
	$sql = "delete from uploads where id = $game";
	if (!$result = $conn->query($sql)) {
		error_log("Error description: " . $conn -> error, 0);
		$state = "err";
		$msg = "unable to delete upload from db";
	}
	echo json_encode(array('info'=>$state, 'message'=>$msg));
	exit;
}
//// END OF GAME DELETE

///// GAME ADD
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_GET["addgame"]) && $_GET["addgame"] == "1") {
	WriteLog(print_r($_POST, true));
	$upload_id = $_POST["uploadid"];
	header('Content-Type: application/json');
	$state = "ok";
	$msg = "game added";
	if (!isset($_POST["exe"])) {
		$state = "err";
		$msg = "You have not selected the game exe file";
		echo json_encode(array('info'=>$state, 'message'=>$msg));
		exit;
	}
	if (!isset($_POST["zip"])) {
		$state = "err";
		$msg = "You have not specified the zip file";
		echo json_encode(array('info'=>$state, 'message'=>$msg));
		exit;
	}
	$uid = uniqid();
	$game=new stdClass();
	$game->UID = $uid;

	// process exe and archive here ///////////////////////////////////////////////////////////////////////////
	$zip = $_POST["zip"];
	list($exe, $filename) = BuildGamePackagev2($zip, $_POST["gamename"], $_POST["exe"], $uid);

	// some escape fixed
	$game->Exe = $exe;
	$game->Name = $conn->real_escape_string($_POST["gamename"]);
	$game->Year = $_POST["year"];
	$game->Genres = $_POST["genre"];
	$game->Modes = $_POST["mode"];
	$game->Producer = $conn->real_escape_string($_POST["developer"]);
	$game->Publisher = $conn->real_escape_string($_POST["publisher"]);
	$game->Tags = $conn->real_escape_string($_POST["tag"]);
	$game->Brief = $conn->real_escape_string($_POST["tag"]);
	$game->Description = $conn->real_escape_string($_POST["description"]);
	$game->ImgUrls = $_POST["images"];
	$game->VideoUrls = $_POST["videos"] ?? array();
	//$data = base64_encode(json_encode($game));
	$sqlgame = "INSERT INTO games (uid,name,filename,year,exe,brief) VALUES ('$uid','$game->Name','$filename','$game->Year','$game->Exe','$game->Brief')";
	if (!$result = $conn->query($sqlgame)) {
		error_log("Error description: " . $conn -> error, 0);
		$state = "err";
		$msg = "Unable to add to the database game record!";
		echo json_encode(array('info'=>$state, 'message'=>$msg));
		exit;
	}
	$reply_id = $conn->insert_id;
	$img_count = 0;
	$fakefiles = array();
	foreach ($game->ImgUrls as $img) {
		$img_count++;
		if (stripos($img, "http") === false) {
			$img = "uploads_images/".$img;
		}
		$fakename = $uid."_".$img_count;
		if (@getimagesize($img)) {
			$data = file_get_contents($img);
			$fakefiles[] = $fakename;
			file_put_contents($IMAGES."/".$fakename, $data);
			$sqlimg = "INSERT INTO games_images (game_id,url) VALUES('$reply_id','$fakename')";
			$conn->query($sqlimg);
		}
	}
	$game->ImgUrls = $fakefiles;
	$data = base64_encode(json_encode($game));
	$sqlupd = "UPDATE games set data = '$data', approved = now() where id = '$reply_id'";
	if (!$result = $conn->query($sqlupd)) {
		error_log("Error description: " . $conn -> error, 0);
		$state = "err";
		$msg = "Error updating inserted game data with uploaded images!";
		echo json_encode(array('info'=>$state, 'message'=>$msg));
		exit;
	}
	$redis->hset("abandonware_games", $uid, json_encode($game));

	foreach ($game->Modes as $mode) {
		$sqlmodes = "INSERT INTO games_modes (game_id,mode_id) VALUES('$reply_id',$mode)";
		if (!$result = $conn->query($sqlmodes)) {
			error_log("Error description: " . $conn -> error, 0);
			$state = "err";
			$msg = "Error inserting game modes for the game!";
			echo json_encode(array('info'=>$state, 'message'=>$msg));
			exit;
		}

	}
	foreach ($game->Genres as $genre) {
		$sqlgenre = "INSERT INTO games_genres (game_id,genre_id) VALUES('$reply_id',$genre)";
		if (!$result = $conn->query($sqlgenre)) {
			error_log("Error description: " . $conn -> error, 0);
			$state = "err";
			$msg = "Error inserting game genres for the game!";
			echo json_encode(array('info'=>$state, 'message'=>$msg));
			exit;
		}
	}

	foreach ($game->VideoUrls as $vid) {
		$sqlvid = "INSERT INTO games_videos (game_id,url) VALUES('$reply_id','$vid')";
		if (!$result = $conn->query($sqlvid)) {
			error_log("Error description: " . $conn -> error, 0);
			$state = "err";
			$msg = "Error inserting videos for the game!";
			echo json_encode(array('info'=>$state, 'message'=>$msg));
			exit;
		}
	}

	$sqlupd2 = "UPDATE uploads set imported = now() where id = '$upload_id'";
	if (!$result = $conn->query($sqlupd2)) {
		error_log("Error description: " . $conn -> error, 0);
		$state = "err";
		$msg = "Error updating scraped item data!";
		echo json_encode(array('info'=>$state, 'message'=>$msg));
		exit;
	}


	echo json_encode(array('info'=>$state, 'message'=>$msg));
	exit;
}
//// GAME ADD END


/**
 *
 * @param unknown $id
 * @return unknown
 */
function GetImagesForGame($id) {
	global $conn;
	$conn->real_escape_string($id);
	$sql = "SELECT uploads.id,uploads_images.url FROM uploads inner join uploads_images on uploads.id = uploads_images.upload_id where uploads.id = $id";
	$images = array();
	if (!$result = $conn->query($sql)) {
		error_log("Error description: " . $conn -> error, 0);
	}
	if ($result->num_rows > 0) {
		while ($row = $result->fetch_assoc()) {
			$images[] = $row["url"];
		}
	}
	return $images;
}


require_once "header.php";
$sql = "select * from uploads where filename != 'buy' and imported IS NULL and invalid IS NULL limit 20";
if (!$result = $conn->query($sql)) {
	error_log("Error description: " . $conn -> error, 0);
}
echo '
<div class="alert alert-success" role="alert" id="infoalert" style="display:none">
</div>
<div class="alert alert-danger" role="alert" id="erroralert" style="display:none">
</div>
';
echo "Total not approved games: ".$result->num_rows;

?>
<table class="table table-striped table-responsive-md btn-table">
<thead>
  <tr>
    <th>id</th>
    <th>Game name</th>
    <th>Actions</th>
  </tr>
</thead>
<tbody>
<?php

if ($result->num_rows > 0) {
	while ($row = $result->fetch_assoc()) {
		echo '<tr>
<td>'.$row["id"].'</td>
<td><a href="preview_game.php?uid='.$row["id"].'" target="_blank">'.$row["id"].'<a/></td>
<td><form id="'.$row["id"].'">
<div class="form-group">
<label for="gamename">Game name:</label>
<input type="text" class="form-control" id="gamename" name="gamename" value="'.$row["name"].'">
</div>
<small>
<div class="form-group">
    <label for="description">Description:</label>
    <input type="text" class="form-control" id="description" name="description" value="'.$row["description"].'">
  </div>
<div class="form-group">
    <label for="year">Year:</label>
    <input type="text" class="form-control" id="year" name="year" value="'.$row["year"].'">
  </div>

<div class="form-group">
<div id="genreplace">
<label for="genre">Select genre: (Currently genre is: '.$row["genre"].')</label>
  <select class="form-control" id="genre" name="genre[]">';
		foreach (GameGenres() as $genre) {
			$selected = "";
			if (stripos($genre["name"], $row["genre"]) !== false) $selected = "selected";
			echo '<option value="'.$genre["id"].'" '.$selected.'>'.$genre["name"].'</option>';
		}
		echo '
  </select>
</div>
<button type="button" onclick="addgenre();">Add Genre</button>
</div>


<div class="form-group">
<div id="modesplace">
<label for="mode">Select mode:</label>
  <select class="form-control" id="mode" name="mode[]">';
		foreach (GameModes() as $mode) {
			$selected = "";
			echo '<option value="'.$mode["id"].'">'.$mode["name"].'</option>';
		}
		echo '
  </select>
</div>
<button type="button" onclick="addmode();">Add Mode</button>
</div>

<div class="form-group">
    <label for="tag">Tag:</label>
    <input type="text" class="form-control" id="tag" name="tag" value="'.$row["tag"].'">
  </div>
<div class="form-group">
    <label for="publisher">Publisher:</label>
    <input type="text" class="form-control" id="publisher" name="publisher" value="'.$row["publisher"].'">
  </div>
<div class="form-group">
    <label for="developer">Developer:</label>
    <input type="text" class="form-control" id="developer" name="developer" value="'.$row['developer'].'">
  </div>
<div class="form-group">
    <label for="rating">Rating:</label>
    <input type="text" class="form-control" id="rating" name="rating" value="'.$row["rating"].'">
  </div>
<br><a href="'.$row['origurl'].'" target="_blank">Original source</a>
</small>
<div id="imagiukai">';
		foreach (GetImagesForGame($row["id"]) as $image) {
			echo '<input type="hidden" name="images[]" value="'.$image.'">';
			echo '<img width="100" src="uploads_images/'.$image.'" name="images[]"/>';
		}
		echo '
</div>
<button type="button" onclick="addimageurl();">Add image url</button>
</div>
<div id="vidosai">
<button type="button" onclick="addvideourl();">Add video url</button>
</div>
<div id="zipfailas">
<div class="form-group">
    <label for="zip">Zip file:</label>
    <input type="text" class="form-control" id="zip_'.$row["id"].'" name="zip" value="data/'.$row["filename"].'">
  </div>
</div>
<div class="form-group">
<label for="zipasas">Zip file size: '.human_filesize("data/".$row["filename"]).'</label>
 <button id="zipasas" class="btn btn-default" type="button" onclick="CheckZip(\''.$row["id"].'\');">Check zip file consistency</button>
</div>
 <div class="form-group" id="uploadfile_'.$row["id"].'" style="display:none">
  <input type="file" name="fileToUpload" id="fileToUpload_'.$row["id"].'" class="btn btn-default">
  <span class="glyphicon glyphicon-cloud-upload"></span>
  <button type="button" onclick="UploadFile(\''.$row["id"].'\')">Upload new file</button>
</div>
<div id="exefiles_'.$row["id"].'">
</div>
<!-- some spinning shit -->
<div class="baras" id="loadingas_'.$row["id"].'" style="display:none"></div>
</div>
<input type="hidden" name="uploadid" value="'.$row["id"].'">
</form></td>
 <td>';
		echo '<button type="button" class="btn btn-indigo btn-sm m-0" onclick="AddGame('.$row["id"].')">Approve</button>';
		echo '<button type="button" class="btn btn-indigo btn-sm m-0" onclick="AddInvalid('.$row["id"].')">Invalid</button>';
		echo '<button type="button" class="btn btn-indigo btn-sm m-0" onclick="DelGame('.$row["id"].')">Delete</button>';

		//echo '<button type="button" class="btn btn-indigo btn-sm m-0" onclick="location.href=\'approval.php?delete='.$row["id"].'\'">Delete</button>
		echo '    </td>
</tr>
';
	}
}
?>
</tbody>

</table>

<script>

function ShowError(text) {
$('#infoalert').hide();
$('#erroralert').html(text);
$('#erroralert').show();
}

function ShowInfo(text) {
$('#erroralert').hide();
$('#infoalert').html(text);
$('#infoalert').show();
}

function AddInvalid(idas) {
 $.ajax({
        url: "massadd.php?invalid="+idas,
        type: "post",
        success: function (response) {
             if (response.info == "ok") {
             //alert("game was set as invalid");
             //$(location).attr('href', 'massadd.php');
ShowInfo("The game was set as invalid!");
window.setTimeout(function() {
    window.location.href = 'massadd.php';
}, 2000);
             } else {

             alert("You have something missing: "+response.message);
             }
        },
        error: function(jqXHR, textStatus, errorThrown) {
           console.log(textStatus, errorThrown);
        }
    });
}

function DelGame(idas) {
 $.ajax({
        url: "massadd.php?delgame="+idas,
        type: "post",
        success: function (response) {
             if (response.info == "ok") {
             alert("game deleted");
             $(location).attr('href', 'massadd.php');
             } else {
             alert("You have something missing: "+response.message);
             }
        },
        error: function(jqXHR, textStatus, errorThrown) {
           console.log(textStatus, errorThrown);
        }
    });
}


function AddGame(idas) {
//alert("add game: "+idas);
$('#loadingas_'+idas).show();

var values = $("#"+idas).serialize();
 $.ajax({
        url: "massadd.php?addgame=1",
        type: "post",
        data: values ,
        success: function (response) {
             if (response.info == "ok") {
ShowInfo("The game was sucessfully added the game database!");
window.setTimeout(function() {
    window.location.href = 'massadd.php';
}, 2000);
             } else {
             alert("You have something missing: "+response.message);
             }
            $('#loadingas_'+idas).hide();
           // alert("post ok");
           // You will get response from your PHP page (what you echo or print)
        },
        error: function(jqXHR, textStatus, errorThrown) {
           console.log(textStatus, errorThrown);
        }
    });
}

function addimageurl() {
$('#imagiukai').append("<input type='text' name='images[]' class='form-control' placeholder='paste game image url here'>");
}
function addvideourl() {
$('#vidosai').append("<input type='text' name='videos[]' class='form-control' placeholder='paste game youtube video url here'>");
}

function addgenre() {
var genr = $('#genre').clone();
$('#genreplace').append(genr);
}

function addmode() {
var mds = $('#mode').clone();
$('#modesplace').append(mds);
}

function UploadFile(fileid) {
var failas = $('#fileToUpload_'+fileid).val().replace(/C:\\fakepath\\/i, '')
//alert('id: '+fileid+' failas: '+failas);
$('#loadingas_'+fileid).show();
var fd = new FormData();
        var files = $('#fileToUpload_'+fileid)[0].files[0];
        fd.append('file',files);
        $.ajax({
            url: 'massadd.php?uploadfile=1',
            type: 'post',
            data: fd,
            contentType: false,
            processData: false,
            success: function(response){
                if(response != 0){
                    alert('file upload ok');
                    $('#zip_'+fileid).val('tmp/'+failas);
                    $('#uploadfile_'+fileid).hide();
                }else{
                    alert('file upload error');
                }
               $('#loadingas_'+fileid).hide();
            },
        });
}

function CheckZip(idas) {
var zipfile = $('#zip_'+idas).val();
$('#loadingas_'+idas).show();
$.ajax({
        url: "massadd.php?checkzip="+zipfile,
        type: "get",
        datatype: "json",
        success: function (response) {
             if (response.info == "ok") {
  //            alert("zip file is ok: "+response.message);
              var countas = 0;
              $('#exefiles_'+idas).html("");
              response.files.forEach(function(item) {
              countas++;
              $('#exefiles_'+idas).append('<div class="form-check">'+
              '<input class="form-check-input" type="radio" id="exe_'+countas+'" name="exe" value="'+item+'">'+
              '<label class="form-check-label" for="exe_'+countas+'">'+item+'</label>'+
              '</div>');
               });
             } else {
              // enable manual upload file
              alert("zip file is corrupted: "+response.message);
              $('#uploadfile_'+idas).show();
             }
               $('#loadingas_'+idas).hide();
        },
        error: function(jqXHR, textStatus, errorThrown) {
           console.log(textStatus, errorThrown);
        }
    });

}
</script>

<?php
require_once "footer.php";
?>
