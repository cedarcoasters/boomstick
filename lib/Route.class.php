<?php


namespace BoomStick\Lib;
use BoomStick\Lib\Globals as G;
use BoomStick\Lib\Debug as D;


/**
 * RouteName
 *
 * Returned object from the Route::byName() method used to
 * associate name aliases with defined routes.
 */
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


/**
 * BoomStick Route
 */
class Route
{
	// SEO Routing
	const SEO_CANONICAL     = 'seo_canonical';
	const SEO_NO_INDEX      = 'seo_no_index';
	const SEO_USE_PERMALINK = 'seo_use_permalink';

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


	/**
	 * Initializes the Route object
	 *
	 * Call after the routes have been defined and the \BoomStick\Lib\Request object has
	 * been created and assined to the G::$request variable.
	 *
	 * @return boolean true on success
	 */
	public function init():bool
	{
		$this->pathOriginal = G::$request->getRequestedPath();
		try {
			list($this->path, $this->pathCanonical) = $this->translate($this->pathOriginal);
			$this->parse();
			return true;
		}
		catch(\Exception $e) {
			// D::printre('made it');
			header('Location: /not-found');
			return false;
		}
	}

	/**
	 * Returns the list of aliases associated with the routes
	 *
	 * When called, the values stored within the static Route::$aliasList class variable
	 * are returned.
	 *
	 * @return array
	 */
	public static function aliasList():array
	{
		return self::$aliasList;
	}


	/**
	 * Clears the list of aliases associated with the routes
	 *
	 * When called, the values stored within the static Route::$aliasList class variable
	 * are cleared and the default empty array is set.
	 *
	 * return void
	 */
	public static function clearAliasList():void
	{
		self::$aliasList       = [];
		self::$nameToAliasList = [];
	}


	/**
	 * Registers a new route and returns a RouteName object
	 *
	 * Sets a Controller Route (Controller/method) to an alias (/url/path).  It returns a RouteName
	 * object that can then be used to assign a route name to the registration.
	 *
	 * @param string $route [Controller]/[method] route path
	 * @param string $alias Requested URL path that is used to reference the controller route
	 * @param string $seoType One of the SEO Route::constant values
	 * @return RouteName
	 * @example
	 * G::$route->register('TestRoute/testByNameWithParameters/s1:valA/i1:valB', '/test/testByNameWithParameters/%s/%i')->name('testByNameWithParameters');
	 */
	public function register($route, $alias, $seoType=null):RouteName
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
		if($seoType === self::SEO_CANONICAL) {
			$this->pathCanonicalList[$route] = $alias;
		}
		elseif($seoType === self::SEO_NO_INDEX) {
			$this->pathNoIndexList[] = $alias;
		}
		return new RouteName($this, $alias);
	}


	/**
	 * Returns a URL Path that matches the route alias with populated parameters
	 *
	 * When building URL Paths inside of PHP Templates (or other places) it is easier
	 * to reference by a name.  This method allows you to get a properly built URL Path
	 * that is human readable in the context of a template.
	 *
	 * @param string $name Human readable name of a route alias
	 * @param array $parameters An ordered list of parameters that are inserted into the placeholders of the route alias
	 * @return string URL Path
	 * @example
	 * <?=G::$route->byName('product-details', [3456]);?>  // Builds /product/details/3456
	 */
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


	/**
	 * Used to associate a RouteName to an alias
	 *
	 * This method is called from with the RouteName class and is handled
	 * automatically when the name is associated.
	 *
	 * @param string $name Human readable name
	 * @param string $alias URL Path alias to a given Controller Route
	 * @see RouteName::name()
	 */
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


	/**
	 * Translates a given URL Path to an alias
	 *
	 * When a given URL Path is provided, this method cascades over the defined
	 * route aliases and returns the real path and the canonical (if one exists)
	 *
	 * @param string $path URL Path
	 */
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

			// Check for /
			if($alias === '/') { //count($parts) == 1 && empty($parts[0])) {
				$regexAliases[$alias] = array('regex' => array('/^$/'), 'route' => $route);
				continue;
			}

			$parts      = explode('/', $alias);
			$regexParts = [];


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

		// D::printre($regexAliases);

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
			throw new \Exception( implode(' ', array(
				 __CLASS__.'::'.__FUNCTION__
				,' - The requested route does not exist.'
			)));
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



	public function controllerLocation()
	{
		return $this->module.'/controller/'.$this->controller.'.ctlr.php';
	}

	public function modulePath()
	{
		return $this->module;
	}

	public function moduleBasename()
	{
		return basename($this->module);
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


	private function parse()
	{
		if(empty($this->path)) {
			throw new \Exception( implode(' ', array(
				 __CLASS__.'::'.__FUNCTION__
				,' - The path submitted is unknown. Please verify your route configuration and try again.'
			)));
		}

		$parts            = explode('/', preg_replace('/^\/*/', '', $this->path));
		$controller       = (isset($parts[0])) ? array_shift($parts) : null;
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
}