<?='<?php'?>

namespace BoomStick\Module\<?=O::$moduleNamespace;?>\Lib;
use BoomStick\Lib\Debug as D;

class Say
{
	public static function hello()
	{
		return (object)[
			'hello' => 'From the Shared Lib module'
		];
	}
}
