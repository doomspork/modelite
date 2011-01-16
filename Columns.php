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

	public function __constructor($name = NULL) {
		$this->name = $name;
	}
	
	public function addValidator(ColumnValidator $validator) {
		if(in_array($validator, $this->validators) == FALSE) {
			array_push($this->validators, $validator);
		}
	}
	
	public function validate(&$errors) {
		foreach($this->validators as $validator) {
			if($validator->isValid($this) == FALSE) {
				array_push($errors, $validator->getMessage());
				return FALSE;
			}
		}
		return TRUE;
	}
	
	public function __get($name) {
		$value = NULL;
		switch(strtolower($name)) {
			case 'modified':
				$this->value = time();
				break;
			case 'created':
				$this->value = ($this->value == NULL) ? time() : $this->value;
				break;
		}
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