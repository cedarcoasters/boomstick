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
 * @copyright     Copyright 2012 - 2025, BlazePHP.com
 * @link          http://blazePHP.com
 */
namespace BoomStick\Lib\Generate\Html;

use BoomStick\Lib\Globals as G;
use BoomStick\Lib\Debug as D;
/**
 * FormHelper
 *
 * This class is the helper that is minimally designed to use for form security and automating the
 * Blaze object intraction.  This is not like other form helpers that try to do far beyond what
 * any helper class should do.
 *
 * @author Matt Roszyk <me@mattroszyk.com>
 */
class Form
{
	public $name;
	public $method = 'post';
	public $action = null;
	public $enctype;

	private $security       = false;
	private $formValues;
	private $formHashValues = array();
	private $session;

	private $errors;

	const INSECURE         = 0;
	const SINGLE_USE_ONLY  = 1;
	const VERIFY_FORM_ONLY = 2;
	const SECURE           = 3;




	/**
	 * Constructor - Creates a form helper object and validates a form that has been submitted
	 *               based on the security level set.
	 *
	 * @param $security - Object constant (INSECURE, SINGLE_USE_ONLY, VERIFY_FORM_ONLY, SECURE)
	 * @param $name - The name of the form
	 * @param $action - The action of the form (a valid route)
	 * @param $method - The method of the form (post|get)
	 * @param $enctype - The enctype of the form (application|text|multipart)
	 *
	 * @return - void
	 */
	public function __construct($security=Form::INSECURE, $name='blazeform', $action=null, $method='post', $enctype='multipart')
	{

		if(empty($name)) {
			throw new \Exception(
				__CLASS__.'::'.__FUNCTION__.' - A form must have a name'
			);
		}
		$this->name       = $name;
		$this->method     = $method;
		$this->action     = $action;
		$this->enctype    = $enctype;
		$this->security   = (integer)$security;
		$this->formValues = new \stdClass();

		// \BlazePHP\Debug::printre(array(G::$request->__form_name__, $this->name));
		if(G::$request->__form_name__ === $this->name) {

			if(G::$request->getMethod() !== strtoupper($this->method)) {
				throw new \Exception(
					__CLASS__.'::'.__FUNCTION__.' - The request method ['.$_SERVER['REQUEST_METHOD']
					.'] received does not match the type for form ['.$this->name.']'
				);
			}

			if(   in_array($this->security, array(self::SINGLE_USE_ONLY, self::SECURE))
			   && true !== self::verifyFormKey(G::$request->__blaze_form_key))
			{
				throw new \Exception(
					__CLASS__.'::'.__FUNCTION__.' - The form key submitted does not match the form'
					.' key on file for form ['.$this->name.']'
				);
			}

			if(G::$request->{$this->name}) {
				// D::printre(G::$request->{$this->name});
				$receivedHashValues = array($this->name);
				foreach(G::$request->{$this->name} as $name => $value) {
					$this->formValues->{$name} = $value;
					$receivedHashValues[] = $name;
				}

				if(in_array($this->security, array(self::VERIFY_FORM_ONLY, self::SECURE))) {
					sort($receivedHashValues);
					$receivedHash = md5(implode($receivedHashValues));
					if(true !== self::verifyFormHash($receivedHash)) {
						throw new \Exception(
							__CLASS__.'::'.__FUNCTION__.' - The form has been modified on the client side'
						);
					}
				}
			}
		}
	}





	/**
	 * updateFormHash - Updates the form hash array with the name of the form element provided
	 *
	 * @param $name - The name of the form element to be added to the hash array
	 * @return - void
	 */
	private function updateFormHash($name)
	{
		$this->formHashValues[] = $name;
		sort($this->formHashValues, SORT_STRING);
	}





	/**
	 * verifyFormHash - Verifies that the form elements hashed received are the same as
	 *                  as the one sent to the client.
	 *
	 * @param $hash
	 * @return - boolean
	 */
	private function verifyFormHash($hash)
	{
		$formHashes = G::$session->formHashes;
		if(isset($formHashes[$this->name]) && $hash === $formHashes[$this->name]) {
			unset($formHashes[$this->name]);
			return true;
		}
		return false;
	}





	/**
	 * singleUseKey - Generates the HTML input tag for the single use key
	 * Uses cryptographically secure random bytes instead of weak MD5 hashing.
	 *
	 * @return - string
	 */
	private function singleUseKey()
	{
		$formKey = bin2hex(random_bytes(32));

		$formKeys = G::$session->formKeys;
		$formKeys[$this->name] = $formKey;
		G::$session->formKeys = $formKeys;
		G::$session->save();

		$s[] = '<input type="hidden" name="__blaze_form_key" value="';
		$s[] = htmlspecialchars($formKey, ENT_QUOTES, 'UTF-8');
		$s[] = '">';

		return implode($s);
	}





	/**
	 * verifyFormKey - Validates the form key and removes it from the session so it cannot
	 *                 be validated again. Uses timing-safe comparison to prevent timing attacks.
	 *
	 * @param $formKey
	 * @return - boolean
	 */
	private function verifyFormKey($formKey)
	{
		$formKeys = G::$session->formKeys;

		if(isset($formKeys[$this->name]) && hash_equals($formKeys[$this->name], $formKey)) {
			unset($formKeys[$this->name]);
			G::$session->formKeys = $formKeys;
			G::$session->save();
			return true;
		}

		return false;
	}





	/**
	 * setErrors - Sets the error messages on the form elements.  This is designed to
	 *             use the errors array received from a Blaze model object's validate()
	 *             method.
	 *
	 * @param $errors - Array of errors keyed by the form element name.
	 * @param $firstOfEachOnly - Boolean switch on whether to display the first error of each
	 *                           element or all of the errors if false is provided.
	 * @return - void
	 */
	public function setErrors($errors, $firstOfEachOnly=true)
	{
		if(!is_array($errors)) {
			throw new \Exception(
				__CLASS__.'::'.__FUNCTION__.' - The value passed to errors is not an array ['.(string)$errors.']'
			);
		}

		foreach($errors as $name => $errorList) {
			if($firstOfEachOnly === true) {
				$this->errors[$name] = $errorList[0];
			}
			else {
				$this->errors[$name] = implode("\n", $errorList);
			}
		}
	}





	/**
	 * hasError - Returns true if the form element named has an error attatched to it
	 *
	 * @param $name - Form elment name
	 * @return - boolean
	 */
	public function hasError($name)
	{
		return (isset($this->errors[$name]) && !empty($this->errors[$name]))
			? true
			: false;
	}





	/**
	 * getError - Returns the error message attached to the form element named
	 *
	 * @param $name - Form element name
	 * @return - string
	 */
	public function getError($name)
	{
		return (isset($this->errors[$name]))
			? $this->errors[$name]
			: null;
	}





	/**
	 * clear - Clears the form values
	 *
	 * @return - void
	 */
	public function clear()
	{
		$this->formValues = array();
	}





	/**
	 * populate - Populates the form values with the values of the object received.  This
	 *            is designed to work with the Blaze model object's getValues() method as
	 *            the input.
	 *
	 * @param $values - PHP stdClass object holding the form values
	 * @return - void
	 */
	public function populate(\stdClass $values)
	{
		if(!is_object($values)) {
			throw new \Exception(
				__CLASS__.'::'.__FUNCTION__.' - An object was not received as expected.'
			);
		}
		foreach($values as $name => $value) {
			$this->formValues->{$name} = $value;
		}
	}





	/**
	 * getValues - Returns the form values object
	 *
	 * @return - object
	 */
	public function getValues()
	{
		return $this->formValues;
	}



	public function setValue($name, $value) {
		$this->formValues->{$name} = $value;
	}





	/**
	 * open - Returns the form open tag and initializes the the form hash to be used
	 *        when validating the received input from the client.
	 *
	 * @param $attr - A string of additional attributes to be placed directly in the <form> open tag
	 * @return - string - <form> open tag
	 */
	public function open($attr=null)
	{
		if($this->action === null) {
			$this->action = G::$request->getRequestedPath();
		}

		self::updateFormHash($this->name);

		$s = array();
		$s[] = '<form';
		$s[] = ' name="';
		$s[] = urlencode((string)$this->name);
		$s[] = '"';
		$s[] = ' action="';
		$s[] = $this->action;
		$s[] = '"';
		$s[] = ' method="';
		$s[] = (in_array(strtolower($this->method), array('get', 'post'))) ? strtolower($this->method) : 'post';
		$s[] = '"';

		switch($this->enctype) {
			case 'application':
				$s[] = ' enctype="application/x-www-form-urlencoded"';
				$s[] = ' formenctype="application/x-www-form-urlencoded"';
				break;

			case 'text':
				$s[] = ' enctype="text/plain"';
				$s[] = ' formenctype="text/plain"';
				break;

			case 'multipart':
			default;
				$s[] = ' enctype="multipart/form-data"';
				$s[] = ' formenctype="multipart/form-data"';
				break;
		}
		$s[] = (!empty($attr)) ? ' '.self::sanitizeAttributes($attr) : '';
		$s[] = '>';
		$s[] = '<input type="hidden" name="__form_name__" value="';
		$s[] = $this->name;
		$s[] = '">'."\n";
		if(G::$session) {
			$s[] = '<input type="hidden" name="csrf-token" value="';
			$s[] = G::$session->CSRFToken();
			$s[] = '">'."\n";
		}

		if(in_array($this->security, array(self::SINGLE_USE_ONLY, self::SECURE))) {
			$s[] = self::singleUseKey();
		}

		return implode($s);
	}





	/**
	 * close - Returns the closing form tag along with saving the session information that
	 *         will be used to validate the input received from the client.
	 *
	 * @return - string - </form>
	 */
	public function close()
	{
		if(in_array($this->security, array(self::VERIFY_FORM_ONLY, self::SINGLE_USE_ONLY, self::SECURE))) {
			$formHashes = G::$session->formHashes;
			$formHashes[$this->name] = md5(implode($this->formHashValues));
			G::$session->formHashes = $formHashes;
			G::$session->save();
		}

		return '</form>';
	}





	/**
	 * Sanitizes HTML attributes to prevent XSS via event handlers.
	 * Removes dangerous attributes like onclick, onerror, etc.
	 *
	 * @param string|null $attr The attributes string to sanitize
	 * @return string The sanitized attributes
	 */
	private static function sanitizeAttributes($attr)
	{
		if (empty($attr)) {
			return '';
		}
		
		// Remove event handler attributes (onclick, onerror, onload, etc.)
		$attr = preg_replace('/\bon\w+\s*=\s*["\'][^"\']*["\']/i', '', $attr);
		$attr = preg_replace('/\bon\w+\s*=\s*[^\s>]*/i', '', $attr);
		
		// Remove javascript: URLs
		$attr = preg_replace('/javascript\s*:/i', '', $attr);
		
		return trim($attr);
	}

	/**
	 * input - Returns the common parts of the <input> tag used by all of the other form element generators.
	 *
	 * @param $name - Name of the input tag: name="$name"
	 * @param $type - Type of the input tag: type="$type"
	 * @return - string
	 */
	private function input($name, $type, $id=null, $disabled=false)
	{
		self::updateFormHash($name);
		$s = array();
		$s[] = '<input type="'.$type.'"';
		$s[] = ' name="'.urlencode((string)$this->name).'['.urlencode((string)$name).']"';

		$id = (empty($id)) ? urlencode((string)$name) : urlencode((string)$id);
		$s[] = ' id="form-'.$this->name.'-'.$id.'"';

		if($disabled === true) {
			$s[] = ' disabled';
		}

		return $s;
	}





	/**
	 * inputHidden - Returns a hidden input tag and adds the element to the form hash
	 *
	 * @param $name - Name of the input tag: name="$name"
	 * @param $value - Value of the input tag: value="$value"
	 * @param $attr - A string of additional attributes to be placed directly in the <input> tag
	 * @return - string
	 */
	public function inputHidden($name, $value=null, $attr=null, $disabled=false)
	{
		$s = self::input(name:$name, type:'hidden', disabled:$disabled);
		$s[] = ' value="';
		if($value === null && isset($this->formValues->{$name})) {
			$s[] = htmlspecialchars((string)$this->formValues->{$name}, ENT_QUOTES, 'UTF-8');
		}
		else {
			$s[] = htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
		}
		$s[] = '"';
		$s[] = ' '.self::sanitizeAttributes($attr);
		$s[] = '>';

		return implode($s);
	}




	/**
	 *
	 */
	public function helpBlock($name, $classes='with-errors')
	{
		return;
		// $s = array();
		// $s[] = '<div class="form-help-block ';
		// $s[] = $classes;
		// $s[] = '">';
		// if($this->hasError($name)) {
		// 	$s[] = $this->getError($name);
		// }
		// $s[] = '</div>';

		// return implode($s);
	}




	/**
	 * inputText - Returns a text input tag and adds the element to the form hash
	 *
	 * @param $name - Name of the input tag: name="$name"
	 * @param $value - Value of the input tag: value="$value"
	 * @param $attr - A string of additional attributes to be placed directly in the <input> tag
	 * @return - string
	 */
	public function inputText($name, $value=null, $attr=null, $disabled=false)
	{
		$s = self::input(name:$name, type:'text', disabled:$disabled);
		$s[] = ' value="';
		if($value === null && isset($this->formValues->{$name})) {
			$s[] = htmlspecialchars($this->formValues->{$name}, ENT_QUOTES, 'UTF-8');
		}
		else {
			$s[] = htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
		}
		$s[] = '"';
		$s[] = ' '.self::sanitizeAttributes($attr);
		$s[] = '>';
		$s[] = $this->helpBlock($name);
		$s[] = "\n";

		return implode($s);
	}





	/**
	 * inputEmail - Returns an email input tag and adds the element to the form hash
	 *
	 * @param $name - Name of the input tag: name="$name"
	 * @param $value - Value of the input tag: value="$value"
	 * @param $attr - A string of additional attributes to be placed directly in the <input> tag
	 * @return - string
	 */
	public function inputEmail($name, $value=null, $attr=null, $disabled=false)
	{
		$s = self::input(name:$name, type:'email', disabled:$disabled);
		$s[] = ' value="';
		if($value === null && isset($this->formValues->{$name})) {
			$s[] = htmlspecialchars($this->formValues->{$name}, ENT_QUOTES, 'UTF-8');
		}
		else {
			$s[] = htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
		}
		$s[] = '"';
		$s[] = ' '.self::sanitizeAttributes($attr);
		$s[] = '>';
		$s[] = $this->helpBlock($name);

		return implode($s);
	}





	/**
	 * inputPassword - Returns a password input tag and adds the element to the form hash
	 *
	 * @param $name - Name of the input tag: name="$name"
	 * @param $value - Value of the input tag: value="$value"
	 * @param $attr - A string of additional attributes to be placed directly in the <input> tag
	 * @return - string
	 */
	public function inputPassword($name, $value=null, $attr=null, $disabled=false)
	{
		$s = self::input(name:$name, type:'password', disabled:$disabled);
		$s[] = ' value="';
		if($value === null && isset($this->formValues->{$name})) {
			$s[] = htmlspecialchars($this->formValues->{$name}, ENT_QUOTES, 'UTF-8');
		}
		else {
			$s[] = htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
		}
		$s[] = '"';
		$s[] = ' '.self::sanitizeAttributes($attr);
		$s[] = '>';
		$s[] = $this->helpBlock($name);

		return implode($s);
	}





	/**
	 * inputCheckbox - Returns a checkbox input tag and adds the element to the form hash
	 *
	 * @param $name - Name of the input tag: name="$name"
	 * @param $value - Value of the input tag: value="$value"
	 * @param $attr - A string of additional attributes to be placed directly in the <input> tag
	 * @return - string
	 */
	public function inputCheckbox($name, $value='1', $attr=null, $disabled=false)
	{
		$s = self::input(name:$name, type:'checkbox', disabled:$disabled);
		$s[] = ' value="'.htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8').'"';
		if(isset($this->formValues->{$name}) && (boolean)$this->formValues->{$name} === true) {
			$s[] = ' checked="checked"';
		}
		$s[] = ' '.self::sanitizeAttributes($attr);
		$s[] = '>';

		return implode($s);
	}





	/**
	 * inputRadio - Returns a radio button input tag and adds the element to the form hash
	 *
	 * @param $name - Name of the input tag: name="$name"
	 * @param $value - Value of the input tag: value="$value"
	 * @param $attr - A string of additional attributes to be placed directly in the <input> tag
	 * @return - string
	 */
	public function inputRadio($name, $value, $attr=null, $disabled=false)
	{
		$s = self::input(name:$name, type:'radio', id:$name.$value, disabled:$disabled);
		$s[] = ' value="'.htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8').'"';
		if(isset($this->formValues->{$name}) && $this->formValues->{$name} === $value) {
			$s[] = ' checked="checked"';
		}
		if(!empty($attr)) {
			$s[] = ' '.self::sanitizeAttributes($attr);
		}
		$s[] = '>';


		return implode($s);
	}


	/**
	 * inputRange - Returns a range input element
	 *
	 * @param $name - Name of the input tag: name="$name"
	 * @param $value - Value of the input tag: value="$value"
	 * @param $attr - A string of additional attributes to be placed directly in the <input> tag
	 * @return - string
	 */
	public function inputRange($name, $value, $attr=null, $disabled=false)
	{
		$s = self::input(name:$name, type:'range', id:$name.$value, disabled:$disabled);
		$s[] = ' value="'.htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8').'"';
		if(!empty($attr)) {
			$s[] = ' '.self::sanitizeAttributes($attr);
		}
		$s[] = '>';

		return implode($s);
	}



	/**
	 * inputSubmit - Returns a submit button input tag and adds the element to the form hash
	 *
	 * @param $name - Name of the input tag: name="$name"
	 * @param $value - Value of the input tag: value="$value"
	 * @param $attr - A string of additional attributes to be placed directly in the <input> tag
	 * @return - string
	 */
	public function inputSubmit($name, $value, $attr=null, $disabled=false)
	{
		$s = self::input(name:$name, type:'submit', disabled:$disabled);
		$s[] = ' value="'.htmlspecialchars($value, ENT_QUOTES, 'UTF-8').'"';
		$s[] = ' '.self::sanitizeAttributes($attr);
		$s[] = '>';

		return implode($s);
	}




	/**
	 * textarea - Returns a textarea tag set and adds the element to the form hash
	 *
	 * @param $name - Name of the input tag: name="$name"
	 * @param $value - Value of the input tag: value="$value"
	 * @param $attr - A string of additional attributes to be placed directly in the <textarea> tag
	 * @return - string
	 */
	public function textarea($name, $value=null, $attr=null, $disabled=false)
	{
		self::updateFormHash($name);

		$s = array();
		$s[] = '<textarea';
		$s[] = ' name="'.urlencode((string)$this->name).'['.urlencode((string)$name).']"';
		$s[] = ' id="'.urlencode((string)$name).'"';
		$s[] = ' '.self::sanitizeAttributes($attr);
		if($disabled === true) {
			$s[] = ' disabled';
		}
		$s[] = '>';
		if($value === null && isset($this->formValues->{$name})) {
			$s[] = htmlspecialchars($this->formValues->{$name}, ENT_QUOTES, 'UTF-8');
		}
		else {
			$s[] = htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
		}
		$s[] = '</textarea>';
		$s[] = $this->helpBlock($name);

		return implode($s);
	}





	/**
	 * select - Returns a select open tag and adds the element to the form hash
	 *
	 * @param $name - Name of the input tag: name="$name"
	 * @param $attr - A string of additional attributes to be placed directly in the <input> tag
	 * @return - string
	 */
	private function select($name, $attr, $disabled=false)
	{
		self::updateFormHash($name);

		$s = array();
		$s[] = '<select';
		$s[] = ' name="'.urlencode((string)$this->name).'['.urlencode((string)$name).']"';
		$s[] = ' id="form-'.htmlspecialchars($this->name, ENT_QUOTES, 'UTF-8').'-'.htmlspecialchars($name, ENT_QUOTES, 'UTF-8').'"';
		$s[] = ' '.self::sanitizeAttributes($attr);
		if($disabled === true) {
			$s[] = ' disabled';
		}
		$s[] = '>';

		return $s;
	}





	/**
	 * selectSingle - Returns a selection dropdown with options for a single dimentioned array and adds the element to the form hash
	 *
	 * @param $name - Name of the input tag: name="$name"
	 * @param $options - Array holding the value=>label structure to build the options
	 * @param $selected - The key selected in the options array
	 * @param $attr - A string of additional attributes to be placed directly in the <select> tag
	 * @return - string
	 */
	public function selectSingle($name, $options, $selected=null, $attr=null, $disabled=false)
	{
		if(!is_array($options)) {
			throw new \Exception(
				__CLASS__.'::'.__FUNCTION__.' - The value passed in to $options was not an array as expected'
			);
		}

		$s = self::select(name:$name, attr:$attr, disabled:$disabled);

		if($selected === null && isset($this->formValues->{$name})) {
			$selected = $this->formValues->{$name};
		}
		// D::printre([$selected, $options]);
		// D::printr([$name, $this->formValues, $options, $selected]);
		foreach($options as $key => $value) {
			$s[] = "\n    ";
			$s[] = '<option value="'.urlencode((string)$key).'"';
			// D::printr([$selected, $key]);
			if($selected == $key) {
				$s[] = ' selected="selected"';
			}
			$s[] = '>';
			$s[] = htmlspecialchars($value);
			$s[] = '</option>';
		}
		$s[] = '</select>';
		$s[] = $this->helpBlock($name);

		return implode($s);
	}





	/**
	 * selectGroup - Returns a selection dropdown with options for a multi dimentioned array
	 *               with the first dimention keys being the option groups and adds the element to the form hash
	 *
	 * @param $name - Name of the input tag: name="$name"
	 * @param $options - Array holding the group=>value=>label structure to build the options
	 * @param $selected - The key selected in the options array
	 * @param $attr - A string of additional attributes to be placed directly in the <select> tag
	 * @return - string
	 */
	public function selectGroups($name, $options, $selected=null, $attr=null, $disabled=false)
	{
		if(!is_array($options)) {
			throw new \Exception(
				__CLASS__.'::'.__FUNCTION__.' - The value passed in to $options was not an array as expected'
			);
		}

		$s = self::select(name:$name, attr:$attr, disabled:$disabled);
		if($selected === null && isset($this->formValues->{$name})) {
			$selected = $this->formValues->{$name};
		}
		foreach($options as $group => $valueList) {
			$s[] = "\n    ";
			$s[] = '<optgroup';
			$s[] = ' label="'.htmlspecialchars($group, ENT_QUOTES, 'UTF-8').'"';
			$s[] = '>';
			foreach($valueList as $value => $label) {
				$s[] = "\n        ";
				$s[] = '<option';
				$s[] = ' value="'.htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8').'"';
				if($selected == $value) {
					$s[] = ' selected="selected"';
				}
				$s[] = '>';
				$s[] = htmlspecialchars($label, ENT_QUOTES, 'UTF-8');
				$s[] = '</option>';
			}
			$s[] = '</optgroup>';
		}
		$s[] = "\n";
		$s[] = '</select>';
		$s[] = $this->helpBlock($name);

		return implode($s);
	}
}