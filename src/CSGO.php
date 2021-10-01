<?php
declare(strict_types=1);

namespace xPaw\SteamID;

class CSGO
{
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
	public static function RenderCsgoFriendCode( SteamID $SteamID ) : string
	{
		$AccountType = $SteamID->GetAccountType();

		if( $AccountType !== SteamID::TypeInvalid && $AccountType !== SteamID::TypeIndividual )
		{
			throw new \InvalidArgumentException( 'This can only be used on Individual SteamID.' );
		}

		// Shift by string "CSGO" (0x4353474)
		$Hash = gmp_or( $SteamID->GetAccountID(), '0x4353474F00000000' );

		// Convert it to little-endian
		$Hash = gmp_export( $Hash, 8, GMP_LITTLE_ENDIAN );

		// Hash the exported number
		$Hash = md5( $Hash, true );

		// Take the first 4 bytes and convert it back to a number
		$Hash = gmp_import( substr( $Hash, 0, 4 ), 4, GMP_LITTLE_ENDIAN );

		$Result = gmp_init( 0 );
		$SteamId64 = $SteamID->ConvertToUInt64();

		for( $i = 0; $i < 8; $i++ )
		{
			$IdNibble = gmp_and( self::ShiftRight( $SteamId64, 4 * $i ), '0xF' );
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
	 * Sets the account from the given CS:GO friend code (looks like SUCVS-FBAC).
	 *
	 * @param string $Value The CS:GO friend code.
	 *
	 * @throws \InvalidArgumentException
	 *
	 * @return SteamID Fluent interface
	 */
	public static function SetFromCsgoFriendCode( string $Value ) : SteamID
	{
		$SteamID = new SteamID();
		$Length = strlen( $Value );

		if( $Length === 10 ) // Friend codes
		{
			$AccountId = self::DecodeCsgoCode( $Value );
			$SteamID->SetAccountID( $AccountId );
			$SteamID->SetAccountType( SteamID::TypeIndividual );
			$SteamID->SetAccountUniverse( SteamID::UniversePublic );
			$SteamID->SetAccountInstance( 1 );
		}
		else if( $Length === 21 ) // Private queue invite codes
		{
			if( $Value[ 10 ] !== '-' )
			{
				throw new \InvalidArgumentException( 'Given input is not a valid CS:GO code.' );
			}

			$Left = self::DecodeCsgoCode( substr( $Value, 0, 10 ) );
			$Right = self::DecodeCsgoCode( substr( $Value, 11, 10 ) );

			$AccountId = gmp_intval( gmp_add(
				gmp_and( $Left, '0x0000FFFF' ),
				self::ShiftLeft( gmp_and( $Right, '0x0000FFFF' ), 16 )
			) );

			$IsGroup =
				gmp_intval( gmp_and( $Left, '0xFFFF0000' ) ) == 0x10000 &&
				gmp_intval( gmp_and( $Right, '0xFFFF0000' ) ) == 0x10000;

			$SteamID->SetAccountID( $AccountId );
			$SteamID->SetAccountType( $IsGroup ? SteamID::TypeClan : SteamID::TypeIndividual );
			$SteamID->SetAccountUniverse( SteamID::UniversePublic );
			$SteamID->SetAccountInstance( 1 );
		}
		else
		{
			throw new \InvalidArgumentException( 'Given input is not a valid CS:GO code.' );
		}

		return $SteamID;
	}

	private static function DecodeCsgoCode( string $Value ) : int
	{
		if( $Value[ 5 ] !== '-' )
		{
			throw new \InvalidArgumentException( 'Given input is not a valid CS:GO code.' );
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
				throw new \InvalidArgumentException( 'Given input is malformed.' );
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
	 * Shift the bits of $x by $n steps to the left.
	 */
	private static function ShiftLeft( int|string|\GMP $x, int $n ) : \GMP
	{
		return gmp_mul( $x, gmp_pow( 2, $n ) );
	}

	/**
	 * Shift the bits of $x by $n steps to the right.
	 */
	private static function ShiftRight( int|string|\GMP $x, int $n ) : \GMP
	{
		return gmp_div_q( $x, gmp_pow( 2, $n ) );
	}
}
