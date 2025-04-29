<?php

namespace BoomStick\Lib;
use BoomStick\Lib\ControllerAPI;
use BoomStick\Lib\Debug as D;


/**
 * ControllerValues - Static values that are used to hold the values set within a controller.
 *                    This avoids variable name collision.
 */
class ControllerValues extends Struct
{
	public $variables = array();
	public $layout    = 'default';
	public $key       = null;
	public $value     = null;
}

/**
 * Controller
 *
 * @author    Matt Roszyk <me@mattroszyk.com>
 * @package   Blaze.Core
 *
 */
abstract class Controller extends ControllerAPI
{
	private $cValues;
	private $modulePath;

	public function __construct()
	{
		if(gettype($this->cValues) != 'object') {
			$this->cValues = new ControllerValues();
		}
	}

	public function setModulePath($modulePath)
	{
		$this->modulePath = $modulePath;
	}

	/**
	 * This used to set the values that will be parsed into the view files.  When
	 * you set controller attribute other than the three reserved (viewPath,
	 * viewFiles, values), the value is stored inside of values and is then
	 * parsed into the view files when buildViews is called.
	 *
	 * $param $key The key/name of the variable to store
	 * $return The value, if it exists inside of the values array
	 */
	public function __get($key)
	{
		return (isset($this->cValues->variables[$key])) ? $this->cValues->variables[$key] : null;
	}



	/**
	 * Used to set the values to be parsed into the view files.  See self::__get()
	 * for more information
	 *
	 * @param $key The key to store the value under in values
	 * @value The actual value of the key
	 */
	public function __set($key, $value)
	{
		if(gettype($this->cValues) != 'object') {
			$this->cValues = new ControllerValues();
		}
		$this->cValues->variables[$key] = $value;
	}



	/**
	 * Sets the layout to be used
	 *
	 * @param $layout
	 * @return - void
	 */
	public function setLayout($layout)
	{
		if(!file_exists($this->modulePath.'/render/layout/'.$layout.'.layout.php')) {
			throw new \Exception( implode(' ', array(
				 __CLASS__.'::'.__FUNCTION__
				,' - The layout file ['.$layout.'] specified does not exist.'
			)));
		}
		if(gettype($this->cValues) != 'object') {
			$this->cValues = new ControllerValues();
		}

		$this->cValues->layout = $layout;
	}





	/**
	 * Renders the output using a layout if one has been set
	 */
	private function processLayout($returnContent)
	{

		if(!file_exists($this->cValues->layout)) {
			throw new \Exception( implode(' ', array(
				 __CLASS__.'::'.__FUNCTION__
				,' - The layout file ['.$this->cValues->layout.'] does not exist'
			)));
		}

		foreach($this->cValues->variables as $this->cValues->key => $this->cValues->value) {
			${$this->cValues->key} = $this->cValues->value;
		}

		if($returnContent === true) {
			ob_start();
		}

		include($this->cValues->layout);

		if($returnContent === true) {
			$content = ob_get_contents();
			ob_end_clean();
			return $content;
		}

		return true;
	}




	/**
	 * Renders the output and returns the value
	 */
	public function renderReturn()
	{
		// D::printre($this->cValues->layout);
		return $this->processFile('layout', $this->cValues->layout, true);
	}

	/**
	 * Renders the output to the screen
	 */
	public function render()
	{
		return $this->processFile('layout', $this->cValues->layout, false);
	}

	public function renderView($file)
	{
		return $this->processFile('view', $file, false);
	}

	public function renderViewCSS($file)
	{
		header('Content-Type: text/css');
		return $this->processFile('view', $file, false);
	}

	public function renderViewXML($file)
	{
		header('Content-Type: application/xml');
		return $this->processFile('view', $file, false);
	}

	public function renderViewTXT($file)
	{
		header('Content-Type: text/plain');
		return $this->processFile('view', $file, false);
	}

	// public function renderViewReturn($file) {
	// 	return $this->processFile('view', $file, true);
	// }

	// public function renderWidgetReturn($file) {
	// 	return $this->processFile('widget', $file, true);
	// }

	public function insertElement($file)
	{
		return $this->processFile('element', $file, true);
	}

	public function insertView($file)
	{
		return $this->processFile('view', $file, true);
	}

	public function insertStyle($file)
	{
		return $this->processFile('style', $file, true);
	}

	public function insertScript($file)
	{
		return $this->processFile('script', $file, true);
	}

	private function processFile($type, $file, $returnContent)
	{
		switch($type) {
			case 'view':
				$fileDir   = 'view';
				$extention = 'view';
				break;

			case 'layout':
				$fileDir   = 'layout';
				$extention = 'layout';
				break;

			case 'element':
				$fileDir   = 'element';
				$extention = 'element';
				break;

			case 'script':
				$fileDir   = 'script';
				$extention = 'script';
				break;

			case 'style':
				$fileDir   = 'style';
				$extention = 'style';
				break;

			// case 'widget':
			// 	$fileDir   = 'widget';
			// 	$extention = 'widget';
			// 	break;
		}
		$fileLoc = $this->modulePath.'/render/'.$fileDir.'/'.$file.'.'.$extention.'.php';

		if(!file_exists($fileLoc)) {
			throw new \Exception( implode(' ', array(
				 __CLASS__.'::'.__FUNCTION__
				,' - The file ['.$fileLoc.'] was not found.'
			)));
		}


		foreach($this->cValues->variables as $this->cValues->key => $this->cValues->value) {
			${$this->cValues->key} = $this->cValues->value;
		}

		if($returnContent === true) {
			ob_start();
		}

		include($fileLoc);

		if($returnContent === true) {
			$content = ob_get_contents();
			ob_end_clean();
			return $content;
		}

		return true;
	}
}
