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
			->from(array('s' => $this->_name), array('*'))
			->setIntegrityCheck(false)
			->joinLeft(array('su' => 'shared_expenses_sheet_users'), 'su.id_sheet = s.id', array())
			->where('s.user_owner = ?', $user_id)
			->orWhere('su.id_user = ?', $user_id)
			->order('s.id asc');
		return $this->fetchAll($select);
	}
	
	public function get_by_unique_id($id) {
		if (is_null($id)) {
			error_log("null id received when getting sheet");
			return null;
		}
		$row = $this->fetchRow('unique_id = "' . $id . '"')->toArray();
		// now fetch expenses, order by date
		$sharedExpense = new SharedExpenses();
		$list = $sharedExpense->fetchAll('id_sheet = ' . $row['id'], array('date ASC', 'id ASC'));
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
		$row['users'] = $this->getUsersForSheet($row['unique_id']);
		// add users that do not have any expense but exist in the sheet
		foreach($row['users'] as $key => $u) {
			if(!in_array($u['id_sheet_user'], $row['distinct_users_list'])) {
				$row['distinct_users_list'][] = $u['id_sheet_user'];
				$row['distinct_users']++;
			}
		}
		return $row;
	}
	
	private function getUsersForSheet($sheet_id) {
		try {
			$nameCoalesce = new Zend_Db_Expr('COALESCE(u.login, sesu.email) as login');
			$emailCoalesce = new Zend_Db_Expr('COALESCE(u.email, sesu.email) as email');
			$select = $this->select()
				->setIntegrityCheck(false)
				->from(array('ses' => 'shared_expenses_sheets'), array(new Zend_Db_Expr ('0 as total')))
				->joinInner(array('sesu' => 'shared_expenses_sheet_users'), 'ses.id = sesu.id_sheet', array('id as id_sheet_user'))
				->joinLeft(array('u' => 'users'), 'u.id = sesu.id_user', array("u.id as id_user", $nameCoalesce, $emailCoalesce))
				->where('ses.unique_id = ?', $sheet_id);
			return $this->fetchAll($select)->toArray();
		}
		catch(Exception $e) {
			return array();
		}
	}
}
