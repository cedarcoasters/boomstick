<?='<?php'?>

namespace BoomStick\Module\<?=O::$moduleNamespace;?>\Controller;
use BoomStick\Lib\ControllerAPI;
use BoomStick\Lib\Debug as D;

use BoomStick\Module\<?=O::$moduleNamespace;?>\Lib\Say;

class <?=O::$moduleNamespace;?> extends ControllerAPI
{
	public function hello()
	{
		$response = $this->buildResponse();
		$response->data = Say::hello();

		$this->renderJSON($response);
	}
}
