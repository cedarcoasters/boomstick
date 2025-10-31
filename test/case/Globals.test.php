<?php

namespace BoomStickTest;
use BoomStick\Lib\Globals as G;
use BoomStick\Lib\Test\Kase;


final class GlobalsTest extends Kase
{
	public function testInstanceCreates()
	{
		$this->assertInstanceOf(G::class, new G());
	}

	public function testExceptionOnUndefinedPropertySet()
	{
		$this->expectException(\ErrorException::class);

		$G = new G();
		$G->testingBadVar = 'test'; // Invalid global variable
	}


	public function testExceptionOnUndefinedPropertyGet()
	{
		$this->expectException(\ErrorException::class);

		$G = new G();
		echo $G->testingBadVar; // Invalid global variable
	}
}
