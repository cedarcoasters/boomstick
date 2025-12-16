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
namespace BoomStick\Lib\Generate\Html;

use BoomStick\Lib\Globals as G;
use BoomStick\Lib\Debug as D;
use BoomStick\Lib\Data\Db\Row;
/**
 * FormHelper
 *
 * This class is the helper that is minimally designed to use for form security and automating the
 * Blaze object intraction.  This is not like other form helpers that try to do far beyond what
 * any helper class should do.
 *
 * @author Matt Roszyk <me@mattroszyk.com>
 */
class FormTemplate
{
	public static function build($form, $fields=null, Row $row=null, $targetPrefix='g-form', $file='g-form')
	{
		$formId = preg_replace('/_/', '-', $form->name);
		$tFile  = MODULE_ROOT.'/render/template/'.$file.'.template.php';
		if(!file_exists($tFile)) {
			throw new \Exception('The template file does not exists ['.$tFile.']');
		}

		$fields = (!empty($fields) && is_array($fields) && count($fields) > 0)
			? $fields
			: ((!empty($row))
				? $row->fields()
				: null);

		if(empty($fields)) {
			$message = 'Neither the $fields nor the $row parameters are set.';
			throw new Exception($message);
		}

		$formValues = new \stdClass();
		foreach($fields as $name => $field) {
			$formValues->{$name} = $field->value;
		}
		$form->populate($formValues);
;
		ob_start();
		include($tFile);
		$content = ob_get_contents();
		ob_end_clean();
		return $content;
	}
}