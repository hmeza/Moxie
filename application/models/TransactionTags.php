<?php

include_once 'Zend/Registry.php';
/**
 * Categories model.
 */
class TransactionTags extends Zend_Db_Table_Abstract {
	private $database;
	protected $_name = 'categories';
	protected $_primary = 'id';
	
	public function __construct() {
		global $db;
		$this->database = $db;
		$this->_db = Zend_Registry::get('db');
	}

	public function addTagToTransaction($transactionId, $tagId) {
	}

	/**
	 * Removes tags from transactions.
	 * @var int $transactionId
	 * @var int|array $tags
	 */
	public function removeTagsFromTransaction($transactionId, $tags) {
		if(is_int($tags)) {
			$tags = array($tags);
		}
		// where=
		$this->delete();
	}
}
