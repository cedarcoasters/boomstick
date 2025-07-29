<?php
define('BSROOT'     ,dirname(__DIR__));
define('BSMODULE'   ,BSROOT.'/module');
define('BSTEMPLATE' ,BSROOT.'/template');
define('BSLIB'      ,BSROOT.'/lib');
define('BSPUBLIC'   ,BSROOT.'/public');

require_once(BSLIB.'/Struct.class.php');
require_once(BSLIB.'/Globals.class.php');


use BoomStick\Lib\Globals as G;
G::$debug = true;
// require_once(BSLIB.'/Debug.class.php');
// use BoomStick\Lib\Debug as D;



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

	// echo $classFile,"\n";

	if(file_exists($classFile)) {
		require_once($classFile);
	}
	if(file_exists($traitFile)) {
		require_once($traitFile);
	}
});

// D::printre('test');