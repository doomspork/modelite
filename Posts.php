<?php
require_once 'Models.php'

class Posts extends Model {
	const SQLITE = 'blog_db.sqlite';

	public function __constructor() {
		parent::__constructor();
	}

}

?>