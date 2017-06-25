<?php

class SharedExpensesSheet extends Zend_Db_Table_Abstract {
	private $database;
	protected $_name = 'shared_expenses_sheets';
	protected $_primary = 'id';
	
	public function __construct() {
		global $db;
		$this->database = $db;
		$this->_db = Zend_Registry::get('db');
	}
	
	public function get_by_user_owner($owner_id) {
		return $this->fetchAll("user_owner = ?", $owner_id)->toArray();
	}
	
	public function get_by_user_match($user_id) {
		$select = $this->select()
			->from(array($this->_name => 's'), array('*'))
			->joinInner(array('shared_expenses_sheet_users' => 'su'), 'su.sheet_id = s.id')
			->where('n.user_owner = ?', $user_id)
			->orWhere('su.id_user = ?', $user_id);
		return $this->fetchAll();
	}
	
	public function get_by_unique_id($id) {
		if (is_null($id)) {
			error_log("null id received when getting sheet");
			return null;
		}
		$row = $this->fetchRow('unique_id = "' . $id . '"')->toArray();
		// now fetch expenses, order by date
		$sharedExpense = new SharedExpenses();
		$list = $sharedExpense->fetchAll('id_sheet = ' . $row['id'], 'date ASC');
		$row['expenses'] = array();
		$distinct_users = 0;
		$distinct_users_list = array();
		foreach($list as $l) {
			if (!in_array($l['id_sheet_user'], $distinct_users_list)) {
				$distinct_users++;
				$distinct_users_list[] = $l['id_sheet_user'];
			}
			$row['expenses'][] = $l->toArray();
		}
		$row['distinct_users'] = $distinct_users;
		$row['distinct_users_list'] = $distinct_users_list;
		return $row;
	}
}
