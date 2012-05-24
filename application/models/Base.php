<?php

abstract class Model_Base {
	const DB_DATE_FORMAT = 'Y-m-d H:i:s';
	const DB_NULL_DATE = '0000-00-00 00:00:00';

	const STATUS_ACTIVE = 1;
	const STATUS_DELETED = 0;

	protected $_dbTableName;
	protected $_dbTable;

	public $id;

	public function __construct($id = null) {
		if (null !== $id) {
			if ($this->find(intval($id))) {
				$this->id = intval($id);
			}
		}
		else {
			foreach ($this->getDbTable()->getColumns() as $f) {
				$this->$f = null;
			}
			$this->id = 0;
		}
	}

	public function setDbTable() {
		$cache = Zend_Cache::factory(
			Zend_Registry::get('config')->cache->frontEnd,
			Zend_Registry::get('config')->cache->backEnd,
			array('automatic_serialization' => true, 'lifetime' => Zend_Registry::get('config')->cache->lifetime)
		);

		//$cache->clean(Zend_Cache::CLEANING_MODE_ALL);
		$this->_dbTable = new Model_BaseMapper($this->_dbTableName, array('metadataCache' => $cache));
		return $this;
	}

	/**
	 *
	 * @return Zend_Db_Table
	 */
	public function getDbTable() {
		if (null === $this->_dbTable) {
			$this->setDbTable();
		}
		return $this->_dbTable;
	}

	public function bindFromRow($row) {
		foreach ($this->getDbTable()->getColumns() as $k) {
			$this->$k = $row->$k;
		}
	}

	public function save($force_insert=false) {
		$data = array();
		foreach ($this->getDbTable()->getColumns() as $k) {
			if (null !== $this->$k) {
				$data[$k] = $this->$k;
			}
		}
		unset($data['id']);

		if ($force_insert || null === $this->id || 0 === $this->id) {
			if (in_array('created', $this->getDbTable()->getColumns())) {
				$data['created'] = $this->now();
			}

			if ($force_insert) {
			    $data['id'] = $this->id;
			}
			$this->id = $this->getDbTable()->insert($data);
		}
		else {
			if (in_array('modified', $this->getDbTable()->getColumns())) {
				$data['modified'] = $this->now();
			}

			$this->getDbTable()->update($data, array('id = ?' => $this->id));
		}
	}

	public function find($id) {
		$result = $this->getDbTable()->find($id);
		if (0 == count($result)) {
			return false;
		}
		$row = $result->current();
		$this->bindFromRow($row);
		return true;
	}

	public function fetchAll($where = null) {
		$class = get_class($this);

		$resultSet = $this->getDbTable()->fetchAll($where);

		$entries = array();
		foreach ($resultSet as $row) {
			$entry = new $class;
			$entry->bindFromRow($row);
			$entries[] = $entry;
		}

		return $entries;
	}

	public function delete($full = false) {
		if ($full) {
			$this->getDbTable()->delete('id = ' . $this->id);
		}
		else {
			$this->status = self::STATUS_DELETED;
			$this->save();
		}
		return $this;
	}


	public function now() {
		return date(self::DB_DATE_FORMAT);
	}
}