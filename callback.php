<?php
include __DIR__."/settings.php";
include __DIR__."/LineNotifySimpleLib.php";

$line_notify = new \Uzulla\Net\LineNotifySimpleLib(LINE_NOTIFY_CLIENT_ID, LINE_NOTIFY_CLIENT_SECRET, CALLBACK_URL);
$access_token = $line_notify->requestAccessToken($_GET);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Document</title>
</head>
<body>

<?php
if($access_token===false){
    echo "<h1>認証失敗</h1>";
    echo "<p>以下はデバッグ情報です（通常表示する必要はありません）</p>";
    echo "<textarea>";
    echo htmlspecialchars(print_r($line_notify->getLastError(),1), ENT_QUOTES);
    echo "</textarea>";
}else{
    echo "<h1>認証成功</h1>";
    echo "<p>access_token <input type='text' width='400px' value='".htmlspecialchars($access_token, ENT_QUOTES)."'></p>";
    echo "<p>上記access_tokenをsettings.phpに設定してください</p>";
}
?>

<p><a href="/">トップに戻る</a></p>
</body>
</html>
