<?php

class Incomes extends Zend_Db_Table_Abstract {
	
	protected $_name = 'transactions';
	protected $_primary = 'id';
	
	public function __construct() {
		$this->_db = Zend_Registry::get('db');
	}
	
	/**
	 * 
	 * Get incomes for a given user, month and year.
	 * If month is null, not set or equal to zero, all months (1..12) are retrieved.
	 * If year is null, not set or equal to zero, current year is retrieved.
	 * If category is null, not set or equal to zero, all categories are retrieved.
	 * @param int $user_id
	 * @param int $i_month
	 * @param int $i_year
	 * @param int $i_category
	 */
	public function getIncomes($user_id, $i_month = 0, $i_year = 0, $i_category = 0) {
		$s_month = ($i_month == 0) ? '1=1' : 'MONTH(i.date) = '.$i_month;
		$query = $this->select()
			->setIntegrityCheck(false)
		    ->from(array('i'=> $this->_name),array(
					'id'	=>	'i.id',
					'user_owner'	=>	'i.user_owner',
					'amount'		=>	'i.amount',
					'note'			=>	'i.note',
					'date'			=>	'i.date',
					'in_sum'		=>	'i.in_sum'
					))
            ->joinLeft(array('c'=>'categories'), 'c.id = i.category', array(
            'name'	=>	'c.name',
            'description'	=>	'c.description',
            'category'	=> 'c.id'
            ))
            ->where($s_month)
            ->where('i.user_owner = '.$user_id)
            ->where('amount >= 0')       // incomes only
            ->order('i.date asc');

		if(!empty($i_year)) {
			$query = $query->where('YEAR(i.date) = ?', $i_year);
		}
		if(!empty($i_category)) {
			$query = $query->where('category = ?', $i_category);
		}
        $result = $this->fetchAll($query);
        return $result;
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
