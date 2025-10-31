<?php

namespace BoomStickTest;
use BoomStick\Lib\Debug;
use BoomStick\Lib\Test\Kase;

final class DebugTest extends Kase
{

	public function testInstanceCreates()
	{
		$this->assertInstanceOf(Debug::class, new Debug(__CLASS__, md5(time())));
	}

}
