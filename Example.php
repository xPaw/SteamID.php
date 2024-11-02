<?php

use xPaw\Steam\SteamID;

require __DIR__ . '/vendor/autoload.php';

// This can be any string, like just the steamid, or a link to the profile
$UserInput = (string)filter_input( INPUT_GET, 'input' );

$WebAPIKey = 'YOUR WEBAPI KEY HERE';

try
{
	// SetFromURL does all the heavy lifing of parsing the input
	// This callback is only called to resolve vanity urls when required
	$SteamID = SteamID::SetFromURL( $UserInput, function( string $URL, int $Type ) use ( $WebAPIKey ) : ?string
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

	// Check that account type is actually a user profile
	if( $SteamID->GetAccountType() !== SteamID::TypeIndividual )
	{
		throw new InvalidArgumentException( 'We only support looking up individual profiles.' );
	}

	// Instance does not matter, reset it to the default one
	$SteamID->SetAccountInstance( SteamID::DesktopInstance );

	// Only public universe is available on Steam, so just reset it as well
	$SteamID->SetAccountUniverse( SteamID::UniversePublic );

	var_dump( $SteamID->RenderSteam3() ); // [U:1:24715681]
	var_dump( $SteamID->ConvertToUInt64() ); // 76561197984981409
}
catch( Exception $e )
{
	exit( $e->getMessage() );
}
