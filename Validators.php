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
		return 'This $field is required.';
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
}

class AlphaNumeric implements Validates {
	private static $alpha = NULL;
	private static $numeric = NULL;
	
	public function __constructor() {
		$this->alpha = new Alpha();
		$this->numeric = new Numeric();	
	}
	
	public function isValid($value) {
		return AlphaNumeric::$alpha->isValid($value) || AlphaNumeric::$numeric->isValid($value);
	}
	
	public function getMessage() {
		return '$field must be alpha numeric.';
	}
}

class Alpha extends BaseValidator {
	
	public function __constructor() {
		super::__constructor("/^[a-z]*$/i");
	}
	
	public function getMessage() {
		return '$field must contain only alpha characters';
	}
}

class Numeric extends BaseValidator {
	
	public function __constructor() {
		super::__constructor("/^[-+]?[0-9]\.?[0-9e-]?[0-9]?*$/i");
	}
	
	public function getMessage() {
		return '$field must contain only numeric values';
	}
}
?>