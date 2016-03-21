<?php

class Incomes extends Transactions {
	
	protected $_name = 'transactions';
	protected $_primary = 'id';
	
	public function __construct() {
		$this->_db = Zend_Registry::get('db');
	}
	
    /**
     * @param int $userId
     * @return mixed
     */
    public function getYearlyIncome($userId) {
        $s_select = $this->_db->select()
            ->from($this->_name,array('sum(amount) as amount','YEAR(date) as date'))
            ->where('in_sum = 1')
            ->where('user_owner = ?', $userId)
            ->where('amount >= 0')
            ->group('YEAR(date)')
            ->order('YEAR(date)');
        $o_rows = $this->_db->fetchAll($s_select);

	    if(empty($o_rows)) {
		    // set default values for empty graph
		    $o_rows = array(
				array('date' => date('Y'), 'amount' => 0)
		    );
	    }

        return $o_rows;
    }
}
?>
