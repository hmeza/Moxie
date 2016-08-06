<?php

include_once 'Zend/Db/Table.php';
include_once 'Zend/Registry.php';
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

	public function getLastBudgetByCategoryId($i_categoryPK) {
		try {
			$o_budget = $this->fetchRow(
					$this->select()
							->where('category = ' . $i_categoryPK)
							->where('date_ended IS NULL')
			);
		}
		catch(Exception $e) {
			error_log($e->getMessage());
			$o_budget = null;
		}
		return $o_budget;
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
	 * @param int $user_id
	 * @param int $i_year
	 * @return	array
	 */
	public function getYearBudgets($user_id, $i_year = null) {
		if (!isset($i_year)) $i_year = date('Y');
		$st_yearBudget = array();
		for ($i=1;$i<13;$i++) {
			$s_nextMonthDate = ($i == 12)
					? strtotime('-1 day', mktime(23, 59, 59, 1, 1, $i_year+1))
					: strtotime('-1 day', mktime(23, 59, 59, $i+1, 1, $i_year));
			$st_data = $this->_db->fetchAll(
				$this->_db->select()
						->from('budgets')
						->where('user_owner = '.$user_id)
						->where('YEAR(date_ended) = '.$i_year.' OR date_ended IS NULL')
						->where('unix_timestamp(date_created) <= '.$s_nextMonthDate)
						->where('unix_timestamp(date_ended) >= '.$s_nextMonthDate.' OR date_ended IS NULL')
						->order('date_created ASC')
				);
			if (empty($st_data)) {
				$st_data = $this->_db->fetchAll(
					$this->_db->select()
							->from('budgets')
							->where('user_owner = '.$user_id)
							->where('YEAR(date_ended) = '.$i_year.' OR date_ended IS NULL')
							->where('date_ended IS NULL')
							->order('date_created ASC')
					);
			}
			$st_budget = array();
			$s_currentDate = null;
			foreach($st_data as $key => $value) {
				$st_budget[$value['category']] = $value['amount'];
			}
			$st_yearBudget[$i] = $st_budget;
		}
		return $st_yearBudget;
	}

	/**
	 *
	 */
	public function getBudgetsDatesList() {
		$st_budgetsList = array();
		$st_budgetsListObjects = $this->fetchAll(
				$this->select()
						->from("budgets", array('DISTINCT(date_created) as date_created'))
						->where('user_owner = ?', $_SESSION['user_id'])
						->where('date_ended IS NOT NULL')
		);
		foreach($st_budgetsListObjects as $target => $budget) {
			$st_budgetsList[] = $budget->toArray();
		}
		return $st_budgetsList;
	}

	/**
	 * @fixme Do not use date
	 * @return bool|int
	 */
	public function delete($user, $date) {
		try {
			$select = $this->select()
					->where('user_owner = ?', $user)
					->where('date_created = ?', $date)
					->getPart(Zend_Db_Select::WHERE);
			return parent::delete(implode(" ",$select));
		}
		catch(Exception $e) {
			error_log($e->getMessage());
			return false;
		}
	}
}
