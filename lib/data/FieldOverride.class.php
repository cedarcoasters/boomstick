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

class FieldOverride extends Struct
{
	private $label;
	private $sourceType;
	private $type;
	private $attributes;
	private $options;
	private $default;
	private $immutable;
	private $hidden;
	private $ignore;
	private $required;
	private $tooltip;

	private $sqlOnInsert;
	private $sqlOnUpdate;

	private $preSaveFunc;
	private $onNewFunc;
	private $validateFunc;

	private $value;

	private $overridden = [];

	public static function new()
	{
		return new FieldOverride();
	}

	public function __get($property)
	{
		$reflector = new \ReflectionClass($this);

		if ($reflector->hasProperty($property) && $reflector->getProperty($property)->name === $property) {
			return $this->{$property};
		}

		parent::__get($property);
	}


	public function unset($property)
	{
		$this->{$property} = null;
		$this->overridden = array_diff($this->overridden, [$property]);
	}

	public function type($type)
	{
		$this->type = $type;
		if(!in_array('type', $this->overridden)) {
			$this->overridden[] = 'type';
		}
		return $this;
	}

	public function preSaveFunc($preSaveFunc)
	{
		$this->preSaveFunc = $preSaveFunc;
		if(!in_array('preSaveFunc', $this->overridden)) {
			$this->overridden[] = 'preSaveFunc';
		}
		return $this;
	}

	public function onNewFunc($onNewFunc)
	{
		$this->onNewFunc = $onNewFunc;
		if(!in_array('onNewFunc', $this->overridden)) {
			$this->overridden[] = 'onNewFunc';
		}
		return $this;
	}

	public function validateFunc($validateFunc)
	{
		$this->validateFunc = $validateFunc;
		if(!in_array('validateFunc', $this->overridden)) {
			$this->overridden[] = 'validateFunc';
		}
		return $this;
	}


	public function sqlOnInsert($sql)
	{
		$this->sqlOnInsert = $sql;
		if(!in_array('sqlOnInsert', $this->overridden)) {
			$this->overridden[] = 'sqlOnInsert';
		}
		return $this;
	}
	public function sqlOnUpdate($sql)
	{
		$this->sqlOnUpdate = $sql;
		if(!in_array('sqlOnUpdate', $this->overridden)) {
			$this->overridden[] = 'sqlOnUpdate';
		}
		return $this;
	}


	public function sqlSkipInsert($value)
	{
		$this->sqlSkipInsert = $value;
		if(!in_array('sqlSkipInsert', $this->overridden)) {
			$this->overridden[] = 'sqlSkipInsert';
		}
		return $this;
	}
	public function sqlSkipUpdate($value)
	{
		$this->sqlSkipUpdate = $sql;
		if(!in_array('sqlSkipUpdate', $this->overridden)) {
			$this->overridden[] = 'sqlSkipUpdate';
		}
		return $this;
	}




	public function label($label)
	{
		$this->label = $label;
		if(!in_array('label', $this->overridden)) {
			$this->overridden[] = 'label';
		}
		return $this;
	}

	public function sourceType($sourceType)
	{
		$this->sourceType = $sourceType;
		if(!in_array('sourceType', $this->overridden)) {
			$this->overridden[] = 'sourceType';
		}
		return $this;
	}

	public function attributes($attributes)
	{
		$this->attributes = $attributes;
		if(!in_array('attributes', $this->overridden)) {
			$this->overridden[] = 'attributes';
		}
		return $this;
	}

	public function options($options)
	{
		$this->options = $options;
		if(!in_array('options', $this->overridden)) {
			$this->overridden[] = 'options';
		}
		return $this;
	}

	public function default($default)
	{
		$this->default = $default;
		if(!in_array('default', $this->overridden)) {
			$this->overridden[] = 'default';
		}
		return $this;
	}

	public function immutable($immutable)
	{
		$this->immutable = $immutable;
		if(!in_array('immutable', $this->overridden)) {
			$this->overridden[] = 'immutable';
		}
		return $this;
	}

	public function hidden($hidden)
	{
		$this->hidden = $hidden;
		if(!in_array('hidden', $this->overridden)) {
			$this->overridden[] = 'hidden';
		}
		return $this;
	}

	public function ignore($ignore)
	{
		$this->ignore = $ignore;
		if(!in_array('ignore', $this->overridden)) {
			$this->overridden[] = 'ignore';
		}
		return $this;
	}

	public function required($required)
	{
		$this->required = $required;
		if(!in_array('required', $this->overridden)) {
			$this->overridden[] = 'required';
		}
		return $this;
	}

	public function tooltip($tooltip)
	{
		$this->tooltip = $tooltip;
		if(!in_array('tooltip', $this->overridden)) {
			$this->overridden[] = 'tooltip';
		}
		return $this;
	}

	public function value($value)
	{
		$this->value = $value;
		if(!in_array('value', $this->overridden)) {
			$this->overridden[] = 'value';
		}
		return $this;
	}

}