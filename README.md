# SteamID.php [![Packagist](https://img.shields.io/packagist/dt/xpaw/steamid.svg)](https://packagist.org/packages/xpaw/steamid) [![Codecov](https://codecov.io/gh/xPaw/SteamID.php/graph/badge.svg?token=MykhdiNWe2)](https://codecov.io/gh/xPaw/SteamID.php)

This 64bit structure is used for identifying various objects on the Steam 
network. This library provides an easy way to work with SteamIDs and makes 
conversions easy.

This library does not use subtraction hacks like described on 
[Valve Developer Wiki](https://developer.valvesoftware.com/wiki/SteamID), 
or used in many other functions.

SteamID.php requires modern PHP version, and [GMP module](http://php.net/manual/en/book.gmp.php)
to be installed to perform operations 64-bit math.

## Brief overview

A SteamID is made up of four parts: its **universe**, its **type**, its 
**instance**, and its **account ID**.

- **Universe**: Currently there are 5 universes. A universe is a unique 
  instance of Steam. You'll probably only be interacting with the public universe, 
  which is the regular Steam. Only Valve employees can access non-public universes.
- **Type**: A SteamID's type determines what it identifies. The most common type 
  is *individual*, for user accounts. There are also other types such as *clans* 
  (Steam groups), *gameservers*, and more.
- **Instance**: Steam allows three simultaneous user account instances right now 
  *(1 = desktop, 2 = console, 4 = web, 0 = all)*
- **Account ID**: This represents a unique account of a type.

## Using this library

It's really easy to use it, as constructor automatically figures out given input 
and works its magic from there. If provided SteamID is not in a valid format, an 
`InvalidArgumentException` is thrown. You can call `IsValid` on given SteamID 
instance to perform various checks which make sure that given account type / 
universe / instance are correct. You can view [test file](.test.php) for 
multiple examples on how to manipulate SteamIDs.

### Example

```php
use xPaw\Steam\SteamID;

try
{
	// Constructor also accepts Steam3 and Steam2 representations
	$s = new SteamID( '76561197984981409' );
	
	// Renders SteamID in it's Steam3 representation (e.g. [U:1:24715681])
	echo $s->RenderSteam3() . PHP_EOL;
	
	// Renders SteamID in it's Steam2 representation (e.g. STEAM_0:1:12357840)
	echo $s->RenderSteam2() . PHP_EOL;
	
	// Converts this SteamID into it's 64bit integer form (e.g. 76561197984981409)
	echo $s->ConvertToUInt64() . PHP_EOL;
}
catch( InvalidArgumentException $e )
{
	echo 'Given SteamID could not be parsed: ' . $e->getMessage();
}
```

### Static helper methods

For quick conversions when you only have an account ID:

```php
use xPaw\Steam\SteamID;

$accountId = 24715681;

// Create a standard individual SteamID from account ID
$steamId = SteamID::FromAccountID( $accountId );
echo $steamId->RenderSteam3(); // [U:1:24715681]

// Quick conversion to 64-bit format
echo SteamID::AccountIDToUInt64( $accountId ); // 76561197984981409

// Quick conversion to Steam3 format
echo SteamID::AccountIDRender( $accountId ); // [U:1:24715681]
```

### Parsing user input

Also see [`VanityURLs.php`](/VanityURLs.php) for parsing any user input including URLs.
If you're going to process user input, `SteamID::SetFromURL()` is all you need to use.

```php
use xPaw\Steam\SteamID;

// Supports various URL formats and direct SteamIDs
$inputs = [
	'https://steamcommunity.com/id/username/',
	'https://steamcommunity.com/profiles/76561197984981409',
	'https://s.team/p/abc-def',
	'76561197984981409',
	'[U:1:24715681]'
];

foreach( $inputs as $input )
{
	try
	{
		$steamId = SteamID::SetFromURL( $input, 'yourVanityResolverCallback' );
		echo $steamId->ConvertToUInt64() . PHP_EOL;
	}
	catch( InvalidArgumentException $e )
	{
		echo "Could not parse: $input" . PHP_EOL;
	}
}
```

### SteamID normalization

If you run some website where users can enter their own SteamIDs, sometimes you
might encounter SteamIDs which have wrong universe or instanceid set, which 
will result in a completely different, yet valid, SteamID. To avoid this, you
can manipulate given SteamID and set universe to public and instance to 
desktop.

```php
use xPaw\Steam\SteamID;

try
{
	$s = new SteamID( $ID );
	
	if( $s->GetAccountType() !== SteamID::TypeIndividual )
	{
		throw new InvalidArgumentException( 'We only support individual SteamIDs.' );
	}
	else if( !$s->IsValid() )
	{
		throw new InvalidArgumentException( 'Invalid SteamID.' );
	}
	
	$s->SetAccountInstance( SteamID::DesktopInstance );
	$s->SetAccountUniverse( SteamID::UniversePublic );

	var_dump( $s->RenderSteam3() ); // [U:1:24715681]
	var_dump( $s->ConvertToUInt64() ); // 76561197984981409
}
catch( InvalidArgumentException $e )
{
	echo $e->getMessage();
}
```

After doing these steps, you can call `RenderSteam3`, `RenderSteam2` or 
`ConvertToUInt64` to get normalized SteamID.

See [`Example.php`](/Example.php) for a fully fledged example.

## Functions

| Name | Parameters | Description |
|------|------------|-------------|
| `IsValid` | - | Gets a value indicating whether this instance is valid. |
| `RenderSteam2` | - | Renders this instance into it's Steam2 "STEAM_" representation. |
| `RenderSteam3` | - | Renders this instance into it's Steam3 representation. |
| `RenderSteamInvite` | - | Encodes accountid as HEX which can be used in `http://s.team/p/` URL. |
| `RenderCsgoFriendCode` | - | Encodes accountid as CS:GO friend code. |
| `ConvertToUInt64` | - | Converts this SteamID into it's 64bit integer form. |
| `SetFromURL` | string, callback | Parse any user input including URLs and just steam ids. |
| `SetFromUInt64` | string or int | Sets the various components of this SteamID from a 64bit integer form. |
| `SetFromCsgoFriendCode` | string | Sets the accountid of this SteamID from a CS:GO friend code. Resets other components to default values. |
| `GetAccountID` | - | Gets the account id. |
| `GetAccountInstance` | - | Gets the account instance. |
| `GetAccountType` | - | Gets the account type. |
| `GetAccountUniverse` | - | Gets the account universe. |
| `SetAccountID` | int | Sets the account id. |
| `SetAccountInstance` | int | Sets the account instance. (e.g. `SteamID::DesktopInstance`) |
| `SetAccountType` | int | Sets the account type. (e.g. `SteamID::TypeAnonGameServer`) |
| `SetAccountUniverse` | int | Sets the account universe. (e.g. `SteamID::UniversePublic`) |
| `FromAccountID` | int | **Static method.** Constructs an individual SteamID in public universe with desktop instance from an account ID. |
| `AccountIDToUInt64` | int | **Static method.** Converts an account ID to a 64bit SteamID string. |
| `AccountIDRender` | int | **Static method.** Renders an account ID as Steam3 representation. |

## Counter-Strike Friend Codes

CS2 (CS:GO) uses special friend codes for sharing profiles within the game. These codes look like `SUCVS-FADA` and can be generated from or parsed into SteamIDs:

```php
use xPaw\Steam\SteamID;

$steamId = new SteamID( '[U:1:123456]' );
$friendCode = $steamId->RenderCsgoFriendCode(); // e.g., "SUCVS-FADA"

// Parse friend code back to SteamID
$steamId2 = ( new SteamID() )->SetFromCsgoFriendCode( $friendCode );
echo $steamId2->RenderSteam3(); // [U:1:123456]
```

## Steam invite URLs

Valve introduced a way of sharing profile URLs (https://s.team/p/hjqp or https://steamcommunity.com/user/hjqp). The encoding is simply hex encoded account id with each letter being replaced using a custom alphabet. While HEX originally is `0-9a-f`, in the converted version numbers and letters `a` or `e` are not included, but they still work in the URL because Valve does a single pass replacement.

This library natively supports parsing `s.team/p/` or `steamcommunity.com/user/` URLs in `SetFromURL` function.

Example usage:

```php
use xPaw\Steam\SteamID;

$steamId = new SteamID( '[U:1:123456]' );
$inviteCode = $steamId->RenderSteamInvite(); // e.g., "abc-def"

echo "https://s.team/p/" . $inviteCode;
echo "https://steamcommunity.com/user/" . $inviteCode;
```

## License

[MIT](LICENSE)
