<?php
/**
 *
 * BoomStick.com - A framework for high performance
 * Copyright 2012 - 2024, BoomStick.com
 *
 * Licensed under The MIT License
 * Any redistribution of this file's contents, both
 * as a whole, or in part, must retain the above information
 *
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 * @copyright     2012 - 2024, BoomStick.com
 * @link          http://blazephp.com/boomstick
 *
 */
namespace BoomStick\Lib\CLI;
use BoomStick\Lib\Struct;
use BoomStick\Lib\Globals as G;
use BoomStick\Lib\Debug as D;

use BoomStick\Lib\CLI\Flag;
use BoomStick\Lib\CLI\Option;

/**
 * CLI
 *
 * @author    Matt Roszyk <me@mattroszyk.com>
 * @package   BoomStick.Core
 *
 */
class CLI extends Struct
{
	private $scriptName           = null;
	private $flags                = array();
	private $options              = array();
	private $helpFlags            = array();
	private $helpOptions          = array();
	private $helpHeader           = null;
	private $helpFooter           = null;
	private $params               = array();
	private $paramsDefault        = array();
	private $paramsMapHash2Name   = array();
	private $paramsMapName2Hash   = array();
	private $paramsMapHash2Var    = array();
	private $paramsMapVar2Hash    = array();
	private $paramsRequired       = array();
	private $argv                 = array();
	private $argc                 = 0;
	private $verboseLog           = array();
	private $verboseLogLineNumber = 0;
	private $verboseLogMaxLines   = 100;




	public function __construct($argc, $argv)
	{
		if(!is_array($argv)) {
			throw new \Exception(
				__CLASS__.'::'.__FUNCTION__.' - The value passed to the constructor is expected to be the $argv array. ['.gettype($argv).' found]'
			);
		}

		$this->argc = $argc;
		$this->argv = $argv;

		// Set the file name
		$this->scriptName = basename($argv[0]);

		// Show the help text
		$helpFlag = new Flag();
		$helpFlag->long = 'help';
		$helpFlag->short = 'h';
		$helpFlag->description = 'Display this help text.';
		$this->addFlag($helpFlag);

		// Show the options class
		$optClFlag = new Flag();
		$optClFlag->long = 'options-class';
		$optClFlag->description = 'Display the copy/paste options class for the defined flags/options';
		$this->addFlag($optClFlag);

		// Verbose mode enabled/disabled
		$verboseFlag = new Flag();
		$verboseFlag->long = 'verbose';
		$verboseFlag->short = 'v';
		$verboseFlag->description = 'Enable verbose output, defaulting to verbose level 1'."\n".'See also --verbose-level';
		$this->addFlag($verboseFlag);

		// Set the verbose level (1-n)
		$verboseLevelOption = new Option();
		$verboseLevelOption->long = 'verbose-level';
		$verboseLevelOption->short = 'V';
		$verboseLevelOption->description = implode(' ', array(
			 'Set the verbose level at witch to run.  If this is set, --verbose is required for output.'
			,'The verbose log will always receive the entries, even if --verbose is not specified.'
			,"\n".'(0 = silent, >0 = more verbose)'
		));
		$verboseLevelOption->default = 1;
		$this->addOption($verboseLevelOption);
	}


	public function init()
	{
		try {
			$this->parse();

			if($this->help === true) {
				echo $this->getHelp();
				exit;
			}

			if($this->optionsClass === true) {
				echo $this->getOptionsClass();
				exit;
			}

		} catch (\Exception $e) {
			$message = 'ERROR: ';
			$this->error('ERROR: '.$e->getMessage());
			exit;
		}
	}



	public function getScriptName()
	{
		return $this->scriptName;
	}



	public function __get($name)
	{
		if(!isset($this->paramsMapVar2Hash[$name])) {
			throw new \Exception(
				__CLASS__.'::'.__FUNCTION__.' - The argument ['.$name.'] is not an argument set for this script.'
			);
		}

		if(isset($this->params[$this->paramsMapVar2Hash[$name]])) {
			return $this->params[$this->paramsMapVar2Hash[$name]];
		}
		else if(isset($this->paramsDefault[$this->paramsMapVar2Hash[$name]])) {
			return $this->paramsDefault[$this->paramsMapVar2Hash[$name]];
		}
	}



	public function __set($name, $value)
	{
		$this->params[$this->paramsMapVar2Hash[$name]] = $value;
	}



	public function getOptionsClass($className='O', $cliVarName='$cli')
	{
		$class  = [];
		$assign = [];

		$class[] = 'class '.$className.' extends Struct';
		$class[] = '{';
		foreach($this->params as $key => $value) {
			$class[] = "\t".'public static $'.$this->paramsMapHash2Var[$key].';';
			// O::$fileHeaderLocation   = G::$cli->fileHeaderLocation;
			$assign[] = $className.'::$'.$this->paramsMapHash2Var[$key].' = '.$cliVarName.'->'.$this->paramsMapHash2Var[$key].';';
		}
		$class[] = '}';

		return "\n\n".implode("\n", array_merge($class,$assign))."\n\n";
	}



	public function getHelp()
	{
		$helpText     = $this->helpHeader;
		$names        = array_merge(array_keys($this->helpOptions), array_keys($this->helpFlags));
		$maxCharCount = 0;
		foreach($names as $name) {
			$nameCharCount = strlen($name);
			$maxCharCount = ($maxCharCount < $nameCharCount) ? $nameCharCount : $maxCharCount;
		}
		$nameColumnWidth   = (/* indent */4 + $maxCharCount + /* right padding*/4);
		$detailColumnWidth = (/* total width */80 - $nameColumnWidth);

		// sort the help array
		ksort($this->helpOptions);
		ksort($this->helpFlags);

		// Build the flag documentation
		$helpText .= "\n\n".'Flags: '."\n";
		foreach($this->helpFlags as $name => $flag) {
			$description = self::wrapText($flag['description'], $detailColumnWidth);

			$helpText .= "\n".'    '.self::colorize(str_pad($name, ($nameColumnWidth - 4)), 'blue').(array_shift($description))."\n";
			foreach($description as $descLine) {
				$helpText .= str_pad(' ', $nameColumnWidth).$descLine."\n";
			}
		}

		// Build the options documentation
		$helpText .= "\n\n".'Options: '."\n";
		foreach($this->helpOptions as $name => $option) {

			$description = self::wrapText($option['description'], $detailColumnWidth);

			if($option['required'] === true) {
				$helpText .= "\n".'    '.self::colorize(str_pad($name, ($nameColumnWidth - 4)), 'blue').self::colorize('[REQUIRED]', 'red')."\n";
			}
			else {
				$helpText .= "\n".'    '.self::colorize(str_pad($name, ($nameColumnWidth - 4)), 'blue').(array_shift($description))."\n";
			}

			$nameColumnPadding = str_pad(' ', $nameColumnWidth);

			foreach($description as $descLine) {
				$helpText .= $nameColumnPadding.$descLine."\n";
			}

			if(!is_null($option['default'])) {
				$default = self::wrapText('[DEFAULT: '.$option['default'].']', $detailColumnWidth);

				$helpText .= $nameColumnPadding.self::colorize(array_shift($default), 'yellow')."\n";
				foreach($default as $defaultLine) {
					$helpText .= $nameColumnPadding.self::colorize($defaultLine, 'yellow')."\n";
				}
			}
		}

		if(!empty($this->helpFooter)) {
			$helpText .= "\n\n".$this->helpFooter;
		}

		$helpText .= "\n\n";

		return $helpText;
	}



	private static function wrapText($text, $maxCharWidth)
	{
		//printr(array($text, $maxCharWidth));
		$i = 0;
		$end = strlen($text);
		$wrapped = array();
		$lineNumber    = 0;
		$line = array();
		$word = '';
		for($i; $i<$end; $i++) {
			//if($text[$i] != ' ') {
			if(!preg_match('/[\s\n]/', $text[$i])) {
				$word .= $text[$i];
				//printr($word);
			}
			else {
				//printr($word);
				$word      = preg_replace('/\%s/', ' ', $word);
				$lineCheck = implode(' ', array_merge($line, array($word)));
				//printr($lineCheck);
				if(strlen($lineCheck) >= $maxCharWidth) {
					$wrapped[] = implode(' ', $line);
					$line = array($word);
					$word = '';
				}
				else {
					$line[] = $word;
					$word   = '';
				}

				if($text[$i] == "\n") {
					$wrapped[] = implode(' ', $line);
					$line      = array();

					if(!empty($word)) {
						$line[] = $word;
					}

					$word = '';
				}

				//printr($line);
			}
		}
		if(strlen($word) > 0) {
			$line[] = $word;
		}
		if(count($line) > 0) {
			$wrapped[] = implode(' ', $line);
		}

		//printre($wrapped);

		return $wrapped;
	}



	public function parse()
	{
		$optionSeekingValue = null;
		for($i=1; $i<$this->argc; $i++) {

			$arg = $this->argv[$i];

			//handle --option=value format vs --option value
			if(preg_match('/^\-.*\=.*$/', $arg)) {
				$parts              = explode('=', $arg);
				$optionSeekingValue = array_shift($parts);
				$arg                = implode('=', $parts);
			}

			if(!empty($optionSeekingValue)) {

				if(in_array($optionSeekingValue, $this->options)) {
					$this->params[$this->paramsMapName2Hash[$optionSeekingValue]] = $arg;
				}
				else {
					throw new \Exception(
						'The argument ['.$optionSeekingValue.'] is not recognized.  Please verify your options and try again.  (Try using --help for more information)'
					);
				}

				// Clear out the option information
				$optionSeekingValue = null;
				continue;
			}

			if(in_array($arg, $this->flags)) {
				$this->params[$this->paramsMapName2Hash[$arg]] = true;
			}
			else if(in_array($arg, $this->options)) {
				$optionSeekingValue = $arg; // The next argument is the value for this option
			}
			else {
				throw new \Exception(
					'The argument ['.$arg.'] is not recognized.  Please verify your options and try again.  (Try using --help for more information)'
				);
			}
		}

		// If --help is specified, show the help text
		if($this->help !== true && $this->optionsClass !== true) {
			foreach($this->paramsRequired as $paramsHash) {
				if(!isset($this->params[$paramsHash])) {
					throw new \Exception(
						'The argument ['.$this->paramsMapHash2Name[$paramsHash].'] is required.  Please verify your options and try again.  (Try using --help for more information)'
					);
				}
			}
		}

		return true;
	}



	public function addFlag(Flag $flag)
	{
		$names = '--'.$flag->long.' -'.$flag->short;
		$paramsHash = md5($names);
		$this->paramsMapHash2Name[$paramsHash] = $names;
		$helpKeyParts = array();

		if(!empty($flag->long)) {
			$long = '--'.$flag->long;
			if(in_array($long, $this->params)) {
				throw new \Exception(
					__CLASS__.'::'.__FUNCTION__.'Script Configuration Error:  ['.$long.'] has already been specified.  You cannot rediclare the same parameter flag twice.'
				);
			}
			$this->paramsMapName2Hash[$long] = $paramsHash;
			$this->flags[]          = $long;
			$helpKeyParts[]         = $long;
		}

		if(!empty($flag->short)) {
			$short = '-'.$flag->short;
			if(in_array($short, $this->params)) {
				throw new \Exception(
					__CLASS__.'::'.__FUNCTION__.' - Script Configuration Error:  ['.$short.'] has already been specified.  You cannot rediclare the same parameter flag twice.'
				);
			}
			$this->paramsMapName2Hash[$short] = $paramsHash;
			$this->flags[]         = $short;
			$helpKeyParts[]          = $short;
		}

		$this->paramsDefault[$paramsHash] = false;

		$varName = $this->makeVarName($flag->long);
		$this->paramsMapHash2Var[$paramsHash] = $varName;
		$this->paramsMapVar2Hash[$varName]    = $paramsHash;

		$this->helpFlags[implode(' ', $helpKeyParts)] = array(
			 'description' => $flag->description
		);
	}



	public function addOption(Option $opt)
	{
		$names = '--'.$opt->long.' -'.$opt->short;
		$paramsHash = md5($names);
		$this->paramsMapHash2Name[$paramsHash] = $names;
		$helpKeyParts = array();

		if(!empty($opt->long)) {
			$long = '--'.$opt->long;
			if(in_array($long, $this->params)) {
				throw new \Exception(
					__CLASS__.'::'.__FUNCTION__.'Script Configuration Error:  ['.$long.'] has already been specified.  You cannot rediclare the same parameter option twice.'
				);
			}
			$this->paramsMapName2Hash[$long] = $paramsHash;
			$this->options[]        = $long;
			$helpKeyParts[]         = $long;
		}

		if(!empty($opt->short)) {
			$short = '-'.$opt->short;
			if(in_array($short, $this->params)) {
				throw new \Exception(
					__CLASS__.'::'.__FUNCTION__.' - Script Configuration Error:  ['.$short.'] has already been specified.  You cannot rediclare the same parameter option twice.'
				);
			}
			$this->paramsMapName2Hash[$short] = $paramsHash;
			$this->options[]         = $short;
			$helpKeyParts[]          = $short;
		}

		if($opt->required === true) {
			$this->paramsRequired[] = $paramsHash;
		}

		$this->paramsDefault[$paramsHash] = $opt->default;

		$varName = $this->makeVarName($opt->long);
		$this->paramsMapHash2Var[$paramsHash] = $varName;
		$this->paramsMapVar2Hash[$varName]    = $paramsHash;

		$this->helpOptions[implode(' ', $helpKeyParts)] = array(
			 'description' => $opt->description
			,'required'    => $opt->required
			,'default'     => $opt->default
		);
	}


	private function limitVerboseLogSize($skipVerboseIncrement=false)
	{
		if($skipVerboseIncrement === false) {
			$this->verboseLogLineNumber++;
		}

		$lineCount = 0;
		foreach($this->verboseLog as $time => $lines) {
			$lineCount += count($lines);
		}

		if($lineCount > $this->verboseLogMaxLines && $this->verboseLogLineNumber > $this->verboseLogMaxLines) {
			$times = array_keys($this->verboseLog);

			$firstTimeLineCount = count($this->verboseLog[$times[0]]);

			if($firstTimeLineCount > 0) {

				array_shift($this->verboseLog[$times[0]]);

			}
			elseif($firstTimeLineCount == 0) {
				unset($this->verboseLog[$times[0]]);
				self::limitVerboseLogSize(true); // If the first time block is empty, remove the next time block's first line
			}

		}

	}

	public function error($message, $addNewLine=true)
	{
		$message = self::colorize($message, 'red');
		$this->_verbose(STDERR, $message, 0, $addNewLine);
		$this->verboseLog[date('Y-m-d H:i:s')][$this->verboseLogLineNumber][] = $message."\n";
		self::limitVerboseLogSize();
	}

	public function verbose($message, $runAtLevel=1, $addNewLine=true)
	{
		$this->_verbose(STDOUT, $message, $runAtLevel, $addNewLine);
	}



	private function _verbose($handle, $message, $runAtLevel=1, $addNewLine=true)
	{

		if((integer)$this->verboseLevel >= (integer)$runAtLevel) {

			$this->verboseLog[date('Y-m-d H:i:s')][$this->verboseLogLineNumber][] = $message.(($addNewLine) ? "\n" : '');

			if($runAtLevel === 0 || $this->verbose === true) {
				fwrite($handle, $message);
				if($addNewLine === true) {
					fwrite($handle, "\n");
				}
			}

			if($addNewLine === true) {
				self::limitVerboseLogSize();
			}
		}
	}

	public function NL($count=1)
	{
		fwrite(STDOUT, str_repeat("\n", $count));
	}
	public function TAB($count=1)
	{
		fwrite(STDOUT, str_repeat("\t", $count));
	}

	public function getVerboseLog($delimiter="\n")
	{
		$lines = array();
		$lineIndex = 0;
		foreach($this->verboseLog as $datetime => $messages) {
			foreach($messages as $key => $messageParts) {

				$message = implode('', $messageParts);

				// remove any color markup
				$messageClean = preg_replace('/\x1b\[[0-9\;]*m/', '', $message);

				// check to see if the line is empty and add the date if it is.
				if(!isset($lines[$lineIndex])) {
					$lines[$lineIndex] = '['.$datetime.'] '. preg_replace('/\n$/', '', $messageClean);
				}
				else {
					$lines[$lineIndex] .= preg_replace('/\n$/', '', $messageClean);
				}

				if(preg_match('/\n$/', $messageClean)) {
					$lineIndex++;
				}
			}
		}
		return implode($delimiter, $lines);
	}


	public function prompt($text=null)
	{
		if(!empty($text)) {
			self::verbose($text, 0, false);
		}
		return trim(fgets(STDIN));
	}


	public static function colorize($text, $foreground='default', $background=null, $effect=null)
	{
		$colorize = array();

		if(isset(self::$colors['foreground'][$foreground])) {
			$colorize[] = self::$colors['foreground'][$foreground];
		}
		if(isset(self::$colors['background'][$background])) {
			$colorize[] = self::$colors['background'][$background];
		}
		if(isset(self::$colors['effect'][$effect])) {
			$colorize[] = self::$colors['effect'][$effect];
		}

		if(count($colorize) <= 0) {
			$colorize[] = 0;
		}

		return "\033[".implode(';', $colorize).'m'.$text.self::resetColor();
	}

	public static function resetColor()
	{
		return "\033[0m";
	}

	private static $colors = array(
		 'foreground' => array(
		 	 'default' =>  0
			,'black'   => 30
			,'red'     => 31
			,'green'   => 32
			,'yellow'  => 33
			,'blue'    => 34
			,'magenta' => 35
			,'cyan'    => 36
			,'white'   => 37
		)
		,'background' => array(
			 'black'   => 40
			,'red'     => 41
			,'green'   => 42
			,'yellow'  => 43
			,'blue'    => 44
			,'magenta' => 45
			,'cyan'    => 46
			,'white'   => 47
		)
		,'effect' => array(
			 'bright'    => 1
			,'dim'       => 2
			,'underline' => 4
			,'blink'     => 5
			,'reverse'   => 7
			,'hidden'    => 8
		)
	);







	private function makeVarName($argument)
	{
		if(!preg_match('/[a-z][a-z\-]*[a-z]$/', $argument)) {
			throw new \Exception(
				'The argument ['.$argument.'] is not valid.  allowed characters are lower case `a` through `z` and a word delimiter `-`.  The name must always start and end with a letter.'
			);
		}

		$parts = explode('-', $argument);
		$varName = array_shift($parts);
		foreach($parts as $part) {
			$varName .= ucfirst($part);
		}

		return $varName;
	}
}
