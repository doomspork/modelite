<?php
class Numeric extends BaseValidator {
	
	public function __construct() {
		parent::__construct("/^[-+]?[0-9]\.?[0-9e-]?[0-9]?*$/i");
	}
	
	public function getMessage() {
		return 'This field must contain only numeric values';
	}
}

?>