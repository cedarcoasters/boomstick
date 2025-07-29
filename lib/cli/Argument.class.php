<?php
/**
 *
 * BoomStick - A framework for high performance
 * Copyright 2012 - 2025, BlazePHP.com/boomstick
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
namespace BoomStick\Lib\CLI;


/**
 * Argument - Abstract base CLI argument object
 *
 * @author    Matt Roszyk <me@mattroszyk.com>
 * @package   Blaze.Core
 *
 */
class Argument extends \BoomStick\Lib\Struct
{
	public $long        = null;
	public $short       = null;
	public $description = null;
	public $dependants  = array();
}
