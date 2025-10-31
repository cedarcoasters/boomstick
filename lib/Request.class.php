<?php

namespace BoomStick\Lib;
use BoomStick\Lib\Debug AS D;

class Request
{
	protected $parameters = ["__requested_path" => null];

	public function __construct()
	{
		foreach($_REQUEST as $name => $value) {
			$this->parameters[$name] = $value;
		}
	}


	public function __get($name)
	{
		return (isset($this->parameters[$name])) ? $this->parameters[$name] : null;
	}

	public function __isset($name)
	{
		if (!isset(this->parameters[$name])) {
			return false;
		}
		return true;
	}

	public function getProtocol()
	{
		// var protocol;
		$protocol = "http";
		if(isset($_SERVER["REQUEST_SCHEME"]) && !empty($_SERVER["REQUEST_SCHEME"])) {
			$protocol = strtolower($_SERVER["REQUEST_SCHEME"]);
		}
		elseif(isset($_SERVER["SERVER_PROTOCOL"]) && !empty($_SERVER["SERVER_PROTOCOL"])) {
			$protocol = (preg_match("/https/i", _SERVER["SERVER_PROTOCOL"])) ? "https" : "http";
		}

		return $protocol;
	}


	public function getMethod()
	{
		return (isset($_SERVER["REQUEST_METHOD"])) ? strtoupper($_SERVER["REQUEST_METHOD"]) : null;
	}


	public function getRequestedPath()
	{
		return "/" . preg_replace("/^\//", "", $this->parameters["__requested_path"]);
	}


	public function getHostConfig()
	{
		// var httphost;
		$httphost = (isset($_SERVER["HTTP_HOST"])) ? strtolower($_SERVER["HTTP_HOST"]) : "default";
		return preg_replace("/[^a-z0-9]/", "", $httphost);
	}


	public function isPOST()
	{
		return (isset($_SERVER["REQUEST_METHOD"]) && strtoupper($_SERVER["REQUEST_METHOD"]) === "POST")
			? true
			: false;
	}


	public function isGET()
	{
		return (isset($_SERVER["REQUEST_METHOD"]) && strtoupper($_SERVER["REQUEST_METHOD"]) === "GET")
			? true
			: false;
	}


	public function isPUT()
	{
		return (isset($_SERVER["REQUEST_METHOD"]) && strtoupper($_SERVER["REQUEST_METHOD"]) === "PUT")
			? true
			: false;
	}


	public function isDELETE()
	{
		return (isset($_SERVER["REQUEST_METHOD"]) && strtoupper($_SERVER["REQUEST_METHOD"]) === "DELETE")
			? true
			: false;
	}


	public function isAJAX()
	{
		return (array_key_exists("HTTP_X_REQUESTED_WITH", $_SERVER) && $_SERVER["HTTP_X_REQUESTED_WITH"] == "XMLHttpRequest");
	}

	public function getPOST()
	{
		if($_SERVER["HTTP_CONTENT_TYPE"] === "application/json") {
			return $this->getJSON();
		}
		else {
			return $_POST;
		}
	}

	public function getJSON()
	{
		return json_decode(file_get_contents("php://input"));
	}

	public function refererRoute()
	{
		return (isset($_SERVER["HTTP_REFERER"]))
			? parse_url($_SERVER["HTTP_REFERER"])["path"]
			: null;
	}
}
