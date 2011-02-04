<?php
require_once 'Validators.php';

class Column {
	
	protected $name = NULL;
	protected $type = NULL;
	protected $length = NULL;
	protected $null = NULL;
	protected $default_value = NULL;
	protected $value = NULL;
	protected $primary_key = FALSE;
	
	protected $validators = array();

	public function __construct($name = NULL) {
		$this->name = $name;
	}
	
	public function addValidator(Validates $validator) {
		if(in_array($validator, $this->validators) == FALSE) {
			array_push($this->validators, $validator);
		}
	}
	
	public function validate() {
		$errors = array();
		foreach($this->validators as $validator) {
			if($validator->isValid($this->value) == FALSE) {
				array_push($errors, $validator->getMessage());
			}
		}
		return empty($errors) ? TRUE : array($this->name => $errors);
	}
	
	public function __get($name) {
		return $this->$name;
	}
	
	public function __set($name, $value) {
		switch(strtolower($name)) {
			case 'rowid':
				$this->id = $value;
				break;
			case 'length':
				if(is_numeric($length)) {
					$this->length = $length;
				}
				break;
			case 'primary_key':
				$this->primary_key = (bool) $value;
				break;
			default:
				$this->$name = $value;
				break;
		}
	}
	
	public function isPrimaryKey() {
		return $this->primary_key;
	}
}

?>