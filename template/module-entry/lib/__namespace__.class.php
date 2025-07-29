<?='<?php'?>

namespace BoomStick\Module\<?=O::$moduleNamespace;?>\Lib;
use BoomStick\Lib\Debug as D;

class <?=O::$moduleNamespace;?>
{
	public function __construct()
	{

	}

	public function hello()
	{
		return 'Hello, from '.__CLASS__.'::'.__FUNCTION__.'()';
	}
}
