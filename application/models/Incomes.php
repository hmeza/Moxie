<?php

class Incomes extends Transactions {
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

	/**
	 * Delete row by id, with user_owner check.
	 * @param int $id
	 * @param int $user
	 * @return int
	 * @throws Zend_Db_Select_Exception
	 */
	public function delete($id, $user) {
		$s_where = $this->select()
				->from($this->_name)
				->where('id = ?', $id)
				->where('user_owner = ?', $user)
				->getPart(Zend_Db_Select::SQL_WHERE);
		return parent::delete(implode(" ", $s_where));
	}
}