<?php
	require __DIR__ . '/SteamID.php';
	
	class SteamIDFacts extends PHPUnit_Framework_TestCase
	{
		public function testEmptyConstructorInvalid( )
		{
			$s = new SteamID();
			
			$this->assertFalse( $s->IsValid() );
		}
		
		public function testManualConstructionValid( )
		{
			$s = new SteamID();
			
			$s->SetAccountUniverse( SteamID :: UniverseBeta );
			$s->SetAccountInstance( SteamID :: ConsoleInstance );
			$s->SetAccountType( SteamID :: TypeChat );
			$s->SetAccountID( 1234 );
			
			$this->assertEquals( 1234, $s->GetAccountID() );
			$this->assertEquals( SteamID :: ConsoleInstance, $s->GetAccountInstance() );
			$this->assertEquals( SteamID :: UniverseBeta, $s->GetAccountUniverse() );
			$this->assertEquals( SteamID :: TypeChat, $s->GetAccountType() );
		}
		
		
	}
