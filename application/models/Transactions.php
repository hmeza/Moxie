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
            if ((int)$st_searchParams['category_search'] >= 0) {
                $query = $query->where('i.category = ?', $st_searchParams['category_search']);
            } else {
                $query = $query->having('c.id IS NULL');
            }
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
			$query = $query->where('ABS(i.amount) >= ?', $st_searchParams['amount_min']);
		}
		if(!empty($st_searchParams['amount_max'])) {
			$query = $query->where('ABS(i.amount) <= ?', $st_searchParams['amount_max']);
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
	 * @return array
	 */
	public function get($user_id, $type = Categories::EXPENSES, $st_searchParams) {
		try {
			$query = $this->select()
					->setIntegrityCheck(false)
					->from(
							array('i' => $this->_name),
							array(
									'id' => 'i.id',
									'user_owner' => 'i.user_owner',
									'amount' => new Zend_Db_Expr('ABS(i.amount)'),
									'note' => 'i.note',
									'date' => 'i.date',
									'in_sum' => 'i.in_sum'
							)
					)
					->joinLeft(
							array('c' => 'categories'),
							'c.id = i.category',
							array(
									'name' => 'c.name',
									'description' => 'c.description',
									'category' => 'c.id'
							)
					)
					->where('i.user_owner = ' . $user_id);

			$this->query_filter($query, $st_searchParams);

			if ($type == Categories::EXPENSES) {
				$query = $query->where('amount < 0');
			} else {
				if ($type == Categories::INCOMES) {
					$query = $query->where('amount >= 0');
				}
			}

			if (isset($st_searchParams['o'])) {
				$filter = $st_searchParams['o'];
				$order = 'desc';
				if($filter[0] == '-') {
					$order = 'asc';
					$filter = substr($filter, 1, strlen($filter) - 1);
				}
				if ($filter == 'date') {
					$filter = 'i.date';
				}
				if($filter == 'amount') {
					$filter = 'i.amount';
				}
				$query = $query->order($filter.' '.$order);
			}
			else {
				$query = $query->order('i.date desc');
			}
			$result = $this->fetchAll($query);
		}
		catch(Exception $e) {
			error_log($e->getMessage());
			$result = array();
		}
        return $result;
	}

	/**
	 * Delete row by id, with user_owner check.
	 * @param int $id
	 * @param int $user
	 * @return int
	 * @throws Zend_Db_Select_Exception
	 * @throws Exception
	 */
	public function delete($id, $user) {
		$s_where = $this->select()
				->from($this->_name)
				->where('id = ?', $id)
				->where('user_owner = ?', $user)
				->getPart(Zend_Db_Select::SQL_WHERE);
		$deleted = parent::delete(implode(" ", $s_where));
		error_log("deleted rows: ".$deleted);
		if($deleted == 0) {
			throw new Exception("Error deleting transaction ".$id." for user ".$user);
		}
	}

	public function getYearly($user, $year) {
		$select = $this->select()
			->setIntegrityCheck(false)
			->from(array('t' => 'transactions'), array('category', 'sum(amount)'))
			->joinLeft(array('c' => 'categories'), 't.category = c.id', array())
			->where('t.user_owner = ?', $user)
			->where('year(date) = ?', $year)
			->group(array('year(date)', 'category'));
		return $this->fetchAll($select)->toArray();
	}

	public function getFavourites($user) {
		$select = $this->select()
				->setIntegrityCheck(false)
				->from(array('f' => 'favourites'), array())
				->joinInner(array('t' => 'transactions'), 'f.id_transaction = t.id', array('t.id', 't.amount', 't.note', 't.category'))
				->where('t.user_owner = ?', $user);
		$results = $this->fetchAll($select)->toArray();
		$tagsModel = new Tags();
		foreach ($results as $key => $result) {
			// use $result['id'] to retrieve tags
			$tags = array();
			foreach($tagsModel->getTagsForTransaction($result['id']) as $t) {
				$tags[] = str_replace("\\'", "'", $t);
			}
			$results[$key]['tags'] = $tags;
		}
		return $results;
	}
}
