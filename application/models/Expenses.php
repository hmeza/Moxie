<?php

include_once 'Zend/Db/Table.php';
include_once 'Zend/Registry.php';
/**
 * Expenses model.
 */
class Expenses extends Zend_Db_Table_Abstract {
	private $database;
	protected $_name = 'expenses';
	protected $_primary = 'id';
	
	public function __construct() {
		global $db;
		$this->database = $db;
		$this->_db = Zend_Registry::get('db');
	}
	
	/**
	 * Gets expenses from a given user, month and year.
	 * If i_category is not set or is set to zero, all categories are retrieved.
	 * @author	hmeza
	 * @since	2011-01-03
	 * @param	int $user_id
	 * @param	int $month
	 * @param	int $year
	 * @param	int $i_category
	 */
	public function getExpenses($user_id, $month, $year, $i_category = 0) {
		$s_category = ($i_category != 0) ? "e.category = ".$i_category : "1=1";
		$query = $this->database->select()
		->from(array('e'=>'expenses'),array(
					'id'	=>	'e.id',
					'user_owner'	=>	'e.user_owner',
					'amount'		=>	'e.amount',
					'note'			=>	'e.note',
					'expense_date'	=>	'e.expense_date',
					'in_sum'		=>	'e.in_sum'
					))
					->joinLeft(array('c'=>'categories'), 'c.id = e.category', array(
					'name'	=>	'c.name',
					'description'	=>	'c.description',
					'category'	=> 'c.id'
					))
					->where('YEAR(e.expense_date) = '.$year)
					->where('MONTH(e.expense_date) = '.$month)
					->where('e.user_owner = '.$user_id)
					->where($s_category)
					->order('e.expense_date asc');
					$stmt = $this->database->query($query);
					$result = $stmt->fetchAll();
					return $result;
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
				->from(array('e'=>'expenses'),array(
						'id'			=>	'e.id',
						'user_owner'	=>	'e.user_owner',
						'amount'		=>	'e.amount',
						'note'			=>	'e.note',
						'expense_date'	=>	'e.expense_date',
						'in_sum'		=>	'e.in_sum',
						'category'		=>	'e.category'
						))
				->where('id = '.$i_expensePK);
			$stmt = $this->database->query($query);
			$result = $stmt->fetch();
		} catch (Expenses $e) {
			error_log("Exception caught in ".__CLASS__."::".__FUNCTION__." on line ".$e->getLine().": ".$e->getMessage());
		}
		return $result;
	}

	/**
	 * Inserts a new expense on DB
	 * @author	hmeza
	 * @since	2011-01-30
	 * @param	int $user_id
	 * @param	date $date
	 * @param	float $amount
	 * @param	int $category
	 * @param	text $note
	 */
	public function addExpense($user_id,$date,$amount,$category,$note) {
		$st_data = array(
			'user_owner'	=>	$user_id,
			'amount'		=>	$amount,
			'category'		=>	$category,
			'note'			=>	$note,
			'expense_date'	=>	$date
		);
		try {
			$query = $this->database->insert("expenses",$st_data);
		}
		catch (Exception $e) {
			error_log("Exception caught in ".__CLASS__."::".__FUNCTION__." on line ".$e->getLine().": ".$e->getMessage());
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
				->from("expenses","in_sum")
				->where("id = ".$i_expensePK);
				$stmt = $this->database->query($query);
				$result = $stmt->fetchAll();
				$result = $result[0];
				if ($result['in_sum'] == '1') {
					$up = 0;
				}
				else {
					$up = 1;
				}
	
				$where[] = "id = ".$i_expensePK;
				$query = $this->database->update("expenses", array("in_sum"=>$up), $where);
			}
			else {
				$st_data = array(
					'amount'	=>	$st_params['amount'],
					'category'	=>	$st_params['category'],
					'note'		=>	$st_params['note'],
					'expense_date'	=>	$st_params['date']
					
				);
				$s_where = 'id = '.$st_params['id'];
				$this->database->update('expenses',$st_data,$s_where);
			}
		} catch (Exception $e) {
			error_log("Exception caught in ".__CLASS__."::".__FUNCTION__." on line ".$e->getLine().": ".$e->getMessage());
		}
	}
		
	/**
	 * Returns the most used expenses for the user specified.
	 * @param int $i_userOwner
	 * @return array, each position contains number_of_rows, category, note, category_id, name
	 */
	public function getMostFrequentExpenses($i_userOwner) {
		$query = " e.user_owner = 1 group by category,note order by number_of_rows;";
		try {
			$query = $this->database->select()
				->from(array("e" => "expenses"), array("count(e.id) as number_of_rows", "category", "note"))
				->joinInner(array("c" => "categories"), "c.id = e.category", array("id as category_id", "name"))
				->where("e.user_owner = ?", $i_userOwner)
				->where("e.expense_date >= ?", date('Y-m-d H:i:s', strtotime("-3 months")))
				->group("category,note")
				->order("number_of_rows DESC")
				->limit(self::MOST_FREQUENT_EXPENSES_LIMIT);
			$query = str_replace("`", "", $query);	
			$rows = $this->database->fetchAll($query);
		}
		catch (Exception $e) {
			error_log("Exception caught in ".__CLASS__."::".__FUNCTION__." on line ".$e->getLine().": ".$e->getMessage()."\n", 3, '/tmp/hmeza.log');
		}
		return $rows;
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
				->from(array('e' => 'expenses'), array('note', 'count(id) as number', 'avg(amount)', 'max(amount)', 'min(amount)'))
				->where('user_owner = ?', $i_userOwner)
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
}

?>
