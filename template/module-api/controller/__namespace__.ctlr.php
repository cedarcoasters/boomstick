<?='<?php'?>

namespace BoomStick\Module\<?=O::$moduleNamespace;?>\Controller;
use BoomStick\Lib\Controller;
use BoomStick\Lib\Debug as D;

class <?=O::$moduleNamespace;?> extends Controller
{
	public function index()
	{
		$this->bodyView = 'index';
		$this->render();
	}
}
