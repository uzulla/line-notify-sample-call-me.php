<?php
include __DIR__."/settings.php";
session_start();
$_SESSION = [];

// セキュリティのために、stateには必ずランダムなものを指定しましょう
if(function_exists('random_bytes')){
    $random_bytes = random_bytes(32);
}else{
    $random_bytes = openssl_random_pseudo_bytes(32);
}
$_SESSION['state_random_str'] = bin2hex($random_bytes);
?>
<!doctype html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>Document</title>
</head>
<body>
<form action="https://notify-bot.line.me/oauth/authorize" method='GET'>
    <input type="hidden" name="response_type" value="code">
    <input type="hidden" name="client_id" value="<?=LINE_NOTIFY_CLIENT_ID?>">
    <input type="hidden" name="redirect_uri" value="<?=CALLBACK_URL?>">
    <input type="hidden" name="scope" value="notify">
    <input type="hidden" name="state" value="<?=$_SESSION['state_random_str']?>">
    <button type="submit">LINE認証ページを開く</button>
</form>
</body>
</html>
