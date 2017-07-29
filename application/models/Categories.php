<?php

include_once 'Zend/Registry.php';
/**
 * Categories model.
 */
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
			error_log('Exception caught on '.__CLASS__.', '.__FUNCTION__.'('.$e->getLine().'), message: '.$e->getMessage());
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
			error_log('Exception caught on '.__CLASS__.', '.__FUNCTION__.'('.$e->getLine().'), message: '.$e->getMessage());
		}
	}
	
	/**
	 * 
	 * Gets categories for a given user.
	 * i_typeFilter stands for the type of category to retrieve. 
	 * @param int $i_typeFilter
	 */
	public function getCategoriesByUser($i_typeFilter) {
		if(empty($_SESSION) || empty($_SESSION['user_id'])) {
			throw new Exception("No user id found in session");
		}
		//select distinct(c2.id),c1.parent,c1.name, c2.name
		//from categories c1 left join categories c2 on c2.parent = c1.id
		//where c2.id is not null
		//order by c1.parent,c2.parent;
		if ($i_typeFilter == self::BOTH) $s_typeFilter = '1 = 1';
		else $s_typeFilter = 'c2.type = '.self::BOTH.' OR c2.type = '.$i_typeFilter;
		
		$query = $this->database->select()
			->from(array('c1'=>'categories'), array(
				'id1'	=>	'distinct(c2.id)',
				'parent1'	=>	'c1.id',
				'grandparent' => 'c1.parent',
				'name1'	=>	'c1.name',
				'name2'	=>	'c2.name',
				'type'	=>	'c2.type',
				'parent' => 'c2.parent'
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
			error_log('Exception caught on '.__CLASS__.', '.__FUNCTION__.'('.$e->getLine().'), message: '.$e->getMessage());
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
		global $st_lang;
		// get categories and prepare them for view
		$s_categories = $this->getCategoriesByUser($i_typeFilter);
		// get root category
		$st_parent = $this->fetchRow($this->select()
			->where('user_owner = '.$_SESSION['user_id'])
			->where('parent IS NULL'));

		$formCategories = array();
		$formCategories[$st_parent->id] = $st_lang['category_new'];
		foreach($s_categories as $key => $value) {
			$formCategories[$value['id1']] = $value['name2'];
			if (!empty($value['name1'])) {
				$formCategories[$value['id1']] = $value['name1'].' - '.$formCategories[$value['id1']];
			}
		}
		return $formCategories;
	}

	/**
	 * Returns the list of categories mounted as a tree.
	 * @param array $st_categories
	 * @return array
	 */
	public function mountCategoryTree($st_categories, $i_userId) {
		$st_parent = $this->fetchRow($this->select()
				->where('user_owner = '.$i_userId)
				->where('parent IS NULL'));
		$st_root = array(
				'id1'		=>	$st_parent->id,
				'parent1'	=>	null,
				'name1'		=>	null,
				'name2'		=>	'New category'
		);
		$st_parentCategories = array();
		$st_parentCategories[] = $st_root;
		foreach ($st_categories as $key => $value) {
			$st_parentCategories[] = $value;
		}
		return $st_parentCategories;
	}

	/**
	 * Mount the category tree for the current budget.
	 * @param array $st_categories
	 * @return array
	 */
	public function prepareCategoriesTree($st_categories) {
		$st_preparedTree = array();
		foreach($st_categories as $key => $value) {
			if (empty($value['id3'])) {
				$i_key = null;
				$st_value = null;
			}
			if (!empty($value['id3']) && $i_key == null) {
				$i_key = $value['id2'];
				$st_parentLine = array(
						'id1'	=>	$value['id1'],
						'name1'	=>	$value['name1'],
						'id2'	=>	$value['id2'],
						'name2'	=>	$value['name2'],
						'id3'	=>	null,
						'name3'	=>	null
				);
				$st_preparedTree[] = $st_parentLine;
			}
			$st_preparedTree[] = $value;
		}
		return $st_preparedTree;
	}

	/**
	 * @param $i_lastInsertId
	 * @return boolean
	 */
	public function insertCategoriesForRegisteredUser($i_lastInsertId) {
		// first insert root category
		$st_categoriesData = array(
				'user_owner' => $i_lastInsertId,
				'description' => 'root category'
		);
		// add default categories for the new user
		$i_rootCategory = $this->insert($st_categoriesData);
		$st_categoriesData = array(
				'user_owner' => $i_lastInsertId,
				'parent' => $i_rootCategory,
				'name' => 'Hogar',
				'description' => 'Hogar',
				'type' => 3
		);
		$this->insert($st_categoriesData);
		$st_categoriesData = array(
				'user_owner' => $i_lastInsertId,
				'parent' => $i_rootCategory,
				'name' => 'Comida',
				'description' => 'Comida',
				'type' => 3
		);
		$this->insert($st_categoriesData);
		$st_categoriesData = array(
				'user_owner' => $i_lastInsertId,
				'parent' => $i_rootCategory,
				'name' => 'Diversión',
				'description' => 'Salidas, cenas fuera, ocio, etc.',
				'type' => 3
		);
		$this->insert($st_categoriesData);
		$st_categoriesData = array(
				'user_owner' => $i_lastInsertId,
				'parent' => $i_rootCategory,
				'name' => 'Tecnología',
				'description' => 'Tecnología',
				'type' => 3
		);
		$this->insert($st_categoriesData);
		$st_categoriesData = array(
				'user_owner' => $i_lastInsertId,
				'parent' => $i_rootCategory,
				'name' => 'Regalos',
				'description' => 'Navidad, reyes, aniversarios, san Valentín, etc.',
				'type' => 3
		);
		$this->insert($st_categoriesData);
		$st_categoriesData = array(
				'user_owner' => $i_lastInsertId,
				'parent' => $i_rootCategory,
				'name' => 'Ropa',
				'description' => 'Ropa',
				'type' => 3
		);
		$this->insert($st_categoriesData);
		$st_categoriesData = array(
				'user_owner' => $i_lastInsertId,
				'parent' => $i_rootCategory,
				'name' => 'Varios',
				'description' => 'Otros gastos',
				'type' => 3
		);
		$this->insert($st_categoriesData);

		$o_foodCategory = $this->fetchRow(
				$this->select()->where('name = "Comida" AND user_owner = ' . $i_lastInsertId)
		);
		$st_categoriesData = array(
				'user_owner' => $i_lastInsertId,
				'parent' => $o_foodCategory->id,
				'name' => 'Casa',
				'description' => 'Comida comprada para casa',
				'type' => 3
		);
		$this->insert($st_categoriesData);
		$st_categoriesData = array(
				'user_owner' => $i_lastInsertId,
				'parent' => $o_foodCategory->id,
				'name' => 'Fuera',
				'description' => 'Comidas fuera de casa',
				'type' => 3
		);
		$this->insert($st_categoriesData);
		$st_categoriesData = array(
				'user_owner' => $i_lastInsertId,
				'parent' => $o_foodCategory->id,
				'name' => 'Café',
				'description' => 'Cafés, bollería durante el día, desayuno en cafetería, etc.',
				'type' => 3
		);
		$this->insert($st_categoriesData);
		return true;
	}
}