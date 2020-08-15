<script type='text/javascript' src='//www.midijs.net/lib/midi.js'></script>
<?php
require(__DIR__ . "/../vendor/autoload.php");

define("DEBUG_MODE", true);
session_start();
if(defined("DEBUG_MODE") && DEBUG_MODE) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
}

function validateCSS($validate) {
	$DISALLOWED = array("<?php", "?>", "behavior: url", ".php", "@import", "@\import", "@/import"); 

	$validated = str_replace($DISALLOWED, "", $validate);
    return $validated;
}
function validateMarkdown($comment) {
	$markdown = new Michelf\Markdown;
	$markdown->no_markup = true;
	$transformed = $markdown->transform($comment);
	return preg_replace(
		"/<a href=(?:'|\")javascript:(.*?)(?:'|\")>(.*?)<\/a>/i",
		"Attempted XSS: $2 ($1)",
		$transformed
	);
}

function validateCaptcha($privatekey, $response) {
	$responseData = json_decode(file_get_contents('https://www.google.com/recaptcha/api/siteverify?secret='.$privatekey.'&response='.$response));
	return $responseData->success;
}

function requireLogin() {
	if (!isset($_SESSION['user'])) {
		header("Location: /login.php?r_login"); die();
	}
}

function getID($user, $connection) {
	$stmt = $connection->prepare("SELECT * FROM users WHERE username = ?");
	$stmt->bind_param("s", $user);
	$stmt->execute();
	$result = $stmt->get_result();
	if($result->num_rows === 0) return 'error';
	while($row = $result->fetch_assoc()) {
		$id = $row['id'];
	} 
	$stmt->close();
	return $id;
}

function getName($id, $connection) {
	$stmt = $connection->prepare("SELECT * FROM users WHERE id = ?");
	$stmt->bind_param("s", $id);
	$stmt->execute();
	$result = $stmt->get_result();
	if($result->num_rows === 0) return('error');
	while($row = $result->fetch_assoc()) {
		$name = htmlspecialchars($row['username']);
	} 
	$stmt->close();
	return $name;
}

function getPFP($user, $connection) {
	$stmt = $connection->prepare("SELECT * FROM users WHERE username = ?");
	$stmt->bind_param("s", $user);
	$stmt->execute();
	$result = $stmt->get_result();
	if($result->num_rows === 0) return('error');
	while($row = $result->fetch_assoc()) {
		$pfp = htmlspecialchars($row['pfp']);
	} 
	$stmt->close();
	return $pfp;
}

function checkIfFriended($friend1, $friend2, $connection)
{
	$stmt = $connection->prepare("SELECT * FROM `friends` WHERE reciever = ? AND sender = ? OR reciever = ? AND sender = ?");
	$stmt->bind_param("ssss", $friend1, $friend2, $friend2, $friend1);
	$stmt->execute();
	$result = $stmt->get_result();
	if($result->num_rows === 1){ return true; }
	return false;
}

function getUser($id, $connection) {
	$stmt = $connection->prepare("SELECT * FROM users WHERE id = ?");
	$stmt->bind_param("i", $id);
	$stmt->execute();
	$result = $stmt->get_result();
	if($result->num_rows === 0) echo('That user does not exist.');
	while($row = $result->fetch_assoc()) {
		$username = $row['username'];
		$id = $row['id'];
		$date = $row['date'];
		$bio = $row['bio'];
		$css = $row['css'];
		$pfp = $row['pfp'];
		$badges = explode(';', $row['badges']);
		$music = $row['music'];
	}
	$stmt->close();

	$stmt = $connection->prepare("SELECT * FROM gamecomments WHERE author = ?");
	$stmt->bind_param("s", $username);
	$stmt->execute();
	$result = $stmt->get_result();

	$comments = 0;
	while($row = $result->fetch_assoc()) {
		$comments++;
	}
	$stmt->close();

	$stmt = $connection->prepare("SELECT * FROM comments WHERE author = ?");
	$stmt->bind_param("s", $username);
	$stmt->execute();
	$result = $stmt->get_result();

	$profilecomments = 0;
	while($row = $result->fetch_assoc()) {
		$profilecomments++;
	}
	$stmt->close();

	$stmt = $connection->prepare("SELECT * FROM files WHERE author = ? AND status='y'");
	$stmt->bind_param("s", $username);
	$stmt->execute();
	$result = $stmt->get_result();

	$filesuploaded = 0;
	while($row = $result->fetch_assoc()) {
		$filesuploaded++;
	}
	$stmt->close();
	return array(
		'id' => $id,
		'date' => $date,
		'bio' => $bio,
		'css' => $css,
		'pfp' => $pfp,
		'badges' => $badges,
		'music' => $music
	);
}
?>