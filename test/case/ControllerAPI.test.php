<?php

namespace BoomStickTest;
use BoomStick\Lib\ControllerAPI;
use BoomStick\Lib\Test\Kase;
use BoomStick\Lib\Debug AS D;
use BoomStick\Lib\Globals AS G;
use BoomStick\Lib\Request;
use BoomStick\Lib\Route;

class Groovy extends Route
{
	public function __construct()
	{
		$this->module = str_replace('/route', '', __DIR__);
	}
}
$_REQUEST['__requested_path'] = '/groovy/one/two/three';
G::$request = new Request();
G::$route = new Groovy();
G::$route->register('Groovy/index' ,'/groovy');
G::$route->register('Groovy/getNumber/number:$s1' ,'/groovy/%s');
G::$route->init();

class CAPI extends ControllerAPI
{
	public function hello()
	{
		$response = $this->buildResponse();
		$response->data = ['say' => 'hello'];

		$this->renderJSON($response);
	}
}

final class ControllerAPITest extends Kase
{
	public function testInstanceCreates()
	{
		$this->assertInstanceOf(CAPI::class, new CAPI());
	}

	public function testExceptionOnUndefinedPropertySet()
	{
		$this->expectException(\ErrorException::class);

		$CAPI = new CAPI();
		$CAPI->testingBadVar = 'test'; // Invalid global variable
	}


	public function testExceptionOnUndefinedPropertyGet()
	{
		$this->expectException(\ErrorException::class);

		$CAPI = new CAPI();
		echo $CAPI->testingBadVar; // Invalid global variable
	}


	public function testSayHello()
	{
		$CAPI = new CAPI();

		ob_start();
		$CAPI->hello();
		$output = json_decode(ob_get_contents());
		ob_end_clean();

		$this->assertTrue($output->status === 'success');
		$this->assertTrue($output->data->say === 'hello');
	}

	public function testBeforeDefault()
	{
		$CAPI = new CAPI();
		$this->assertTrue($CAPI->before() === null);
	}

	public function testAfterDefault()
	{
		$CAPI = new CAPI();
		$this->assertTrue($CAPI->after() === null);
	}

	public function testGetParameters()
	{
		$CAPI = new CAPI();

		// ob_start();
		$params = $CAPI->getParameters();
		$this->assertTrue(isset($params['number']));
		$this->assertTrue($params['number'] === 'one');
		$this->assertTrue(isset($params['two']));
		$this->assertTrue($params['two'] === true);
		$this->assertTrue(isset($params['three']));
		$this->assertTrue($params['three'] === true);
	}

	public function ___testGetControllerClass()
	{
		$CAPI = new CAPI();

		// $className  = G::$route->controllerClassName();
		// $controller = new $className();
		// $action     = G::$route->controllerAction();
		// D::printre([$params, $className, $action]);
		// $output = ob_get_clean();
		// D::printre($output);
		// $this->assertTrue($CAPI->notFound() === null);
	}
}