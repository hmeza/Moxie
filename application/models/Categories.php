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

class Categories extends Zend_Db_Table_Abstract {
	private $database;
	protected $_name = 'categories';
	protected $_primary = 'id';
	
	public function __construct() {
		global $db;
		$this->database = $db;
	}
	
	public function addCategory($data) {
		try {
			$this->database->insert('categories',
				array(
					'user_owner' => $data['user_owner'],
					'parent' => $data['parent'],
					'name' => $data['name'],
					'description' => $data['description']
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
	
	public function getCategoriesByUser($user_id) {
		//select distinct(c2.id),c1.parent,c1.name, c2.name
		//from categories c1 left join categories c2 on c2.parent = c1.id
		//where c2.id is not null
		//order by c1.parent,c2.parent;
		$query = $this->database->select()
			->from(array('c1'=>'categories'), array(
				'id1'	=>	'distinct(c2.id)',
				'parent1'	=>	'c1.id',
				'name1'	=>	'c1.name',
				'name2'	=>	'c2.name',
			))
			->joinLeft(array('c2'=>'categories'),'c2.parent = c1.id',array())
			->where('c1.user_owner = ?', $user_id)
			->where('c2.id is not null')
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
}

?>