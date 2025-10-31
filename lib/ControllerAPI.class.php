<?php


namespace BoomStick\Lib;
use \BoomStick\Lib\Globals as G;

/**
 * ControllerValues - Static values that are used to hold the values set within a controller.
 *                    This avoids variable name collision.
 */
class ControllerValues extends Struct
{
	public $variables = array();
	public $layout    = [
		 'modulePath' => null
		,'layout' => 'default'
	];
	public $key       = null;
	public $value     = null;
}


abstract class ControllerAPI extends Struct
{
	protected $cValues;
	protected $modulePath;

	public function __construct()
	{
		if(gettype($this->cValues) != 'object') {
			$this->cValues = new ControllerValues();
		}
	}

	public function setModulePath($modulePath)
	{
		$this->modulePath = $modulePath;
	}

	public function before()
	{
		return null;
	}

	public function after()
	{
		return null;
	}

	public function notfound()
	{
		header("HTTP/1.0 404 Not Found");
		die('Request is invalid');
	}

	public function notAuthorized()
	{
		header("HTTP/1.0 401 Unauthorized");
		die('Request is not authorized.');
	}

	protected function redirect($destination)
	{
		header('HTTP/1.1 302 Found');
		header('Location: ' . $destination);
		exit;
	}

	protected function redirectPerm($destination)
	{
		header('HTTP/1.1 301 Found');
		header('Location: ' . $destination);
		exit;
	}

	public function renderJSON($output)
	{
		header('Content-Type: application/json');
		echo json_encode($output);
		return;
	}

	public function invalidRequest()
	{
		header("HTTP/1.0 405 Method Not Allowed");
		die('Method Not Allowed');
	}

	public function getRequestedPath()
	{
		return G::$request->getRequestedPath();
	}

	public function getParameters()
	{
		return G::$route->getParameters();
	}

	public function buildResponse()
	{
		return new APIResponse();
	}

	public function buildResponseField()
	{
		return new APIResponseField();
	}
}

class _APIResponse extends Struct
{
	public $status   = 'success';
	public $data     = null;

	public function __construct()
	{
		$this->data = new \stdClass();
	}

	public function statusIs($status)
	{
		return ($this->status == $status);
	}

	public function isSuccess()
	{
		$this->status = 'success';
	}

	public function isError()
	{
		$this->status = 'error';
	}

	public function isWarning()
	{
		$this->status = 'warning';
	}
}

class APIResponse extends _APIResponse
{

	public $messages = array();

	public function __construct()
	{
		parent::__construct();
	}

}

class APIResponseField extends _APIResponse
{
	public $field          = null;
	public $fieldType      = 'input';
	public $validity       = 'valid';
	public $message        = null;
	public $value          = null;
	public $actionRequired = null;

	public function __construct()
	{
		parent::__construct();
	}

	public function isValid()
	{
		$this->validity='valid';
	}

	public function isInvalid()
	{
		$this->validity='invalid';
	}

	public function typeInput()
	{
		$this->fieldType = 'input';
	}

	public function typeSelect()
	{
		$this->fieldType = 'select';
	}

	public function typeTextarea()
	{
		$this->fieldType = 'textarea';
	}
}
