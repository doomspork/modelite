<?php
require_once 'Column.php';

class Model {
	
	private $db = NULL;
	
	protected $has_one = array();
	protected $has_many = array();
	protected $belongs_to = array();
	protected $habtm = array();
	
	protected static $query_options = array(
		'limit' => 'return "LIMIT $parameter";',
		'group' => 'return "GROUP BY $parameter";',
		'desc' => 'return "ORDER BY $parameter DESC";',
		'asc' => 'return "ORDER BY $parameter ASC";');
		
	protected $use_table = NULL;
	protected $validators = array();
	protected $columns = array();
	
	public function __construct($use_schema = TRUE) {
		$model_name =  get_called_class();
		if($use_schema) {
			$this->db = new PDO('sqlite:' . $model_name::SQLITE);
			$this->schema();
		}
		$this->setValidators();
	}
	
	private function setValidators() {
		$registry = ValidatorRegistry::instance();
		foreach($this->validators as $field => $dators) { //dator, it's slang for validator, word.
			$col = $this->columns[$field]; 
			if($col != FALSE) {
				$dators = is_array($dators) ? $dators : array($dators);
				foreach($dators as $type) {
					$validator = NULL;
					if($type instanceof Validates) {
						$validator = $type;
					} else {
						$name = $type;
						$options;
						$clz = NULL;
						if(is_array($type)) {
							$name = key($type);
							$options = $type[$name];
						}
						$clz = ($registry->contains($name)) ? $registry->get($name) : new ReflectionClass($name);
						$validator = $clz->newInstance($options);
					}
					$col->addValidator($validator);
				}
			}
		}
	}
	
	public function value($field) {
		$col = $this->column($field);
		return ($col == NULL) ? NULL : $col->value;
	}
	
	public function column($field) {
		if(array_key_exists($field, $this->columns)) {
			return $this->columns[$field];
		}
		return NULL;
	}
	
	public function __get($name) {
		if(array_key_exists($name, $this->columns)) {
			return $this->value($name);
		} else if (in_array($name, $this->has_one)) {
			return $name::first(array(strtolower(get_called_class()) . '_id' => $this->rowid->value));
		} else if (in_array(ucfirst($name), $this->has_many)) {
			$results = $name::find(array(strtolower(get_called_class()) . '_id' => $this->rowid->value));
			return (is_array($results)) ? $results : array($results);
		} else if(in_array($name, $this->belongs_to)) {
			$field = $name . '_id';
			return $name::first($this->$field->value);
		}
		return NULL;
	}
	
	
	public function __set($name, $value) {
		if(array_key_exists($name, $this->columns)) {
			$col = $this->columns[$name];
			$col->value = $value;
		}
	}
	
	public function validate() {
		$errors = array();
		foreach($this->columns as $column) {
			if(is_array($message = $column->validate())){
				$errors = array_merge($errors, $message);
			}
		}
		return empty($errors) ? TRUE : $errors;
	}
	
	public static function query($query_stmt) {
		if(preg_match('/^INSERT|UPDATE|DELETE.*', $query_stmt)){
			return $db->exec($query_stmt);
		}
		return $db->query($query_stmt);
	}
	
	public static function all($options = array()) {
		return self::find(array(1 => 1), $options);
	}
	
	public static function count($fields = array()) {
		$model_name = get_called_class();
		$where = join((count($fields) > 1) ? ' AND ' : '', $fields);
		return self::query('SELECT count(*) FROM ' . $model_name . ' WHERE ' . $where);
	}
	
	public static function first($fields = NULL, $options = array()) {
		$options['limit'] = 1;
		if($fields == NULL) {
			return self::all($options);
		}
		return self::find($fields, $options);
	}
	
	
	public static function find($fields = NULL, $options = array()) {
		$model_name = get_called_class();
		if($fields == NULL) 
			return NULL;
			
		$fields = (is_array($fields) == FALSE) ? array('RowID' => $fields) : $fields;
		
		array_walk($fields, array($model_name, 'walk'));
		
		$where = join((count($fields) > 1) ? ' AND ' : '', $fields);
		
		$query = 'SELECT rowid, * FROM ' . $model_name . ' WHERE ' . $where;
		foreach($options as $option => $parameters) {
			if(array_key_exists($option, Model::$query_options)) {
				$func = create_function('$parameter', Model::$query_options[$option]);
				$query .= ' ' . $func($parameters);
			}
		}
		if(($results = $this->query($query)) != NULL) {
			$models = array();
			foreach($results->fetchAll(PDO::FETCH_ASSOC) as $result) {
				$clz = new ReflectionClass($model_name);
				$model = $clz->newInstance();
				foreach($result as $name => $value) {
					$model->$name = $value;
				}
				array_push($models, $model);
			}
		}
		return (count($models) > 1) ? $models : $models[0];
	}
	
	public function save() {
		$table = ($this->use_table == NULL) ? get_called_class() : $this->use_table;
		$cols = $this->columns;
		unset($cols['rowid']);
		$values = array_map(create_function('$c', 'return $c->value;'), $cols);  //This can be accomplished with the new methods
		$rows = 0;
		if(isset($id)) {
			$name_str = join(',', array_keys($values));
			$value_str = join(',', $values);
			$insert_str = array();
			for($i = 0 ; $i < count($name_str) ; $i++) {
				array_push($insert_str, $name_str[$i] . '=' . $value_str[$i]);
			}
			$insert_str = join(',', $insert_str);
			$rows = $this->query('UPDATE ' . $table . ' SET (' . $insert_str . ') WHERE rowid = ' . $this->value('rowid'));
		} else {
			if(array_key_exists('created', $values)) {
				$values['created'] = time();
			}
			$name_str = join(',', array_keys($values));
			$value_str = join(',', array_values($values));
			$rows = $this->query('INSERT INTO ' . $table . '(' . $name_str .') VALUES (' . $value_str . ')');
		}
		return ($rows == 1) ? TRUE : FALSE;
	}
	
	protected function walk(&$value, $key) {
		if(strtolower($key) == 'or' && is_array($value)) {
			array_walk($value, array(get_called_class(), 'walk'));
			$value = implode(' OR ', $value);
		} else {
			if(is_array($value)) {
				$value = "$key IN (" . implode(',', $value) . ")";
			} else {
				$operator = '=';
				if(preg_match('/([><=]{1,2})$/', $val, $operator)) {
					$operator = $operator[1];
				}
				$val = is_string($value) ? '"' . $value . '"' : $value;
				$value = $key . $operator . $val;		
			}
		}	
	}
	
	public function schema() {
		if(empty($this->columns)) {
			$stm = $this->db->query('PRAGMA table_info('. get_called_class() .')');
			foreach($stm->fetchAll(PDO::FETCH_ASSOC) as $column) {
				
				if(in_array($column['name'], $this->columns)) {
					//Allow people to assign custom column types to a field
				}
				
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
		//Add in rowid here for now
		$id = new Column();
		$id->primary_key = TRUE;
		$id->notnull = FALSE;
		$id->name = 'rowid';
		$this->columns['rowid'] = $id;
		
		return $this->columns;
	}
}

?>