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
use BoomStick\Lib\Data\Db\Connect;



class Table
{
	private $conn;


	public function __construct(Connect $conn)
	{
		$this->conn = $conn;
	}


	public function desc(string $table, array $fields=[], bool $print=false)
	{
		$stmt = $this->conn->pdo->prepare('DESC `'.$table.'`');
		$stmt->execute();
		$rows = $stmt->fetchAll();
		// D::printre($rows);
		$desc = [];
		foreach($rows as $row) {

			if(count($fields) > 0 && !in_array($row['Field'], $fields)) {
				continue;
			}

			$immutable = false;
			if(
				$row['Type'] == 'timestamp' && preg_match('/^default_generated\son\supdate/', strtolower($row['Extra']))
			) {
				$immutable = true;
			}

			$hidden = false;
			if($row['Key'] === 'PRI') {
				$hidden = true;
			}

			$field = new Field($row['Type']);
			$field->name       = $row['Field'];
			$field->label      = ucwords(implode(' ', explode('_', $row['Field'])));
			$field->default    = $row['Default'];
			$field->immutable  = $immutable;
			$field->hidden     = $hidden;
			$field->ignore     = false;

			$desc[$field->name] = $field;
		}

		if($print === true) {
			header('Content-Type: application/json');
			echo json_encode($desc);
			exit;
		}
		return $desc;
	}



}