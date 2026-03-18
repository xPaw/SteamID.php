<?php
declare(strict_types=1);

namespace xPaw\Steam;

use \InvalidArgumentException;
use \Uri\WhatWg\Url;

/**
 * The SteamID library provides an easy way to work with SteamIDs and makes
 * conversions easy. Ported from SteamKit.
 *
 * This 64bit structure is used for identifying various objects on the Steam
 * network.
 *
 * This library requires the GMP module to be installed.
 *
 * This implementation was ported from SteamKit:
 * {@link https://github.com/SteamRE/SteamKit/blob/master/SteamKit2/SteamKit2/Types/SteamID.cs}
 *
 * GitHub: {@link https://github.com/xPaw/SteamID.php}
 * Website: {@link https://xpaw.me}
 *
 * @author xPaw
 * @license MIT
 */
class SteamID implements \Stringable
{
	/**
	 * @var array<int, string> Types of steam account
	 */
	private const array AccountTypeChars =
	[
		self::TypeAnonGameServer => 'A',
		self::TypeGameServer     => 'G',
		self::TypeMultiseat      => 'M',
		self::TypePending        => 'P',
		self::TypeContentServer  => 'C',
		self::TypeClan           => 'g',
		self::TypeChat           => 'T', // Lobby chat is 'L', Clan chat is 'c'
		self::TypeInvalid        => 'I',
		self::TypeIndividual     => 'U',
		self::TypeAnonUser       => 'a',
	];

	/**
	 * @var array<int|string, string> List of replacement hex characters used in /user/ URLs
	 */
	private const array SteamInviteDictionary =
	[
		'0' => 'b',
		'1' => 'c',
		'2' => 'd',
		'3' => 'f',
		'4' => 'g',
		'5' => 'h',
		'6' => 'j',
		'7' => 'k',
		'8' => 'm',
		'9' => 'n',
		'a' => 'p',
		'b' => 'q',
		'c' => 'r',
		'd' => 't',
		'e' => 'v',
		'f' => 'w',
	];

	/**
	 * @var array<string, string> Inverse of SteamInviteDictionary for decoding
	 */
	private const array SteamInviteDictionaryFlipped =
	[
		'b' => '0',
		'c' => '1',
		'd' => '2',
		'f' => '3',
		'g' => '4',
		'h' => '5',
		'j' => '6',
		'k' => '7',
		'm' => '8',
		'n' => '9',
		'p' => 'a',
		'q' => 'b',
		'r' => 'c',
		't' => 'd',
		'v' => 'e',
		'w' => 'f',
	];

	/**
	 * Steam universes. Each universe is a self-contained Steam instance.
	 */
	public const int UniverseInvalid  = 0;
	public const int UniversePublic   = 1;
	public const int UniverseBeta     = 2;
	public const int UniverseInternal = 3;
	public const int UniverseDev      = 4;

	/**
	 * Steam account types.
	 */
	public const int TypeInvalid        = 0;
	public const int TypeIndividual     = 1;
	public const int TypeMultiseat      = 2;
	public const int TypeGameServer     = 3;
	public const int TypeAnonGameServer = 4;
	public const int TypePending        = 5;
	public const int TypeContentServer  = 6;
	public const int TypeClan           = 7;
	public const int TypeChat           = 8;
	public const int TypeP2PSuperSeeder = 9;
	public const int TypeAnonUser       = 10;

	/**
	 * Steam allows 3 simultaneous user account instances right now.
	 */
	public const int AllInstances    = 0;
	public const int DesktopInstance = 1;
	public const int ConsoleInstance = 2;
	public const int WebInstance     = 4;

	/**
	 * Special flags for Chat accounts - they go in the top 8 bits
	 * of the Steam ID's "instance", leaving 12 for the actual instances.
	 */
	public const int InstanceFlagClan     = 524288; // ( k_unSteamAccountInstanceMask + 1 ) >> 1
	public const int InstanceFlagLobby    = 262144; // ( k_unSteamAccountInstanceMask + 1 ) >> 2
	public const int InstanceFlagMMSLobby = 131072; // ( k_unSteamAccountInstanceMask + 1 ) >> 3

	/**
	 * Vanity URL types used by ResolveVanityURL method.
	 */
	public const int VanityIndividual = 1;
	public const int VanityGroup      = 2;
	public const int VanityGameGroup  = 3;

	private \GMP $Data;

	/**
	 * Initializes a new instance of the SteamID class.
	 *
	 * It automatically guesses which type the input is, and works from there.
	 */
	public function __construct( int|string|null $Value = null )
	{
		$this->Data = gmp_init( 0 );

		if( $Value === null )
		{
			return;
		}

		// SetFromString
		if( preg_match( '/^STEAM_(?P<universe>[0-4]):(?P<authServer>[0-1]):(?P<id>0|[1-9][0-9]{0,9})$/', (string)$Value, $Matches ) === 1 )
		{
			$AccountID = $Matches[ 'id' ];

			// Check for max unsigned 32-bit number
			if( gmp_cmp( $AccountID, '4294967295' ) > 0 )
			{
				throw new InvalidArgumentException( 'Provided SteamID exceeds max unsigned 32-bit integer.' );
			}

			$Universe = (int)$Matches[ 'universe' ];

			// Games before orange box used to incorrectly display universe as 0, we support that
			if( $Universe === self::UniverseInvalid )
			{
				$Universe = self::UniversePublic;
			}

			$AuthServer = (int)$Matches[ 'authServer' ];
			$AccountID = ( (int)$AccountID << 1 ) | $AuthServer;

			$this->SetAccountUniverse( $Universe );
			$this->SetAccountInstance( self::DesktopInstance );
			$this->SetAccountType( self::TypeIndividual );
			$this->SetAccountID( $AccountID );
		}
		// SetFromSteam3String
		else if( preg_match( '/^\\[(?P<type>[AGMPCgcLTIUai]):(?P<universe>[0-4]):(?P<id>0|[1-9][0-9]{0,9})(?:\:(?P<instance>[0-9]{1,7}))?\\]$/', (string)$Value, $Matches ) === 1 )
		{
			$AccountID = $Matches[ 'id' ];

			// Check for max unsigned 32-bit number
			if( gmp_cmp( $AccountID, '4294967295' ) > 0 )
			{
				throw new InvalidArgumentException( 'Provided SteamID exceeds max unsigned 32-bit integer.' );
			}

			$Type = $Matches[ 'type' ];

			if( $Type === 'i' )
			{
				$Type = 'I';
			}

			if( $Type === 'T' || $Type === 'g' )
			{
				$InstanceID = self::AllInstances;
			}
			else if( isset( $Matches[ 'instance' ] ) )
			{
				$InstanceID = (int)$Matches[ 'instance' ];
			}
			else if( $Type === 'U' )
			{
				$InstanceID = self::DesktopInstance;
			}
			else
			{
				$InstanceID = self::AllInstances;
			}

			if( $Type === 'c' )
			{
				$InstanceID = self::InstanceFlagClan;

				$this->SetAccountType( self::TypeChat );
			}
			else if( $Type === 'L' )
			{
				$InstanceID = self::InstanceFlagLobby;

				$this->SetAccountType( self::TypeChat );
			}
			else
			{
				/** @var int $AccountType */
				$AccountType = array_search( $Type, self::AccountTypeChars, true );

				$this->SetAccountType( $AccountType );
			}

			$this->SetAccountUniverse( (int)$Matches[ 'universe' ] );
			$this->SetAccountInstance( $InstanceID );
			$this->SetAccountID( $AccountID );
		}
		else if( self::IsNumeric( $Value ) )
		{
			$this->Data = gmp_init( $Value, 10 );
		}
		else
		{
			throw new InvalidArgumentException( 'Provided SteamID is invalid.' );
		}
	}

	/**
	 * Renders this instance into its Steam2 "STEAM_" representation.
	 *
	 * @pure
	 *
	 * @return string A string Steam2 "STEAM_" representation of this SteamID.
	 */
	public function RenderSteam2() : string
	{
		switch( $this->GetAccountType() )
		{
			case self::TypeInvalid:
			case self::TypeIndividual:
			{
				$Universe = $this->GetAccountUniverse();
				$AccountID = $this->GetAccountID();

				return 'STEAM_' . $Universe . ':' . ( $AccountID & 1 ) . ':' .
					( $AccountID >> 1 );
			}
			default:
			{
				return $this->ConvertToUInt64();
			}
		}
	}

	/**
	 * Renders this instance into its Steam3 representation.
	 *
	 * @pure
	 *
	 * @return string A string Steam3 representation of this SteamID.
	 */
	public function RenderSteam3() : string
	{
		$AccountInstance = $this->GetAccountInstance();
		$AccountType = $this->GetAccountType();
		$AccountTypeChar = self::AccountTypeChars[ $AccountType ] ?? 'i';

		$RenderInstance = false;

		switch( $AccountType )
		{
			case self::TypeChat:
			{
				if( ( $AccountInstance & self::InstanceFlagClan ) !== 0 )
				{
					$AccountTypeChar = 'c';
				}
				else if( ( $AccountInstance & self::InstanceFlagLobby ) !== 0 )
				{
					$AccountTypeChar = 'L';
				}

				break;
			}
			case self::TypeAnonGameServer:
			case self::TypeMultiseat:
			{
				$RenderInstance = true;

				break;
			}
		}

		$Return = '[' . $AccountTypeChar . ':' . $this->GetAccountUniverse() .
			':' . $this->GetAccountID();

		if( $RenderInstance )
		{
			$Return .= ':' . $AccountInstance;
		}

		return $Return . ']';
	}

	/**
	 * Renders this instance into Steam's new invite code.
	 *
	 * Invites can be formatted as:
	 * http://s.team/p/%s
	 * https://steamcommunity.com/user/%s
	 *
	 * @return string A Steam invite code which can be used in a URL.
	 *
	 * @pure
	 *
	 * @throws InvalidArgumentException
	 */
	public function RenderSteamInvite() : string
	{
		switch( $this->GetAccountType() )
		{
			case self::TypeInvalid:
			case self::TypeIndividual:
			{
				$Code = dechex( $this->GetAccountID() );
				$Code = strtr( $Code, self::SteamInviteDictionary );
				$Length = strlen( $Code );

				// TODO: We don't know when Valve starts inserting the dash
				if( $Length > 3 )
				{
					$Code = substr_replace( $Code, '-', (int)( $Length / 2 ), 0 );
				}

				return $Code;
			}
			default:
			{
				throw new InvalidArgumentException( 'This can only be used on Individual SteamID.' );
			}
		}
	}

	/**
	 * Renders this instance into a friend code used by CS:GO.
	 * Looks like SUCVS-FADA.
	 *
	 * Based on <https://github.com/emily33901/go-csfriendcode>
	 * and looking at CSGO's client.dll.
	 *
	 * @return string A friend code which can be used in CS:GO.
	 *
	 * @pure
	 *
	 * @throws InvalidArgumentException
	 */
	public function RenderCsgoFriendCode() : string
	{
		$AccountType = $this->GetAccountType();

		if( $AccountType !== self::TypeInvalid && $AccountType !== self::TypeIndividual )
		{
			throw new InvalidArgumentException( 'This can only be used on Individual SteamID.' );
		}

		// Shift by string "CSGO" (0x4353474)
		$Hash = gmp_or( $this->GetAccountID(), '0x4353474F00000000' );

		// Convert it to little-endian
		$Hash = gmp_export( $Hash, 8, GMP_LITTLE_ENDIAN );

		// Hash the exported number
		$Hash = md5( $Hash, true );

		// Take the first 4 bytes and convert it back to a number
		$Hash = gmp_import( substr( $Hash, 0, 4 ), 4, GMP_LITTLE_ENDIAN );

		$Result = gmp_init( 0 );

		for( $i = 0; $i < 8; $i++ )
		{
			$IdNibble = $this->Get( 4 * $i, '0xF' );
			$HashNibble = gmp_and( self::ShiftRight( $Hash, $i ), 1 );

			$a = gmp_or( self::ShiftLeft( $Result, 4 ), $IdNibble );

			// Valve certainly knows how to turn accountid into
			// a complicated algorithm for no good reason
			$Result = gmp_or( self::ShiftLeft( self::ShiftRight( $Result, 28 ), 32 ), $a );
			$Result = gmp_or(
				self::ShiftLeft( self::ShiftRight( $Result, 31 ), 32 ),
				gmp_or( self::ShiftLeft( $a, 1 ), $HashNibble )
			);
		}

		// Is there a better way of doing this?
		$Result = gmp_import( gmp_export( $Result, 8, GMP_BIG_ENDIAN ), 8, GMP_LITTLE_ENDIAN );
		$Base32 = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
		$FriendCode = '';

		for( $i = 0; $i < 13; $i++ )
		{
			if( $i === 4 || $i === 9 )
			{
				$FriendCode .= '-';
			}

			$FriendCode .= $Base32[ gmp_intval( gmp_and( $Result, 31 ) ) ];
			$Result = self::ShiftRight( $Result, 5 );
		}

		// Strip the AAAA- prefix
		return substr( $FriendCode, 5 );
	}

	/**
	 * Gets a value indicating whether this instance is valid.
	 *
	 * @pure
	 *
	 * @return bool true if this instance is valid; otherwise, false.
	 */
	public function IsValid() : bool
	{
		$AccountType = $this->GetAccountType();

		if( $AccountType <= self::TypeInvalid || $AccountType > self::TypeAnonUser )
		{
			return false;
		}

		$AccountUniverse = $this->GetAccountUniverse();

		if( $AccountUniverse <= self::UniverseInvalid || $AccountUniverse > self::UniverseDev )
		{
			return false;
		}

		$AccountID = $this->GetAccountID();
		$AccountInstance = $this->GetAccountInstance();

		if( $AccountType === self::TypeIndividual )
		{
			if( $AccountID === 0 || $AccountInstance > self::WebInstance )
			{
				return false;
			}
		}

		if( $AccountType === self::TypeClan )
		{
			if( $AccountID === 0 || $AccountInstance !== 0 )
			{
				return false;
			}
		}

		if( $AccountType === self::TypeGameServer )
		{
			if( $AccountID === 0 )
			{
				return false;
			}
		}

		return true;
	}

	/**
	 * Returns a SteamID instance constructed from a steamcommunity.com
	 * URL form, or simply from a vanity url.
	 *
	 * Please note that you must implement vanity lookup function using
	 * ISteamUser/ResolveVanityURL api interface yourself.
	 *
	 * Callback function must return resolved SteamID as a string,
	 * or null if API returns success=42 (meaning no match).
	 *
	 * It's up to you to throw any exceptions if you wish to do so.
	 *
	 * This function can act as a pass-through for rendered Steam2/Steam3 ids.
	 *
	 * Example implementation is provided in `VanityURLs.php` file.
	 *
	 * @param string $Value Input URL
	 * @param callable(string, int): ?string $VanityCallback Callback which is called when a vanity lookup is required
	 *
	 * @return SteamID Fluent interface
	 *
	 * @throws InvalidArgumentException
	 */
	public static function SetFromURL( string $Value, callable $VanityCallback ) : self
	{
		$Url = Url::parse( $Value );

		if( $Url === null )
		{
			// Not a URL - treat as plain vanity string or pass through to SteamID constructor
			if( self::IsVanityString( $Value ) )
			{
				return self::ResolveVanityOrNumeric( $Value, '', $VanityCallback );
			}

			return new self( $Value );
		}

		$Scheme = $Url->getScheme();

		if( $Scheme !== 'http' && $Scheme !== 'https' )
		{
			return new self( $Value );
		}

		$Host = $Url->getAsciiHost();
		$IsSteamDomain = $Host === 'steamcommunity.com' || $Host === 'my.steamchina.com';
		$Path = $Url->getPath();
		$Segments = explode( '/', $Path );

		if( $IsSteamDomain && isset( $Segments[ 1 ], $Segments[ 2 ] ) && $Segments[ 2 ] !== '' )
		{
			switch( $Segments[ 1 ] )
			{
				case 'profiles':
				case 'gid':
					return new self( $Segments[ 2 ] );

				case 'id':
				case 'groups':
				case 'games':
				{
					$Id = $Segments[ 2 ];

					// Validate vanity ID format
					if( !self::IsVanityString( $Id ) )
					{
						break;
					}

					return self::ResolveVanityOrNumeric( $Id, $Segments[ 1 ], $VanityCallback );
				}

				case 'tradeoffer':
					if( $Segments[ 2 ] === 'new' )
					{
						return self::ParseTradeOfferQuery( $Url->getQuery() );
					}
					break;

				case 'user':
					return self::ParseSteamInvite( $Segments[ 2 ] );
			}
		}

		if( $Host === 's.team' && ( $Segments[ 1 ] ?? '' ) === 'p' && isset( $Segments[ 2 ] ) && $Segments[ 2 ] !== '' )
		{
			return self::ParseSteamInvite( $Segments[ 2 ] );
		}

		return new self( $Value );
	}

	/**
	 * @param callable(string, int): ?string $VanityCallback
	 */
	private static function ResolveVanityOrNumeric( string $Id, string $Type, callable $VanityCallback ) : self
	{
		$Length = strlen( $Id );

		if( $Length < 2 || $Length > 32 )
		{
			throw new InvalidArgumentException( 'Provided vanity url has bad length.' );
		}

		// Steam doesn't allow vanity urls to be valid steamids
		if( self::IsNumeric( $Id ) )
		{
			$SteamID = new self( $Id );

			if( $SteamID->IsValid() )
			{
				return $SteamID;
			}
		}

		$VanityType = match( $Type )
		{
			'groups' => self::VanityGroup,
			'games' => self::VanityGameGroup,
			default => self::VanityIndividual,
		};

		$Value = $VanityCallback( $Id, $VanityType );

		if( $Value === null )
		{
			throw new InvalidArgumentException( 'Provided vanity url does not resolve to any SteamID.', 404 );
		}

		return new self( $Value );
	}

	private static function ParseTradeOfferQuery( ?string $Query ) : self
	{
		if( $Query === null )
		{
			throw new InvalidArgumentException( 'Provided trade offer URL has no query string.' );
		}

		parse_str( $Query, $Params );

		if( !isset( $Params[ 'partner' ] ) || !is_string( $Params[ 'partner' ] ) || !self::IsNumeric( $Params[ 'partner' ] ) )
		{
			throw new InvalidArgumentException( 'Provided trade offer URL has no valid partner parameter.' );
		}

		return self::FromAccountID( $Params[ 'partner' ] );
	}

	private static function ParseSteamInvite( string $Id ) : self
	{
		$Value = strtolower( $Id );
		$Value = preg_replace( '/[^bcdfghjkmnpqrtvw]/', '', $Value ) ?? '';
		$Value = strtr( $Value, self::SteamInviteDictionaryFlipped );

		return self::FromAccountID( gmp_strval( gmp_init( $Value !== '' ? $Value : '0', 16 ) ) );
	}

	/**
	 * Sets the various components of this SteamID from a 64-bit integer form.
	 *
	 * @param int|string $Value The 64bit integer to assign this SteamID from.
	 *
	 * @return SteamID Fluent interface
	 *
	 * @throws InvalidArgumentException
	 */
	public function SetFromUInt64( int|string $Value ) : self
	{
		if( self::IsNumeric( $Value ) )
		{
			$this->Data = gmp_init( $Value, 10 );
		}
		else
		{
			throw new InvalidArgumentException( 'Provided SteamID is not numeric.' );
		}

		return $this;
	}

	/**
	 * Converts this SteamID into its 64-bit integer form. This function returns
	 * as a string to work on 32-bit PHP systems.
	 *
	 * @pure
	 *
	 * @return string A 64bit integer representing this SteamID.
	 */
	public function ConvertToUInt64() : string
	{
		return gmp_strval( $this->Data );
	}

	/**
	 * Sets the account from the given CS:GO friend code (looks like SUCVS-FBAC).
	 *
	 * @param string $Value The CS:GO friend code.
	 *
	 * @return SteamID Fluent interface
	 *
	 * @throws InvalidArgumentException
	 */
	public function SetFromCsgoFriendCode( string $Value ) : self
	{
		$Length = strlen( $Value );

		if( $Length === 10 ) // Friend codes
		{
			$AccountId = self::DecodeCsgoCode( $Value );
			$this->SetAccountID( $AccountId );
			$this->SetAccountType( self::TypeIndividual );
			$this->SetAccountUniverse( self::UniversePublic );
			$this->SetAccountInstance( 1 );
		}
		else if( $Length === 21 ) // Private queue invite codes
		{
			if( $Value[ 10 ] !== '-' )
			{
				throw new InvalidArgumentException( 'Given input is not a valid CS:GO code.' );
			}

			$Left = self::DecodeCsgoCode( substr( $Value, 0, 10 ) );
			$Right = self::DecodeCsgoCode( substr( $Value, 11, 10 ) );

			$AccountId = gmp_intval( gmp_add(
				gmp_and( $Left, '0x0000FFFF' ),
				self::ShiftLeft( gmp_and( $Right, '0x0000FFFF' ), 16 )
			) );

			$IsGroup =
				gmp_cmp( gmp_and( $Left, '0xFFFF0000' ), 0x10000 ) === 0 &&
				gmp_cmp( gmp_and( $Right, '0xFFFF0000' ), 0x10000 ) === 0;

			$this->SetAccountID( $AccountId );
			$this->SetAccountType( $IsGroup ? self::TypeClan : self::TypeIndividual );
			$this->SetAccountUniverse( self::UniversePublic );
			$this->SetAccountInstance( 1 );
		}
		else
		{
			throw new InvalidArgumentException( 'Given input is not a valid CS:GO code.' );
		}

		return $this;
	}

	private static function DecodeCsgoCode( string $Value ) : int
	{
		if( $Value[ 5 ] !== '-' )
		{
			throw new InvalidArgumentException( 'Given input is not a valid CS:GO code.' );
		}

		$Value = 'AAAA-' . $Value;
		$Value = str_replace( '-', '', $Value );

		if( strlen( $Value ) !== 13 )
		{
			throw new InvalidArgumentException( 'Given input is not a valid CS:GO code.' );
		}

		$Base32 = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
		$Result = gmp_init( 0 );

		for( $i = 0; $i < 13; $i++ )
		{
			$Character = strpos( $Base32, $Value[ $i ] );

			if( $Character === false )
			{
				throw new InvalidArgumentException( 'Given input is not a valid CS:GO code.' );
			}

			$Result = gmp_or( $Result, self::ShiftLeft( $Character, 5 * $i ) );
		}

		// Is there a way to avoid this?
		$Result = gmp_import( gmp_export( $Result, 8, GMP_BIG_ENDIAN ), 8, GMP_LITTLE_ENDIAN );
		$AccountId = 0;

		for( $i = 0; $i < 8; $i++ )
		{
			$Result = self::ShiftRight( $Result, 1 );
			$IdNibble = gmp_and( $Result, '0xF' );
			$Result = self::ShiftRight( $Result, 4 );

			$AccountId = gmp_or( self::ShiftLeft( $AccountId, 4 ), $IdNibble );
		}

		return gmp_intval( $AccountId );
	}

	/**
	 * Gets the account id.
	 *
	 * @pure
	 *
	 * @return int The account id.
	 */
	public function GetAccountID() : int
	{
		return gmp_intval( $this->Get( 0, '4294967295' ) ); // 4294967295 = 0xFFFFFFFF
	}

	/**
	 * Gets the account instance.
	 *
	 * @pure
	 *
	 * @return int The account instance.
	 */
	public function GetAccountInstance() : int
	{
		return gmp_intval( $this->Get( 32, '1048575' ) ); // 1048575 = 0xFFFFF
	}

	/**
	 * Gets the account type.
	 *
	 * @pure
	 *
	 * @return int The account type.
	 */
	public function GetAccountType() : int
	{
		return gmp_intval( $this->Get( 52, '15' ) ); // 15 = 0xF
	}

	/**
	 * Gets the account universe.
	 *
	 * @pure
	 *
	 * @return int The account universe.
	 */
	public function GetAccountUniverse() : int
	{
		return gmp_intval( $this->Get( 56, '255' ) ); // 255 = 0xFF
	}

	/**
	 * Sets the account id.
	 *
	 * @param int|string $Value The account id.
	 *
	 * @return SteamID Fluent interface
	 */
	public function SetAccountID( int|string $Value ) : self
	{
		if( $Value < 0 || $Value > 0xFFFFFFFF )
		{
			throw new InvalidArgumentException( 'Account id can not be higher than 0xFFFFFFFF.' );
		}

		$this->Set( 0, '4294967295', $Value ); // 4294967295 = 0xFFFFFFFF

		return $this;
	}

	/**
	 * Sets the account instance.
	 *
	 * @param int $Value The account instance.
	 *
	 * @return SteamID Fluent interface
	 */
	public function SetAccountInstance( int $Value ) : self
	{
		if( $Value < 0 || $Value > 0xFFFFF )
		{
			throw new InvalidArgumentException( 'Account instance can not be higher than 0xFFFFF.' );
		}

		$this->Set( 32, '1048575', $Value ); // 1048575 = 0xFFFFF

		return $this;
	}

	/**
	 * Sets the account type.
	 *
	 * @param int $Value The account type.
	 *
	 * @return SteamID Fluent interface
	 */
	public function SetAccountType( int $Value ) : self
	{
		if( $Value < 0 || $Value > 0xF )
		{
			throw new InvalidArgumentException( 'Account type can not be higher than 0xF.' );
		}

		$this->Set( 52, '15', $Value ); // 15 = 0xF

		return $this;
	}

	/**
	 * Sets the account universe.
	 *
	 * @param int $Value The account universe.
	 *
	 * @return SteamID Fluent interface
	 */
	public function SetAccountUniverse( int $Value ) : self
	{
		if( $Value < 0 || $Value > 0xFF )
		{
			throw new InvalidArgumentException( 'Account universe can not be higher than 0xFF.' );
		}

		$this->Set( 56, '255', $Value ); // 255 = 0xFF

		return $this;
	}

	/**
	 * @pure
	 */
	private function Get( int $BitOffset, int|string $ValueMask ) : \GMP
	{
		return gmp_and( self::ShiftRight( $this->Data, $BitOffset ), $ValueMask );
	}

	private function Set( int $BitOffset, int|string $ValueMask, int|string $Value ) : void
	{
		$this->Data = gmp_or(
			gmp_and( $this->Data, gmp_com( self::ShiftLeft( $ValueMask, $BitOffset ) ) ),
			self::ShiftLeft( gmp_and( $Value, $ValueMask ), $BitOffset )
		);
	}

	/**
	 * @pure
	 *
	 * Shift the bits of $x by $n steps to the left.
	 */
	private static function ShiftLeft( int|string|\GMP $x, int $n ) : \GMP
	{
		return gmp_mul( $x, gmp_pow( 2, $n ) );
	}

	/**
	 * @pure
	 *
	 * Shift the bits of $x by $n steps to the right.
	 */
	private static function ShiftRight( int|string|\GMP $x, int $n ) : \GMP
	{
		return gmp_div_q( $x, gmp_pow( 2, $n ) );
	}

	private static function IsVanityString( string $s ) : bool
	{
		return preg_match( '/^[\w-]+$/', $s ) === 1;
	}

	/**
	 * @pure
	 *
	 * This is way more restrictive than PHP's is_numeric().
	 */
	private static function IsNumeric( int|string $n ) : bool
	{
		if( is_int( $n ) )
		{
			return $n > 0;
		}

		// @phpstan-ignore-next-line possiblyImpure.functionCall
		return preg_match( '/^[1-9][0-9]{0,19}$/', $n ) === 1;
	}

	/**
	 * Static method to construct a SteamID and call ConvertToUInt64 on it.
	 */
	public static function AccountIDToUInt64( int $AccountID ) : string
	{
		return self::FromAccountID( $AccountID )->ConvertToUInt64();
	}

	/**
	 * Static method to construct a SteamID and call RenderSteam3 on it.
	 */
	public static function RenderAccountID( int $AccountID ) : string
	{
		return self::FromAccountID( $AccountID )->RenderSteam3();
	}

	/**
	 * Construct an individual SteamID in public universe given an account id.
	 */
	public static function FromAccountID( int|string $AccountID ) : self
	{
		return new self()
			->SetAccountID( $AccountID )
			->SetAccountUniverse( self::UniversePublic )
			->SetAccountInstance( self::DesktopInstance )
			->SetAccountType( self::TypeIndividual )
		;
	}

	#[\Override]
	public function __toString() : string
	{
		return $this->ConvertToUInt64();
	}
}
