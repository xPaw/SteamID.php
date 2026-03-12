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

	#[PHPUnit\Framework\Attributes\DataProvider('steam3StringProvider')]
	public function testSteam3StringSymmetric( string $SteamID ) : void
	{
		$s = new SteamID( $SteamID );
		$this->assertEquals( $SteamID, $s->RenderSteam3() );
	}

	#[PHPUnit\Framework\Attributes\DataProvider('steamId64BitProvider')]
	public function testConvertToUInt64( string $SteamID ) : void
	{
		$s = new SteamID( $SteamID );
		$this->assertEquals( $SteamID, $s->ConvertToUInt64() );
	}

	#[PHPUnit\Framework\Attributes\DataProvider('steamId64BitProvider')]
	public function testSetFromUInt64( string $SteamID ) : void
	{
		$s = new SteamID();
		$s->SetFromUInt64( $SteamID );
		$this->assertEquals( $SteamID, $s->ConvertToUInt64() );
	}

	#[PHPUnit\Framework\Attributes\DataProvider('steamId64BitProvider')]
	public function testToStringCast( string $SteamID ) : void
	{
		$s = new SteamID( $SteamID );
		$this->assertEquals( $SteamID, (string)$s );
	}

	#[PHPUnit\Framework\Attributes\DataProvider('invalidIdProvider')]
	public function testConstructorHandlesInvalid( $SteamID ) : void
	{
		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Provided SteamID is invalid.' );

		new SteamID( $SteamID );
	}

	#[PHPUnit\Framework\Attributes\DataProvider('invalidAccountIdsOverflowProvider')]
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

	#[PHPUnit\Framework\Attributes\DataProvider('vanityUrlProvider')]
	public function testSetFromUrl( string $URL ) : void
	{
		$s = SteamID::SetFromURL( $URL, [ $this, 'fakeResolveVanityURL' ] );
		$this->assertTrue( $s->IsValid() );
	}

	#[PHPUnit\Framework\Attributes\DataProvider('inviteUrlProvider')]
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

	#[PHPUnit\Framework\Attributes\DataProvider('tradeOfferUrlProvider')]
	public function testSetFromTradeOfferUrl( string $URL, string $expected ) : void
	{
		$s = SteamID::SetFromURL( $URL, [ $this, 'fakeResolveVanityURL' ] );
		$this->assertEquals( $expected, $s->RenderSteam3() );
	}

	#[PHPUnit\Framework\Attributes\DataProvider('invalidTradeOfferUrlProvider')]
	public function testInvalidTradeOfferUrl( string $URL ) : void
	{
		$this->expectException( InvalidArgumentException::class );
		SteamID::SetFromURL( $URL, [ $this, 'fakeResolveVanityURL' ] );
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

	#[PHPUnit\Framework\Attributes\DataProvider('invalidVanityUrlProvider')]
	public function testInvalidSetFromUrl( string $URL ) : void
	{
		$this->expectException( InvalidArgumentException::class );

		SteamID::SetFromURL( $URL, [ $this, 'fakeResolveVanityURL' ] );
	}

	#[PHPUnit\Framework\Attributes\DataProvider('notFoundVanityUrlProvider')]
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

	#[PHPUnit\Framework\Attributes\DataProvider('validCsgoCodeProvider')]
	public function testSetFromCsgoFriendCodes(string $code, string $expected) : void
	{
		$s = (new SteamID())->SetFromCsgoFriendCode($code);
		$this->assertEquals($expected, $s->RenderSteam3());
	}

	public function testNotIndividualCsgoFriendCodes() : void
	{
		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'This can only be used on Individual SteamID.' );

		$s = new SteamID( '[g:1:4777282]' );
		$s->RenderCsgoFriendCode();
	}

	public function testInvalidCsgoFriendCodeCharacters() : void
	{
		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Given input is not a valid CS:GO code.' );

		$s = new SteamID();
		$s->SetFromCsgoFriendCode( 'AAAAA-ZZZZZ' );
	}

	#[PHPUnit\Framework\Attributes\DataProvider('invalidCsgoCodeProvider')]
	public function testInvalidCsgoFriendCodes(string $code) : void
	{
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('Given input is not a valid CS:GO code.');
		(new SteamID())->SetFromCsgoFriendCode($code);
	}

	#[PHPUnit\Framework\Attributes\DataProvider('setterOverflowProvider')]
	public function testSetterOverflow(string $method, int $value) : void
	{
		$this->expectException(InvalidArgumentException::class);
		(new SteamID())->$method($value);
	}

	public function testMaxValidAccountId() : void
	{
		$s = new SteamID();
		$s->SetAccountID(4294967295);
		$this->assertEquals(4294967295, $s->GetAccountID());

		$s = new SteamID('STEAM_1:1:2147483647');
		$this->assertEquals(4294967295, $s->GetAccountID());

		$s = new SteamID('[U:1:4294967295]');
		$this->assertEquals(4294967295, $s->GetAccountID());
	}

	public function testNegativeAccountParameters() : void
	{
		$this->expectException(InvalidArgumentException::class);

		$s = new SteamID();
		$s->SetAccountID(-1);
	}

	#[PHPUnit\Framework\Attributes\DataProvider('validUrlVariationsProvider')]
	public function testSetFromURLVariations(string $url, string $expected) : void
	{
		$s = SteamID::SetFromURL($url, [$this, 'fakeResolveVanityURL']);
		$this->assertEquals($expected, $s->ConvertToUInt64());
	}

	public function testCsgoFriendCodeRoundTrip() : void
	{
		$original = new SteamID('[U:1:12229257]');
		$friendCode = $original->RenderCsgoFriendCode();
		$reconstructed = (new SteamID())->SetFromCsgoFriendCode($friendCode);

		$this->assertEquals($original->ConvertToUInt64(), $reconstructed->ConvertToUInt64());
	}

	public function testSteamInviteRoundTrip() : void
	{
		$originalId = 12229257;
		$original = new SteamID();
		$original->SetAccountType(SteamID::TypeIndividual);
		$original->SetAccountUniverse(SteamID::UniversePublic);
		$original->SetAccountInstance(SteamID::DesktopInstance);
		$original->SetAccountID($originalId);

		$invite = $original->RenderSteamInvite();
		$result = SteamID::SetFromURL('https://s.team/p/' . $invite, [$this, 'fakeResolveVanityURL']);

		$this->assertEquals($originalId, $result->GetAccountID());
	}

	public function testAllChatInstanceFlags() : void
	{
		// Test Clan chat
		$s = new SteamID();
		$s->SetAccountType(SteamID::TypeChat);
		$s->SetAccountInstance(SteamID::InstanceFlagClan);
		$s->SetAccountID(123);
		$s->SetAccountUniverse(SteamID::UniversePublic);

		$this->assertEquals('[c:1:123]', $s->RenderSteam3());

		// Test Lobby chat
		$s = new SteamID();
		$s->SetAccountType(SteamID::TypeChat);
		$s->SetAccountInstance(SteamID::InstanceFlagLobby);
		$s->SetAccountID(123);
		$s->SetAccountUniverse(SteamID::UniversePublic);

		$this->assertEquals('[L:1:123]', $s->RenderSteam3());

		// Test MMS Lobby chat
		$s = new SteamID();
		$s->SetAccountType(SteamID::TypeChat);
		$s->SetAccountInstance(SteamID::InstanceFlagMMSLobby);
		$s->SetAccountID(123);
		$s->SetAccountUniverse(SteamID::UniversePublic);

		// Note: This tests that the MMSLobby flag is preserved but doesn't have a special render mode
		$this->assertEquals('[T:1:123]', $s->RenderSteam3());
	}

	public function testDefaultValues() : void
	{
		$s = new SteamID();

		$this->assertEquals(0, $s->GetAccountID());
		$this->assertEquals(0, $s->GetAccountInstance());
		$this->assertEquals(0, $s->GetAccountType());
		$this->assertEquals(0, $s->GetAccountUniverse());
		$this->assertEquals('0', $s->ConvertToUInt64());
	}

	public function testSpecificAccountTypeValidations() : void
	{
		// Individual type requires non-zero ID and valid instance
		$s = new SteamID();
		$s->SetAccountType(SteamID::TypeIndividual);
		$s->SetAccountUniverse(SteamID::UniversePublic);
		$s->SetAccountID(0);
		$this->assertFalse($s->IsValid());

		$s->SetAccountID(1);
		$s->SetAccountInstance(SteamID::WebInstance);
		$this->assertTrue($s->IsValid());

		$s->SetAccountInstance(5); // Invalid instance
		$this->assertFalse($s->IsValid());

		// Clan type requires non-zero ID and instance 0
		$s = new SteamID();
		$s->SetAccountType(SteamID::TypeClan);
		$s->SetAccountUniverse(SteamID::UniversePublic);
		$s->SetAccountID(1);
		$s->SetAccountInstance(0);
		$this->assertTrue($s->IsValid());

		$s->SetAccountInstance(1);
		$this->assertFalse($s->IsValid());

		$s->SetAccountInstance(0);
		$s->SetAccountID(0);
		$this->assertFalse($s->IsValid());

		// GameServer type requires non-zero ID
		$s = new SteamID();
		$s->SetAccountType(SteamID::TypeGameServer);
		$s->SetAccountUniverse(SteamID::UniversePublic);
		$s->SetAccountID(0);
		$this->assertFalse($s->IsValid());

		$s->SetAccountID(1);
		$this->assertTrue($s->IsValid());
	}

	public function testStringValidation() : void
	{
		$longString = str_repeat('a', 1000);

		$this->expectException(InvalidArgumentException::class);
		new SteamID($longString);
	}

	public function testSequentialOperations() : void
	{
		$s = new SteamID();

		// Start with a valid ID
		$s->SetAccountType(SteamID::TypeIndividual);
		$s->SetAccountUniverse(SteamID::UniversePublic);
		$s->SetAccountID(123);
		$s->SetAccountInstance(SteamID::DesktopInstance);

		$this->assertTrue($s->IsValid());
		$this->assertEquals('[U:1:123]', $s->RenderSteam3());

		// Change to clan
		$s->SetAccountType(SteamID::TypeClan);
		$s->SetAccountInstance(0); // Clan requires instance 0

		$this->assertTrue($s->IsValid());
		$this->assertEquals('[g:1:123]', $s->RenderSteam3());

		// Change universe
		$s->SetAccountUniverse(SteamID::UniverseBeta);

		$this->assertTrue($s->IsValid());
		$this->assertEquals('[g:2:123]', $s->RenderSteam3());
	}

	#[PHPUnit\Framework\Attributes\DataProvider('fromAccountIdInvalidProvider')]
	public function testFromAccountIDInvalid(int $accountId) : void
	{
		$this->expectException(InvalidArgumentException::class);
		SteamID::FromAccountID($accountId);
	}

	#[PHPUnit\Framework\Attributes\DataProvider('staticHelperProvider')]
	public function testStaticHelpers(string $method, int $input, string $expected) : void
	{
		$result = SteamID::$method($input);
		$this->assertEquals($expected, $result);
	}

	public function testStaticMethodsConsistency() : void
	{
		$accountId = 4491990;

		$fromStatic = SteamID::FromAccountID( $accountId );
		$uint64 = SteamID::AccountIDToUInt64( $accountId );
		$steam3 = SteamID::RenderAccountID( $accountId );

		$this->assertEquals( $fromStatic->ConvertToUInt64(), $uint64 );
		$this->assertEquals( $fromStatic->RenderSteam3(), $steam3 );
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
			[ '[P:1:123]' ],
			[ '[a:1:123]' ],
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
			[ "0" ],
			[ "00000000000000000000" ],
			[ 'STEAM_0:2:123' ],
			[ 'STEAM_0:5:123' ],
			[ '[u:1:123]' ],
			[ '[U:01:123]' ],
			[ '[U:1:0123]' ],
			[ 'U:1:123]' ],
			[ '[U:1:123' ],
			[ ' 76561197960265851 ' ],
			[ ' [U:1:123] ' ],
			[ '999999999999999999999' ],
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
			[ 'https://STEAMCOMMUNITY.COM/id/xpaw' ],
			[ 'https://steamcommunity.com:443/id/xpaw' ],
			[ 'https://steamcommunity.com/id/test@domain' ],
			[ 'https://steamcommunity.com/id/xpaw#section' ],
			[ 'https://steamcommunity.com/id/xpaw?tab=games&sort=recent&filter=all' ],
			[ 'a' ],
			[ str_repeat('a', 33) ],
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

	public function testSteam2EdgeCases() : void
	{
		$s = new SteamID('STEAM_0:0:0');
		$this->assertEquals(0, $s->GetAccountID());
		$this->assertEquals(SteamID::UniversePublic, $s->GetAccountUniverse());

		$s = new SteamID('STEAM_4:1:2147483647');
		$this->assertEquals(4294967295, $s->GetAccountID());
		$this->assertEquals(SteamID::UniverseDev, $s->GetAccountUniverse());
	}

	public function testSteam3InvalidTypeHandling() : void
	{
		$s = new SteamID('[I:1:123]');
		$this->assertEquals(SteamID::TypeInvalid, $s->GetAccountType());
	}

	public function testBoundaryValueConditions() : void
	{
		$s = new SteamID();
		$s->SetAccountID(4294967295);
		$this->assertEquals(4294967295, $s->GetAccountID());

		$s->SetAccountInstance(1048575);
		$this->assertEquals(1048575, $s->GetAccountInstance());

		$s->SetAccountType(15);
		$this->assertEquals(15, $s->GetAccountType());

		$s->SetAccountUniverse(255);
		$this->assertEquals(255, $s->GetAccountUniverse());
	}

	public function testInviteCodeDashPlacement() : void
	{
		$s = new SteamID('[U:1:15]');
		$this->assertEquals('w', $s->RenderSteamInvite());

		$s = new SteamID('[U:1:16]');
		$this->assertEquals('cb', $s->RenderSteamInvite());

		$s = new SteamID('[U:1:255]');
		$this->assertEquals('ww', $s->RenderSteamInvite());

		$s = new SteamID('[U:1:256]');
		$this->assertEquals('cbb', $s->RenderSteamInvite());
	}

	public function testInstanceFlagCombinations() : void
	{
		$s = new SteamID();
		$s->SetAccountType(SteamID::TypeChat);
		$s->SetAccountInstance(SteamID::InstanceFlagClan | SteamID::InstanceFlagLobby);
		$s->SetAccountID(123);
		$s->SetAccountUniverse(SteamID::UniversePublic);

		$this->assertEquals('[c:1:123]', $s->RenderSteam3());
	}

	public function testSteam2RenderingForInvalidState() : void
	{
		$s = new SteamID();
		$s->SetAccountType(SteamID::TypeClan);
		$s->SetAccountID(0);
		$s->SetAccountUniverse(SteamID::UniversePublic);

		$this->assertEquals('103582791429521408', $s->RenderSteam2());
	}

	public function testLargeNumberHandling() : void
	{
		$s = new SteamID('18446744073709551614');
		$this->assertEquals('18446744073709551614', $s->ConvertToUInt64());

		$s->SetFromUInt64('18446744073709551615');
		$this->assertEquals('18446744073709551615', $s->ConvertToUInt64());
	}

	public function testNewlineHandling() : void
	{
		$s = new SteamID("76561197960265851\n");
		$this->assertEquals("76561197960265851", $s->ConvertToUInt64());
	}

	public function testSteamChinaDomainHandling() : void
	{
		$s = SteamID::SetFromURL('https://my.steamchina.com/id/xpaw/', [$this, 'fakeResolveVanityURL']);
		$this->assertEquals('76561197972494985', $s->ConvertToUInt64());

		$s = SteamID::SetFromURL('http://my.steamchina.com/profiles/76561197972494985', [$this, 'fakeResolveVanityURL']);
		$this->assertEquals('76561197972494985', $s->ConvertToUInt64());
	}

	public function testVanityUrlLengthBoundaries() : void
	{
		$s = SteamID::SetFromURL('ab', [$this, 'fakeResolveVanityURLSpecial']);
		$this->assertEquals('76561197960265733', $s->ConvertToUInt64());

		$longVanity = str_repeat('a', 32);
		$s = SteamID::SetFromURL($longVanity, [$this, 'fakeResolveVanityURLSpecial']);
		$this->assertEquals('76561197960265733', $s->ConvertToUInt64());
	}

	public function testInviteUrlCaseInsensitive() : void
	{
		$s = SteamID::SetFromURL('https://steamcommunity.com/user/QPN-PMN/', [$this, 'fakeResolveVanityURL']);
		$this->assertEquals('[U:1:12229257]', $s->RenderSteam3());

		$s = SteamID::SetFromURL('https://s.team/p/QpN-pMn', [$this, 'fakeResolveVanityURL']);
		$this->assertEquals('[U:1:12229257]', $s->RenderSteam3());
	}

	public function testInviteUrlWithInvalidCharacters() : void
	{
		$s = SteamID::SetFromURL('https://s.team/p/qpn-pmn-xyz123', [$this, 'fakeResolveVanityURL']);
		$this->assertEquals('[U:1:12229257]', $s->RenderSteam3());
	}

	public function testP2PSuperSeederAccountType() : void
	{
		$s = new SteamID();
		$s->SetAccountType(SteamID::TypeP2PSuperSeeder);
		$s->SetAccountUniverse(SteamID::UniversePublic);
		$s->SetAccountID(123);
		$this->assertEquals(SteamID::TypeP2PSuperSeeder, $s->GetAccountType());
		$this->assertTrue($s->IsValid());
	}

	public function testIndividualAccountInstanceLimit() : void
	{
		$s = new SteamID();
		$s->SetAccountType(SteamID::TypeIndividual);
		$s->SetAccountUniverse(SteamID::UniversePublic);
		$s->SetAccountID(123);
		$s->SetAccountInstance(SteamID::WebInstance + 1);
		$this->assertFalse($s->IsValid());
	}

	public function testUnknownAccountTypeRendering() : void
	{
		$this->expectException(InvalidArgumentException::class);
		$s = new SteamID();
		$s->SetAccountType(99);
	}

	public function testSetFromUInt64WithFloat() : void
	{
		$this->expectException(TypeError::class);
		$s = new SteamID();
		$s->SetFromUInt64(123.45);
	}

	public function testMaxInstanceBoundary() : void
	{
		$s = new SteamID();
		$s->SetAccountType(SteamID::TypeIndividual);
		$s->SetAccountUniverse(SteamID::UniversePublic);
		$s->SetAccountID(123);
		$s->SetAccountInstance(SteamID::WebInstance);
		$this->assertTrue($s->IsValid());
	}

	public static function fakeResolveVanityURLSpecial(string $URL, int $Type) : ?string
	{
		if ($URL === 'ab' || strlen($URL) === 32) {
			return '76561197960265733';
		}
		return null;
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

	public static function setterOverflowProvider() : array
	{
		return [
			['SetAccountID', 0xFFFFFFFF + 1],
			['SetAccountType', 0xF + 1],
			['SetAccountInstance', 0xFFFFF + 1],
			['SetAccountUniverse', 0xFF + 1],
		];
	}

	public static function invalidCsgoCodeProvider() : array
	{
		return [
			['AAAAA-AAAA-AAAAA-AAAA-'],
			['AAAAAAAAAA'],
			['AAAAA-AAAA-AAAAAAAAAA'],
			['AAAAAAAAAA-AAAAA-AAAA'],
			['AAAAA-AAAAAAAAAA-AAAA'],
			['STEAM-AM-A'],
			['11111-1111'],
			['alqf4-byca'],
			['ALqf4-BYCA'],
			['ALQF4-BYCÁ'],
		];
	}

	public static function fromAccountIdInvalidProvider() : array
	{
		return [
			[-1],
			[0xFFFFFFFF + 1],
		];
	}

	public static function validUrlVariationsProvider() : array
	{
		return [
			['https://steamcommunity.com/id/xpaw/?l=english', '76561197972494985'],
			['https://steamcommunity.com/id/xpaw/screenshots/', '76561197972494985'],
		];
	}

	public static function staticHelperProvider() : array
	{
		return [
			['AccountIDToUInt64', 123, '76561197960265851'],
			['RenderAccountID', 123, '[U:1:123]'],
		];
	}

	public static function tradeOfferUrlProvider() : array
	{
		return [
			[ 'https://steamcommunity.com/tradeoffer/new/?partner=22202', '[U:1:22202]' ],
			[ 'https://steamcommunity.com/tradeoffer/new/?partner=22202&token=abc123', '[U:1:22202]' ],
			[ 'https://steamcommunity.com/tradeoffer/new/?token=abc123&partner=22202', '[U:1:22202]' ],
			[ 'http://steamcommunity.com/tradeoffer/new/?partner=4491990&token=xyz', '[U:1:4491990]' ],
			[ 'https://steamcommunity.com/tradeoffer/new?partner=22202', '[U:1:22202]' ],
			[ 'https://steamcommunity.com/tradeoffer/new?token=xyz&partner=22202', '[U:1:22202]' ],
			[ 'https://my.steamchina.com/tradeoffer/new/?partner=22202', '[U:1:22202]' ],
		];
	}

	public static function invalidTradeOfferUrlProvider() : array
	{
		return [
			[ 'https://steamcommunity.com/tradeoffer/new/' ],
			[ 'https://steamcommunity.com/tradeoffer/new/?token=abc' ],
			[ 'https://steamcommunity.com/tradeoffer/new/?partner=0' ],
			[ 'https://steamcommunity.com/tradeoffer/new/?partner=abc' ],
			[ 'https://steamcommunity.com/tradeoffer/new/?partner=-1' ],
		];
	}

	public static function validCsgoCodeProvider() : array
	{
		return [
			['ALQF4-BYCA', '[U:1:12229257]'],
			['SFW3A-MPAQ', '[U:1:123777904]'],
			['ALGFL-BYAA', '[U:1:12229257]'],
			['AQQP4-BZDC', '[U:1:12229257]'],
			['AQGPL-3EUJ-SYLSB-J5SL', '[U:1:12229257]'],
			['AJJA6-SSEL-AAJJE-AVBC', '[U:1:1]'],
			['ATWCB-GBBA-ABLAB-ABCC', '[g:1:4777282]'],
		];
	}
}
