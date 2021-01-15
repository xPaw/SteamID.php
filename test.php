<?php
require __DIR__ . '/SteamID.php';
$a = new SteamID( '76561202255233023' );
var_dump( $a->RenderCsgoFriendCode() );
var_dump( $a->ConvertToUInt64() );
