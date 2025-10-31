<?php

namespace BoomStickTest;
use BoomStick\Lib\ControllerAPI;
use BoomStick\Lib\Test\Kase;
use BoomStick\Lib\Debug AS D;
use BoomStick\Lib\Globals AS G;
use BoomStick\Lib\Request;
use BoomStick\Lib\Route;

class TestRoute extends Route
{
	public function __construct()
	{
		$this->module = str_replace('/route', '', __DIR__);
	}
}



final class RouteTest extends Kase
{

	public function testRegister()
	{
		G::$route = new TestRoute();
		G::$route->register('TestRoute/register' ,'/testing/register');

		$al = G::$route::aliasList();
		$this->assertNotEmpty($al);
		$this->assertTrue(isset($al['/testing/register']));
		$this->assertTrue(isset($al['/testing/register']['route']));
		$this->assertValueEquals($al['/testing/register']['route'], 'TestRoute/register');

		G::$route::clearAliasList();
		G::$route = null;
	}

	public function testInitFail()
	{
		$_REQUEST['__requested_path'] = '/invalid/route';
		G::$request = new Request();
		G::$route = new TestRoute();
		G::$route->register('TestRoute/invalid' ,'/invalid/test/route');
		$this->assertFalse(true === G::$route->init());
		G::$route::clearAliasList();
		G::$route = null;
		G::$request = null;
	}

	public function testInitSuccess()
	{
		$_REQUEST['__requested_path'] = '/valid/route';
		G::$request = new Request();
		G::$route = new TestRoute();
		G::$route->register('TestRoute/valid' ,'/valid/route');
		$this->assertTrue(G::$route->init());
		G::$route::clearAliasList();
		G::$route = null;
		G::$request = null;
	}

	public function testAliasList()
	{
		G::$route = new TestRoute();
		G::$route->register('TestRoute/aliasList' ,'/alias/list');

		$al = G::$route::aliasList();

		$this->assertTrue(isset($al['/alias/list']));
		$this->assertTrue(isset($al['/alias/list']['route']));
		$this->assertValueEquals($al['/alias/list']['route'], 'TestRoute/aliasList');

		G::$route::clearAliasList();

		$al = G::$route::aliasList();
		$this->assertEmpty($al);

		G::$route = null;


	}

	public function testByName()
	{
		G::$route = new TestRoute();
		G::$route->register('TestRoute/byName' ,'/test/byName')->name('testByName');

		$this->assertValueEquals(G::$route->byName('testByName'), '/test/byName');

		G::$route->register('TestRoute/testByNameWithParameters/s1:valA/i1:valB', '/test/testByNameWithParameters/%s/%i')->name('testByNameWithParameters');

		$this->assertValueEquals(
			 G::$route->byName('testByNameWithParameters', ['ONE', 2])
			,'/test/testByNameWithParameters/ONE/2'
		);

		G::$route::clearAliasList();
		G::$route = null;
	}


	public function testTranslate()
	{
		G::$route = new TestRoute();
		G::$route->register('TestRoute/byName' ,'/other/path/to/byName');
		G::$route->register('TestRoute/byName' ,'/test/byName', G::$route::SEO_CANONICAL);

		list($cRoute, $canonPath) = G::$route->translate('/other/path/to/byName');

		$this->assertValueEquals($cRoute, 'TestRoute/byName');
		$this->assertValueEquals($canonPath, '/test/byName');

		G::$route::clearAliasList();
		G::$route = null;
	}


	public function testClassActionNames()
	{
		$_REQUEST['__requested_path'] = '/TestRoute/one/two/three';
		G::$request = new Request();
		G::$route = new TestRoute();
		G::$route->register('TestRoute/index' ,'/TestRoute');
		G::$route->register('TestRoute/getNumber/number:$s1' ,'/TestRoute/%s');
		G::$route->init();

		$className  = G::$route->controllerClassName();
		$action     = G::$route->controllerAction();
		$this->assertValueEquals($className, 'BoomStick\Module\Case\Controller\TestRoute');
		$this->assertValueEquals($action, 'getnumber');
	}

}