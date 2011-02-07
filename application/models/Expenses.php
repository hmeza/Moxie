<?php

/**
 * 
 * @desc
 * 			id
 * 			user_owner
 * 			parent
 * 			name
 * 			description
 * @author root
 *
 */

class Expenses {
	private $database;
	
	public function __construct() {
		global $db;
		$this->database = $db;
	}
	
	/**
	 * @desc	Gets expenses from a given user, month and year
	 * @author	hmeza
	 * @since	2011-01-03
	 * @param	int $user_id
	 * @param	int $month
	 * @param	int $year
	 */
	public function getExpenses($user_id, $month, $year) {
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
					'description'	=>	'c.description'
				))
			->where('YEAR(e.expense_date) = '.$year)
			->where('MONTH(e.expense_date) = '.$month)
			->where('e.user_owner = '.$user_id)
			->order('e.expense_date asc');
		$stmt = $this->database->query($query);
		$result = $stmt->fetchAll();
		return $result;
	}
	
	/**
	 * @desc	Inserts a new expense on DB
	 * @author	hmeza
	 * @since	2011-01-30
	 * @param	int $user
	 * @param	date $date
	 * @param	float $amount
	 * @param	int $category
	 * @param	text $note
	 */
	public function addExpense($user,$date,$amount,$category,$note) {
		$st_data = array(
			'user_owner'	=>	$user,
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
	 * @desc	Deletes an expense
	 * @author	hmeza
	 * @since	2011-01-30
	 * @param	int $expensePK
	 */
	public function deleteExpense($expensePK) {
		try {
			$query = $this->database->delete('expenses','id = '.$expensePK);
		} catch (Exception $e) {
			error_log("Exception caught in ".__CLASS__."::".__FUNCTION__." on line ".$e->getLine().": ".$e->getMessage());
		}
	}
	
	/**
	 * @desc	Updates expenses by setting in_sum to option value, filtering by year and month
	 * @author	hmeza
	 * @since	2011-02-06
	 * @param $i_option 1 or 0
	 * @param $i_year
	 * @param $i_month
	 * @todo	Implement to update different attributes
	 * @todo	Implement to check user owner
	 */
	public function updateAllExpenses($i_option, $i_year, $i_month) {
		$where[] = "MONTH(expense_date) = ".$i_month;
		$where[] = "YEAR(expense_date) = ".$i_year;
		$where[] = "user_owner = 1";
		try {
			error_log(print_r($where,true));
			$query = $this->database->update("expenses", array("in_sum"=>$i_option), $where);
		} catch (Exception $e) {
			error_log("Exception caught in ".__CLASS__."::".__FUNCTION__." on line ".$e->getLine().": ".$e->getMessage());
		}
	}
	
	/**
	 * @desc	Updates an expense by setting in_sum to 0 or 1
	 * @author	hmeza
	 * @since	2011-02-03
	 * @param unknown_type $i_expensePK
	 * @todo	Implement to update different expense attributes, as a complete edit function
	 */
	public function updateExpense($i_expensePK) {
		try {
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
		} catch (Exception $e) {
			error_log("Exception caught in ".__CLASS__."::".__FUNCTION__." on line ".$e->getLine().": ".$e->getMessage());
		}
	}
}

?>