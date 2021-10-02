<?php
declare(strict_types=1);

namespace xPaw\Steam;

/**
 * The SteamID library provides an easy way to work with SteamIDs and makes
 * conversions easy. Ported from SteamKit.
 *
 * This 64bit structure is used for identifying various objects on the Steam
 * network.
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
class SteamID
{
	/**
	 * @var array<int, string> Types of steam account
	 */
	private static $AccountTypeChars =
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
	private static $SteamInviteDictionary =
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
	 * Steam universes. Each universe is a self-contained Steam instance.
	 */
	const UniverseInvalid  = 0;
	const UniversePublic   = 1;
	const UniverseBeta     = 2;
	const UniverseInternal = 3;
	const UniverseDev      = 4;

	/**
	 * Steam account types.
	 */
	const TypeInvalid        = 0;
	const TypeIndividual     = 1;
	const TypeMultiseat      = 2;
	const TypeGameServer     = 3;
	const TypeAnonGameServer = 4;
	const TypePending        = 5;
	const TypeContentServer  = 6;
	const TypeClan           = 7;
	const TypeChat           = 8;
	const TypeP2PSuperSeeder = 9;
	const TypeAnonUser       = 10;

	/**
	 * Steam allows 3 simultaneous user account instances right now.
	 */
	const AllInstances    = 0;
	const DesktopInstance = 1;
	const ConsoleInstance = 2;
	const WebInstance     = 4;

	/**
	 * Special flags for Chat accounts - they go in the top 8 bits
	 * of the steam ID's "instance", leaving 12 for the actual instances.
	 */
	const InstanceFlagClan     = 524288; // ( k_unSteamAccountInstanceMask + 1 ) >> 1
	const InstanceFlagLobby    = 262144; // ( k_unSteamAccountInstanceMask + 1 ) >> 2
	const InstanceFlagMMSLobby = 131072; // ( k_unSteamAccountInstanceMask + 1 ) >> 3

	/**
	 * Vanity URL types used by ResolveVanityURL method.
	 */
	const VanityIndividual = 1;
	const VanityGroup      = 2;
	const VanityGameGroup  = 3;

	private int $ID = 0;
	private int $Instance = 0;
	private int $Type = 0;
	private int $Universe = 0;

	/**
	 * Initializes a new instance of the SteamID class.
	 *
	 * It automatically guesses which type the input is, and works from there.
	 */
	public function __construct( int|string|null $Value = null )
	{
		if( PHP_INT_SIZE !== 8 )
		{
			throw new \RuntimeException( '64-bit PHP is required.' );
		}

		if( $Value === null )
		{
			return;
		}

		// SetFromString
		if( preg_match( '/^STEAM_([0-4]):([0-1]):([0-9]{1,10})$/', (string)$Value, $Matches ) === 1 )
		{
			$AccountID = (int)$Matches[ 3 ];

			// Check for max unsigned 32-bit number
			if( $AccountID > 4294967295 )
			{
				throw new \InvalidArgumentException( 'Provided SteamID exceeds max unsigned 32-bit integer.' );
			}

			$Universe = (int)$Matches[ 1 ];

			// Games before orange box used to incorrectly display universe as 0, we support that
			if( $Universe === self::UniverseInvalid )
			{
				$Universe = self::UniversePublic;
			}

			$AuthServer = (int)$Matches[ 2 ];
			$AccountID = ( $AccountID << 1 ) | $AuthServer;

			$this->SetAccountUniverse( $Universe );
			$this->SetAccountInstance( self::DesktopInstance );
			$this->SetAccountType( self::TypeIndividual );
			$this->SetAccountID( $AccountID );
		}
		// SetFromSteam3String
		else if( preg_match( '/^\\[([AGMPCgcLTIUai]):([0-4]):([0-9]{1,10})(:([0-9]+))?\\]$/', (string)$Value, $Matches ) === 1 )
		{
			$AccountID = (int)$Matches[ 3 ];

			// Check for max unsigned 32-bit number
			if( $AccountID > 4294967295 )
			{
				throw new \InvalidArgumentException( 'Provided SteamID exceeds max unsigned 32-bit integer.' );
			}

			$Type = $Matches[ 1 ];

			if( $Type === 'i' )
			{
				$Type = 'I';
			}

			if( $Type === 'T' || $Type === 'g' )
			{
				$InstanceID = self::AllInstances;
			}
			else if( isset( $Matches[ 5 ] ) )
			{
				$InstanceID = (int)$Matches[ 5 ];
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
				$AccountType = array_search( $Type, self::$AccountTypeChars, true );

				$this->SetAccountType( $AccountType );
			}

			$this->SetAccountUniverse( (int)$Matches[ 2 ] );
			$this->SetAccountInstance( $InstanceID );
			$this->SetAccountID( $AccountID );
		}
		else if( self::IsNumeric( $Value ) )
		{
			$this->ParseUInt64( (int)$Value );
		}
		else
		{
			throw new \InvalidArgumentException( 'Provided SteamID is invalid.' );
		}
	}

	/**
	 * Renders this instance into it's Steam2 "STEAM_" representation.
	 *
	 * @return string A string Steam2 "STEAM_" representation of this SteamID.
	 */
	public function RenderSteam2() : string
	{
		switch( $this->Type )
		{
			case self::TypeInvalid:
			case self::TypeIndividual:
			{
				$Universe = $this->Universe;
				$AccountID = $this->ID;

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
	 * @return string A string Steam3 representation of this SteamID.
	 */
	public function RenderSteam3() : string
	{
		$AccountInstance = $this->Instance;
		$AccountType = $this->Type;
		$AccountTypeChar = self::$AccountTypeChars[ $AccountType ] ?? 'i';

		$RenderInstance = false;

		switch( $AccountType )
		{
			case self::TypeChat:
			{
				if( $AccountInstance & self::InstanceFlagClan )
				{
					$AccountTypeChar = 'c';
				}
				else if( $AccountInstance & self::InstanceFlagLobby )
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

		$Return = '[' . $AccountTypeChar . ':' . $this->Universe . ':' . $this->ID;

		if( $RenderInstance )
		{
			$Return .= ':' . $AccountInstance;
		}

		return $Return . ']';
	}

	/**
	 * Renders this instance into Steam's new invite code. Which can be formatted as:
	 * http://s.team/p/%s
	 * https://steamcommunity.com/user/%s
	 *
	 * @throws \InvalidArgumentException
	 *
	 * @return string A Steam invite code which can be used in a URL.
	 */
	public function RenderSteamInvite() : string
	{
		switch( $this->Type )
		{
			case self::TypeInvalid:
			case self::TypeIndividual:
			{
				$Code = dechex( $this->ID );
				$Code = strtr( $Code, self::$SteamInviteDictionary );
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
				throw new \InvalidArgumentException( 'This can only be used on Individual SteamID.' );
			}
		}
	}

	/**
	 * Renders this instance into friend code used by CS:GO.
	 * Looks like SUCVS-FADA.
	 *
	 * Based on <https://github.com/emily33901/go-csfriendcode>
	 * and looking at CSGO's client.dll.
	 *
	 * @throws \InvalidArgumentException
	 *
	 * @return string A friend code which can be used in CS:GO.
	 */
	public function RenderCsgoFriendCode() : string
	{
		return CSGOFriendCodes::Render( $this );
	}

	/**
	 * Gets a value indicating whether this instance is valid.
	 *
	 * @return bool true if this instance is valid; otherwise, false.
	 */
	public function IsValid() : bool
	{
		$AccountType = $this->Type;

		if( $AccountType <= self::TypeInvalid || $AccountType > self::TypeAnonUser )
		{
			return false;
		}

		$AccountUniverse = $this->Universe;

		if( $AccountUniverse <= self::UniverseInvalid || $AccountUniverse > self::UniverseDev )
		{
			return false;
		}

		$AccountID = $this->ID;
		$AccountInstance = $this->Instance;

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
	 * @param callable(string, int):?string $VanityCallback Callback which is called when a vanity lookup is required
	 *
	 * @throws \InvalidArgumentException
	 *
	 * @return SteamID Fluent interface
	 */
	public static function SetFromURL( string $Value, callable $VanityCallback ) : self
	{
		if( preg_match( '/^https?:\/\/(?:my\.steamchina|steamcommunity)\.com\/(?P<type>profiles|gid)\/(?P<id>.+?)(?:\/|$)/', $Value, $Matches ) === 1 )
		{
			$Value = $Matches[ 'id' ];
		}
		else if( preg_match( '/^https?:\/\/(?:my\.steamchina|steamcommunity)\.com\/(?P<type>id|groups|games)\/(?P<id>[\w-]+)(?:\/|$)/', $Value, $Matches ) === 1
		||       preg_match( '/^(?P<type>)(?P<id>[\w-]+)$/', $Value, $Matches ) === 1 ) // Empty capturing group so that $Matches has same indexes
		{
			$Length = strlen( $Matches[ 'id' ] );

			if( $Length < 2 || $Length > 32 )
			{
				throw new \InvalidArgumentException( 'Provided vanity url has bad length.' );
			}

			// Steam doesn't allow vanity urls to be valid steamids
			if( self::IsNumeric( $Matches[ 'id' ] ) )
			{
				$SteamID = new self( $Matches[ 'id' ] );

				if( $SteamID->IsValid() )
				{
					return $SteamID;
				}
			}

			switch( $Matches[ 'type' ] )
			{
				case 'groups': $VanityType = self::VanityGroup; break;
				case 'games' : $VanityType = self::VanityGameGroup; break;
				default      : $VanityType = self::VanityIndividual;
			}

			$Value = call_user_func( $VanityCallback, $Matches[ 'id' ], $VanityType );

			if( $Value === null )
			{
				throw new \InvalidArgumentException( 'Provided vanity url does not resolve to any SteamID.' );
			}
		}
		else if( preg_match( '/^https?:\/\/(?:(?:my\.steamchina|steamcommunity)\.com\/user|s\.team\/p)\/(?P<id>[\w-]+)(?:\/|$)/', $Value, $Matches ) === 1 )
		{
			$Value = strtolower( $Matches[ 'id' ] );
			$Value = preg_replace( '/[^' . implode( '', self::$SteamInviteDictionary ) . ']/', '', $Value );
			$Value = strtr( (string)$Value, array_flip( self::$SteamInviteDictionary ) );
			$Value = hexdec( $Value );

			$Value = '[U:1:' . $Value . ']';
		}

		return new self( $Value );
	}

	/**
	 * Sets the various components of this SteamID from a 64bit integer form.
	 *
	 * @param int|string $Value The 64bit integer to assign this SteamID from.
	 *
	 * @throws \InvalidArgumentException
	 *
	 * @return SteamID Fluent interface
	 */
	public function SetFromUInt64( int|string $Value ) : self
	{
		if( self::IsNumeric( $Value ) )
		{
			$this->ParseUInt64( (int)$Value );
		}
		else
		{
			throw new \InvalidArgumentException( 'Provided SteamID is not numeric.' );
		}

		return $this;
	}

	/**
	 * Converts this SteamID into it's 64bit integer form.
	 *
	 * @return string A 64bit integer representing this SteamID.
	 */
	public function ConvertToUInt64() : string
	{
		$ID = ( $this->Universe << 56 )
			| ( $this->Type << 52 )
			| ( $this->Instance << 32 )
			| ( $this->ID );

		return (string)$ID;
	}

	/**
	 * Gets the account id.
	 *
	 * @return int The account id.
	 */
	public function GetAccountID() : int
	{
		return $this->ID;
	}

	/**
	 * Gets the account instance.
	 *
	 * @return int The account instance.
	 */
	public function GetAccountInstance() : int
	{
		return $this->Instance;
	}

	/**
	 * Gets the account type.
	 *
	 * @return int The account type.
	 */
	public function GetAccountType() : int
	{
		return $this->Type;
	}

	/**
	 * Gets the account universe.
	 *
	 * @return int The account universe.
	 */
	public function GetAccountUniverse() : int
	{
		return $this->Universe;
	}

	/**
	 * Sets the account from the given CS:GO friend code (looks like SUCVS-FBAC).
	 *
	 * @param string $Value The CS:GO friend code.
	 *
	 * @throws \InvalidArgumentException
	 *
	 * @return SteamID Fluent interface
	 */
	public function SetFromCsgoFriendCode( string $Value ) : self
	{
		$s = CSGOFriendCodes::SetFromCode( $Value );
		$this->SetAccountID( $s->GetAccountID() );
		$this->SetAccountType( $s->GetAccountType() );
		$this->SetAccountInstance( $s->GetAccountInstance() );
		$this->SetAccountUniverse( $s->GetAccountUniverse() );

		return $this;
	}

	/**
	 * Sets the account id.
	 *
	 * @param int $Value The account id.
	 *
	 * @return SteamID Fluent interface
	 */
	public function SetAccountID( int $Value ) : self
	{
		if( $Value < 0 || $Value > 0xFFFFFFFF )
		{
			throw new \InvalidArgumentException( 'Account id can not be higher than 0xFFFFFFFF.' );
		}

		$this->ID = $Value;

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
			throw new \InvalidArgumentException( 'Account instance can not be higher than 0xFFFFF.' );
		}

		$this->Instance = $Value;

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
			throw new \InvalidArgumentException( 'Account type can not be higher than 0xF.' );
		}

		$this->Type = $Value;

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
			throw new \InvalidArgumentException( 'Account universe can not be higher than 0xFF.' );
		}

		$this->Universe = $Value;

		return $this;
	}

	/**
	 * Splits 64-bit steamid into individual components.
	 *
	 * @param int $Value 64-bit steamid
	 */
	private function ParseUInt64( int $Value ) : void
	{
		$this->Universe = $Value >> 56;
		$this->Type = ( $Value >> 52 ) & 0xF;
		$this->Instance = ( $Value >> 32 ) & 0xFFFFF;
		$this->ID = $Value & 0xFFFFFFFF;
	}

	/**
	 * This is way more restrictive than php's is_numeric().
	 */
	private static function IsNumeric( int|string $n ) : bool
	{
		if( is_int( $n ) )
		{
			return $n > 0;
		}

		return preg_match( '/^[1-9][0-9]{0,19}$/', $n ) === 1;
	}

	public function __toString() : string
	{
		return $this->ConvertToUInt64();
	}
}
