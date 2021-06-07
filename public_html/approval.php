<?php
/**
 * approval.php
 *
 * @package default
 */


if ($_SERVER["REMOTE_ADDR"] != "xxxxxxxxxxxxx") {
	echo "Access denied!";
	die;
}
require_once "config.php";


if (isset($_GET["deletegame"]) && $_GET["deletegame"] > 0) {
	$game = $conn->real_escape_string($_GET["deletegame"]);
	$sql = "delete from games where id = $game";
	if (!$result = $conn->query($sql)) {
		error_log("Error description: " . $conn -> error, 0);
		$state = "err";
		$msg = "unable to delete from db";
	} else {
		$state = "ok";
		$msg = "okay";
	}
	header('Content-Type: application/json');
	echo json_encode(array('info'=>$state, 'message' => $msg));
	exit;
}


require_once "header.php";
$sql = "select * from games";
if (!$result = $conn->query($sql)) {
	error_log("Error description: " . $conn -> error, 0);
}
echo "Total not approved games: ".$result->num_rows;

?>
<div class="table-responsive">
  <table class="table">
 <thead>
    <tr>
      <th scope="col">#</th>
      <th scope="col">Game</th>
      <th scope="col">Actions</th>
    </tr>
  </thead>
<tbody>
<?php
if ($result->num_rows > 0) {
	while ($row = $result->fetch_assoc()) {
		echo '<tr>
 <th scope="row">'.$row["id"].'</th>
 <td>'.$row["name"].'</td>
 <td><button type="button" class="btn btn-indigo btn-sm m-0" onclick="DeleteGame('.$row["id"].')">Delete</button></td>
';
	}
}

?>
</tbody>
  </table>
</div>
<script>
function DeleteGame(idas) {
 $.ajax({
        url: "approval.php?deletegame="+idas,
        type: "get",
        success: function (response) {
             if (response.info == "ok") {
             alert("game deleted");
             } else {
             alert("You must be something missing: "+response.message);
             }
  //         alert("post ok");
           // You will get response from your PHP page (what you echo or print)
        },
        error: function(jqXHR, textStatus, errorThrown) {
           console.log(textStatus, errorThrown);
        }
    });
}
</script>
<?php
require_once "footer.php";
