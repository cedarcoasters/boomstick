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

namespace BoomStick\Lib\Data;

use \BoomStick\Lib\Debug as D;
use \BoomStick\Lib\Struct;
use \BoomStick\Lib\Data\Db\Connect;

abstract class Manager extends Struct
{
	protected $conn;
	protected $table;

	public static function new(): self
	{
		return new static();
	}

	protected function table($name)
	{
		$this->table = $name;
	}

	public function __get($table)
	{
		$this->table = $table;
		return $this; //->initRow()->fromTable($table);
	}

	public function row()
	{
		if(empty($this->table)) {
			$message = 'You are executing '.__CLASS__.'::'.__FUNCTION__.' before establishing the table name to pull the row from.';
			throw new \LogicException($message);
		}

		$tableConfig = (method_exists($this, 'CONFIG_'.$this->table))
			? $this->{'CONFIG_'.$this->table}()
			: null;
		return $this->conn->initRow($tableConfig)->fromTable($this->table);
	}

	public function lyst()
	{
		if(empty($this->table)) {
			$message = 'You are executing '.__CLASS__.'::'.__FUNCTION__.' before establishing the table name to pull the lyst from.';
			throw new \LogicException($message);
		}
		$tableConfig = (method_exists($this, 'CONFIG_'.$this->table))
			? $this->{'CONFIG_'.$this->table}()
			: null;
		return $this->conn->initLyst($tableConfig)->fromTable($this->table);
	}

	public function __set($property, $value)
	{
		$reflector = new \ReflectionClass($this);

		if ($reflector->hasProperty($property) && $reflector->getProperty($property)->name === $property) {

			switch($property) {
				case 'type':
					$this->setType($value);
					break;

				default;
					$this->{$property} = $value;
					break;
			}
			return;
		}

		parent::__set($property, $value);
	}


	public function __construct()
	{
		$this->conn = new Connect(
			 dsn:'mysql:host=db;port=3306;dbname=blaze_press_site;charset=utf8mb4'
			,username:'coaster_link'
			,password:'7000b2272d24c3d1e81545accd21f93d'
		);
	}

	public function makeConfigFields()
	{
		$fields = new \stdClass();

		$fields->id = FieldOverride::new()
			->hidden(true)
			->onNewFunc(function(){
				return 'NEW';
			});

		$fields->time_created = FieldOverride::new()
			->sqlOnInsert('NOW()')
			->immutable(true)
			->ignore(true);

		$fields->time_modified = FieldOverride::new()
			->immutable(true)
			->ignore(true);

		return $fields;
	}


	public static function pivotRowsByKey($rowsRaw, $keyField='id')
	{
		$rows = [];
		foreach($rowsRaw as $row) {
			$rows[$row[$keyField]] = $row;
		}
		return $rows;
	}

	public static function pivotRowsForSelect($rowsRaw, $displayField, $keyField='id', $showKey=false)
	{
		$rows = [];
		foreach($rowsRaw as $row) {
			$rows[$row[$keyField]] = ($showKey === true)
				? '['.$row[$keyField].'] '.$row[$displayField]
				: $row[$displayField];
		}
		return $rows;
	}
}