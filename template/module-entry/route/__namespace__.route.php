<?='<?php';?>

namespace BoomStick\Module\<?=O::$moduleNamespace;?>\Route;
use BoomStick\Lib\Route;
use BoomStick\Lib\Debug as D;

class <?=O::$moduleNamespace;?> extends Route
{
	public function __construct()
	{
		$this->module = str_replace('/route', '', __DIR__);
	}
}

$route = new <?=O::$moduleNamespace;?>();

$route->register('<?=O::$moduleNamespace;?>/notFound' ,'/not-found');

$route->register('<?=O::$moduleNamespace;?>/index' ,'/');


