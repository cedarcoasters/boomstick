<?php
/**    ____                       _____ __  _      __
 *    / __ )____  ____  ____ ___ / ___// /_(_)____/ /__
 *   / __  / __ \/ __ \/ __ `__ \\__ \/ __/ / ___/ //_/
 *  / /_/ / /_/ / /_/ / / / / / /__/ / /_/ / /__/ ,<
 * /_____/\____/\____/_/ /_/ /_/____/\__/_/\___/_/|_|
 *
 * BoomStick.com - A framework for high explosive performance
 * Copyright 2012 - 2024, BlazePHP.com
 *
 * Licensed under The MIT License
 * Any redistribution of this file's contents, both
 * as a whole, or in part, must retain the above information
 *
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 * @copyright     Copyright 2012 - 2017, BlazePHP.com
 * @link          http://blazePHP.com
 */

namespace BoomStick\Lib;


/**
 * Globals
 *
 * @author    Matt Roszyk <matt@roszyk.com>
 * @package   BoomStick.Core
 *
 */
class Globals extends Struct
{
	public static $debug = false;
	public static $route = [];
	public static $request;
}
