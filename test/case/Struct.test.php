<?php

namespace BoomStickTest;
use BoomStick\Lib\Struct;
use BoomStick\Lib\Test\Kase;


class TestStruct extends Struct
{
	public $name;
	public $code;
}

final class StructTest extends Kase
{
	public function testInstanceCreates()
	{
		$this->assertInstanceOf(Struct::class, new Struct());
	}

	public function testExceptionOnUndefinedPropertySet()
	{
		$this->expectException(\ErrorException::class);

		$S = new TestStruct();
		$S->testingBadVar = 'test'; // Invalid global variable
	}

	public function testExceptionOnUndefinedPropertyGet()
	{
		$this->expectException(\ErrorException::class);

		$S = new TestStruct();
		echo $S->testingBadVar; // Invalid global variable
	}

	public function testAssignValidVariable()
	{
		$S = new TestStruct();
		$S->name = 'Ash Williams';
		$S->code = 'GROOVY';

		$this->assertTrue($S->name === 'Ash Williams' && $S->code = 'GOOVY');
	}
}
