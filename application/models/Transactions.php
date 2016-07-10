<?php

class Transactions extends Zend_Db_Table_Abstract {
	
	protected $_name = 'transactions';
	protected $_primary = 'id';
	
	public function __construct() {
		$this->_db = Zend_Registry::get('db');
	}

	/**
	 * @note use i as transactions table alias.
	 * @param Zend_Db_Select $query
	 * @param $st_searchParams
	 * @return $query modified
	 */
	protected function query_filter($query, $st_searchParams) {
		if(!empty($st_searchParams['category_search'])) {
			$query = $query->where('i.category = ?', $st_searchParams['category_search']);
		}
		if(!empty($st_searchParams['note_search'])) {
			$query = $query->where('i.note like ?', '%'.$st_searchParams['note_search'].'%');
		}
		if(!empty($st_searchParams['tag_search'])) {
			$s_tag = urldecode($st_searchParams['tag_search']);
			$query = $query->joinInner(array('tt' => 'transaction_tags'), 'tt.id_transaction = i.id', array())
					->joinInner(array('t' => 'tags'), 't.id = tt.id_tag', array())
					->where('t.name = ?', $s_tag);
		}
		if(!empty($st_searchParams['amount_min'])) {
			$query = $query->where('ABS(i.amount) <= ?', $st_searchParams['amount_min']);
		}
		if(!empty($st_searchParams['amount_max'])) {
			$query = $query->where('ABS(i.amount) >= ?', $st_searchParams['amount_max']);
		}
		if(!empty($st_searchParams['date_min'])) {
			$query = $query->where('i.date >= ?', $st_searchParams['date_min']);
		}
		if(!empty($st_searchParams['date_max'])) {
			$query = $query->where('i.date <= ?', $st_searchParams['date_max']);
		}
	}
	
	/**
	 * 
	 * Get incomes for a given user, month and year.
	 * If month is null, not set or equal to zero, all months (1..12) are retrieved.
	 * If year is null, not set or equal to zero, current year is retrieved.
	 * If category is null, not set or equal to zero, all categories are retrieved.
	 * @param int $user_id
	 * @param array $st_searchParams
	 */
	public function get($user_id, $type = Categories::EXPENSES, $st_searchParams) {
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
            ->where('i.user_owner = '.$user_id);

		$this->query_filter($query, $st_searchParams);

        if($type == Categories::EXPENSES) {
            $query = $query->where('amount < 0');
        }
        else if($type == Categories::INCOMES) {
            $query = $query->where('amount >= 0');
        }
		$query = $query->order('i.date asc');
        $result = $this->fetchAll($query);
        return $result;
	}
}
