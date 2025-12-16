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

namespace BoomStick\Lib\Data\Db;

use \BoomStick\Lib\Debug as D;
use \PDO;

use BoomStick\Lib\Data\Field;



class Connect
{
	public $pdo;

	public function __construct($dsn, $username, $password)
	{
		$options = [
			 PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION
			,PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
			,PDO::ATTR_EMULATE_PREPARES   => false
			,PDO::ATTR_PERSISTENT         => true
			,PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci'
		];
		$this->pdo = new PDO($dsn, $username, $password, $options);
	}

	public function initTable()
	{
		return new Table($this);
	}

	public function initLyst($tableConfig=null)
	{
		return new Lyst(conn:$this, tableConfig:$tableConfig);
	}

	public function initRow($tableConfig=null)
	{
		return new Row(conn:$this, tableConfig:$tableConfig);
	}
}