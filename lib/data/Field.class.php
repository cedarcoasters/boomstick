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

class Field extends Struct
{
	private $name          = 'text-field';
	private $label         = 'No Label';
	private $sourceType    = null;
	private $type          = 'text';
	private $attributes    = null;
	private $options       = [];
	private $default       = null;
	private $immutable     = false;
	private $hidden        = false;
	private $ignore        = false;
	private $required      = false;
	private $tooltip       = null;

	private $sqlOnInsert   = null;
	private $sqlOnUpdate   = null;
	private $sqlSkipInsert = false;
	private $sqlSkipUpdate = false;

	private $preSaveFunc   = null;
	private $onNewFunc     = null;
	private $validateFunc  = null;


	private $value = null;

	public function __get($property)
	{
		$reflector = new \ReflectionClass($this);

		if ($reflector->hasProperty($property) && $reflector->getProperty($property)->name === $property) {
			return $this->{$property};
		}

		parent::__get($property);
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


	public function __construct($type='text')
	{
		$this->setType(sourceType:$type, flush:true);
	}


	public function setType($sourceType, $flush=false)
	{
		$this->sourceType = $sourceType;

		$patterns = [
			 '/^(text\-int|tinyint|smallint|mediumint|int|integer|bigint)\s?(.*)?$/'
                                                              => 'text-int'

			,"/^(text\-double|double|decimal|float)\s?(.*)$/" => 'text-double'

			,"/^(text\-money\-usd\-00)$/"                     => 'text-money-usd-00'
			,"/^(text\-money\-usd\-000)$/"                    => 'text-money-usd-000'
			,"/^(text\-money\-usd\-0000)$/"                   => 'text-money-usd-0000'

			,'/^(text\-timestamp|timestamp)$/'                => 'text-timestamp'
			,'/^(text\-datetime|datetime)$/'                  => 'text-datetime'
			,'/^(text\-date|date)$/'                          => 'text-date'

			,'/^(text|char|varchar)$/'                        => 'text'
			,'/^(text|char|varchar)\s?\((\d+)\)$/'            => 'text'

			,'/^(textarea|longtext|mediumtext|text)$/'        => 'textarea'

			,'/^(binary|varbinary)\s?\((\d+)\)$/'             => 'binary'
			,'/^(binary|varbinary)$/'                         => 'binary'

			,"/^(select|enum|set)\s?\((.*)\)$/"               => 'select'
			,"/^(select|enum|set)$/"                          => 'select'
		];

		$typeFound = false;
		foreach ($patterns as $pattern => $type) {
			if (preg_match($pattern, $sourceType, $matches)) {

				array_shift($matches);

				$options      = [];
				$attributes   = '';
				$preSaveFunc  = null;
				$onNewFunc    = null;
				$validateFunc = null;

				switch($matches[0])
				{
					case 'tinyint':
					case 'smallint':
					case 'mediumint':
					case 'int':
					case 'integer':
					case 'bigint':
						$attributes = self::buildNumericOptions(isInteger:true, integerType:$matches[0], input:$matches[1]);
						break;

					case 'double':
					case 'decimal':
					case 'float':
						$preSaveFunc = function($value) {
							return floatval(preg_replace('/[^0-9\.\-]/', '', $value));
						};
						$attributes = self::buildNumericOptions(isInteger:false, input:$matches[1]);
						break;

					case 'text-money-usd-00':
					case 'text-money-usd-000':
					case 'text-money-usd-0000':
						$preSaveFunc = function($value) {
							return floatval(preg_replace('/[^0-9\.\-]/', '', $value));
						};
						$onNewFunc = function($value) {
							return (double)$value;
						};
						break;

					case 'char':
					case 'varchar':
						$attributes = (isset($matches[1]) && (integer)$matches > 0)
							? 'maxlenth="'.(string)(integer)$matches[1].'"'
							: null;
						break;

					case 'binary':
					case 'varbinary':
						$attributes = (isset($matches[1]) && (integer)$matches > 0)
							? 'data-char-set="utf8" maxlenth="'.(string)(integer)$matches[1].'"'
							: 'data-char-set="utf8"';
						break;

					case 'enum':
					case 'set':
					case 'select':
						if(!isset($matches[1])) {
							break;
						}
						$matches[1] = preg_replace('/\',\s*\'/', '\',\'', $matches[1]);
						$optKeys = explode('\',\'', substr($matches[1], 1, -1));
						$options = [];
						foreach($optKeys as $key) {
							$options[$key] = ucfirst($key);
						}

						$onNewFunc = function($default) {
							return $default;
						};
						break;

					case 'timestamp':
					case 'datetime':
					case 'date':
						$attributes = 'data-picker-type="calendar"';
						break;

					default;
						// D::printr($matches);
						array_shift($matches);
						$options = (is_array($matches) && count($matches) > 0)
							? $matches
							: null;
						break;
				}

				$this->type = $type;
				if($flush === true) {
					$this->attributes   = $attributes;
					$this->options      = $options;
					$this->preSaveFunc  = $preSaveFunc;
					$this->onNewFunc    = $onNewFunc;
					$this->validateFunc = $validateFunc;
				}

				$typeFound        = true;
				break;
			}
		}

		if($typeFound === true) {
			return;
		}

		$validTypes = [
			 'text-int'
			,'text-double'
			,'text-timestamp'
			,'text-datetime'
			,'text-date'
			,'text-money-usd-00'
			,'text-money-usd-000'
			,'text-money-usd-0000'
			,'text'
			,'textarea'
			,'binary'
			,'select'
		];

		$message = 'The '.__CLASS__.'::type value provided ['.$sourceType.'] is invalid.  Valid values: [(MySQL Data Types), '.implode(', ', $validTypes).']';
		throw new \InvalidArgumentException($message);
	}


	public static function buildNumericOptions($input, $isInteger=true, $integerType='int')
	{
		$optionParts = [];

		if(preg_match('/(\(.*\))/', $input, $precMatch)) {
			list($whole, $decimal) = explode(',', substr($precMatch[1], 1, -1));
			$optionParts[] = 'data-double-precision-whole="'.(string)$whole.'" data-double-precision-decimal="'.(string)$decimal.'"';
		}

		$signed    = 'data-is-numeric-signed="true"';
		$signedKey = 'unsign';
		if(preg_match('/(unsigned)/', $input, $usMatch)) {
			$signed    = 'data-is-numeric-signed="false"';
			$signedKey = 'unsigned';
		}
		$optionParts[] = $signed;

		$zerofill = 'data-is-numeric-zerofill="false"';
		if(preg_match('/(zerofill)/', $input, $zerofillMatch)) {
			$zerofill = 'data-is-numeric-zerofill="true"';
		}
		$optionParts[] = $zerofill;

		if($isInteger === true) {
			$range['signed'] = [
				// Signed ranges (default)
				'tinyint'   => ['-128', '127'],
				'smallint'  => ['-32768', '32767'],
				'mediumint' => ['-8388608', '8388607'],
				'int'       => ['-2147483648', '2147483647'],
				'bigint'    => ['-9223372036854775808', '9223372036854775807'],
			];
			$range['unsigned'] = [
				// Unsigned ranges
				'tinyint'   => ['0', '255'],
				'smallint'  => ['0', '65535'],
				'mediumint' => ['0', '16777215'],
				'int'       => ['0', '4294967295'],
				'bigint'    => ['0', '18446744073709551615'],
			];

			$optionParts[] = ($signedKey === 'unsigned')
				? 'data-min-value="'.$range['unsigned'][$integerType][0].'" data-max-value="'.$range['unsigned'][$integerType][1].'"'
				: 'data-min-value="'.$range['signed'][$integerType][0].'" data-max-value="'.$range['signed'][$integerType][1].'"';
		}

		$attributes = implode(' ', $optionParts);

		return $attributes;
	}
}