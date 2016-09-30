<?php
include __DIR__."/settings.php";
include __DIR__."/LineNotifySimpleLib.php";

session_start();

$is_execute = false;
$success = false;
if($_SERVER["REQUEST_METHOD"]==="POST" && $_SESSION['csrf_token']===$_POST['csrf_token']){
    $line_notify = new \Uzulla\Net\LineNotifySimpleLib(LINE_NOTIFY_CLIENT_ID, LINE_NOTIFY_CLIENT_SECRET, CALLBACK_URL, ACCESS_TOKEN);
    $success = $line_notify->sendMessage($_POST['message'], $_POST['imageThumbnail'], $_POST['imageFullsize']);
    $is_execute = true;
}

if(function_exists('random_bytes')){
    $random_bytes = random_bytes(32);
}else{
    $random_bytes = openssl_random_pseudo_bytes(32);
}
$_SESSION['csrf_token'] = bin2hex($random_bytes);

?>
<!doctype html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>Document</title>
</head>
<body>

<h1>LINE Notify APIに送信する</h1>
<form action="callme.php" method="post">
    <label>message<textarea name="message">message as you like</textarea></label><br>
    <label>imageThumbnail(URL)<input name="imageThumbnail"></label><br>
    <label>imageFullsize(URL)<input name="imageFullsize"></label><br>
    <input name="csrf_token" type="hidden" value="<?=$_SESSION['csrf_token']?>">
    <button type="submit">送信</button>
</form>

<?php
if($is_execute) {
    if ($success) {
        echo "<h1>送信成功</h1>";
        echo "<p>APIのrate limitはあと{$line_notify->getLastRatelimitRemaining()}回のこっています。</p>";
        $rate_limit_reset_date_str = date('Y-m-d H:i:s', $line_notify->getLastRateLimitResetDateEpoch());
        echo "<p>APIのrate limitは{$rate_limit_reset_date_str}(UNIX秒:{$line_notify->getLastRateLimitResetDateEpoch()})に回復します。</p>";
    } else {
        echo "<h1>送信失敗</h1>";
        echo "<p>以下はデバッグ情報です（通常表示する必要はありません）</p>";
        echo "<textarea>";
        echo htmlspecialchars(print_r($line_notify->getLastError(), 1), ENT_QUOTES);
        echo "</textarea>";
    }
}
?>

<p><a href='/'>トップへもどる</a></p>
</body>
</html>


