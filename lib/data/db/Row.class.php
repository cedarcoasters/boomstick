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

class Row
{
	private $conn;
	private $tableName;
	private $tableObj;
	private $tableConfig;

	private $fieldChecksums;
	private $fields;


	public function __get($property)
	{
		if(isset($this->fields[$property])) {
			return $this->fields[$property];
		}

		$message = 'The field ['.$property.'] does not exist in the table ['.$this->tableName.']';
		throw new \OutOfBoundsException($message);
	}

	public function __set($property, $value)
	{
		if(isset($this->fields[$property])) {
			$this->fields[$property]->value = $value;
			return;
		}

		$message = 'The field ['.$property.'] does not exist in the table ['.$this->tableName.']';
		throw new \OutOfBoundsException($message);
	}

	public function __construct(Connect $conn, $tableConfig)
	{
		$this->conn        = $conn;
		$this->tableConfig = $tableConfig;
	}

	public function fields()
	{
		return $this->fields;
	}

	public function fromTable($tableName, array $fields=[])
	{
		$this->tableObj = new Table($this->conn);
		$this->fields   = $this->tableObj->desc($tableName, $fields);
		$this->tableName    = $tableName;
		return $this;
	}

	public function isNew()
	{
		$this->applyFieldOverrides();

		foreach($this->fields as $fieldName => $fieldObj) {
			if(is_callable($fieldObj->onNewFunc)) {
				$func = $fieldObj->onNewFunc;
				$this->fields[$fieldName]->value = $func($this->fields[$fieldName]->default);
			}
		}

		return $this;
	}

	public function byId($id)
	{
		if(empty($this->tableName) || empty($this->fields)) {
			$message = 'You are executing '.__CLASS__.'::'.__FUNCTION__.' before establishing the table to pull the row from.  [$row->fromTable(\'your_table\')->byId(123);]';
			throw new \LogicException($message);
		}

		$this->byField('id', $id);

		return $this;
	}


	public function byField($field, $value)
	{
		if(empty($this->tableName) || empty($this->fields)) {
			$message = 'You are executing '.__CLASS__.'::'.__FUNCTION__.' before establishing the table to pull the row from.  [$row->fromTable(\'your_table\')->byField(\'my_table\', 123);]';
			throw new LogicException($message);
		}

		$sql = [];
		$sql[] = 'SELECT';
		$sql[] = ' * ';
		$sql[] = 'FROM';
		$sql[] = '      `'.$this->tableName.'`';
		$sql[] = 'WHERE';
		$sql[] = '     `'.$field.'` = :value';
		$sql[] = 'LIMIT 1';
		$stmt = $this->conn->pdo->prepare(implode(' ', $sql));
		$stmt->bindValue(':value', $value, PDO::PARAM_INT);
		$stmt->execute();
		$row = $stmt->fetch();

		if ($row) {
			foreach($row as $field => $value) {

				$value = (!empty($value)) ? $value : '';
				$this->fieldChecksums[$field] =  hash('sha256', $value);
			}
			$this->populate($row);
		} else {
			$message = 'The `'.$field.'` ['.$value.'] does not exist in the table ['.$this->tableName.']';
			throw new \OutOfBoundsException($message);
		}

		$this->applyFieldOverrides();

		return $this;
	}

	public function existsByField($field, $value)
	{
		try {
			$this->byField($field, $value);
			return true;
		}
		catch(\OutOfBoundsException $e) {
			return false;
		}
	}

	public function populate($rowValues)
	{
		foreach($rowValues as $field => $value)
		{
			$this->fields[$field]->value = $value;
		}

		return $this;
	}


	public function save()
	{
		if($this->fields['id']->value === 'NEW') {
			return $this->insert();
		}
		else {
			return $this->update();
		}
	}


	private function insert()
	{
		$status        = 'success';
		$fieldsUpdated = [];
		$fieldsError   = [];
		foreach($this->fields as $fieldName => $fieldObj) {

			try {
				if(is_callable($fieldObj->onNewFunc)) {
					$func = $fieldObj->onNewFunc;
					$value = $func($fieldObj->value);
				}
				else {
					$value = $fieldObj->value;
				}

				if(is_callable($fieldObj->preSaveFunc)) {
					$func = $fieldObj->preSaveFunc;
					$value = $func($value);
					if(is_callable($fieldObj->validateFunc)) {
						$func = $fieldObj->validateFunc;
						$func($value);
					}
				}

				if($fieldObj->required === true && (string)$value == '') {
					throw new \ValueError($fieldObj->label.' ['.$fieldObj->name.'] is required.');
				}
			}
			catch(\ValueError $e) {
				$fieldsError[$fieldName] = ['message' => $e->getMessage(), 'system_message' => $e->getMessage(), 'dataSent' => $value];
				$status = 'error';
			}
			catch(\ArgumentCountError $e) {
				$fieldsError[$fieldName] = ['message' => $e->getMessage()];
				$status = 'error';
			}
			$this->fields[$fieldName]->value = $value;
		}
		if($status == 'error') {
			return [
				 'status'            => $status
				,'fields_updated'    => []
				,'fields_with_error' => $fieldsError
			];
		}

		try {

			$fieldsSQL  = [];
			$bindFields = [];

			$sql   = [];
			$sql[] = 'INSERT INTO `'.$this->tableName.'` SET';

			foreach($this->fields as $field => $fieldObj) {
				if(in_array($field, ['id', 'time_modified']) || $fieldObj->sqlSkipInsert === true) {
					continue;
				}
				elseif($field == 'time_created') {
					$fieldsSQL[] = '`time_created` = NOW()';
					continue;
				}

				if(!empty($fieldObj->sqlOnInsert)) {
					$fieldsSQL[] = '`'.$field.'` = '.$fieldObj->sqlOnInsert;
				}
				elseif((string)$fieldObj->value == '') {
					continue;
				}
				else {
					$fieldsSQL[] = '`'.$field.'` = :'.$field;
				}

				$bindFields[] = $field;
			}
			$sql[] = implode(', ', $fieldsSQL);
			// D::printre(implode("\n", $sql));
			$this->conn->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			$this->conn->pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);

			$stmt = $this->conn->pdo->prepare(implode(' ', $sql));

			// D::printre($bindFields);
			foreach($bindFields as $fieldName) {
				$value = $this->fields[$fieldName]->value;
				$stmt->bindValue(':'.$fieldName, $value);
				$fieldsUpdated[$fieldName] = $value;
			}

			// $stmt->debugDumpParams();
			// D::printre($bindValues);

			$stmt->execute();

			$newId = $this->conn->pdo->lastInsertId();
		}
		catch(\PDOException $e) {
			return [
				 'status' => 'error'
				,'message' => $e->getMessage()
				,'fields_updated'    => []
				,'fields_with_error' => []
			];
		}

		return [
			 'status'            => 'success'
			,'message'           => null
			,'id_created'        => $newId
			,'fields_updated'    => $fieldsUpdated
			,'fields_with_error' => []
		];

	}

	private function update()
	{
		$status        = 'success';
		$changedFields = [];
		foreach($this->fields as $fieldName => $fieldObj) {
			if(in_array($fieldName, ['id', 'time_created', 'time_modified']) || $fieldObj->sqlSkipUpdate === true) {
				continue;
			}

			if(is_callable($fieldObj->preSaveFunc)) {
				$func = $fieldObj->preSaveFunc;
				$value = $func($fieldObj->value);
				$this->fields[$fieldName]->value = $value;
			}
			else {
				$value = $fieldObj->value;
			}

			$value = (!empty($value)) ? $value : '';

			if($this->fieldChecksums[$fieldName] !== hash('sha256', $value)) {
				$changedFields[] = $fieldName;
			}
		}
		if(count($changedFields) <= 0) {
			return [
				 'status'            => $status
				,'fields_updated'    => []
				,'fields_with_error' => []
			];
		}

		// D::printre($this->fields);

		$fieldsUpdated = [];
		$fieldsError   = [];
		foreach($changedFields as $fieldName) {
			try {
				$sql = [];
				$sql[] = 'UPDATE `'.$this->tableName.'` SET';
				$sql[] = ' `'.$fieldName.'` = :'.$fieldName;
				$sql[] = 'WHERE';
				$sql[] = '     `id` = :id';
				$stmt = $this->conn->pdo->prepare(implode(' ', $sql));
				$id = $this->fields['id']->value;
				$stmt->bindParam(':id', $id);

				$fieldValue = $this->fields[$fieldName]->value;

				// The validation function, if defined, should throw a ValueError Exception
				if(is_callable($this->fields[$fieldName]->validateFunc)) {
					$func = $this->fields[$fieldName]->validateFunc;
					$func($fieldValue);
				}

				if($this->fields[$fieldName]->required && (string)$fieldValue == '') {
					throw new \Exception('The field '.$this->fields[$fieldName]->label.' ['.$fieldName.'] is required. ['.(string)$fieldValue.']');
				}
				$stmt->bindParam(':'.$fieldName, $fieldValue);
				$stmt->execute();
				$fieldsUpdated[$fieldName] = $fieldValue;
			}
			catch(\PDOException $e) {
				$fieldsError[$fieldName] = ['message' => 'The data submitted is invalid.', 'system_message' => $e->getMessage(), 'dataSent' => $fieldValue];
				$status = 'error';
			}
			catch(\ValueError $e) {
				$fieldsError[$fieldName] = ['message' => $e->getMessage(), 'system_message' => $e->getMessage(), 'dataSent' => $fieldValue];
				$status = 'error';
			}
			catch(\Exception $e) {
				$fieldsError[$fieldName] = ['message' => $e->getMessage(), 'system_message' => $e->getMessage()];
				$status = 'error';
			}
		}

		return [
			 'status'            => $status
			,'fields_updated'    => $fieldsUpdated
			,'fields_with_error' => $fieldsError
		];
	}


	public function valueFor($field)
	{
		if(!isset($this->fields[$field])) {
			$message = 'The field ['.(string)$field.'] does not exist in the table ['.$this->tableName.']';
			throw new \OutOfBoundsException($message);
		}
		return $this->fields[$field]->value;
	}


	private function setAttribute($attribute, $fields, $value)
	{
		foreach($fields as $field) {
			$this->fields[$field]->{$attribute} = $value;
		}
	}

	public function setImmutable(array $fields=[], $value=true)
	{
		$fields = (empty($fields)) ? array_keys($this->fields) : $fields;
		$this->setAttribute(attribute:'immutable', fields:$fields, value:$value);
		return $this;
	}

	public function setHidden(array $fields=[], $value=true)
	{
		$fields = (empty($fields)) ? array_keys($this->fields) : $fields;
		$this->setAttribute(attribute:'hidden', fields:$fields, value:$value);
		return $this;
	}

	public function setIgnore(array $fields=[], $value=true)
	{
		$fields = (empty($fields)) ? array_keys($this->fields) : $fields;
		$this->setAttribute(attribute:'ignore', fields:$fields, value:$value);
		return $this;
	}

	public function applyFieldOverrides()
	{
		if(empty($this->tableConfig)) {
			return;
		}

		foreach($this->tableConfig as $name => $override)
		{
			if(is_array($override->overridden) && count($override->overridden) > 0) {
				foreach($override->overridden as $property) {
					// D::printr([$property, $override->{$property}]);
					if($property === 'preSaveFunc') {
						$func = $override->{$property};
						// D::printr($func('$100.90'));
					}
					$this->fields[$name]->{$property} = $override->{$property};
				}
			}
		}

		return $this;
	}


}