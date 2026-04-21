<?php
/**    ____                       _____ __  _      __
 *    / __ )____  ____  ____ ___ / ___// /_(_)____/ /__
 *   / __  / __ \/ __ \/ __ `__ \\__ \/ __/ / ___/ //_/
 *  / /_/ / /_/ / /_/ / / / / / /__/ / /_/ / /__/ ,<
 * /_____/\____/\____/_/ /_/ /_/____/\__/_/\___/_/|_|
 *
 * BoomStick.com - A framework for high explosive performance
 * Copyright 2012 - <?=date('Y');?>, BlazePHP.com
 *
 * Licensed under The MIT License
 * Any redistribution of this file's contents, both
 * as a whole, or in part, must retain the above information
 *
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 * @copyright     Copyright 2012 - 2025, BlazePHP.com
 * @link          http://blazePHP.com
 */
namespace Boomstick\Lib;
use Boomstick\Lib\Globals as G;
use Boomstick\Lib\Debug as D;

/**
 * Struct - A basic structure wrapper.  This is a very controlled way to create
 *          parameters for methods, template value holders, etc...
 *
 *          The goal of this class is to eliminate, as much as possible, the
 *          ambiguous nature of a template/class/method/etc
 *
 * @author    Matt Roszyk <me@mattroszyk.com>
 * @package   Blaze.Core
 *
 */
class Session extends Struct
{
	private $session;

	const STATUS_ACTIVE   = 'ACTIVE';
	const STATUS_NONE     = 'NONE';
	const STATUS_DISABLED = 'DISABLED';

	public function __construct($name='__boomstick_sid__', $type='local', $lifetime=(10 * 365 * 8640)/* 10 years */)
	{
		$type = (isset(G::$env->sessionType)) ? G::$env->sessionType : null;

		switch($type) {

			case 'local':
			default;
				$this->session = new \BoomStick\Lib\Session\LocalPHP($name, $lifetime);
				break;
		}

		if($this->session->status() != self::STATUS_ACTIVE) {
			throw new \Exception( implode(' ', array(
				 __CLASS__.'::'.__FUNCTION__
				,' - Session failed to activate'
			)));
		}
	}

	public function __get($key)
	{
		return $this->session->{$key};
	}

	public function __set($key, $value)
	{
		$this->session->{$key} = $value;
	}

	public function all()
	{
		return $this->session->all();
	}

	public function __isset($key)
	{
		return isset($this->session->{$key});
	}

	public function URLToken()
	{
		return $this->session->URLToken();
	}

	public function CSRFToken()
	{
		return $this->session->CSRFToken();
	}

	public function validateCSRFToken($token)
	{
		return $this->session->validateCSRFToken($token);
	}

	public function regenerateCSRFToken()
	{
		return $this->session->regenerateCSRFToken();
	}

	public function save()
	{
		return $this->session->save();
	}
}
