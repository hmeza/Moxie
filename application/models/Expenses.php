<?php

/**
 * Expenses model.
 */
class Expenses extends Transactions {
	/** @var int Number of frequent expenses to retrieve */
	const MOST_FREQUENT_EXPENSES_LIMIT = 5;

	private $database;

	public function __construct() {
		global $db;
		$this->database = $db;
		parent::__construct();
	}

	/**
	 * @param int $user_id
	 * @param array $st_searchParams
	 * @return array
	 * @throws Zend_Exception
	 */
	public function getExpenses($user_id, $st_searchParams) {
		try {
			$s_select = $this->select()
					->setIntegrityCheck(false)
					->from(
							array('i' => $this->_name),
							array(
									'sum(e.amount)' => new Zend_Db_Expr('-sum(i.amount)')
							)
					)
					->joinLeft(
							array('c' => 'categories'),
							'i.category = c.id',
							array(
									'id' => 'c.id',
									'name' => new Zend_Db_Expr('CONCAT(COALESCE(CONCAT(c0.name, " - "), ""), c.name)'),
							)
					)
					->joinLeft(array('c0' => 'categories'), 'c.parent = c0.id', array())
					->where('i.user_owner = ' . $user_id)
					->where('i.in_sum = 1')
					->where('amount < 0');

			$this->query_filter($s_select, $st_searchParams);

			$s_select = $s_select
					->group('c.id')
					->order(array('c.id'));

			$st_list = $this->fetchAll($s_select);
		}
		catch(Exception $e) {
			error_log($e->getMessage());
			$st_list = array();
		}
		return $st_list;
	}

	/**
	 * Retrieve an expense by its PK
	 * @author	hmeza
	 * @since	2011-02-08
	 * @param	int $i_expensePK
	 */
	public function getExpenseByPK($i_expensePK) {
		try {
			$query = $this->database->select()
				->from(array('e'=> $this->_name),array(
						'id'			=>	'e.id',
						'user_owner'	=>	'e.user_owner',
						'amount'		=>	new Zend_Db_Expr('-e.amount'),
						'note'			=>	'e.note',
						'date'	        =>	new Zend_Db_Expr('DATE(e.date)'),
						'in_sum'		=>	'e.in_sum',
						'category'		=>	'e.category'
						))
				->where('id = '.$i_expensePK)
                ->where('amount < 0');
			$stmt = $this->database->query($query);
			$result = $stmt->fetch();
		} catch (Exception $e) {
			error_log($e->getMessage(),3,'/tmp/hmeza.log');
			error_log("Exception caught in ".__CLASS__."::".__FUNCTION__." on line ".$e->getLine().": ".$e->getMessage());
		}
		return $result;
	}

	/**
	 * Inserts a new expense on DB
	 * @author	hmeza
	 * @param	int $user_id
	 * @param	string $date
	 * @param	float $amount
	 * @param	int $category
	 * @param	string $note
	 * @return int
	 */
	public function addExpense($user_id,$date,$amount,$category,$note) {
		$st_data = array(
			'user_owner'	=>	$user_id,
			'amount'		=>	-$amount,
			'category'		=>	$category,
			'note'			=>	$note,
			'date'	=>	$date
		);
		try {
			return $this->insert($st_data);
		}
		catch (Exception $e) {
			error_log(__METHOD__.": ".$e->getMessage());
		}
	}

	/**
	 * Updates an expense by setting in_sum to 0 or 1
	 * @author	hmeza
	 * @since	2011-02-03
	 * @param	int $i_expensePK
	 * @param	array $st_params
	 * @todo	correct this bullshit. updateExpense should do only one type of update
	 */
	public function updateExpense($i_expensePK, $st_params = null) {
		try {
			if ($st_params == null) {
				$query = $this->database->select()
				->from($this->_name,"in_sum")
				->where("id = ?", $i_expensePK);
				$stmt = $this->database->query($query);
				$result = $stmt->fetchAll();
				$result = $result[0];
                $up = ($result['in_sum'] == '1') ? 0 : 1;

				$where[] = "id = ".$i_expensePK;
				$this->database->update($this->_name, array("in_sum"=>$up), $where);
			}
			else {
				$st_data = array(
					'amount'	=>	-$st_params['amount'],
					'category'	=>	$st_params['category'],
					'note'		=>	$st_params['note'],
					'date'	=>	$st_params['date']
					
				);
				$s_where = 'id = '.$st_params['id'];
				$this->database->update($this->_name,$st_data,$s_where);
			}
		} catch (Exception $e) {
			error_log("Exception caught in ".__CLASS__."::".__FUNCTION__." on line ".$e->getLine().": ".$e->getMessage());
		}
	}
	
	/**
	 * Retrieve a list of item notes, number of times used, sum expent,
	 * average of the spents, minimum amount and maximum amount.
	 * @param int $i_userOwner
	 * @return array
	 */
	public function getPerItemData($i_userOwner) {
		//select note, count(id) as number, sum(amount), avg(amount), max(amount), min(amount)  from expenses where user_owner = 1 group by note order by number DESC;
		try {
			$query = $this->database->select()
				->from(array('e' => $this->_name), array('note', 'count(id) as number', 'avg(amount)', 'max(amount)', 'min(amount)'))
				->where('user_owner = ?', $i_userOwner)
                ->where('amount < 0')
				->group('note')
				->order('number DESC')
				->limit(10);
			$rows = $this->database->fetchAll($query);
		}
		catch(Exception $e) {
			error_log(__METHOD__.": ".$e->getMessage());
		}
		return $rows;
	}

    /**
     * @param int $i_dateLimit
     * @param string $s_category
     * @return mixed
     */
    public function getMonthExpensesData($i_userId, $i_dateLimit) {
        $s_query = $this->select()
            ->from($this->_name, array(
		            'year' => new Zend_Db_Expr('YEAR('.$this->_name.'.date)'),
		            'month' => new Zend_Db_Expr('MONTH('.$this->_name.'.date)'),
		            'amount' => new Zend_Db_Expr('-sum('.$this->_name.'.amount)'))
            )
            ->where('in_sum = 1')
            ->where('user_owner = ?', $i_userId)
            ->where('date >= "'.$i_dateLimit.'"')
            ->where('amount < 0')
            ->group(array(new Zend_Db_Expr('MONTH(date)'), new Zend_Db_Expr('YEAR(date)')))
            ->order(array(new Zend_Db_Expr('YEAR(date)'), new Zend_Db_Expr('MONTH(date)')));

        $o_rows = $this->database->fetchAll($s_query);

	    if(empty($o_rows)) {
		    // set default values to avoid error on empty data chart
		    $o_rows = array(
			    array(
				    'month' => date('m'),
				    'year' => date('Y'),
				    'amount' => 0
			    )
		    );
	    }
        return $o_rows;
    }

    public function getSum($userId, $key) {
        $s_select = $this->database->select()
            ->from($this->_name,
                array(
                    new Zend_Db_Expr('-SUM(amount) as sum')
                )
            )
            ->where("user_owner = ".$userId)
            ->where("category = ".$key)
            ->where('amount < 0');
        $st_data = $this->database->fetchRow($s_select);
        return $st_data;
    }

    public function getStats($userId, $key) {
        $s_select = $this->database->select()
            ->from($this->_name,
                array(
                    new Zend_Db_Expr('-SUM(amount) as sum'),
                    new Zend_Db_Expr('AVG(amount) as avg')
                )
            )
            ->where("user_owner = ".$userId)
            ->where("category = ".$key)
            ->where("YEAR(date) = ".date('Y'))
            ->where('amount < 0');
        $st_data = $this->database->fetchRow($s_select);
        return $st_data;
    }
}
