<?php
// Simpler solution which doesn't perform any API requests and simply only works on /profiles/ urls
$SteamID = SteamID::SetFromURL( 'http://steamcommunity.com/profiles/[U:1:2]', function()
{
	return null;
} );


$WebAPIKey = 'YOUR WEBAPI KEY HERE';

$SteamID = SteamID::SetFromURL( 'http://steamcommunity.com/groups/valve', function( $URL, $Type ) use ( $WebAPIKey )
{
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
	
	curl_close( $c );
	
	$Response = json_decode( $Response, true );
	
	if( isset( $Response[ 'response' ][ 'success' ] ) )
	{
		switch( (int)$Response[ 'response' ][ 'success' ] )
		{
			case 1: return $Response[ 'response' ][ 'steamid' ];
			case 42: return null;
		}
	}
	
	throw new Exception( 'Failed to perform API request' );
} );
