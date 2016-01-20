<?php
/**
 * Tags model.
 */
class Tags extends Zend_Db_Table_Abstract {
	private $database;
	protected $_name = 'tags';
	protected $_primary = 'id';

	public function __construct() {
		global $db;
		$this->database = $db;
		$this->_db = Zend_Registry::get('db');
	}

	/**
	 * Adds a new tag for $userId
	 * @var int $userId
	 * @var string $name
	 * @return int
	 */	
	public function addTag($userId, $name) {
		if(empty($userId)) {
			throw new Exception("Empty user id");
		}
		if(empty($name)) {
			throw new Exception("Empty tag name");
		}
		$data = array(
			'user_owner' => $userId,
			'name' => $name
		);
		try {
			return $this->insert($data);
		} catch (Exception $e) {
			error_log('Exception caught on '.__METHOD__.'('.$e->getLine().'), message: '.$e->getMessage());
		}
	}
	
	/**
	 * Gets categories for a given user.
	 * i_typeFilter stands for the type of category to retrieve. 
	 * @param int $i_typeFilter
	 */
	public function getTagsByUser($userId) {
		if(empty($userId)) {
			throw new Exception("Empty user id");
		}
		try {
			$query = $this->select()->where('user_owner = ?', $userId);
		
			$rows = $this->fetchAll($query)->toArray();
			$tags = array();
			foreach($rows as $row) {
				$tags[$row['id']] = $row['name'];
			}
		}
		catch(Exception $e) {
			error_log(__METHOD__.": ".$e->getMessage());
			$tags = array();
		}
		return $tags;
	}
}
