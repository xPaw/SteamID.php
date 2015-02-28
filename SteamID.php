<?php
/**
 * This 64bit structure is used for identifying various objects on the Steam network.
 * 
 * This library requires GMP module to be installed.
 * 
 * This implementation was ported by xPaw from SteamKit:
 * https://github.com/SteamRE/SteamKit/blob/master/SteamKit2/SteamKit2/Types/SteamID.cs
 * 
 * GitHub: https://github.com/xPaw/SteamID.php
 * Website: http://xpaw.me
 */
class SteamID
{
	private static $AccountTypeChars = Array(
		self :: TypeAnonGameServer => 'A',
		self :: TypeGameServer => 'G',
		self :: TypeMultiseat => 'M',
		self :: TypePending => 'P',
		self :: TypeContentServer => 'C',
		self :: TypeClan => 'g',
		self :: TypeChat => 'T', // Lobby chat is 'L', Clan chat is 'c'
		self :: TypeInvalid => 'I',
		self :: TypeIndividual => 'U',
		self :: TypeAnonUser => 'a',
	);
	
	/**
	 * Steam universes. Each universe is a self-contained Steam instance.
	 */
	const UniverseInvalid = 0;
	const UniversePublic = 1;
	const UniverseBeta = 2;
	const UniverseInternal = 3;
	const UniverseDev = 4;
	const UniverseRC = 5;
	
	/**
	 * Steam account types.
	 */
	const TypeInvalid = 0;
	const TypeIndividual = 1;
	const TypeMultiseat = 2;
	const TypeGameServer = 3;
	const TypeAnonGameServer = 4;
	const TypePending = 5;
	const TypeContentServer = 6;
	const TypeClan = 7;
	const TypeChat = 8;
	const TypeP2PSuperSeeder = 9;
	const TypeAnonUser = 10;
	
	/**
	 * The account instance value when representing all instanced SteamIDs.
	 */
	const AllInstances = 0;
	
	/**
	 * The account instance value for a desktop SteamID.
	 */
	const DesktopInstance = 1;
	
	/**
	 * The account instance value for a console SteamID.
	 */
	const ConsoleInstance = 2;
	
	/**
	 * The account instance for mobile or web based SteamIDs.
	 */
	const WebInstance = 4;
	
	private $Data;
	
	private function Get( $bitoffset, $valuemask )
	{
		return gmp_and( self :: ShiftRight( $this->Data, $bitoffset ), $valuemask );
	}
	
	private function Set( $bitoffset, $valuemask, $value )
	{
		$this->Data = gmp_or(
			gmp_and( $this->Data, gmp_com( self :: ShiftLeft( $valuemask, $bitoffset ) ) ),
			self :: ShiftLeft( gmp_and( $value, $valuemask ), $bitoffset )
		);
	}
	
	/**
	 * Initializes a new instance of the SteamID class.
	 * 
	 * It automatically guessess which type the input is, and works from there.
	 */
	public function __construct( $Value = null )
	{
		$this->Data = gmp_init( 0 );
		
		if( !$Value )
		{
			return;
		}
		
		// SetFromString
		if( preg_match( '/^STEAM_([0-5]):([0-1]):([0-9]+)$/', $Value, $Matches ) === 1 )
		{
			$AuthServer = (int)$Matches[ 2 ];
			$AccountID = ( (int)$Matches[ 3 ] << 1 ) | $AuthServer;
			
			$this->SetAccountUniverse( self :: UniversePublic );
			$this->SetAccountInstance( self :: DesktopInstance );
			$this->SetAccountType( self :: TypeIndividual );
			$this->SetAccountID( $AccountID );
		}
		// SetFromSteam3String
		else if( preg_match( '/^\\[([AGMPCgcLTIUai]):([0-5]):([0-9]+)(:[0-9]+)?\\]$/', $Value, $Matches ) === 1 )
		{
			$Type = $Matches[ 1 ];
			
			$InstanceID = isset( $Matches[ 4 ] ) ? (int)ltrim( $Matches[ 4 ], ':' ) : ( $Type === 'g' ? 0 : 1 );
			
			if( $Type === 'c' )
			{
				$InstanceID |= 524288; // ( AccountInstanceMask + 1 ) >> 1
				
				$this->SetAccountType( self :: TypeChat );
			}
			else if( $Type === 'L' )
			{
				$InstanceID |= 262144; // ( AccountInstanceMask + 1 ) >> 2
				
				$this->SetAccountType( self :: TypeChat );
			}
			else
			{
				$this->SetAccountType( array_search( $Type, self :: $AccountTypeChars, true ) );
			}
			
			$this->SetAccountUniverse( (int)$Matches[ 2 ] );
			$this->SetAccountInstance( $InstanceID );
			$this->SetAccountID( (int)$Matches[ 3 ] );
		}
		else if( is_numeric( $Value ) )
		{
			$this->Data = gmp_init( $Value );
		}
	}
	
	/**
	 * Renders this instance into it's Steam2 "STEAM_" representation.
	 * 
	 * @return string A string Steam2 "STEAM_" representation of this SteamID.
	 */
	public function RenderSteam2()
	{
		switch( $this->GetAccountType() )
		{
			case self :: TypeInvalid:
			case self :: TypeIndividual:
			{
				$Universe = $this->GetAccountUniverse();
				
				if( $Universe === self :: UniversePublic )
				{
					// They're both STEAM_0
					$Universe = self :: UniverseInvalid;
				}
				
				$AccountID = $this->GetAccountID();
				
				return 'STEAM_' . $Universe . ':' . ( $AccountID & 1 ) . ':' . ( $AccountID >> 1 );
			}
			default:
			{
				return 'STEAM_INVALID';
			}
		}
	}
	
	/**
	 * Renders this instance into it's Steam3 representation.
	 * 
	 * @return string A string Steam3 representation of this SteamID.
	 */
	public function RenderSteam3()
	{
		$AccountInstance = $this->GetAccountInstance();
		$AccountType = $this->GetAccountType();
		$AccountTypeChar = isset( self :: $AccountTypeChars[ $AccountType ] ) ? self :: $AccountTypeChars[ $AccountType ] : 'i';
		
		/*if ( AccountType == EAccountType.Chat )
		{
		    if ( ( ( ChatInstanceFlags )AccountInstance ).HasFlag( ChatInstanceFlags.Clan ) )
		       accountTypeChar = 'c';
		    else if ( ( ( ChatInstanceFlags )AccountInstance ).HasFlag( ChatInstanceFlags.Lobby ) )
		        accountTypeChar = 'L';
		}*/
		
		$RenderInstance = false;
		
		switch( $AccountType )
		{
			case self :: TypeAnonGameServer:
			case self :: TypeMultiseat:
			{
				$RenderInstance = true;
				
				break;
			}
			case self :: TypeIndividual:
			{
				$RenderInstance = $AccountInstance != self :: DesktopInstance;
				
				break;
			}
		}
		
		$Return = '[' . $AccountTypeChar . ':' . $this->GetAccountUniverse() . ':' . $this->GetAccountID();
		
		if( $RenderInstance )
		{
			$Return .= ':' . $AccountInstance;
		}
		
		return $Return . ']';
	}
	
	/**
	 * Gets a value indicating whether this instance is valid.
	 * 
	 * @return bool true if this instance is valid; otherwise, false.
	 */
	public function IsValid( )
	{
		$AccountType = $this->GetAccountType();
		
		if( $AccountType <= self :: TypeInvalid || $AccountType >= 11 ) // EAccountType.Max
		{
			return false;
		}
		
		$AccountUniverse = $this->GetAccountUniverse();
		
		// We don't careabout non-public universes,
		// but it should be ( this.AccountUniverse <= EUniverse.Invalid || this.AccountUniverse >= EUniverse.Max )
		if( $AccountUniverse > self :: UniversePublic )
		{
			return false;
		}
		
		$AccountID = $this->GetAccountID();
		$AccountInstance = $this->GetAccountInstance();
		
		if( $AccountType === self :: TypeIndividual )
		{
			if( $AccountID == 0 || $AccountInstance > self :: WebInstance )
			{
				return false;
			}
		}
		
		if( $AccountType === self :: TypeClan )
		{
			if( $AccountID == 0 || $AccountInstance != 0 )
			{
				return false;
			}
		}
		
		if( $AccountType === self :: TypeGameServer )
		{
			if( $AccountID == 0 )
			{
				return false;
			}
		}
		
		return true;
	}
	
	/**
	 * Sets the various components of this SteamID from a 64bit integer form.
	 * 
	 * @param Value The 64bit integer to assign this SteamID from.
	 */
	public function SetFromUInt64( $Value )
	{
		if( is_numeric( $Value ) )
		{
			$this->Data = gmp_init( $Value );
		}
		
		// TODO: should it throw on non-numeric value, or return a boolean?
	}
	
	/**
	 * Converts this SteamID into it's 64bit integer form. This function returns as a string to work on 32-bit PHP systems.
	 * 
	 * @return string A 64bit integer representing this SteamID.
	 */
	public function ConvertToUInt64()
	{
		return gmp_strval( $this->Data );
	}
	
	/**
	 * Gets the account id.
	 * 
	 * @return int The account id.
	 */
	public function GetAccountID()
	{
		return gmp_intval( $this->Get( 0, '0xFFFFFFFF' ) );
	}
	
	/**
	 * Gets the account instance.
	 * 
	 * @return int The account instance.
	 */
	public function GetAccountInstance()
	{
		return gmp_intval( $this->Get( 32, '0xFFFFF' ) );
	}
	
	/**
	 * Gets the account type.
	 * 
	 * @return int The account type.
	 */
	public function GetAccountType()
	{
		return gmp_intval( $this->Get( 52, '0xF' ) );
	}
	
	/**
	 * Gets the account universe.
	 * 
	 * @return int The account universe.
	 */
	public function GetAccountUniverse()
	{
		return gmp_intval( $this->Get( 56, '0xFF' ) );
	}
	
	/**
	 * Sets the account id.
	 * 
	 * @param Value The account id.
	 */
	public function SetAccountID( $Value )
	{
		$this->Set( 0, '0xFFFFFFFF', $Value );
	}
	
	/**
	 * Sets the account instance.
	 * 
	 * @param Value The account instance.
	 */
	public function SetAccountInstance( $Value )
	{
		$this->Set( 32, '0xFFFFF', $Value );
	}
	
	/**
	 * Sets the account type.
	 * 
	 * @param Value The account type.
	 */
	public function SetAccountType( $Value )
	{
		$this->Set( 52, '0xF', $Value );
	}
	
	/**
	 * Sets the account universe.
	 * 
	 * @param Value The account universe.
	 */
	public function SetAccountUniverse( $Value )
	{
		$this->Set( 56, '0xFF', $Value );
	}
	
	private static function ShiftLeft( $x, $n )
	{
		return gmp_mul( $x, gmp_pow( 2, $n ) );
	}

	private static function ShiftRight( $x, $n )
	{
		return gmp_div( $x, gmp_pow( 2, $n ) );
	}
}
