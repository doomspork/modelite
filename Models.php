<?php

require_once 'Columns.php';

class Model {
	
	private $db = NULL;
	
	protected static $query_options = array(
		'limit' => 'return "LIMIT BY $parameter"',
		'group' => 'return "GROUP BY $parameter"',
		'desc' => 'return "ORDER BY $parameter DESC"',
		'asc' => 'return "ORDER BY $parameter ASC"');
		
	protected $use_table = NULL;
	protected $columns = array();
	protected $validators = array();
	
	public function __constructor() {
		$this->db = new PDO('sqlite:blog_db.sqlite');
		$this->schema();
		$this->setValidators();
	}
	
	private function setValidators() {
		foreach($this->validators as $field => $dators) { //dator, it's slang for validator, word.
			if($col = $this->columns[$field]) {
				foreach($dators as $type) {
					$validator = NULL;
					if($type instanceof Validates) {
						$validator = $type;
					} else {
						switch($type) {
							case 'required':
								$validator = new Required();
								break;
							case 'alphanumeric':
								$validator = new AlphaNumeric();
								break;
							case 'alpha':
								$validator = new Alpha();
								break;
							case 'numeric':
								$validator = new Numeric();
								break;
							default:
								try {
									$clz = new ReflectionClass($type);
									$instance = $clz->newInstance();
									$validator = $instance;
								} catch (ReflectionException $exception) {
									// Should this project utilize lumberjack?
								}
								break;
						}
					}
					$col->addValidator($validator);
				}
			}
		}
	}
	
	public function __get($name) {
		if(array_key_exists($name, $this->columns)) {
			return $this->columns[$name];
		}
		return NULL;
	}
	
	public function __set($name, $value) {
		$col = $this->__get($name);
		if($col != NULL) {
			$col->value = $value;
		}
	}
	
	public static function all($fields = NULL, $options = array()) {
		$clz = get_class($this);
		return $clz::find($fields, $options);
	}
	
	public static function first($fields = NULL, $options = array()) {
		$clz = get_class($this);
		return $clz::find($fields, $options);
	}
	
	public static function find($fields = NULL, $options = array()) {
		$model_name = get_class($this);
		if($fields == NULL) 
			return NULL;
			
		$fields = (is_array($fields) == FALSE) ? array('RowID' => $fields) : $fields;
		
		array_walk($fields, array($model_name, 'walk'));
		
		$where = join((count($fields) > 1) ? ' AND ' : '', $fields);
		
		$db = new PDO('sqlite:' . $model_name::SQLITE);
		$query = 'SELECT rowid, * FROM ' . $model_name . ' WHERE ' . $where;
		foreach($options as $option => $parameters) {
			if(array_key_exists($option, Model::$query_options)) {
				$func = create_function('$parameters', Model::$query_options[$option]);
				$query .= ' ' . $func($parameters);
			}
		}
		$results = $db->query($query);
		$models = array();
		foreach($results->fetchAll(PDO::FETCH_ASSOC) as $result) {
			$model = new $model_name();
			foreach($result as $name => $value) {
				$model->$name = $value;
			}
			array_push($models, $model);
		}
		
		return (count($models) > 1) ? $models : $models[0];
	}
	
	public function save() {
		$table = ($this->use_table == NULL) ? __CLASS__ : $this->use_table;
		$cols = array_map(create_function('$c', 'if($c->name != "rowid") return $c;'), $this->columns);
		$names = array_keys($this->columns);
		$values = array_map(create_function('$c', 'return $c->value;'), $this->columns);
		
		if(isset($id)) {
			$name_str = join(',', $name);
			$value_str = join(',', $values);
			$insert_str = array();
			for($i = 0 ; $i < count($name_str) ; $i++) {
				array_push($insert_str, $name_str[$i] . '=' . $value_str[$i]);
			}
			$insert_str = join(',', $insert_str);
			$rows = $this->db->exec('UPDATE ' . $table . ' SET (' . $insert_str . ') WHERE rowid = ' . $this->columns['id']->value);
		} else {
			$name_str = join(',', $name);
			$value_str = join(',', $values);
			$rows = $this->db->exec('INSERT INTO ' . $table . '(' . $name_str .') VALUES (' . $value_str . ')');
		}
		return ($rows == 1) ? TRUE : FALSE;
	}
	
	private function walk(&$value, $key) {
		if(strtolower($key) == 'or' && is_array($value)) {
			array_walk($value, array(__CLASS__, 'walk'));
			$value = implode(' OR ', $value);
		} else {
			if(is_array($value)) {
				$value = "$key IN (" . implode(',', $value) . ")";
			} else {
				$val = is_string($value) ? "'$value'" : $value; 
				$value = "$key = $val";		
			}
		}	
	}
	
	public function schema() {
		if(empty($this->columns)) {
			$stm = $this->db->query('PRAGMA table_info(Posts)');
			foreach($stm->fetchAll(PDO::FETCH_ASSOC) as $column) {
				$col = new Column();
				foreach($column as $key => $value) {
					switch($key) {
						case 'notnull':
							$col->null = !$value;
							break;
						case 'dflt_value':
							$col->default_value = $value;
							break;
						case 'pk':
							$col->primary_key = $value;
							break;
						default:
							$col->$key = $value;
							break;
					}
				}
				$this->columns[$column['name']] = $col;
			}
		}
		return $this->columns;
	}
}

?>