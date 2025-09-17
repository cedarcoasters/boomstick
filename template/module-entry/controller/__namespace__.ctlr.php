<?='<?php'?>

namespace BoomStick\Module\<?=O::$moduleNamespace;?>\Controller;
use BoomStick\Lib\Controller;
use BoomStick\Lib\Debug as D;

use BoomStick\Module\<?=O::$moduleNamespace;?>\Lib\<?=O::$moduleNamespace;?> as Hello;

class <?=O::$moduleNamespace;?> extends Controller
{
	public function index()
	{
		$lib = new Hello();

		$this->libSaysHello = $lib->hello();
		$this->bodyView = 'index';
		$this->render();
	}

	public function notFound()
	{
		$this->bodyView = 'not-found';
		header("HTTP/1.1 404 Not Found");
		$this->render();
	}
}
