<?php

include_once '../Zend/Zend/Db/Table.php';
include_once '../Zend/Zend/Registry.php';
class Incomes extends Zend_Db_Table_Abstract {
	
	protected $_name = 'incomes';
	protected $_primary = 'id';
	
	public function __construct() {
		$this->_db = Zend_Registry::get('db');
	}
}
?>