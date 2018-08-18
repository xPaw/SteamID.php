<?php
require __DIR__ . '/SteamID.php';

class SteamIDFacts extends PHPUnit_Framework_TestCase
{
	public function testEmptyConstructorInvalid( )
	{
		$s = new SteamID;
		$this->assertFalse( $s->IsValid() );
	}
	
	public function testManualConstructionValid( )
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
	
	public function testLongConstructorAndSetterGetterValid( )
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
	
	public function testSteam2CorrectParse( )
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
	
	public function testAccountIdDefault( )
	{
		$s = new SteamID( 123 );
		
		$this->assertTrue( $s->IsValid() );
		$this->assertEquals( 123, $s->GetAccountID() );
		$this->assertEquals( SteamID::DesktopInstance, $s->GetAccountInstance() );
		$this->assertEquals( SteamID::UniversePublic, $s->GetAccountUniverse() );
		$this->assertEquals( SteamID::TypeIndividual, $s->GetAccountType() );
		
		$s = new SteamID( '4294967295' );
		
		$this->assertTrue( $s->IsValid() );
		$this->assertEquals( 4294967295, $s->GetAccountID() );
		$this->assertEquals( SteamID::DesktopInstance, $s->GetAccountInstance() );
		$this->assertEquals( SteamID::UniversePublic, $s->GetAccountUniverse() );
		$this->assertEquals( SteamID::TypeIndividual, $s->GetAccountType() );
		
		$s = new SteamID( '4294967296' );
		
		$this->assertFalse( $s->IsValid() );
		$this->assertEquals( 0, $s->GetAccountID() );
		$this->assertEquals( SteamID::DesktopInstance, $s->GetAccountInstance() );
		$this->assertEquals( SteamID::UniverseInvalid, $s->GetAccountUniverse() );
		$this->assertEquals( SteamID::TypeInvalid, $s->GetAccountType() );
	
	public function testSteam3CorrectParse( )
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
	}
	
	public function testSteam3CorrectInvalidParse( )
	{
		$s = new SteamID( '[i:1:123]' );
		$this->assertEquals( 123, $s->GetAccountID() );
		$this->assertEquals( SteamID::TypeInvalid, $s->GetAccountType() );
	}
	
	public function testSteam2RenderIsValid( )
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
	public function testSteam3StringSymmetric( $SteamID )
	{
		$s = new SteamID( $SteamID );
		$this->assertEquals( $SteamID, $s->RenderSteam3() );
	}
	
	/**
	 * @dataProvider invalidIdProvider
	 *
	 * @expectedException InvalidArgumentException
	 * @expectedException Provided SteamID is invalid
	 */
	public function testConstructorHandlesInvalid( $SteamID )
	{
		new SteamID( $SteamID );
	}
	
	/**
	 * @dataProvider invalidAccountIdsOverflowProvider
	 * 
	 * @expectedException InvalidArgumentException
	 * @expectedExceptionMessage 32-bit
	 */
	public function testInvalidConstructorOverflow( $SteamID )
	{
		new SteamID( $SteamID );
	}
	
	/**
	 * @expectedException InvalidArgumentException
	 * @expectedExceptionMessage not numeric
	 */
	public function testInvalidSetFromUInt64( )
	{
		$s = new SteamID( );
		$s->SetFromUInt64( '111failure111' );
	}
	
	/**
	 * @dataProvider vanityUrlProvider
	 */
	public function testSetFromUrl( $URL )
	{
		$s = SteamID::SetFromURL( $URL, [ $this, 'fakeResolveVanityURL' ] );
		$this->assertTrue( $s->IsValid() );
	}
	
	/**
	 * @dataProvider inviteUrlProvider
	 */
	public function testSetFromInviteUrl( $URL )
	{
		$s = SteamID::SetFromURL( $URL, [ $this, 'fakeResolveVanityURL' ] );
		$this->assertEquals( '[U:1:12229257]', $s->RenderSteam3() );
	}
	
	/**
	 * @expectedException InvalidArgumentException
	 */
	public function testInvalidSteamInviteType( )
	{
		$a = new SteamID( '[A:2:165:1234]' );
		$a->RenderSteamInvite();
	}
	
	public function testRenderSteamInvite( )
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
	 *
	 * @expectedException InvalidArgumentException
	 */
	public function testInvalidSetFromUrl( $URL )
	{
		SteamID::SetFromURL( $URL, [ $this, 'fakeResolveVanityURL' ] );
	}
	
	public function steam3StringProvider( )
	{
		return
		[
			[ '[U:1:123]' ],
			[ '[U:1:123:2]' ],
			[ '[G:1:626]' ],
			[ '[A:2:165:1234]' ],
			[ '[T:1:123]' ],
			[ '[c:1:123]' ],
			[ '[L:1:123]' ],
		];
	}
	
	public function invalidIdProvider( )
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
			[ -1 ],
		];
	}
	
	public function invalidAccountIdsOverflowProvider( )
	{
		return
		[
			[ '[U:1:9999999999]' ],
			[ 'STEAM_0:1:9999999999' ],
		];
	}
	
	public function invalidVanityUrlProvider( )
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
	
	public function vanityUrlProvider( )
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
			[ '76561210845167618' ],
			[ '[U:1:123]' ],
			[ 'alfredr' ],
			[ 'xpaw' ],
		];
	}
	
	public function inviteUrlProvider( )
	{
		return
		[
			[ 'http://steamcommunity.com/user/qpn-pmn/' ],
			[ 'https://steamcommunity.com/user/QPNpmn--/' ],
			[ 'https://steamcommunity.com/user/qpn-pmllllllllllln/' ],
			[ 'http://s.team/p/qpn-pmn/abc' ],
			[ 'https://s.team/p/qpnpmn' ],
			[ 'https://s.team/p/qpnpmn-YZ' ],
		];
	}
	
	public function fakeResolveVanityURL( $URL, $Type )
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
		
		if( isset( $FakeValues[ $Type ][ $URL ] ) )
		{
			return $FakeValues[ $Type ][ $URL ];
		}
		
		return null;
	}
}
