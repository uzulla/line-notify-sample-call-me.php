<?php
include __DIR__."/settings.php";
if(!defined("ACCESS_TOKEN")){
    echo "LINE Notifyの認証を先におこなってください";
    exit;
}
?>
<!doctype html>
<html lang="en">
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
        <button type="submit">送信</button>
    </form>

<?php
if($_SERVER["REQUEST_METHOD"]==="POST"){
    $params = [];
    $params['message'] = (string)mb_substr($_POST['message'],0,1000);
    if(strlen($_POST['imageThumbnail'])>0){$params['imageThumbnail'] = $_POST['imageThumbnail'];}
    if(strlen($_POST['imageFullsize'])>0){$params['imageFullsize'] = $_POST['imageFullsize'];}

    // 送信データ組み立て
    $post_content = http_build_query($params, "", "&");

    $header = array(
        "Content-Type: application/x-www-form-urlencoded",
        "Content-Length: ".strlen($post_content),
        "Authorization: Bearer ".(string)ACCESS_TOKEN,
    );

    $context = array(
        "http" => array(
            "method"  => "POST",
            "header"  => implode("\r\n", $header),
            "content" => $post_content
        )
    );

    // APIへ送信
    $response_json_str = file_get_contents('https://notify-api.line.me/api/notify', false, stream_context_create($context));

    $response = json_decode($response_json_str, true);

    // Parseできているか、statusが200か、access_tokenが有るか確認する
    if(!isset($response['status']) || $response['status'] != 200){
        echo "<h1>送信失敗</h1>";
        // デバッグするなら、以下を戻すと良いでしょう
//        echo "<pre>";
//        echo "\nResponse Header\n";
//        echo htmlspecialchars(print_r($http_response_header,1), ENT_QUOTES);
//        echo "\nParsed response\n";
//        echo htmlspecialchars(print_r($response,1), ENT_QUOTES);
//        echo "\nResponse JSON\n";
//        echo htmlspecialchars(print_r($response_json_str,1), ENT_QUOTES);

    }else{
        echo "<h1>送信成功</h1>";
    }

    // APIをたたきすぎないように、何らかの形でX-RateLimit-Remainingを確認し、
    // 残数が0であればX-RateLimit-Resetの時刻までリクエストしないように実装する必要があります。
    // 以下は確認する方法であって、別途送信制御を実装してください。
    foreach($http_response_header as $header_line){
        if(preg_match('/^X-RateLimit-Remaining: ([0-9]+)/i', $header_line, $_)){
            $x_ratelimit_remaining = (int)$_[1];
            echo "<p>APIのrate limitは残り{$x_ratelimit_remaining}回です</p>";
        }else if(preg_match('/^X-RateLimit-Reset: ([0-9]+)/i', $header_line, $_)){
            $rate_limit_reset_date_epoch = (int)$_[1];
            $rate_limit_reset_date_str = date('Y-m-d H:i:s', $rate_limit_reset_date_epoch);
            echo "<p>APIのrate limitは{$rate_limit_reset_date_str}(UNIX秒:{$rate_limit_reset_date_epoch})に回復します。</p>";
        }
    }

    echo "<p><a href='/'>トップへもどる</a></p>";
}

?>

</body>
</html>


