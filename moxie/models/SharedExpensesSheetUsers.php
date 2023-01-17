<?php

class SharedExpensesSheetUsers extends Zend_Db_Table_Abstract {
	
	protected $_name = 'shared_expenses_sheet_users';
	protected $_primary = 'id';

    public function __construct() {
        $this->_db = Zend_Registry::get('db');
    }
}
