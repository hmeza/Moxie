<?php

class Transactions extends Zend_Db_Table_Abstract {
	
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
	public function get($user_id, $type = Categories::EXPENSES, $i_month = 0, $i_year = 0, $i_category = 0) {
		$query = $this->select()
			->setIntegrityCheck(false)
		    ->from(array('i'=> $this->_name),array(
					'id'	=>	'i.id',
					'user_owner'	=>	'i.user_owner',
					'amount'		=>	new Zend_Db_Expr('ABS(i.amount)'),
					'note'			=>	'i.note',
					'date'			=>	'i.date',
					'in_sum'		=>	'i.in_sum'
					))
            ->joinLeft(array('c'=>'categories'), 'c.id = i.category', array(
                'name'	=>	'c.name',
                'description'	=>	'c.description',
                'category'	=> 'c.id'
                ))
            ->where('i.user_owner = '.$user_id)
            ->order('i.date asc');

        if($type == Categories::EXPENSES) {
            $query = $query->where('amount < 0');
        }
        else if($type == Categories::INCOMES) {
            $query = $query->where('amount >= 0');
        }
        if(!empty($i_month)) {
            $query = $query->where('MONTH(i.date) = ?', $i_month);
        }
		if(!empty($i_year)) {
			$query = $query->where('YEAR(i.date) = ?', $i_year);
		}
		if(!empty($i_category)) {
			$query = $query->where('category = ?', $i_category);
		}
        $result = $this->fetchAll($query);
        return $result;
	}
}
?>
