<?php

class SharedExpenses extends Zend_Db_Table_Abstract {
	
	protected $_name = 'shared_expenses';
	protected $_primary = 'id';
	
	public function __construct() {
		$this->_db = Zend_Registry::get('db');
	}
}
