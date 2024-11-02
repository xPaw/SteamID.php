<?php
declare(strict_types=1);

use xPaw\Steam\SteamID;

class SteamIDFacts extends PHPUnit\Framework\TestCase
{
	public function testEmptyConstructorInvalid( ) : void
	{
		$s = new SteamID;
		$this->assertFalse( $s->IsValid() );
	}

	public function testManualConstructionValid( ) : void
	{
		$s = (new SteamID)
			->SetAccountUniverse( SteamID::UniverseBeta )
			->SetAccountInstance( SteamID::ConsoleInstance )
			->SetAccountType( SteamID::TypeChat )
			->SetAccountID( 1234 );

		$this->assertEquals( 1234, $s->GetAccountID() );
		$this->assertEquals( SteamID::ConsoleInstance, $s->GetAccountInstance() );
		$this->assertEquals( SteamID::UniverseBeta, $s->GetAccountUniverse() );
		$this->assertEquals( SteamID::TypeChat, $s->GetAccountType() );

		$s = (new SteamID)
			->SetAccountUniverse( SteamID::UniverseInternal )
			->SetAccountType( SteamID::TypeContentServer )
			->SetAccountID( 1234 );

		$this->assertEquals( 1234, $s->GetAccountID() );
		$this->assertEquals( SteamID::UniverseInternal, $s->GetAccountUniverse() );
		$this->assertEquals( SteamID::TypeContentServer, $s->GetAccountType() );

		$s->SetAccountType( 15 );

		$this->assertFalse( $s->IsValid() );
		$this->assertEquals( 15, $s->GetAccountType() );

		$s = (new SteamID)
			->SetAccountUniverse( 255 )
			->SetAccountType( SteamID::TypeClan )
			->SetAccountID( 4321 );

		$this->assertFalse( $s->IsValid() );
		$this->assertEquals( 4321, $s->GetAccountID() );
		$this->assertEquals( 0, $s->GetAccountInstance() );
		$this->assertEquals( 255, $s->GetAccountUniverse() );
		$this->assertEquals( SteamID::TypeClan, $s->GetAccountType() );

		$s->SetAccountUniverse( SteamID::UniversePublic );

		$this->assertTrue( $s->IsValid() );
		$this->assertEquals( SteamID::UniversePublic, $s->GetAccountUniverse() );
	}

	public function testLongConstructorAndSetterGetterValid( ) : void
	{
		$s = new SteamID( '103582791432294076' );

		$this->assertEquals( 2772668, $s->GetAccountID() );
		$this->assertEquals( SteamID::AllInstances, $s->GetAccountInstance() );
		$this->assertEquals( SteamID::UniversePublic, $s->GetAccountUniverse() );
		$this->assertEquals( SteamID::TypeClan, $s->GetAccountType() );

		$s->SetFromUInt64( '157626004137848889' );

		$this->assertEquals( 12345, $s->GetAccountID() );
		$this->assertEquals( SteamID::WebInstance, $s->GetAccountInstance() );
		$this->assertEquals( SteamID::UniverseBeta, $s->GetAccountUniverse() );
		$this->assertEquals( SteamID::TypeGameServer, $s->GetAccountType() );
	}

	public function testSteam2CorrectParse( ) : void
	{
		$s = new SteamID( 'STEAM_0:0:4491990' );

		$this->assertEquals( 8983980, $s->GetAccountID() );
		$this->assertEquals( SteamID::DesktopInstance, $s->GetAccountInstance() );
		$this->assertEquals( SteamID::UniversePublic, $s->GetAccountUniverse() );

		$s = new SteamID( 'STEAM_0:1:4491990' );

		$this->assertEquals( 8983981, $s->GetAccountID() );
		$this->assertEquals( SteamID::DesktopInstance, $s->GetAccountInstance() );
		$this->assertEquals( SteamID::UniversePublic, $s->GetAccountUniverse() );

		$s = new SteamID( 'STEAM_1:1:4491990' );

		$this->assertEquals( 8983981, $s->GetAccountID() );
		$this->assertEquals( SteamID::DesktopInstance, $s->GetAccountInstance() );
		$this->assertEquals( SteamID::UniversePublic, $s->GetAccountUniverse() );
	}

	public function testSteam3CorrectParse( ) : void
	{
		$s = new SteamID( '[U:1:123]' );

		$this->assertTrue( $s->IsValid() );
		$this->assertEquals( 123, $s->GetAccountID() );
		$this->assertEquals( SteamID::DesktopInstance, $s->GetAccountInstance() );
		$this->assertEquals( SteamID::UniversePublic, $s->GetAccountUniverse() );
		$this->assertEquals( SteamID::TypeIndividual, $s->GetAccountType() );

		$s->SetAccountInstance( 1337 );

		$this->assertEquals( 1337, $s->GetAccountInstance() );
		$this->assertFalse( $s->IsValid() );

		$s = new SteamID( '[A:1:123:456]' );

		$this->assertTrue( $s->IsValid() );
		$this->assertEquals( 123, $s->GetAccountID() );
		$this->assertEquals( 456, $s->GetAccountInstance() );
		$this->assertEquals( SteamID::UniversePublic, $s->GetAccountUniverse() );
		$this->assertEquals( SteamID::TypeAnonGameServer, $s->GetAccountType() );

		$s = new SteamID( '[L:2:123]' );

		$this->assertTrue( $s->IsValid() );
		$this->assertEquals( 123, $s->GetAccountID() );
		$this->assertTrue( !!( $s->GetAccountInstance() & SteamID::InstanceFlagLobby ) );
		$this->assertEquals( SteamID::UniverseBeta, $s->GetAccountUniverse() );
		$this->assertEquals( SteamID::TypeChat, $s->GetAccountType() );

		$s = new SteamID( '[c:3:123]' );

		$this->assertTrue( $s->IsValid() );
		$this->assertEquals( 123, $s->GetAccountID() );
		$this->assertTrue( !!( $s->GetAccountInstance() & SteamID::InstanceFlagClan ) );
		$this->assertEquals( SteamID::UniverseInternal, $s->GetAccountUniverse() );
		$this->assertEquals( SteamID::TypeChat, $s->GetAccountType() );

		$s = new SteamID( '[g:1:456]' );
		$s->SetAccountInstance( 1337 );
		$s->SetAccountID( 0 );

		$this->assertFalse( $s->IsValid() );
		$this->assertEquals( 0, $s->GetAccountID() );
		$this->assertEquals( SteamID::UniversePublic, $s->GetAccountUniverse() );
		$this->assertEquals( SteamID::TypeClan, $s->GetAccountType() );

		$s = new SteamID( '[G:4:1]' );
		$this->assertTrue( $s->IsValid() );

		$s->SetAccountID( 0 );

		$this->assertFalse( $s->IsValid() );
		$this->assertEquals( 0, $s->GetAccountID() );
		$this->assertEquals( SteamID::UniverseDev, $s->GetAccountUniverse() );
		$this->assertEquals( SteamID::TypeGameServer, $s->GetAccountType() );

		$this->assertNotEquals( 15, $s->GetAccountType() );

		$s->SetAccountType( 15 );
		$s->SetAccountUniverse( 200 );

		$this->assertFalse( $s->IsValid() );
		$this->assertEquals( 15, $s->GetAccountType() );
		$this->assertEquals( 200, $s->GetAccountUniverse() );
		$this->assertEquals( '[i:200:0]', $s->RenderSteam3() );

		$s = new SteamID( '[U:1:123:0923]' );
		$this->assertEquals( 923, $s->GetAccountInstance() );
	}

	public function testSteam3CorrectInvalidParse( ) : void
	{
		$s = new SteamID( '[i:1:123]' );
		$this->assertEquals( 123, $s->GetAccountID() );
		$this->assertEquals( SteamID::TypeInvalid, $s->GetAccountType() );
	}

	public function testSteam2RenderIsValid( ) : void
	{
		$s = new SteamID( '76561197969249708' );
		$this->assertEquals( 'STEAM_1:0:4491990', $s->RenderSteam2() );

		$s->SetAccountUniverse( SteamID::UniverseInvalid );
		$this->assertEquals( 'STEAM_0:0:4491990', $s->RenderSteam2() );

		$s->SetAccountUniverse( SteamID::UniverseBeta );
		$this->assertEquals( 'STEAM_2:0:4491990', $s->RenderSteam2() );

		$s->SetAccountType( SteamID::TypeGameServer );
		$this->assertEquals( '157625991261918636', $s->RenderSteam2() );
	}

	/**
	 * @dataProvider steam3StringProvider
	 */
	public function testSteam3StringSymmetric( string $SteamID ) : void
	{
		$s = new SteamID( $SteamID );
		$this->assertEquals( $SteamID, $s->RenderSteam3() );
	}

	/**
	 * @dataProvider steamId64BitProvider
	 */
	public function testConvertToUInt64( string $SteamID ) : void
	{
		$s = new SteamID( $SteamID );
		$this->assertEquals( $SteamID, $s->ConvertToUInt64() );
	}

	/**
	 * @dataProvider steamId64BitProvider
	 */
	public function testSetFromUInt64( string $SteamID ) : void
	{
		$s = new SteamID();
		$s->SetFromUInt64( $SteamID );
		$this->assertEquals( $SteamID, $s->ConvertToUInt64() );
	}

	/**
	 * @dataProvider steamId64BitProvider
	 */
	public function testToStringCast( string $SteamID ) : void
	{
		$s = new SteamID( $SteamID );
		$this->assertEquals( $SteamID, (string)$s );
	}

	/**
	 * @dataProvider invalidIdProvider
	 * @param int|string|null $SteamID
	 */
	public function testConstructorHandlesInvalid( $SteamID ) : void
	{
		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Provided SteamID is invalid.' );

		new SteamID( $SteamID );
	}

	/**
	 * @dataProvider invalidAccountIdsOverflowProvider
	 */
	public function testInvalidConstructorOverflow( string $SteamID ) : void
	{
		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Provided SteamID exceeds max unsigned 32-bit integer.' );

		new SteamID( $SteamID );
	}

	public function testInvalidSetFromUInt64( ) : void
	{
		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Provided SteamID is not numeric.' );

		$s = new SteamID( );
		$s->SetFromUInt64( '111failure111' );
	}

	/**
	 * @dataProvider vanityUrlProvider
	 */
	public function testSetFromUrl( string $URL ) : void
	{
		$s = SteamID::SetFromURL( $URL, [ $this, 'fakeResolveVanityURL' ] );
		$this->assertTrue( $s->IsValid() );
	}

	/**
	 * @dataProvider inviteUrlProvider
	 */
	public function testSetFromInviteUrl( string $URL ) : void
	{
		$s = SteamID::SetFromURL( $URL, [ $this, 'fakeResolveVanityURL' ] );
		$this->assertEquals( '[U:1:12229257]', $s->RenderSteam3() );
	}

	public function testSetFromGidUrl( ) : void
	{
		$s = SteamID::SetFromURL( 'https://steamcommunity.com/gid/103582791433666425', [ $this, 'fakeResolveVanityURL' ] );
		$this->assertEquals( '[g:1:4145017]', $s->RenderSteam3() );
	}

	public function testInvalidSteamInviteType( ) : void
	{
		$this->expectException( InvalidArgumentException::class );

		$a = new SteamID( '[A:2:165:1234]' );
		$a->RenderSteamInvite();
	}

	public function testRenderSteamInvite( ) : void
	{
		$a = new SteamID( '[U:1:12229257]' );
		$this->assertEquals( 'qpn-pmn', $a->RenderSteamInvite() );

		$a = new SteamID( '[U:1:819]' );
		$this->assertEquals( 'fff', $a->RenderSteamInvite() );

		$a = new SteamID( '[U:1:0]' );
		$this->assertEquals( 'b', $a->RenderSteamInvite() );

		$a = new SteamID( '[U:1:1]' );
		$this->assertEquals( 'c', $a->RenderSteamInvite() );

		$a = new SteamID( '[U:1:2]' );
		$this->assertEquals( 'd', $a->RenderSteamInvite() );

		$a = new SteamID( '[U:1:3]' );
		$this->assertEquals( 'f', $a->RenderSteamInvite() );

		$a = new SteamID( '[U:1:4]' );
		$this->assertEquals( 'g', $a->RenderSteamInvite() );

		$a = new SteamID( '[U:1:5]' );
		$this->assertEquals( 'h', $a->RenderSteamInvite() );

		$a = new SteamID( '[U:1:6]' );
		$this->assertEquals( 'j', $a->RenderSteamInvite() );

		$a = new SteamID( '[U:1:7]' );
		$this->assertEquals( 'k', $a->RenderSteamInvite() );

		$a = new SteamID( '[U:1:8]' );
		$this->assertEquals( 'm', $a->RenderSteamInvite() );

		$a = new SteamID( '[U:1:9]' );
		$this->assertEquals( 'n', $a->RenderSteamInvite() );

		$a = new SteamID( '[U:1:10]' );
		$this->assertEquals( 'p', $a->RenderSteamInvite() );

		$a = new SteamID( '[U:1:11]' );
		$this->assertEquals( 'q', $a->RenderSteamInvite() );

		$a = new SteamID( '[U:1:12]' );
		$this->assertEquals( 'r', $a->RenderSteamInvite() );

		$a = new SteamID( '[U:1:13]' );
		$this->assertEquals( 't', $a->RenderSteamInvite() );

		$a = new SteamID( '[U:1:14]' );
		$this->assertEquals( 'v', $a->RenderSteamInvite() );

		$a = new SteamID( '[U:1:15]' );
		$this->assertEquals( 'w', $a->RenderSteamInvite() );
	}

	/**
	 * @dataProvider invalidVanityUrlProvider
	 */
	public function testInvalidSetFromUrl( string $URL ) : void
	{
		$this->expectException( InvalidArgumentException::class );

		SteamID::SetFromURL( $URL, [ $this, 'fakeResolveVanityURL' ] );
	}

	/**
	 * @dataProvider notFoundVanityUrlProvider
	 */
	public function testSetFromUrlCode404( string $URL ) : void
	{
		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionCode( 404 );

		SteamID::SetFromURL( $URL, [ $this, 'fakeResolveVanityURL' ] );
	}

	public function testRenderCsgoFriendCodes() : void
	{
		$a = new SteamID( '[U:1:12229257]' );
		$this->assertEquals( 'ALQF4-BYCA', $a->RenderCsgoFriendCode() );

		$a = new SteamID( '76561198084043632' );
		$this->assertEquals( 'SFW3A-MPAQ', $a->RenderCsgoFriendCode() );

		$a = new SteamID( '[U:1:0]' );
		$this->assertEquals( 'AEJJS-ABCA', $a->RenderCsgoFriendCode() );

		$a = new SteamID( '[U:1:1]' );
		$this->assertEquals( 'AJJJS-ABAA', $a->RenderCsgoFriendCode() );

		$a = new SteamID( '[U:1:4294967295]' );
		$this->assertEquals( 'S9ZZR-999P', $a->RenderCsgoFriendCode() );

		$a = new SteamID( '[U:1:501294967]' );
		$this->assertEquals( 'S335T-46EG', $a->RenderCsgoFriendCode() );

		$a = new SteamID( '[I:4:12229257:1048575]' );
		$this->assertEquals( 'ALQF4-BYCA', $a->RenderCsgoFriendCode() );
	}

	public function testSetFromCsgoFriendCodes() : void
	{
		$a = ( new SteamID() )->SetFromCsgoFriendCode( 'ALQF4-BYCA' );
		$this->assertEquals( '[U:1:12229257]', $a->RenderSteam3() );

		$a = ( new SteamID() )->SetFromCsgoFriendCode( 'SFW3A-MPAQ' );
		$this->assertEquals( '[U:1:123777904]', $a->RenderSteam3() );

		// Generated id without md5 niblets ($HashNibble=0), still parses because parser ignores it
		$a = ( new SteamID() )->SetFromCsgoFriendCode( 'ALGFL-BYAA' );
		$this->assertEquals( '[U:1:12229257]', $a->RenderSteam3() );

		// Generated id without md5 niblets ($HashNibble=1), still parses because parser ignores it
		$a = ( new SteamID() )->SetFromCsgoFriendCode( 'AQQP4-BZDC' );
		$this->assertEquals( '[U:1:12229257]', $a->RenderSteam3() );

		$a = ( new SteamID() )->SetFromCsgoFriendCode( 'AQGPL-3EUJ-SYLSB-J5SL' );
		$this->assertEquals( '[U:1:12229257]', $a->RenderSteam3() );

		$a = ( new SteamID() )->SetFromCsgoFriendCode( 'AJJA6-SSEL-AAJJE-AVBC' );
		$this->assertEquals( '[U:1:1]', $a->RenderSteam3() );

		$a = ( new SteamID() )->SetFromCsgoFriendCode( 'ATWCB-GBBA-ABLAB-ABCC' );
		$this->assertEquals( '[g:1:4777282]', $a->RenderSteam3() );
	}

	public function testNotIndividualCsgoFriendCodes() : void
	{
		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'This can only be used on Individual SteamID.' );

		$s = new SteamID( '[g:1:4777282]' );
		$s->RenderCsgoFriendCode();
	}

	public function testInvalidFriendCodeLength( ) : void
	{
		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Given input is not a valid CS:GO code.' );

		$s = new SteamID( );
		$s->SetFromCsgoFriendCode( 'AAAAA-AAAA-AAAAA-AAAA-' );
	}

	public function testInvalidFriendCodeDash1( ) : void
	{
		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Given input is not a valid CS:GO code.' );

		$s = new SteamID( );
		$s->SetFromCsgoFriendCode( 'AAAAAAAAAA' );
	}

	public function testInvalidFriendCodeDash2( ) : void
	{
		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Given input is not a valid CS:GO code.' );

		$s = new SteamID( );
		$s->SetFromCsgoFriendCode( 'AAAAA-AAAA-AAAAAAAAAA' );
	}

	public function testInvalidFriendCodeDash3( ) : void
	{
		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Given input is not a valid CS:GO code.' );

		$s = new SteamID( );
		$s->SetFromCsgoFriendCode( 'AAAAAAAAAA-AAAAA-AAAA' );
	}

	public function testInvalidFriendCodeDash4( ) : void
	{
		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Given input is not a valid CS:GO code.' );

		$s = new SteamID( );
		$s->SetFromCsgoFriendCode( 'AAAAA-AAAAAAAAAA-AAAA' );
	}

	public function testInvalidFriendCodeFuzzer1( ) : void
	{
		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Given input is not a valid CS:GO code.' );

		$s = new SteamID( );
		$s->SetFromCsgoFriendCode( 'STEAM-AM-A' );
	}

	public function testInvalidFriendCodeFuzzer2( ) : void
	{
		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Given input is not a valid CS:GO code.' );

		$s = new SteamID( );
		$s->SetFromCsgoFriendCode( '11111-1111' );
	}

	public function testAccountIdMaxValue( ) : void
	{
		$this->expectException( InvalidArgumentException::class );

		( new SteamID() )->SetAccountID( 0xFFFFFFFF + 1 );
	}

	public function testAccountTypeMaxValue( ) : void
	{
		$this->expectException( InvalidArgumentException::class );

		( new SteamID() )->SetAccountType( 0xF + 1 );
	}

	public function testAccountInstanceMaxValue( ) : void
	{
		$this->expectException( InvalidArgumentException::class );

		( new SteamID() )->SetAccountInstance( 0xFFFFF + 1 );
	}

	public function testAccountUniverseMaxValue( ) : void
	{
		$this->expectException( InvalidArgumentException::class );

		( new SteamID() )->SetAccountUniverse( 0xFF + 1 );
	}

	public static function steam3StringProvider( ) : array
	{
		return
		[
			[ '[U:1:123]' ],
			[ '[G:1:626]' ],
			[ '[A:2:165:1234]' ],
			[ '[M:2:165:1234]' ],
			[ '[T:1:123]' ],
			[ '[C:1:123]' ],
			[ '[c:1:123]' ],
			[ '[L:1:123]' ],
		];
	}

	public static function steamId64BitProvider( ) : array
	{
		return
		[
			[ '76561197960265851' ],
			[ '76561202255233147' ],
			[ '85568392920040050' ],
			[ '162134886574981285' ],
			[ '108086391056892027' ],
			[ '110338190870577275' ],
			[ '109212290963734651' ],
			[ '1234' ],
		];
	}

	public static function invalidIdProvider( ) : array
	{
		return
		[
			[ 0 ],
			[ '' ],
			[ 'NOT A STEAMID!' ],
			[ 'STEAM_0:1:999999999999999999999999999999' ],
			[ '[kek:1:0]' ],
			[ '[Z:1:1]' ],
			[ '[A:1:2:345)]' ],
			[ '[A:1:2(345]' ],
			[ '[A:1:2:(345]' ],
			[ '[A:1:2:(345)]' ],
			[ '[A:1:2(345):]' ],
			[ 'STEAM_0:6:4491990' ],
			[ 'STEAM_6:0:4491990' ],
			[ 'STEAM_1:0:04491990' ],
			[ '[U:1:009234567]' ],
			[ '[U:1:01234]' ],
			[ -1 ],
		];
	}

	public static function invalidAccountIdsOverflowProvider( ) : array
	{
		return
		[
			[ '[U:1:9999999999]' ],
			[ 'STEAM_0:1:9999999999' ],
		];
	}

	public static function invalidVanityUrlProvider( ) : array
	{
		return
		[
			[ '31525201686561879' ],
			[ 'top_kek_person' ],
			[ 'http://steamcommunity.com/id/some_amazing_person/' ],
			[ 'https://steamcommunity.com/games/stanleyparable/' ],
			[ 'http://steamcommunity.com/id/a/' ],
			[ 'http://steamcommunity.com/id/aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa/' ],
			[ 'http://steamcommunity_com/id/xpaw/' ],
			[ 'https://steamcommunity,com/profiles/76561210845167618' ],
			[ 'https://google.com' ],
		];
	}

	public static function notFoundVanityUrlProvider( ) : array
	{
		return
		[
			[ 'http://steamcommunity.com/id/surely_not_found/' ],
		];
	}

	public static function vanityUrlProvider( ) : array
	{
		return
		[
			[ 'http://steamcommunity.com/id/xpaw/' ],
			[ 'https://steamcommunity.com/id/alfredr/' ],
			[ 'https://steamcommunity.com/id/alfredr/games' ],
			[ 'http://steamcommunity.com/groups/valve/' ],
			[ 'http://steamcommunity.com/groups/valve/memberslistxml' ],
			[ 'http://steamcommunity.com/games/dota2' ],
			[ 'http://steamcommunity.com/games/tf2/' ],
			[ 'http://steamcommunity.com/profiles/[U:1:2:3]/' ],
			[ 'https://steamcommunity.com/profiles/[U:1:2]/games' ],
			[ 'http://steamcommunity.com/profiles/76561197960265733' ],
			[ 'http://steamcommunity.com/profiles/76561197960265733/games' ],
			[ 'http://steamcommunity.com/profiles/76561197960265733/games' ],
			[ 'https://steamcommunity.com/profiles/76561210845167618' ],
			[ 'https://steamcommunity.com/gid/103582791433666425' ],
			[ 'http://my.steamchina.com/profiles/76561197960265733/games' ],
			[ 'https://my.steamchina.com/profiles/76561210845167618' ],
			[ 'http://my.steamchina.com/groups/valve/memberslistxml' ],
			[ '76561210845167618' ],
			[ '[U:1:123]' ],
			[ 'alfredr' ],
			[ 'xpaw' ],
		];
	}

	public static function inviteUrlProvider( ) : array
	{
		return
		[
			[ 'http://steamcommunity.com/user/qpn-pmn/' ],
			[ 'https://steamcommunity.com/user/QPNpmn--/' ],
			[ 'https://steamcommunity.com/user/qpn-pmllllllllllln/' ],
			[ 'https://my.steamchina.com/user/qpn-pmllllllllllln/' ],
			[ 'http://s.team/p/qpn-pmn/abc' ],
			[ 'https://s.team/p/qpnpmn' ],
			[ 'https://s.team/p/qpnpmn-YZ' ],
		];
	}

	public static function fakeResolveVanityURL( string $URL, int $Type ) : ?string
	{
		$FakeValues =
		[
			1 => // individual
			[
				'alfredr' => '76561197960265733',
				'xpaw' => '76561197972494985'
			],

			2 => // group
			[
				'valve' => '103582791429521412',
				'steamdb' => '103582791434298690'
			],

			3 => // game group
			[
				'tf2' => '103582791430075519',
				'dota2' => '103582791433224455'
			],
		];

		return $FakeValues[ $Type ][ $URL ] ?? null;
	}
}
