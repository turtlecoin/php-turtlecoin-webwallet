<?php
#Config
use TurtleCoin\Walletd;

$config = [
    'rpcHost'     => "127.0.0.1",
    'rpcPort'     => 8070,
    'rpcPassword' => "{password}",
];
$walletd = new Walletd\Client($config);

#Server Creds
$servername = "localhost";
$user = "{user}";
$pw = "{password}";
$db = "turtlecoin";
#Connect to Server
$con = new mysqli($servername, $user, $pw, $db);
 ?>
