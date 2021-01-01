<?php
declare(strict_types=1);

/**
 * The SteamID library provides an easy way to work with SteamIDs and makes
 * conversions easy. Ported from SteamKit.
 *
 * This 64bit structure is used for identifying various objects on the Steam
 * network.
 *
 * This library requires GMP module to be installed.
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
	 * @var array Types of steam account
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
	 * @var array List of replacement hex characters used in /user/ URLs
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
	 * of the steam ID's "instance", leaving 12 for the actual instances
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
	
	/**
	 * @var \GMP
	 */
	private $Data;
	
	/**
	 * Initializes a new instance of the SteamID class.
	 *
	 * It automatically guesses which type the input is, and works from there.
	 *
	 * @param int|string|null $Value
	 */
	public function __construct( $Value = null )
	{
		$this->Data = gmp_init( 0 );
		
		if( $Value === null )
		{
			return;
		}
		
		// SetFromString
		if( preg_match( '/^STEAM_([0-4]):([0-1]):([0-9]{1,10})$/', (string)$Value, $Matches ) === 1 )
		{
			$AccountID = $Matches[ 3 ];
			
			// Check for max unsigned 32-bit number
			if( gmp_cmp( $AccountID, '4294967295' ) > 0 )
			{
				throw new InvalidArgumentException( 'Provided SteamID exceeds max unsigned 32-bit integer.' );
			}
			
			$Universe = (int)$Matches[ 1 ];
			
			// Games before orange box used to incorrectly display universe as 0, we support that
			if( $Universe === self::UniverseInvalid )
			{
				$Universe = self::UniversePublic;
			}
			
			$AuthServer = (int)$Matches[ 2 ];
			$AccountID = ( (int)$AccountID << 1 ) | $AuthServer;
			
			$this->SetAccountUniverse( $Universe );
			$this->SetAccountInstance( self::DesktopInstance );
			$this->SetAccountType( self::TypeIndividual );
			$this->SetAccountID( $AccountID );
		}
		// SetFromSteam3String
		else if( preg_match( '/^\\[([AGMPCgcLTIUai]):([0-4]):([0-9]{1,10})(:([0-9]+))?\\]$/', (string)$Value, $Matches ) === 1 )
		{
			$AccountID = $Matches[ 3 ];
			
			// Check for max unsigned 32-bit number
			if( gmp_cmp( $AccountID, '4294967295' ) > 0 )
			{
				throw new InvalidArgumentException( 'Provided SteamID exceeds max unsigned 32-bit integer.' );
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
				$this->SetAccountType( array_search( $Type, self::$AccountTypeChars, true ) );
			}
			
			$this->SetAccountUniverse( (int)$Matches[ 2 ] );
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
	 * Renders this instance into it's Steam2 "STEAM_" representation.
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
	 * @return string A string Steam3 representation of this SteamID.
	 */
	public function RenderSteam3() : string
	{
		$AccountInstance = $this->GetAccountInstance();
		$AccountType = $this->GetAccountType();
		$AccountTypeChar = isset( self::$AccountTypeChars[ $AccountType ] ) ? 
			self::$AccountTypeChars[ $AccountType ] : 
			'i';
		
		$RenderInstance = false;
		
		switch( $AccountType )
		{
			case self::TypeChat:
			{
				if( $AccountInstance & SteamID::InstanceFlagClan )
				{
					$AccountTypeChar = 'c';
				}
				else if( $AccountInstance & SteamID::InstanceFlagLobby )
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
			case self::TypeIndividual:
			{
				$RenderInstance = $AccountInstance != self::DesktopInstance;
				
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
	 * Renders this instance into Steam's new invite code. Which can be formatted as:
	 * http://s.team/p/%s
	 * https://steamcommunity.com/user/%s
	 *
	 * @return string A Steam invite code which can be used in a URL.
	 */
	public function RenderSteamInvite() : string
	{
		switch( $this->GetAccountType() )
		{
			case self::TypeInvalid:
			case self::TypeIndividual:
			{
				$Code = dechex( $this->GetAccountID() );
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
				throw new InvalidArgumentException( 'This can only be used on Individual SteamID.' );
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
	 * @return string A friend code which can be used in CS:GO.
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
		// not using gmp as it is 32-bit
		$Hash = unpack( 'i', substr( $Hash, 0, 4 ) )[ 1 ];

		$Result = gmp_init( 0 );

		for( $i = 0; $i < 8; $i++ )
		{
			$IdNibble = $this->Get( 4 * $i, '0xF' );
			$HashNibble = gmp_and( self::ShiftRight( $Hash, $i ), 1 );

			$a = gmp_or( self::ShiftLeft( $Result, 4 ), $IdNibble );

			// Valve certainly knows how to turn accountid into
			// a complicated algorhitm for no good reason
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
		$i = 0;

		for( $i = 0; $i < 13; $i++ )
		{
			if( $i === 4 || $i === 9 )
			{
				$FriendCode .= '-';
			}

			$FriendCode .= $Base32[ (int)gmp_and( $Result, 31 ) ];
			$Result = self::ShiftRight( $Result, 5 );
		}

		// Strip the AAAA- prefix
		return substr( $FriendCode, 5 );
	}

	/**
	 * Gets a value indicating whether this instance is valid.
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
			if( $AccountID == 0 || $AccountInstance > self::WebInstance )
			{
				return false;
			}
		}
		
		if( $AccountType === self::TypeClan )
		{
			if( $AccountID == 0 || $AccountInstance != 0 )
			{
				return false;
			}
		}
		
		if( $AccountType === self::TypeGameServer )
		{
			if( $AccountID == 0 )
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
	 * @param callable $VanityCallback Callback which is called when a vanity lookup is required
	 * 
	 * @return SteamID Fluent interface
	 * 
	 * @throws InvalidArgumentException
	 */
	public static function SetFromURL( string $Value, callable $VanityCallback ) : SteamID
	{
		if( preg_match( '/^https?:\/\/steamcommunity\.com\/profiles\/(.+?)(?:\/|$)/', $Value, $Matches ) === 1 )
		{
			$Value = $Matches[ 1 ];
		}
		else if( preg_match( '/^https?:\/\/steamcommunity\.com\/(id|groups|games)\/([\w-]+)(?:\/|$)/', $Value, $Matches ) === 1
		||       preg_match( '/^()([\w-]+)$/', $Value, $Matches ) === 1 ) // Empty capturing group so that $Matches has same indexes
		{
			$Length = strlen( $Matches[ 2 ] );
			
			if( $Length < 2 || $Length > 32 )
			{
				throw new InvalidArgumentException( 'Provided vanity url has bad length.' );
			}
			
			// Steam doesn't allow vanity urls to be valid steamids
			if( self::IsNumeric( $Matches[ 2 ] ) )
			{
				$SteamID = new SteamID( $Matches[ 2 ] );
				
				if( $SteamID->IsValid() )
				{
					return $SteamID;
				}
			}
			
			switch( $Matches[ 1 ] )
			{
				case 'groups': $VanityType = self::VanityGroup; break;
				case 'games' : $VanityType = self::VanityGameGroup; break;
				default      : $VanityType = self::VanityIndividual;
			}
			
			$Value = call_user_func( $VanityCallback, $Matches[ 2 ], $VanityType );
			
			if( $Value === null )
			{
				throw new InvalidArgumentException( 'Provided vanity url does not resolve to any SteamID.' );
			}
		}
		else if( preg_match( '/^https?:\/\/(steamcommunity\.com\/user|s\.team\/p)\/([\w-]+)(?:\/|$)/', $Value, $Matches ) === 1 )
		{
			$Value = strtolower( $Matches[ 2 ] );
			$Value = preg_replace( '/[^' . implode( '', self::$SteamInviteDictionary ) . ']/', '', $Value );
			$Value = strtr( $Value, array_flip( self::$SteamInviteDictionary ) );
			$Value = hexdec( $Value );
			
			$Value = '[U:1:' . $Value . ']';
		}
		
		return new SteamID( $Value );
	}
	
	/**
	 * Sets the various components of this SteamID from a 64bit integer form.
	 *
	 * @param int|string $Value The 64bit integer to assign this SteamID from.
	 * 
	 * @return SteamID Fluent interface
	 * 
	 * @throws InvalidArgumentException
	 */
	public function SetFromUInt64( $Value ) : SteamID
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
	 * Converts this SteamID into it's 64bit integer form. This function returns 
	 * as a string to work on 32-bit PHP systems.
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
	public function SetFromCsgoFriendCode( $Value ) : SteamID
	{
		if( !is_string( $Value ) || strlen( $Value ) !== 10 || $Value[ 5 ] !== '-' )
		{
			throw new InvalidArgumentException( 'Given input is not a valid CS:GO friend code.' );
		}

		$Value = 'AAAA-' . $Value;
		$Value = str_replace( '-', '', $Value );

		$Base32 = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
		$Result = gmp_init( 0 );

		for( $i = 0; $i < 13; $i++ )
		{
			$Character = strpos( $Base32, $Value[ $i ] );

			if( $Character === false )
			{
				throw new InvalidArgumentException( 'Given input is malformed.' );
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

		$this->SetAccountID( $AccountId );
		$this->SetAccountType( self::TypeIndividual );
		$this->SetAccountUniverse( self::UniversePublic );
		$this->SetAccountInstance( 1 );

		return $this;
	}

	/**
	 * Gets the account id.
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
	 * @return int The account instance.
	 */
	public function GetAccountInstance() : int
	{
		return gmp_intval( $this->Get( 32, '1048575' ) ); // 1048575 = 0xFFFFF
	}
	
	/**
	 * Gets the account type.
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
	public function SetAccountID( $Value ) : SteamID
	{
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
	public function SetAccountInstance( int $Value ) : SteamID
	{
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
	public function SetAccountType( int $Value ) : SteamID
	{
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
	public function SetAccountUniverse( $Value ) : SteamID
	{
		$this->Set( 56, '255', $Value ); // 255 = 0xFF
		
		return $this;
	}
	
	/**
	 * @param int $BitOffset
	 * @param int|string $ValueMask
	 * 
	 * @return \GMP
	 */
	private function Get( int $BitOffset, $ValueMask ) : \GMP
	{
		return gmp_and( self::ShiftRight( $this->Data, $BitOffset ), $ValueMask );
	}
	
	/**
	 * @param int $BitOffset
	 * @param int|string $ValueMask
	 * @param int|string $Value
	 * 
	 * @return void
	 */
	private function Set( int $BitOffset, $ValueMask, $Value ) : void
	{
		$this->Data = gmp_or(
			gmp_and( $this->Data, gmp_com( self::ShiftLeft( $ValueMask, $BitOffset ) ) ),
			self::ShiftLeft( gmp_and( $Value, $ValueMask ), $BitOffset )
		);
	}
	
	/**
	 * Shift the bits of $x by $n steps to the left
	 * 
	 * @param int|string|\GMP $x
	 * @param int $n
	 *
	 * @return \GMP
	 */
	private static function ShiftLeft( $x, int $n ) : \GMP
	{
		return gmp_mul( $x, gmp_pow( 2, $n ) );
	}
	
	/**
	 * Shift the bits of $x by $n steps to the right
	 * 
	 * @param int|string|\GMP $x
	 * @param int $n
	 *
	 * @return \GMP
	 */
	private static function ShiftRight( $x, int $n ) : \GMP
	{
		return gmp_div_q( $x, gmp_pow( 2, $n ) );
	}
	
	/**
	 * This is way more restrictive than php's is_numeric()
	 *
	 * @param int|string $n
	 *
	 * @return bool
	 */
	private static function IsNumeric( $n ) : bool
	{
		return preg_match( '/^[1-9][0-9]{0,19}$/', (string)$n ) === 1;
	}
}
