<?php

use xPaw\Steam\SteamID;

require __DIR__ . '/vendor/autoload.php';

// Simpler solution which doesn't perform any API requests and simply only works on /profiles/ urls
$SteamID = SteamID::SetFromURL( 'https://steamcommunity.com/profiles/[U:1:2]', function()
{
	return null;
} );

// These also work
$SteamID = SteamID::SetFromURL( '[U:1:2]', function() { return null; } );
$SteamID = SteamID::SetFromURL( '76561197960265733', function() { return null; } );

// Lookup vanity urls via Steam web api
$WebAPIKey = 'YOUR WEBAPI KEY HERE';

$SteamID = SteamID::SetFromURL( 'https://steamcommunity.com/id/xpaw', function( string $URL, int $Type ) use ( $WebAPIKey ) : ?string
{
	// This callback is only used to resolve vanity urls when required
	$Parameters =
	[
		'format' => 'json',
		'key' => $WebAPIKey,
		'vanityurl' => $URL,
		'url_type' => $Type
	];

	$c = curl_init( );

	curl_setopt_array( $c, [
		CURLOPT_USERAGENT      => 'Steam Vanity URL Lookup',
		CURLOPT_ENCODING       => 'gzip',
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_URL            => 'https://api.steampowered.com/ISteamUser/ResolveVanityURL/v1/?' . http_build_query( $Parameters ),
		CURLOPT_CONNECTTIMEOUT => 5,
		CURLOPT_TIMEOUT        => 5
	] );

	$Response = curl_exec( $c );
	$Response = json_decode( (string)$Response, true );

	if( is_array( $Response ) && isset( $Response[ 'response' ][ 'success' ] ) )
	{
		switch( (int)$Response[ 'response' ][ 'success' ] )
		{
			case 1: return $Response[ 'response' ][ 'steamid' ];
			case 42: return null;
		}
	}

	throw new Exception( 'Failed to perform API request' );
} );
