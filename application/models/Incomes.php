<?php

include_once '../Zend/Zend/Db/Table.php';
include_once '../Zend/Zend/Registry.php';
class Incomes extends Zend_Db_Table_Abstract {
	
	protected $_name = 'incomes';
	protected $_primary = 'id';
	
	public function __construct() {
		$this->_db = Zend_Registry::get('db');
	}
	
	public function getIncomesList($i_year) {
		$st_data = array(
			'sum'	=>	'sum(i.amount)',
			'name'	=>	'c.name'
		);
		try {
			$i_month = 1;
			$s_select = $this->_db->select()
				->from(array('i'=>'incomes'), $st_data)
				->joinLeft(array('c'=>'categories','c.id = i.category',array()))
				->where('YEAR(i.date) = '.$i_year)
				->where('MONTH(i.date) = '.$i_month);
			
		} catch (Exception $e) {
			error_log("Exception caught in ".__CLASS__."::".__FUNCTION__." on line ".$e->getLine().": ".$e->getMessage());
		}
	}
}

?>