<?php
/**    ____                       _____ __  _      __
 *    / __ )____  ____  ____ ___ / ___// /_(_)____/ /__
 *   / __  / __ \/ __ \/ __ `__ \\__ \/ __/ / ___/ //_/
 *  / /_/ / /_/ / /_/ / / / / / /__/ / /_/ / /__/ ,<
 * /_____/\____/\____/_/ /_/ /_/____/\__/_/\___/_/|_|
 *
 * BoomStick.com - A framework for highly explosive performance
 * Copyright 2012 - 2025, BlazePHP.com
 *
 * Licensed under The MIT License
 * Any redistribution of this file's contents, both
 * as a whole, or in part, must retain the above information
 *
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 * @copyright     Copyright 2012 - 2025, BlazePHP.com
 * @link          http://blazePHP.com
 */
namespace BoomStick;

define('BSROOT', dirname(__DIR__));
define('BSMODULE', BSROOT.'/module');

require_once(BSROOT.'/lib/Struct.class.php');
require_once(BSROOT.'/lib/Globals.class.php');
require_once(BSROOT.'/lib/Request.class.php');

use BoomStick\Lib\Globals as G;
use BoomStick\Lib\Request as Request;
use BoomStick\Lib\Route   as Route;

G::$debug = true;
require_once(BSROOT.'/lib/Debug.class.php');
use BoomStick\Lib\Debug as D;

spl_autoload_register(function($className) {

	$parts = explode('\\', $className);
	if(array_shift($parts) != 'BoomStick') {
		return;
	}

	$objName = array_pop($parts);
	$class = $objName.'.class.php';
	$trait = $objName.'.trait.php';

	$translate = [BSROOT];
	foreach($parts as $part) {
		$translate[] = strtolower($part);
	}

	$classFile = implode('/', $translate).'/'.$class;
	$traitFile = implode('/', $translate).'/'.$trait;

	if(file_exists($classFile)) {
		require_once($classFile);
	}
	if(file_exists($traitFile)) {
		require_once($traitFile);
	}
});

G::$request = new Request();
require_once(BSMODULE.'/route.map.php');
G::$route = new Route();



// D::printr(G::$route::aliasList());

G::$route->init();
require(G::$route->controllerLocation());
$className  = G::$route->controllerClassName();
$controller = new $className();
$controller->setModulePath($route->modulePath());
$action     = G::$route->controllerAction();
$controller->before();
if(method_exists($controller, $action)) {
	$controller->{$action}();
}
else {
	$controller->notfound();
}
$controller->after();