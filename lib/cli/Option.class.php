<?php
/**
 *
 * BoomStick - A framework for high performance
 * Copyright 2012 - 2025, BlazePHP.com/BoomStick
 *
 * Licensed under The MIT License
 * Any redistribution of this file's contents, both
 * as a whole, or in part, must retain the above information
 *
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 * @copyright     2012 - 2025, BlazePHP.com/BoomStick
 * @link          http://blazephp.com/boomstick
 *
 */
namespace BoomStick\Lib\CLI	;
use BoomStick\Lib\CLI\Argument as Argument;
/**
 * Flag - CLI Flag object
 *
 * @author    Matt Roszyk <me@mattroszyk.com>
 * @package   Blaze.Core
 *
 */
class Option extends Argument
{

	public $required    = false;
	public $default     = null;
}
