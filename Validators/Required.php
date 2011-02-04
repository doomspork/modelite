<?php

class Required implements Validates {
		
	public function __construct() {}
	
	public function isValid($value) {
		return (isset($value) && $value != NULL);
	}
	
	public function getMessage() {
		return 'This field is required.';
	}
}

?>
