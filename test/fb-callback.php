<?php
if (!session_id()) {
    session_start();
}
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once 'php-graph-sdk/vendor/autoload.php';
$fb = new Facebook\Facebook([
  'app_id' => '1884021058580514', // Replace {app-id} with your app id
  'app_secret' => 'c0dad1ae0b4651f6d5b9197dff72fae8',
  'default_graph_version' => 'v2.2',
  ]);

$helper = $fb->getRedirectLoginHelper();

try {
  $accessToken = $helper->getAccessToken();
} catch(Facebook\Exceptions\FacebookResponseException $e) {
  // When Graph returns an error
  echo 'Graph returned an error: ' . $e->getMessage();
  exit;
} catch(Facebook\Exceptions\FacebookSDKException $e) {
  // When validation fails or other local issues
  echo 'Facebook SDK returned an error: ' . $e->getMessage();
  exit;
}

if (! isset($accessToken)) {
  if ($helper->getError()) {
    header('HTTP/1.0 401 Unauthorized');
    echo "Error: " . $helper->getError() . "\n";
    echo "Error Code: " . $helper->getErrorCode() . "\n";
    echo "Error Reason: " . $helper->getErrorReason() . "\n";
    echo "Error Description: " . $helper->getErrorDescription() . "\n";
  } else {
    header('HTTP/1.0 400 Bad Request');
    echo 'Bad request';
  }
  exit;
}

// Logged in
//echo '<h3>Access Token</h3>';
//var_dump($accessToken->getValue());

// The OAuth 2.0 client handler helps us manage access tokens
$oAuth2Client = $fb->getOAuth2Client();

// Get the access token metadata from /debug_token
$tokenMetadata = $oAuth2Client->debugToken($accessToken);
//echo '<h3>Metadata</h3>';
//var_dump($tokenMetadata);

// Validation (these will throw FacebookSDKException's when they fail)
$tokenMetadata->validateAppId('1884021058580514'); // Replace {app-id} with your app id
// If you know the user ID this access token belongs to, you can validate it here
//$tokenMetadata->validateUserId('123');
$tokenMetadata->validateExpiration();

if (! $accessToken->isLongLived()) {
  // Exchanges a short-lived access token for a long-lived one
  try {
    $accessToken = $oAuth2Client->getLongLivedAccessToken($accessToken);
  } catch (Facebook\Exceptions\FacebookSDKException $e) {
    echo "<p>Error getting long-lived access token: " . $helper->getMessage() . "</p>\n\n";
    exit;
  }

  //echo '<h3>Long-lived</h3>';
  //var_dump($accessToken->getValue());
}

$_SESSION['fb_access_token'] = (string) $accessToken;

$uid = $tokenMetadata->getField('user_id');

$user = 'mybcabus';
$pass = 'mybcabus';
$dbh = new PDO('mysql:host=localhost;dbname=mybcabus', $user, $pass);

global $dbh;
$stmt = $dbh->prepare("SELECT * user where uid = :uid");
$stmt->bindParam(':uid', $uid);
$stmt->execute();
$result = $stmt->fetchAll();

if(!$result){
  $stmt = $dbh->prepare("INSERT INTO user (uid) values (:uid)");
  $stmt->bindParam(':uid', $uid);
  $stmt->execute();
}
$_SESSION['uid'] = $uid;
header("Location: controller.php");


?>
