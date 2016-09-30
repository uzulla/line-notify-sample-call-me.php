<?php
namespace Uzulla\Net;

/**
 * LINE Notify を利用するライブラリです
 * Class LineNotifySimpleLib
 * @package Uzulla\Net
 */
class LineNotifySimpleLib
{
    private $LINE_NOTIFY_CLIENT_ID;
    private $LINE_NOTIFY_CLIENT_SECRET;
    private $CALLBACK_URL;
    private $SESSION_STATE_KEY = 'state_random_str';
    private $lastError = [];
    private $lastRatelimitRemaining = null;
    private $lastRateLimitResetDateEpoch = null;

    /**
     * LineNotifySimpleLib constructor.
     * @param string $LINE_NOTIFY_CLIENT_ID LINE Notify Client ID.
     * @param string $LINE_NOTIFY_CLIENT_SECRET LINE Notify Client Secret.
     * @param string $CALLBACK_URL 実際に設置されるcallbackのURL
     */
    public function __construct($LINE_NOTIFY_CLIENT_ID, $LINE_NOTIFY_CLIENT_SECRET, $CALLBACK_URL)
    {
        $this->LINE_NOTIFY_CLIENT_ID = $LINE_NOTIFY_CLIENT_ID;
        $this->LINE_NOTIFY_CLIENT_SECRET = $LINE_NOTIFY_CLIENT_SECRET;
        $this->CALLBACK_URL = $CALLBACK_URL;
    }

    /**
     * LINE Notifyの認証フォームへリダイレクト
     */
    public function redirectToAuthorizeURL()
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $_SESSION[$this->SESSION_STATE_KEY] = $this->randomStr();

        $url = "https://notify-bot.line.me/oauth/authorize?" .
            http_build_query([
                'response_type' => 'code',
                'client_id' => $this->LINE_NOTIFY_CLIENT_ID,
                'redirect_uri' => $this->CALLBACK_URL,
                'scope' => 'notify',
                'state' => $_SESSION[$this->SESSION_STATE_KEY],
            ], "", "&");

        header("Location: {$url}");
    }

    public function setSessionStateKey($key_name)
    {
        $this->SESSION_STATE_KEY = $key_name;
    }

    private function randomStr($length = 32)
    {
        if (function_exists('random_bytes')) {
            $random_bytes = random_bytes($length);
        } else {
            $random_bytes = openssl_random_pseudo_bytes($length);
        }
        return bin2hex($random_bytes);
    }

    /**
     * LINE Notifyからのcallbackで取得できるcode、stateを確認し、
     * access_tokenをLINE Notifyから取得する。
     * @param $params $_GETなど
     * @return bool 成功/失敗
     */
    public function requestAccessToken($params)
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        if (!isset($_SESSION[$this->SESSION_STATE_KEY]) || $params['state'] !== $_SESSION[$this->SESSION_STATE_KEY]) {
            $_SESSION = [];
            $this->lastError = [
                'message' => 'state validation fail',
            ];
            return false;
        }

        $_SESSION = [];

        // APIへのPOSTデータ用意
        $post_content = http_build_query([
            'code' => (string)$_GET['code'],
            'redirect_uri' => CALLBACK_URL,
            'client_id' => LINE_NOTIFY_CLIENT_ID,
            'client_secret' => LINE_NOTIFY_CLIENT_SECRET,
            'grant_type' => 'authorization_code',
        ], "", "&");

        $header = [
            "Content-Type: application/x-www-form-urlencoded",
            "Content-Length: " . strlen($post_content)
        ];

        $context = stream_context_create([
            "http" => [
                "method" => "POST",
                "header" => $header,
                "content" => $post_content
            ]
        ]);

        // 送信
        $response_json_str = file_get_contents('https://notify-bot.line.me/oauth/token', false, $context);

        // レスポンスされた結果を確認
        $response = json_decode($response_json_str, true);

        if (!isset($response['status']) || $response['status'] != 200 || !isset($response['access_token'])) {
            // Parseできているか、statusが200か、access_tokenが有るか確認する
            $this->lastError = [
                'message' => 'Request failed',
                'http_response_header' => $http_response_header,
                'response_json' => $response_json_str
            ];
            return false;

        } else if (preg_match('/[^a-zA-Z0-9]/u', $response['access_token'])) {
            // access_tokenが期待しているフォーマットではない気がする
            $this->lastError = [
                'message' => 'Got wired access_token',
                'access_token' => $response['access_token'],
                'http_response_header' => $http_response_header,
                'response_json' => $response_json_str
            ];
            return false;

        } else {
            return $response['access_token'];

        }
    }

    /**
     * 最後の実行時エラーの詳細を納めた配列を取得
     * @return array
     */
    public function getLastError()
    {
        return $this->lastError;
    }

    /**
     * 最後の実行時（投稿など）に取得できた API実行可能残回数
     * @return null/integer
     */
    public function getLastRatelimitRemaining()
    {
        return $this->lastRatelimitRemaining;
    }

    /**
     * 最後の実行時（投稿など）に取得できた API実行可能残回数のリセット予定時刻
     * @return null/integer
     */
    public function getLastRateLimitResetDateEpoch()
    {
        return $this->lastRateLimitResetDateEpoch;
    }

    /**
     * LINE Notify APIにより、メッセージを送信
     * 各パラメタの詳細はLINE Notifyドキュメントの同名のパラメタを確認してください
     * @param string $access_token アクセストークン
     * @param string $message メッセージ文字列
     * @param string $imageThumbnail サムネイル画像URL
     * @param string $imageFullsize フルサイズ画像URL
     * @return bool 成功/失敗
     */
    public function sendMessage($access_token, $message, $imageThumbnail = '', $imageFullsize = '')
    {
        $params = [];
        $params['message'] = $message;
        if (strlen($imageThumbnail) > 0) {
            $params['imageThumbnail'] = $imageThumbnail;
        }
        if (strlen($imageFullsize) > 0) {
            $params['imageFullsize'] = $imageFullsize;
        }

        // 送信データ組み立て
        $post_content = http_build_query($params, "", "&");

        $header = [
            "Content-Type: application/x-www-form-urlencoded",
            "Content-Length: " . strlen($post_content),
            "Authorization: Bearer " . $access_token,
        ];

        $context = stream_context_create([
            "http" => [
                "method" => "POST",
                "header" => implode("\r\n", $header),
                "content" => $post_content
            ]
        ]);

        // APIへ送信
        $response_json_str = file_get_contents('https://notify-api.line.me/api/notify', false, $context);

        $response = json_decode($response_json_str, true);

        if (!isset($response['status']) || $response['status'] != 200) {
            $this->lastError = [
                'message' => 'Request failed',
                'http_response_header' => $http_response_header,
                'response_json' => $response_json_str
            ];
            return false;
        }

        // APIをたたきすぎないように、何らかの形でX-RateLimit-Remainingを確認し、
        // 残数が0であればX-RateLimit-Resetの時刻までリクエストしないように実装する必要があります。
        // 以下は確認する方法であって、別途送信制御を実装してください。
        $this->lastRatelimitRemaining = null;
        $this->lastRateLimitResetDateEpoch = null;
        foreach ($http_response_header as $header_line) {
            if (preg_match('/^X-RateLimit-Remaining: ([0-9]+)/i', $header_line, $_)) {
                $this->lastRatelimitRemaining = (int)$_[1];
            } else if (preg_match('/^X-RateLimit-Reset: ([0-9]+)/i', $header_line, $_)) {
                $this->lastRateLimitResetDateEpoch = (int)$_[1];
            }
        }

        return true;
    }

}

