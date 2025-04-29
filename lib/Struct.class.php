<?php

namespace BoomStick\Lib;


class Struct
{
	public function __get($invalidAttribute)
	{
		throw new \ErrorException('Trying to access an invalid attribute Struct::'.$invalidAttribute);
	}

	public function __set($invalidAttribute, $sValue)
	{
		throw new \ErrorException('Trying to write to an invalid attribute Struct::'.$invalidAttribute);
	}
}
