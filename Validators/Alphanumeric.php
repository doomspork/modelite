<?php
class AlphaNumeric extends BaseValidator {
	
	public function __construct() {
		parent::__construct("/^[a-z0-9]*$/i");
	}
	
	public function getMessage() {
		return 'This field may only contain alpha or numeric characters.';
	}
}

?>