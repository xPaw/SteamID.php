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
			->SetAccountUniverse( SteamID :: UniverseBeta )
			->SetAccountInstance( SteamID :: ConsoleInstance )
			->SetAccountType( SteamID :: TypeChat )
			->SetAccountID( 1234 );
		
		$this->assertEquals( 1234, $s->GetAccountID() );
		$this->assertEquals( SteamID :: ConsoleInstance, $s->GetAccountInstance() );
		$this->assertEquals( SteamID :: UniverseBeta, $s->GetAccountUniverse() );
		$this->assertEquals( SteamID :: TypeChat, $s->GetAccountType() );
		
		$s = (new SteamID)
			->SetAccountUniverse( SteamID :: UniverseInternal )
			->SetAccountType( SteamID :: TypeContentServer )
			->SetAccountID( 1234 );
		
		$this->assertEquals( 1234, $s->GetAccountID() );
		$this->assertEquals( SteamID :: UniverseInternal, $s->GetAccountUniverse() );
		$this->assertEquals( SteamID :: TypeContentServer, $s->GetAccountType() );
		
		$s = (new SteamID)
			->SetAccountUniverse( SteamID :: UniversePublic )
			->SetAccountType( SteamID :: TypeClan )
			->SetAccountID( 4321 );
		
		$this->assertEquals( 4321, $s->GetAccountID() );
		$this->assertEquals( 0, $s->GetAccountInstance() );
		$this->assertEquals( SteamID :: UniversePublic, $s->GetAccountUniverse() );
		$this->assertEquals( SteamID :: TypeClan, $s->GetAccountType() );
	}
	
	public function testLongConstructorAndSetterGetterValid( )
	{
		$s = new SteamID( '103582791432294076' );
		
		$this->assertEquals( 2772668, $s->GetAccountID() );
		$this->assertEquals( SteamID :: AllInstances, $s->GetAccountInstance() );
		$this->assertEquals( SteamID :: UniversePublic, $s->GetAccountUniverse() );
		$this->assertEquals( SteamID :: TypeClan, $s->GetAccountType() );
		
		$s->SetFromUInt64( '157626004137848889' );
		
		$this->assertEquals( 12345, $s->GetAccountID() );
		$this->assertEquals( SteamID :: WebInstance, $s->GetAccountInstance() );
		$this->assertEquals( SteamID :: UniverseBeta, $s->GetAccountUniverse() );
		$this->assertEquals( SteamID :: TypeGameServer, $s->GetAccountType() );
	}
	
	public function testSteam2CorrectParse( )
	{
		$s = new SteamID( 'STEAM_0:0:4491990' );
		
		$this->assertEquals( 8983980, $s->GetAccountID() );
		$this->assertEquals( SteamID :: DesktopInstance, $s->GetAccountInstance() );
		$this->assertEquals( SteamID :: UniversePublic, $s->GetAccountUniverse() );
		
		$s = new SteamID( 'STEAM_0:1:4491990' );
		
		$this->assertEquals( 8983981, $s->GetAccountID() );
		$this->assertEquals( SteamID :: DesktopInstance, $s->GetAccountInstance() );
		$this->assertEquals( SteamID :: UniversePublic, $s->GetAccountUniverse() );
	}
	
	public function testSteam3CorrectParse( )
	{
		$s = new SteamID( '[U:1:123]' );
		
		$this->assertEquals( 123, $s->GetAccountID() );
		$this->assertEquals( SteamID :: DesktopInstance, $s->GetAccountInstance() );
		$this->assertEquals( SteamID :: UniversePublic, $s->GetAccountUniverse() );
		$this->assertEquals( SteamID :: TypeIndividual, $s->GetAccountType() );
		
		$s = new SteamID( '[A:1:123:456]' );
		
		$this->assertEquals( 123, $s->GetAccountID() );
		$this->assertEquals( 456, $s->GetAccountInstance() );
		$this->assertEquals( SteamID :: UniversePublic, $s->GetAccountUniverse() );
		$this->assertEquals( SteamID :: TypeAnonGameServer, $s->GetAccountType() );
		
		$s = new SteamID( '[L:1:123]' );
		
		$this->assertEquals( 123, $s->GetAccountID() );
		$this->assertTrue( !!( $s->GetAccountInstance() & SteamID :: InstanceFlagLobby ) );
		$this->assertEquals( SteamID :: UniversePublic, $s->GetAccountUniverse() );
		$this->assertEquals( SteamID :: TypeChat, $s->GetAccountType() );
		
		$s = new SteamID( '[c:1:123]' );
		
		$this->assertEquals( 123, $s->GetAccountID() );
		$this->assertTrue( !!( $s->GetAccountInstance() & SteamID :: InstanceFlagClan ) );
		$this->assertEquals( SteamID :: UniversePublic, $s->GetAccountUniverse() );
		$this->assertEquals( SteamID :: TypeChat, $s->GetAccountType() );
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
	 */
	public function testConstructorHandlesInvalid( $SteamID )
	{
		new SteamID( $SteamID );
	}
	
	public function testSteam2RenderIsValid( )
	{
		$s = new SteamID( '76561197969249708' );
		$this->assertEquals( 'STEAM_0:0:4491990', $s->RenderSteam2() );
		
		$s->SetAccountUniverse( SteamID :: UniverseBeta );
		$this->assertEquals( 'STEAM_2:0:4491990', $s->RenderSteam2() );
		
		$s->SetAccountType( SteamID :: TypeGameServer );
		$this->assertEquals( '157625991261918636', $s->RenderSteam2() );
	}
	
	public function steam3StringProvider( )
	{
		return Array(
			Array( '[U:1:123]' ),
			Array( '[U:1:123:2]' ),
			Array( '[G:1:626]' ),
			Array( '[A:2:165:1234]' ),
			Array( '[T:1:123]' ),
			Array( '[c:1:123]' ),
			Array( '[L:1:123]' ),
		);
	}
	
	public function invalidIdProvider( )
	{
		return Array(
			Array( '' ),
			Array( 'NOT A STEAMID!' ),
			Array( 'STEAM_0:1:999999999999999999999999999999' ),
			Array( '[kek:1:0]' ),
			Array( '[Z:1:1]' ),
			Array( '[A:1:2:345)]' ),
			Array( '[A:1:2(345]' ),
			Array( 'STEAM_0:6:4491990' ),
			Array( 'STEAM_6:0:4491990' ),
			Array( -1 ),
		);
	}
}
