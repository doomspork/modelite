<?php

interface Validates {
	public function isValid($value);
	public function getMessage();
}

class Required implements Validates {
	public function isValid($value) {
		return (isset($value) && $value != NULL);
	}
	
	public function getMessage() {
		return '';
	}
}

abstract class BaseValidator implements Validates {
	private static $pattern = NULL;
	
	public function __constructor($pattern) {
		$this->pattern = $pattern;
	}
	
	public function isValid($value) {
		return preg_match($this->pattern, $value);
	}
	
	abstract public function getMessage();
}

class AlphaNumeric implements Validates {
	private static $alpha = new Alpha();
	private static $numeric = new Numeric();
	
	public function isValid($value) {
		return AlphaNumeric::$alpha->isValid($value) || AlphaNumeric::$numeric->isValid($value);
	}
	
	public function getMessage() {
		return '';
	}
}

class Alpha extends BaseValidator {
	
	public function __constructor() {
		super::__constructor("/^[a-z]*$/i");
	}
	
	public function getMessage() {
		return '';
	}
}

class Numeric extends BaseValidator {
	
	public function __constructor() {
		super::__constructor("/^[-+]?[0-9]\.?[0-9e-]?[0-9]?*$/i");
	}
	
	public function getMessage() {
		return '';
	}
}
?>