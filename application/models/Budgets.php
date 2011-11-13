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
	 * @author	hmeza
	 * @param int $user_id
	 * @return	array
	 */
	public function getBudget($user_id) {
		$st_data = $this->_db->fetchAll(
						$this->_db->select()
								->from('budgets')
								->where('user_owner = '.$user_id)
								->where('date_ended IS NULL')
					);
		$st_budget = array();
		foreach($st_data as $key => $value) {
			$st_budget[$value['category']] = $value['amount'];
		}
		return $st_budget;
	}
	
	/**
	 * 
	 * Creates a snapshot of current budget, leaves it useful for stats
	 * and creates a new budget to use from this point forward.
	 * @author	hmeza
	 * @since	2011-11-12
	 * @param int $user_id
	 * @return	array
	 */
	public function snapshot($user_id) {
		// get current id's
		$st_currentBudget = $this->getBudget($user_id);
		// mark current budget with end date		
		$st_data = array('date_ended'	=> date('Y-m-d h:i:s'));
		$this->_db->beginTransaction();
		try {
			$this->_db->update($this->_name, $st_data,
				'user_owner = '.$user_id.' AND date_ended IS NULL');
			// duplicate latest budget
			foreach ($st_currentBudget as $key => $value) {
				$st_data = array(
					'user_owner'	=>	$user_id,
					'category'		=>	$key,
					'amount'		=>	$value,
					'date_created'	=>	date('Y-m-d h:i:s')
				);
				$this->_db->insert($this->_name, $st_data);
			}
			$this->_db->commit();
		}
		catch (Exception $e) {
			$this->_db->rollBack();
			throw $e;
		}
		// finally get the current budget for this user
		return $this->getBudget($user_id);
	}
	
	/**
	 * 
	 * Returns all budgets applicable this year.
	 * @author	hmeza
	 * @since	2011-11-12
	 * @param unknown_type $user_id
	 * @return	array
	 */
	public function getYearBudgets($user_id) {
		$st_data = $this->_db->fetchAll(
						$this->_db->select()
								->from('budgets')
								->where('user_owner = '.$user_id)
								->where('YEAR(date IS NULL')
					);
		$st_budget = array();
		foreach($st_data as $key => $value) {
			$st_budget[$value['category']] = $value['amount'];
		}
		return $st_budget;
	}
}

?>