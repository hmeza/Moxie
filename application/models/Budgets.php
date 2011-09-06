<?php

include_once '../Zend/Zend/Db/Table.php';
include_once '../Zend/Zend/Registry.php';
class Budgets extends Zend_Db_Table_Abstract {
	
	protected $_name = 'budgets';
	protected $_primary = 'id';
	
	public function __construct() {
		$this->_db = Zend_Registry::get('db');
	}
	
	/**
	 * 
	 * Returns the current budget for a given user.
	 * @param int $user_id
	 */
	public function getBudget($user_id) {
		error_log($this->_db->select()->where('user_owner = '.$user_id));
		$st_data = $this->_db->fetchAll(
						$this->_db->select()
								->from('budgets')
								->where('user_owner = '.$user_id)
					);
		$st_budget = array();
		foreach($st_data as $key => $value) {
			$st_budget[$value['category']] = $value['amount'];
		}
		return $st_budget;
	}
}

?>