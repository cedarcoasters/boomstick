<?php


namespace BoomStick\Lib;
use BoomStick\Lib\Globals as G;
use BoomStick\Lib\Debug as D;

class RouteName
{
	protected $route;
	protected $alias;

	public function __construct(Route $route, $alias)
	{
		$this->route = $route;
		$this->alias = $alias;
	}
	public function name($name)
	{
		$this->route->setNameToAlias($name, $this->alias);
	}
}

class Route
{
	protected static $aliasList       = [];
	protected static $nameToAliasList = [];

	protected $module;
	protected $pathOriginal      = null;
	protected $path              = null;
	protected $pathCanonical     = null;
	protected $pathCanonicalList = [];
	protected $pathNoIndexList   = [];
	protected $controller;
	protected $action;
	protected $parameters        = [];


	public function init()
	{
		$this->pathOriginal = G::$request->getRequestedPath();
		list($this->path, $this->pathCanonical) = $this->translate($this->pathOriginal);
		$this->parse();
	}

	public static function aliasList()
	{
		return self::$aliasList;
	}

	public function register($route, $alias)
	{
		if(isset(self::$aliasList[$alias])) {
			throw new \Exception( implode(' ', array(
				 __CLASS__.'::'.__FUNCTION__
				,' - The alias ['.$alias.'] has already been registered to ['.implode('/', self::$aliasList[$alias]).']'
			)));
		}
		self::$aliasList[$alias] = [
			 'module' => $this->module
			,'route' => $route
		];
		return new RouteName($this, $alias);
	}

	public function byName($name, $parameters=[])
	{
		if(!isset(self::$nameToAliasList[$name])) {
			throw new \Exception( implode(' ', array(
				 __CLASS__.'::'.__FUNCTION__
				,' - The route name ['.$name.'] does not exist.'
			)));
		}
 		$parts = explode('/', self::$nameToAliasList[$name]);
 		$parKey = 0;
 		foreach($parts as $key => $part) {
 			if(in_array($part, ['%s', '%i'])) {
 				$parts[$key] = (isset($parameters[$parKey])) ? $parameters[$parKey++] : '';
 			}
 		}
 		return  implode('/', $parts);
	}

	public function setNameToAlias($name, $alias)
	{
		if(isset(self::$nameToAliasList[$name])) {
			throw new \Exception( implode(' ', array(
				 __CLASS__.'::'.__FUNCTION__
				,' - The name ['.$name.'] has already been registered to ['.implode('/', self::$aliasList[self::$nameToAliasList[$name]]).']'
			)));
		}
		elseif(empty($name) || empty($alias)) {
			throw new \Exception( implode(' ', array(
				 __CLASS__.'::'.__FUNCTION__
				,' - The name ['.$name.'] or alias ['.$alias.'] is/are empty.'
			)));
		}

		self::$nameToAliasList[$name] = $alias;
	}



	public function translate($path)
	{
		$orgPath = $path;
		$search = array(
			 '/^\%i$/'
			,'/^\%s$/'
		);
		$replace = array(
			 '[0-9]+'
			,'[a-zA-Z0-9\-\.\%\@_\=\s]+'
		);
		$regexAliases = [];
		foreach(self::$aliasList as $alias => $config) {
			$module = $config['module'];
			$route  = $config['route'];

			$parts      = explode('/', $alias);
			$regexParts = [];

			// Check for /
			if(count($parts) == 1 && empty($parts[0])) {
				$regexAliases[$alias] = array('regex' => array('/^\/$/'), 'route' => $route);
				continue;
			}

			foreach($parts as $part) {
				if(empty($part)) {
					continue;
				}
				$regexPart = preg_replace($search, $replace, $part, -1, $found);
				if($found <= 0) {
					$regexPart = preg_replace('/\-/', '\\-', $regexPart);
				}
				$regexParts[] = '/^'.$regexPart.'$/';
			}
			$regexAliases[$alias] = array('regex' => $regexParts, 'route' => $route);
		}

		$path      = preg_replace('/^\//', '', $path);
		$pathParts = explode('/', $path);
		$matches   = [];

		foreach($regexAliases as $alias => $conf) {

			$match = true;
			$i = 0;
			foreach($conf['regex'] as $pattern) {

				if(!isset($pathParts[$i])) {
					$match = false;
					break;
				}

				if(!preg_match($pattern, $pathParts[$i])) {
					$match = false;
				}

				$i++;

				if($match === false) {
					break;
				}
			}
			if($match === true) {
				$matches[] = $alias;
			}
		}

		if(count($matches) <= 0) {
			return $path;
		}
		ksort($matches);
		$aliasMatch = array_pop($matches);

		$pathParts = explode('/', preg_replace('/^\//', '', $path));
		$aliasParts = explode('/', preg_replace('/^\//', '', $aliasMatch));

		$c = count($aliasParts);
		for($i=0; $i<$c; $i++) {

			$pathPart  = array_shift($pathParts);
			$aliasPart = array_shift($aliasParts);

			if($pathPart == $aliasPart) {
				continue;
			}
			$type = 'integer';
			$partCheck = preg_replace('/^[0-9]+$/', '%i', $pathPart, -1, $found);
			if($found <= 0) {
				$type = 'string';
				$partCheck = preg_replace('/^[a-z0-9A-Z\-\.\%\@_\=]+$/', '%s', $pathPart, -1, $found);
			}

			$parameterValues[$type][] = $pathPart;
		}

		$realPath     = self::$aliasList[$aliasMatch]['route'];
		$this->module = self::$aliasList[$aliasMatch]['module'];

		$v = (isset($parameterValues['integer'])) ? count($parameterValues['integer']) : 0;
		for($i=1; $i<=$v; $i++) {
			$realPath = preg_replace('/\$i'.$i.'/', $parameterValues['integer'][$i-1], $realPath);
		}
		$v = (isset($parameterValues['string'])) ? count($parameterValues['string']) : 0;
		for($i=1; $i<=$v; $i++) {
			$realPath = preg_replace('/\$s'.$i.'/', $parameterValues['string'][$i-1], $realPath);
		}

		$canonicalPath = (array_key_exists($realPath, $this->pathCanonicalList)) ? $this->pathCanonicalList[$realPath] : null;

		if(count($pathParts) > 0) {
			$realPath .= '/'.implode('/', $pathParts);
		}

		return [$realPath, $canonicalPath];
	}

	public function parse()
	{
		if(empty($this->path)) {
			throw new \Exception( implode(' ', array(
				 __CLASS__.'::'.__FUNCTION__
				,' - The path submitted is unknown. Please verify your route configuration and try again.'
			)));
		}

		$parts            = explode('/', preg_replace('/^\/*/', '', $this->path));
		$controller = (isset($parts[0])) ? array_shift($parts) : null;
		$this->controller = implode('', array_map('ucfirst', explode('-', $controller)));
		$this->action     = (isset($parts[0])) ? array_shift($parts) : null;

		$partsCount = count($parts);
		$key        = null;
		for($i=0; $i<$partsCount; $i++) {
			$part = array_shift($parts);
			if(preg_match('/\:/', $part)) {
				list($key, $value) = explode(':', $part);
				$this->parameters[$key] = $value;
				$key  = null;
			}
			else if(preg_match('/^[a-zA-Z_][a-zA-Z0-9\-_]*$/', $part)) {
				if(is_null($key)) {
					$key = $part;
				}
			}
			else {
				// TODO: Log an invalid parameter
			}
			if(!is_null($key) && !isset($this->parameters[$key])) {
				$this->parameters[$key] = true;
				$key = null;
			}
		}
	}


	public function controllerLocation()
	{
		return $this->module.'/controller/'.$this->controller.'.ctlr.php';
	}

	public function modulePath()
	{
		return $this->module;
	}

	public function moduleNamespace()
	{
		$namespace = [];
		$parts     = explode('/', $this->module);
		$nsParts   = explode('_', array_pop($parts));
		foreach($nsParts as $part) {
			$namespace[] = implode('', array_map('ucfirst', explode('-', $part)));
		}
		return 'BoomStick\Module\\'.implode('', $namespace);
	}

	public function controllerClassName()
	{
		return $this->moduleNamespace().'\\Controller\\'.$this->controller;
	}

	public function controllerAction()
	{
		return (!empty($this->action)) ? strtolower($this->action) : 'index';
	}

	public function getParameters()
	{
		return $this->parameters;
	}

	public function getOriginalPath()
	{
		return $this->pathOriginal;
	}

	public function getCanonicalPath()
	{
		return $this->pathCanonical;
	}

	public function getNoIndexList()
	{
		return $this->pathNoIndexList;
	}
}