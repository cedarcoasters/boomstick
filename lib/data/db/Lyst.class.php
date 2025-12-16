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

use PDO;

use BoomStick\Lib\Debug as D;

class Lyst
{
	private $conn;
	private $tableName;
	private $tableObj;
	private $tableConfig;

	public $fields;
	public $validFieldNames;

	public function __construct(Connect $conn, $tableConfig=null)
	{
		$this->conn        = $conn;
		$this->tableConfig = $tableConfig;
	}

	public function fromTable($tableName)
	{
		$this->tableObj        = new Table($this->conn);
		$this->fields          = $this->tableObj->desc($tableName);
		$this->validFieldNames = array_keys($this->fields);
		$this->tableName       = $tableName;
		return $this;
	}

	public function forSelect(string $key, string $fieldDisplay, bool $showKey=false)
	{

		$rows = $this->distinct(fields:[$key, $fieldDisplay]);

		$list = [];
		foreach($rows as $row) {
			$list[$row[$key]] = ($showKey === true)
				? '['.$row[$key].'] '.$row[$fieldDisplay]
				: $row[$fieldDisplay];
		}

		return $list;
	}


	public function distinct($fields, $count=100, $valuesOnly=true, $orderBy=[], $where=[])
	{
		return $this->page(fields:$fields, distinct:true, page:1, count:$count, orderBy:$orderBy, where:$where, valuesOnly:$valuesOnly);
	}


	public function page(int $page, int $count=100, array $orderBy=[], array $where=[], array $fields=[], bool $dataOnly=false, $distinct=false, $valuesOnly=false)
	{
		$page   = max(1, $page);
		$count  = max(1, min(100, $count)); // optional: limit max per page
		$offset = ($page - 1) * $count;

		$dataOnly = ($valuesOnly === true) ? true : $dataOnly;

		$whereSQL  = [];
		$sqlParams = [];

		// Where Filter
		if(!empty($where) && count($where) > 0 && is_array($where)) {
			foreach($where as $sql => $params) {
				$whereSQL[] = $sql;
				$sqlParams += $params;
			}
		}

		// Total Pages/Records
		$sql = [];
		$sql[] = 'SELECT COUNT(1) AS `totalRecords` FROM `'.$this->tableName.'`';

		if(count($whereSQL) > 0) {
			$sql[] = 'WHERE';
			$sql[] = implode(' ', $whereSQL);
		}

		try {
			$stmt = $this->conn->pdo->prepare(implode(' ', $sql));
			$stmt->execute($sqlParams);
			$totalRecords = (int) $stmt->fetchColumn();
		} catch (\PDOException $e) {
			// In production, log this instead of exposing
			throw new \RuntimeException('Database error: ' . $e->getMessage());
		}


		// Field List
		$fieldNames = $fields;
		if(!empty($fields) && count($fields) > 0) {
			array_walk($fields, function(&$field, $key) {
				if(!in_array($field, $this->validFieldNames)) {
					$message = 'The field ['.$field.'] is invalid for table ['.$this->tableName.'].  Valid fields: ['.implode(', ', $this->validFieldNames).']';
					throw new \InvalidArgumentException($message);
				}
				$field = '`'.$this->tableName.'`.`'.preg_replace('/[^a-zA-Z0-9_]/', '_', $field).'`';
			});
			$fieldsSQL = $fields;
		}
		else {
			$fieldsSQL = ['`'.$this->tableName.'`.*'];
		}


		// Order By (this probably needs revisiting)
		if(!empty($orderBy) && count($orderBy) > 0) {
			$orderBySQL = $orderBy;
		}
		else {
			$orderBySQL = [];
		}


		// Results SQL
		$sql = [];
		$sql[] = 'SELECT';
		if($distinct === true) {
			$sql[] = 'DISTINCT';
		}
		$sql[] = implode(', ', $fieldsSQL);
		$sql[] = 'FROM `'.$this->tableName.'`';
		if(count($whereSQL) > 0) {
			$sql[] = 'WHERE';
			$sql[] = implode(' AND ', $whereSQL);
		}
		if(count($orderBySQL) > 0) {
			$sql[] = 'ORDER BY';
			$sql[] = implode(', ', $orderBySQL);
		}
		$sql[] = 'LIMIT :count OFFSET :offset';
		$sqlParams += [':count' => $count, ':offset' => $offset];



		try {
			$stmt = $this->conn->pdo->prepare(implode(' ', $sql));
			$stmt->execute($sqlParams);


			$rowsRaw = $stmt->fetchAll(PDO::FETCH_ASSOC);
			$fields = array_keys($rowsRaw[0]);
			$fieldLabels = [];
			$labelFields = [];
			foreach($fields as $field) {
				$label = ucwords(implode(' ', explode('_', $field)));
				$fieldLabels[$field] = $label;
				$labelFields[$label] = $field;
			}

			if($dataOnly === true) {
				$rows = &$rowsRaw;
			}
			else {
				$rows = [];
				foreach($rowsRaw as $rowRaw) {
					$row = $this->conn->initRow();
					// D::printre($row);
					$row->fromTable($this->tableName, $fieldNames)->populate($rowRaw);
					$rows[] = $row;
				}
			}

		} catch (\PDOException $e) {
			// In production, log this instead of exposing
			throw new \RuntimeException('Database error: ' . $e->getMessage());
		}

		if($valuesOnly === true) {
			return $rows;
		}

		$totalPages = ceil($totalRecords / $count);
		return (object)[
			 'page'         => $page
			,'count'        => $count
			,'totalRecords' => $totalRecords
			,'totalPages'   => $totalPages
			,'hasNext'      => ($page < $totalPages)
			,'hasPrev'      => ($page > 1)
			,'fields'       => $fields
			,'fieldLabels'  => $fieldLabels
			,'labelFields'  => $labelFields
			,'tableConfig'  => $this->tableConfig
			,'rows'         => $rows
		];
	}
}