<?php

/**
 * TransactionTags model.
 */
class TransactionTags extends Zend_Db_Table_Abstract {
	private $database;
	protected $_name = 'transaction_tags';
	protected $_primary = 'id';
	
	public function __construct() {
		global $db;
		$this->database = $db;
		$this->_db = Zend_Registry::get('db');
	}

	public function addTagToTransaction($transactionId, $tagId) {
		return $this->insert(array(
			'id_transaction' => $transactionId,
			'id_tag' => $tagId
		));
	}

	public function getTagsForTransaction($transactionId) {
		$select = $this->select()
				->setIntegrityCheck(false)
				->from(array('tt' => $this->_name), array())
				->joinInner(array('t' => 'tags'), 't.id = tt.id_tag', array('name'))
				->joinInner(array('tr' => 'transactions'), 'tr.id = tt.id_transaction', array())
				->where('tr.id = ?', $transactionId);
		$rows = $this->fetchAll($select)->toArray();
		$tags = array();
		foreach($rows as $row) {
			$tags[] = $row['name'];
		}
		return $tags;
	}

	/**
	 * Removes tags from transactions.
	 * @var int $transactionId
	 * @var int|array $tags
	 */
	public function removeTagsFromTransaction($transactionId) {
		$this->delete("id_transaction = ".$transactionId);
	}
}
