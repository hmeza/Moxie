<?php

class Incomes extends Zend_Db_Table_Abstract {
	
	protected $_name = 'incomes';
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
		if ($i_year==0) $i_year = date('Y');
		$s_category = ($i_category != 0) ? "i.category = ".$i_category : "1=1";
		$query = $this->_db->select()
		->from(array('i'=>'incomes'),array(
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
					->where('YEAR(i.date) = '.$i_year)
					->where($s_month)
					->where('i.user_owner = '.$user_id)
					->where($s_category)
					->order('i.date asc');
					$stmt = $this->_db->query($query);
					$result = $stmt->fetchAll();
					return $result;
	}
}
?>
