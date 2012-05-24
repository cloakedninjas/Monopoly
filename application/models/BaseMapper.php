<?php

class Model_BaseMapper extends Zend_Db_Table_Abstract {
	protected $_name;

	public function __construct($name, $config=array()) {
		$this->_name = $name;
		parent::__construct($config);
	}

	public function getColumns() {
		return $this->info(Zend_Db_Table_Abstract::COLS);
	}
}