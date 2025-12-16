<?php
/**    ____                       _____ __  _      __
 *    / __ )____  ____  ____ ___ / ___// /_(_)____/ /__
 *   / __  / __ \/ __ \/ __ `__ \\__ \/ __/ / ___/ //_/
 *  / /_/ / /_/ / /_/ / / / / / /__/ / /_/ / /__/ ,<
 * /_____/\____/\____/_/ /_/ /_/____/\__/_/\___/_/|_|
 *
 * BoomStick.com - A framework for high explosive performance
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

namespace BoomStick\Lib\Session;
use BoomStick\Lib\Session as S;
use BoomStick\Lib\Debug as D;

/**
 * Runtime
 *
 * @author    Matt Roszyk <me@mattroszyk.com>
 * @package   Blaze.Core
 *
 */
class LocalPHP
{
	private $status;

	public function __construct($name, $lifetime)
	{
		$domain = preg_replace('/\:[0-9]*$/', '', $_SERVER['HTTP_HOST']);
		session_set_cookie_params([
			 'lifetime' => $lifetime
 			,'path' => '/'
			,'domain' => $domain
			,'httponly' => true
		]);

		session_name($name);
		session_start();
		$this->status = session_status();
	}

	public function __destruct()
	{
		session_write_close();
	}

	public function __get($key)
	{
		if(isset($_SESSION[$key])) {
			return $_SESSION[$key];
		}
		else {
			return null;
		}
	}

	public function __set($key, $value)
	{

		$_SESSION[$key] = $value;
	}

	public function __isset($key)
	{
		return isset($_SESSION[$key]);
	}

	public function all()
	{
		return $_SESSION;
	}

	public function URLToken()
	{
		return session_name().'='.session_id();
	}

	public function CSRFToken()
	{
		return session_id();
	}

	public function status()
	{
		switch($this->status) {
			case PHP_SESSION_DISABLED:
				return S::STATUS_DISABLED;
				break;

			case PHP_SESSION_ACTIVE:
				return S::STATUS_ACTIVE;
				break;

			case PHP_SESSION_NONE:
			default;
				return S::STATUS_NONE;
				break;
		}
	}

	public function save()
	{
		return true; // This session type automatically writes each value as it it set
	}
}
