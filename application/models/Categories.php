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

include_once '../Zend/Zend/Registry.php';
class Categories extends Zend_Db_Table_Abstract {
	const EXPENSES = 1;
	const INCOMES = 2;
	const BOTH = 3;

	private $database;
	protected $_name = 'categories';
	protected $_primary = 'id';
	
	public function __construct() {
		global $db;
		$this->database = $db;
		$this->_db = Zend_Registry::get('db');
	}
	
	public function addCategory($data) {
		try {
			$this->database->insert('categories',
				array(
					'user_owner' => $_SESSION['user_id'],
					'parent' => $data['parent'],
					'name' => $data['name'],
					'description' => $data['description'],
					'type'	=>	$data['type']
				));
		} catch (Exception $e) {
			echo 'Exception caught on '.__CLASS__.', '.__FUNCTION__.'('.$e->getLine().'), message: '.$e->getMessage();
		}
	}
	
	public function editCategory($category_id, $name, $description) {
		try {
			$query = $this->database->update('categories',
				array(
					'name' => $name,
					'description' => $description
				));
		} catch (Exception $e) {
			echo 'Exception caught on '.__CLASS__.', '.__FUNCTION__.'('.$e->getLine().'), message: '.$e->getMessage();
		}
	}
	
	public function getCategoriesByUser($i_typeFilter) {
		//select distinct(c2.id),c1.parent,c1.name, c2.name
		//from categories c1 left join categories c2 on c2.parent = c1.id
		//where c2.id is not null
		//order by c1.parent,c2.parent;
		if ($i_typeFilter == 3) $s_typeFilter = '1 = 1';
		else $s_typeFilter = 'c2.type = 3 OR c2.type = '.$i_typeFilter;
		
		$query = $this->database->select()
			->from(array('c1'=>'categories'), array(
				'id1'	=>	'distinct(c2.id)',
				'parent1'	=>	'c1.id',
				'name1'	=>	'c1.name',
				'name2'	=>	'c2.name',
				'type'	=>	'c2.type'
			))
			->joinLeft(array('c2'=>'categories'),'c2.parent = c1.id',array())
			->where('c1.user_owner = ?', $_SESSION['user_id'])
			->where('c2.id is not null')
			->where($s_typeFilter)
			->order('c1.parent')
			->order('c2.parent');
		$stmt = $this->database->query($query);
		return $stmt->fetchAll();
	}
	
	public function getCategoriesByParent($parent_id) {
		$query = $db->select()
			->from('categories')
			->where('parent = ?', $parent_id);
			//->order('');
		$stmt = $db->query($query);
		$result = $stmt->fetchAll();
		return $result;
	}
	
	/**
	 * @desc	Get 3 level categories tree, only with leaves
	 * @author	hmeza
	 * @since	2011-04-23
	 * @return	array
	 */
	public function getCategoriesTree() {
		try {
			$query = $this->database->select()
				->from(array('c1'=>'categories'), array(
					'id1'	=>	'c1.id',
					'name1'	=>	'c1.name',
					'id2'	=>	'c2.id',
					'name2'	=>	'c2.name',
					'id3'	=>	'c3.id',
					'name3'	=>	'c3.name'
				))
				->joinLeft(array('c2'=>'categories'),'c2.parent = c1.id',array())
				->joinLeft(array('c3'=>'categories'),'c3.parent = c2.id',array())
				->where('c1.user_owner = ?', $_SESSION['user_id'])
				->where('c1.name IS NULL')
				->order('c2.id');
			$stmt = $this->database->query($query);
			return $stmt->fetchAll();
		}
		catch (Exception $e) {
			echo 'Exception caught on '.__CLASS__.', '.__FUNCTION__.'('.$e->getLine().'), message: '.$e->getMessage();
		}
		return array();
	}
	
	public function getCategoriesForView($i_typeFilter) {
		// get categories and prepare them for view
		$s_categories = $this->getCategoriesByUser($i_typeFilter);
		$formCategories = array();
		foreach($s_categories as $key => $value) {
			$formCategories[$value['id1']] = $value['name2'];
			if (!empty($value['name1'])) {
				$formCategories[$value['id1']] = $value['name1'].' - '.$formCategories[$value['id1']];
			}
		}
		return $formCategories;
	}
	
	public function getCategoriesForSelect($i_typeFilter) {
		// get categories and prepare them for view
		$s_categories = $this->getCategoriesByUser($i_typeFilter);
		// get root category
		$st_parent = $this->fetchRow($this->select()
			->where('user_owner = '.$_SESSION['user_id'])
			->where('parent IS NULL'));

		$formCategories = array();
		$formCategories[$st_parent->id] = 'New category';
		foreach($s_categories as $key => $value) {
			$formCategories[$value['id1']] = $value['name2'];
			if (!empty($value['name1'])) {
				$formCategories[$value['id1']] = $value['name1'].' - '.$formCategories[$value['id1']];
			}
		}
		return $formCategories;
	}
}

?>