<?php

class SharedExpenses extends Zend_Db_Table_Abstract {
	
	protected $_name = 'shared_expenses';
	protected $_primary = 'id';
	
	public function __construct() {
		$this->_db = Zend_Registry::get('db');
	}
	
	public function getSheetByExpenseIdAndUserId($sharedExpenseId, $userId) {
		$select = $this->select()
			->from(array('se' => $this->_name), array())
			->setIntegrityCheck(false)
			->joinInner(array('ses' => 'shared_expenses_sheets'), 'se.id_sheet = ses.id', array('unique_id'))
			->joinInner(array('sesu' => 'shared_expenses_sheet_users'), 'sesu.id_sheet = ses.id', array('id'))
			->where('sesu.id_user = ?', $userId)
			->where('se.id = ?', $sharedExpenseId);
		error_log($select);
		try {
			$row = $this->fetchRow($select);
			if($row) {
				$row = $row->toArray();
			}
		}
		catch(Exception $e) {
			error_log($e->getMessage());
			return false;
		}
		return $row;
	}
}
