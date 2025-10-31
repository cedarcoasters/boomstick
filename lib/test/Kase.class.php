<?php
/**    ____                       _____ __  _      __
 *    / __ )____  ____  ____ ___ / ___// /_(_)____/ /__
 *   / __  / __ \/ __ \/ __ `__ \\__ \/ __/ / ___/ //_/
 *  / /_/ / /_/ / /_/ / / / / / /__/ / /_/ / /__/ ,<
 * /_____/\____/\____/_/ /_/ /_/____/\__/_/\___/_/|_|
 *
 * BoomStick.com - A framework for high explosive performance
 * Copyright 2012 - <?=date('Y');?>, BlazePHP.com
 *
 * Licensed under The MIT License
 * Any redistribution of this file's contents, both
 * as a whole, or in part, must retain the above information
 *
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 * @copyright     Copyright 2012 - <?=date('Y');?>, BlazePHP.com
 * @link          http://blazePHP.com
 */

namespace BoomStick\Lib\Test;

class Kase
{
	public $expectedException;


	public function executeTest($method)
	{
		try {
			$this->$method();
		}
		catch(\Exception $e) {
			if(get_class($e) === $this->expectedException) {
				$this->expectedException = null;
				return;
			}
			else {
				throw $e;
			}
		}
	}

	public function assertInstanceOf($class, $object)
	{
		if($class !== get_class($object)) {
			throw new \Exception( implode(' ', array(
				 __CLASS__.'::'.__FUNCTION__
				,' - The instance expected ['.$class.'] does not match the object ['.get_class($object).']'
			)));
		}
	}

	public function assertInteger($value)
	{
		if((string)(integer)$value !== (string)$value) {
			throw new \Exception( implode(' ', array(
				 __CLASS__.'::'.__FUNCTION__.' => '
				,'FALSE'
			)));
		}
	}

	public function assertBoolean($value)
	{
		if(!is_bool($value)) {
			throw new \Exception( implode(' ', array(
				 __CLASS__.'::'.__FUNCTION__.' => '
				,'FALSE'
			)));
		}
	}

	public function assertTrue($value)
	{
		if($value !== true) {
			throw new \Exception( implode(' ', array(
				 __CLASS__.'::'.__FUNCTION__.' => '
				,'FALSE'
			)));
		}
	}


	public function assertFalse($value)
	{
		if($value !== false) {
			throw new \Exception( implode(' ', array(
				 __CLASS__.'::'.__FUNCTION__.' => '
				,'FALSE'
			)));
		}
	}

	public function assertResource($value)
	{
		if(is_resource($value) !== true) {
			throw new \Exception( implode(' ', array(
				 __CLASS__.'::'.__FUNCTION__.' => '
				,'FALSE'
			)));
		}
	}

	public function assertNotResource($value)
	{
		if(is_resource($value) === true) {
			throw new \Exception( implode(' ', array(
				 __CLASS__.'::'.__FUNCTION__.' => '
				,'FALSE'
			)));
		}
	}

	public function assertArray($value)
	{
		if(!is_array($value)) {
			throw new \Exception( implode(' ', array(
				 __CLASS__.'::'.__FUNCTION__.' => '
				,'FALSE'
			)));
		}
	}

	public function assertArrayNotEmpty($value)
	{
		if(!is_array($value) || (is_array($value) && count($value) <= 0)) {
			throw new \Exception( implode(' ', array(
				 __CLASS__.'::'.__FUNCTION__.' => '
				,'FALSE'
			)));
		}
	}


	public function assertNull($value)
	{
		if($value !== null) {
			throw new \Exception( implode(' ', array(
				 __CLASS__.'::'.__FUNCTION__.' => '
				,'FALSE'
			)));
		}
	}

	public function assertEmpty($value)
	{
		if(!empty($value)) {
			throw new \Exception( implode(' ', array(
				 __CLASS__.'::'.__FUNCTION__.' => '
				,'FALSE'
			)));
		}
	}

	public function assertNotEmpty($value)
	{
		if(empty($value)) {
			throw new \Exception( implode(' ', array(
				 __CLASS__.'::'.__FUNCTION__.' => '
				,'FALSE'
			)));
		}
	}

	public function assertFileExists($file)
	{
		if(!file_exists($file)) {
			throw new \Exception( implode(' ', array(
				 __CLASS__.'::'.__FUNCTION__.' => '
				,'FALSE ['.$file.'] DOES NOT EXISTs'
			)));
		}
	}

	public function assertFileDoesNotExist($file)
	{
		if(file_exists($file)) {
			throw new \Exception( implode(' ', array(
				 __CLASS__.'::'.__FUNCTION__.' => '
				,'FALSE ['.$file.'] DOES EXIST'
			)));
		}
	}


	public function assertFileWriteable($file)
	{
		if(!is_writable($file)) {
			throw new \Exception( implode(' ', array(
				 __CLASS__.'::'.__FUNCTION__.' => '
				,'FALSE ['.$file.']'
			)));
		}
	}

	public function assertFileReadable($file)
	{
		if(!is_readable($file)) {
			throw new \Exception( implode(' ', array(
				 __CLASS__.'::'.__FUNCTION__.' => '
				,'FALSE ['.$file.']'
			)));
		}
	}

	public function assertStringFoundInFile($string, $file)
	{
		// If the log file size is greater than 1MB, throw an error
		if(filesize($file) > (1024 * 1024)) {
			throw new \Exception( implode(' ', array(
				 __CLASS__.'::'.__FUNCTION__
				,'FILE SIZE TOO LARGE ['.$file.'] exceeds 1MB. Delete this file before continuing the test'
			)));
		}
		$contents   = file_get_contents($file);
		$pattern    = preg_quote($string, '/');
		if(!preg_match('/'.$pattern.'/', $contents)) {
			throw new \Exception( implode(' ', array(
				 __CLASS__.'::'.__FUNCTION__
				,'FALSE ['.$string.'] NOT FOUND IN ['.$file.']'
			)));
		}
	}

	public function assertStringNotFoundInFile($string, $file)
	{
		// If the log file size is greater than 1MB, throw an error
		if(filesize($file) > (1024 * 1024)) {
			throw new \Exception( implode(' ', array(
				 __CLASS__.'::'.__FUNCTION__
				,'FILE SIZE TOO LARGE ['.$file.'] exceeds 1MB. Delete this file before continuing the test'
			)));
		}
		$contents   = file_get_contents($file);
		$pattern    = preg_quote($string, '/');
		if(preg_match('/'.$pattern.'/', $contents)) {
			throw new \Exception( implode(' ', array(
				 __CLASS__.'::'.__FUNCTION__
				,'FALSE ['.$string.'] FOUND IN ['.$file.']'
			)));
		}
	}

	public function assertTimeLapsAvsB($timeA, $timeB)
	{
		$a = new \DateTime($timeA);
		$b = new \DateTime($timeB);
		$intA = gmp_init($a->format('YmdHis'));
		$intB = gmp_init($b->format('YmdHis'));

		if(gmp_cmp($intB, $intA) <= 0) {
			throw new \Exception( implode(' ', array(
				 __CLASS__.'::'.__FUNCTION__
				,'FALSE ['.$a->format('Y-m-d H:i:s').'] IS NOT LESS THAN ['.$b->format('Y-m-d H:i:s').']'
			)));
		}
	}

	public function assertNoTimeLapsAvsB($timeA, $timeB)
	{
		$a = new \DateTime($timeA);
		$b = new \DateTime($timeB);
		$intA = gmp_init($a->format('YmdHis'));
		$intB = gmp_init($b->format('YmdHis'));

		if(gmp_cmp($intB, $intA) !== 0) {
			throw new \Exception( implode(' ', array(
				 __CLASS__.'::'.__FUNCTION__
				,'FALSE ['.$a->format('Y-m-d H:i:s').'] IS NOT LESS THAN ['.$b->format('Y-m-d H:i:s').']'
			)));
		}
	}


	public function assertValueEquals($value1, $value2)
	{
		if($value1 !== $value2) {
			throw new \Exception( implode(' ', array(
				 __CLASS__.'::'.__FUNCTION__
				,'FALSE ['.$value1.'] DOES NOT EQUAL ['.$value2.']'
			)));
		}
	}




	public function expectException($expected)
	{
		$this->expectedException = $expected;
	}
}
