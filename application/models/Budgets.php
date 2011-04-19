<?php

include_once '../Zend/Zend/Db/Table.php';
include_once '../Zend/Zend/Registry.php';
class Budgets extends Zend_Db_Table_Abstract {
	
	protected $_name = 'budgets';
	protected $_primary = 'id';
	
	public function __construct() {
		$this->_db = Zend_Registry::get('db');
	}
}

?>