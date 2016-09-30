<?php
include __DIR__ . "/settings.php";
include __DIR__ . "/LineNotifySimpleLib.php";

$line_notify = new \Uzulla\Net\LineNotifySimpleLib(LINE_NOTIFY_CLIENT_ID, LINE_NOTIFY_CLIENT_SECRET, CALLBACK_URL);
$line_notify->redirectToAuthorizeURL();
