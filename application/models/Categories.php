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

class Categories {
	private $database;
	
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
		//order by c1.parent,c2.parent;
		$query = $this->database->select()
			->from(array('c1'=>'categories'), array(
				'id2'	=>	'distinct(c2.id)',
				'id1'	=>	'c1.id',
				'name1'	=>	'c1.name',
				'name2'	=>	'c2.name',
				'parent'	=>	'c1.parent'
			))
			->joinLeft(array('c2'=>'categories'),'c2.parent = c1.id',array())
			->where('c1.user_owner = ?', $user_id)
			->where('c1.name != "root"')
			->order('c1.parent');
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