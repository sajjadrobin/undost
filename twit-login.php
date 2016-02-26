<?php if(!isset($_SESSION))   session_start();

include_once 'lib/EpiCurl.php';
include_once 'lib/EpiOAuth.php';
include_once 'lib/EpiTwitter.php';
include_once 'secret.php';

$twitterObj = new EpiTwitter($consumer_key, $consumer_secret);
$oauth_token = $_GET['oauth_token'];

if($oauth_token == '') {
?>
<?php include_once("header.php") ?>
<?php	session_destroy();
	//$url = $twitterObj->getAuthorizationUrl();
	$url = $twitterObj->getAuthenticateUrl();
	//echo "<h2>$url</h2>";
	echo "<div style='width:350px;margin:50px auto;color: green;'>";
	echo "<p>Follow and unfollow users without limit (*twitter limits apply)</p>";
	echo "<a href='$url'><img src='/img/sign-in-with-twitter-gray.png' /> </a><br />";
	echo "<p> We won't send tweets or DMs without your explicit permission</p>";
	echo "</div>";
?>

<div class="container">
	<div class="row">
		<script async src="//pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"></script>
	<!-- Undosto twitter -->
	<ins class="adsbygoogle"
		 style="display:block"
		 data-ad-client="ca-pub-8883766202094793"
		 data-ad-slot="4741842253"
		 data-ad-format="auto"></ins>
	<script>
	(adsbygoogle = window.adsbygoogle || []).push({});
	</script>
	</div>
	<div class="span12 center-block donateButton vcenter">
		<iframe width="560" height="315" src="https://www.youtube.com/embed/MbzXScMrT4A" frameborder="0" allowfullscreen></iframe>
	</div>
</div>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.2/jquery.min.js"></script>
<script src="/js/bootstrap.min.js"></script>
</body>
</html>
<?php
}
else
{
	$twitterObj->setToken($_GET['oauth_token']);
	$token = $twitterObj->getAccessToken();
	$twitterObj->setToken($token->oauth_token, $token->oauth_token_secret);
	$_SESSION['ot'] = $token->oauth_token;
	$_SESSION['ots'] = $token->oauth_token_secret;
	//$twitterInfo= $twitterObj->get_accountVerify_credentials();
	//$twitterInfo->response;

	//$username = $twitterInfo->screen_name;
	//$profilepic = $twitterInfo->profile_image_url;
	header("Location:/");
	die();
}
?>