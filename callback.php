<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Document</title>
</head>
<body>

<?php
include __DIR__."/settings.php";
session_start();

// セキュリティの為にstateがセッションと一致するか確認します
if(!isset($_SESSION['state_random_str']) || $_GET['state'] !== $_SESSION['state_random_str'] ){
    echo "セッションが不正です、最初からやりなおしてください。";
    echo "<a href='/'>トップへもどる</a>";
    $_SESSION = [];
    exit;
}

// 確認したので、セッションを必ず消去します
$_SESSION = [];

// APIへのPOSTデータ用意
$post_content = http_build_query([
    'code' => (string)$_GET['code'],
    'redirect_uri' => CALLBACK_URL,
    'client_id' => LINE_NOTIFY_CLIENT_ID,
    'client_secret' => LINE_NOTIFY_CLIENT_SECRET,
    'grant_type' => 'authorization_code',
], "", "&");

$header = array(
    "Content-Type: application/x-www-form-urlencoded",
    "Content-Length: ".strlen($post_content)
);

$context = stream_context_create([
    "http" => [
        "method"  => "POST",
        "header"  => $header,
        "content" => $post_content
    ]
]);

// 送信
$response_json_str = file_get_contents('https://notify-bot.line.me/oauth/token', false, $context);

// レスポンスされた結果を確認
$response = json_decode($response_json_str, true);

// Parseできているか、statusが200か、access_tokenが有るか確認する
if(!isset($response['status']) || $response['status'] != 200 || !isset($response['access_token'])){
    echo "<pre>";
    echo "\n<h1>認証失敗</h1>\n";

    // デバッグするなら、以下を戻すと良いでしょう
//    echo "\nSESSION vars\n";
//    echo htmlspecialchars(print_r($_SESSION,1), ENT_QUOTES);
//    echo "\nGET params\n";
//    echo htmlspecialchars(print_r($_GET,1), ENT_QUOTES);
//    echo "\nResponse Header\n";
//    echo htmlspecialchars(print_r($http_response_header,1), ENT_QUOTES);
//    echo "\nParsed response\n";
//    echo htmlspecialchars(print_r($response,1), ENT_QUOTES);
//    echo "\nResponse JSON\n";
//    echo htmlspecialchars(print_r($response_json_str,1), ENT_QUOTES);

    echo "<p><a href='/'>トップへもどる</a></p>";

}else if(preg_match('/[^a-zA-Z0-9]/u', $response['access_token'])){
    echo "<h1>エラー</h1>";
    echo "<p>なぜかaccess_tokenとして返された値が怪しいかもしれません</p>";
    htmlspecialchars(print_r($response['access_token'],1), ENT_QUOTES);

}else{
    echo "<h1>認証成功</h1>";
    echo "<p>access_token: {$response['access_token']}</p>";
    echo "<p>以下をsettings.phpに追記してください</p>";
    echo "<textarea style='width:100%;'>define('ACCESS_TOKEN', '{$response['access_token']}');</textarea>";
    echo "<p><a href='/'>トップへもどる</a></p>";
}
?>

</body>
</html>
