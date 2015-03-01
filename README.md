# SteamID.php [![Build Status](https://travis-ci.org/xPaw/SteamID.php.svg?branch=master)](https://travis-ci.org/xPaw/SteamID.php)

This 64bit structure is used for identifying various objects on the Steam network. This library provides an easy way to work with SteamIDs and makes conversions easy.

This library does not use subtraction hacks like described on [Valve Developer Wiki](https://developer.valvesoftware.com/wiki/SteamID), or used in many other functions.

## Brief overview

A SteamID is made up of four parts: its **universe**, its **type**, its **instance**, and its **account ID**.

- **Universe**: Currently, there are 5 universes. A universe is a unique instance of Steam. You'll probably only be interacting with the public universe, which is the regular Steam. Only Valve employees can access non-public universes.
- **Type**: A SteamID's type determines what it identifies. The most common type is *individual*, for user accounts. There are also other types such as *clans* (Steam groups), *gameservers*, and more.
- **Instance**: Steam allows three simultaneous user account instances right now *(1 = desktop, 2 = console, 4 = web, 0 = all)*
- **Account ID**: This represents a unique account of a type.

## Using this library

It's really use to use it, as constructor automatically figures out given input and works its magic from there. If provided SteamID is not in a valid format, an `InvalidArgumentException` is thrown. You can call `IsValid` on given SteamID instance to perform various checks which make sure that given account type/universe/instance are correct. You can view [test file](.test.php) for multiple examples on how to manipulate SteamIDs.

### Example

```php
try
{
	$s = new SteamID( '76561197984981409' ); // Also accepts Steam3 and Steam2 representations
	
	echo $s->RenderSteam3() . PHP_EOL; // Renders SteamID in it's Steam3 representation *(e.g. [U:1:24715681])*
	echo $s->RenderSteam2() . PHP_EOL; // Renders SteamID in it's Steam2 representation *(e.g. STEAM_0:1:12357840)*
	echo $s->ConvertToUInt64() . PHP_EOL; // Converts this SteamID into it's 64bit integer form *(e.g. 76561197984981409)*
}
catch( InvalidArgumentException $e )
{
	echo 'Given SteamID could not be parsed.';
}
```

### SteamID normalization

If you run some website where users can enter their own SteamIDs, sometimes you might encounter SteamIDs which have wrong universe or instanceid set, which will result in a completely different, yet valid, SteamID. To avoid this, you can manipulate given SteamID and set universe to public and instance to desktop.

```php
try
{
	$s = new SteamID( $ID );
	
	if( $s->GetAccountType() !== SteamID :: TypeIndividual )
	{
		throw new InvalidArgumentException( 'We only support individual SteamIDs.' );
	}
	else if( !$SteamID->IsValid() )
	{
		throw new InvalidArgumentException( 'Invalid SteamID.' );
	}
	
	$s->SetAccountInstance( SteamID :: DesktopInstance );
	$s->SetAccountUniverse( SteamID :: UniversePublic );
}
catch( InvalidArgumentException $e )
{
	echo $e->getMessage();
}
```

After doing these steps, you can call `RenderSteam3`, `RenderSteam2` or `ConvertToUInt64` to get normalized SteamID.
