LINE Notify を利用するサンプル
==

LINE Notifyで認証し`access_token`を取得し、それを利用して送信するサンプルです


# 注意

- 「PHPは古くさいベタッとした書き方のほうが参考にされるのでは」という意見を尊重し、非常に古くさいオールドスクールな書き方をしています。
- あくまでこれはサンプルです、これを公開するのはどうかとおもいます。

# 環境

PHP7で確認しています（が、おそらくそれより前でも動きます）。


# 使い方

LINE Notifyに事前に登録します。

LINE Notifyに登録するCallback URLは適切に設定する必要があります。たとえばlocalhostに立てるなら`http://localhost/callback.php`でしょう。もし公開サーバーに設置する場合には適切に変更してください。

その後、`settings.sample.php`を`settings.php`とコピーして、各種Client IDやcallback urlなどの内容を適切に修正し、`/index.php`をひらいてください。


# LINE Notifyについて

- 公式サイト [https://notify-bot.line.me/ja/](https://notify-bot.line.me/ja/)


# LICENSE

`CC0`

