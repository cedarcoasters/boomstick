<?php
/**
 *
 * Boomstick\Lib.com - A framework for high performance
 * Copyright 2012 - 2024, Boomstick.com
 *
 * Licensed under The MIT License
 * Any redistribution of this file's contents, both
 * as a whole, or in part, must retain the above information
 *
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 * @copyright     2012 - 2024, Boomstick.com
 * @link          http://Boomstick\Lib.com
 *
 */
namespace BoomStick\Lib\CLI;
use BoomStick\Lib\Globals as G;
use BoomStick\Lib\Struct;
use BoomStick\Lib\Debug as D;

/**
 * Message
 *
 * @author    Matt Roszyk <me@mattroszyk.com>
 * @package   Blaze.Core
 *
 */
class Message extends Struct
{
	public static $padLength = 80;
	public static $padChar   = '.';
	public static $padType   = STR_PAD_RIGHT; // (STR_PAD_RIGHT, STR_PAD_LEFT, or STR_PAD_BOTH)

	const LEVEL_0  = 0;
	const LEVEL_1  = 1;
	const LEVEL_2  = 2;
	const LEVEL_3  = 3;
	const LEVEL_4  = 4;
	const LEVEL_5  = 5;
	const LEVEL_6  = 6;
	const LEVEL_7  = 7;
	const LEVEL_8  = 8;
	const LEVEL_9  = 9;
	const LEVEL_10 = 10;
	const LEVEL_11 = 11;
	const LEVEL_12 = 12;

	const NO_NEW_LINE  = false;
	const ADD_NEW_LINE = true;

	public static function send($message, $level=1, $addNewLine=true, $color=null)
	{
		if(isset(G::$cli) && is_object(G::$cli) && get_class(G::$cli) == 'BoomStick\Lib\CLI\CLI') {
			if(!empty($color)) {
				G::$cli->verbose(G::$cli->colorize($message, $color), $level, $addNewLine);
			}
			else {
				G::$cli->verbose($message, $level, $addNewLine);
			}
		}

		if(isset(G::$log) && is_object(G::$log) && get_class(G::$log) == 'BoomStick\Lib\Log') {
			G::$log->write($level, $message, $addNewLine);
		}
	}

	public static function sendLineBreak($level=1, $quantity=1)
	{
		for($i=0; $i<$quantity; $i++) {
			self::send('', $level, true);
		}
	}


	public static function error($message, $addNewLine=true, $color=null)
	{
		if(is_object(G::$cli) && !empty($color)) {
			G::$cli->error(G::$cli->colorize($message, $color), $addNewLine);
		}
		elseif(is_object(G::$cli)) {
			G::$cli->error($message, $addNewLine);
		}
		if(is_object(G::$log) && get_class(G::$log) == '\Boomstick\Lib\Log') {
			G::$log->write(1, $message);
		}
	}

	public static function pad($message)
	{
		return str_pad($message, self::$padLength, self::$padChar);
	}

	public static function getLog()
	{
		return G::$cli->getVerboseLog();
	}
}
