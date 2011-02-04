<?php

class Alpha extends BaseValidator {
	
	public function __construct() {
		parent::__construct("/^[a-z]*$/i");
	}
	
	public function getMessage() {
		return 'This field must contain only alpha characters';
	}
}

?>
