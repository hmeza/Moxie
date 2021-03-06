<?php
/**
 * Tags model.
 */
class Tags extends Zend_Db_Table_Abstract {
	private $database;
	protected $_name = 'tags';
	protected $_primary = 'id';

    /* @var TransactionTags */
    private $transactionTags;

    private $existingTags = null;

	public function __construct() {
		global $db;
		$this->database = $db;
		$this->_db = Zend_Registry::get('db');
        $this->transactionTags = new TransactionTags();
	}

	/**
	 * Adds a new tag for $userId
	 * @var int $userId
	 * @var string $name
	 * @return int
     * @throws Exception
	 */	
	public function addTag($userId, $name) {
		if(empty($userId)) {
			throw new Exception("Empty user id");
		}
		if(empty($name)) {
			throw new Exception("Empty tag name");
		}
        if(is_null($this->existingTags)) {
            $this->existingTags = $this->getTagsByUser($userId);
        }

        // check if backslashes have been already replaced
        $name = trim($name);
        $pos = strpos($name, "\\'");
		if ($pos === false) {
        }
        else {
            $name = str_replace("\\'", "'", $name);
        }
        $tagId = array_search($name, $this->existingTags);
        if($tagId === FALSE) {
            $data = array(
                'user_owner' => $userId,
                'name' => $name
            );
            try {
                return $this->insert($data);
            } catch (Exception $e) {
                error_log('Exception caught on '.__METHOD__.'('.$e->getLine().'), message: '.$e->getMessage());
                return null;
            }
        }
        else {
            return $tagId;
        }
	}
	
	/**
	 * Gets categories for a given user.
	 * i_typeFilter stands for the type of category to retrieve. 
	 * @param int $userId
	 * @return array
	 * @throws Exception
	 */
	public function getTagsByUser($userId) {
		if(empty($userId)) {
			throw new Exception("Empty user id");
		}
		$query = $this->select()->where('user_owner = ?', $userId);
		return $this->getTagsFromQuery($query);
	}

	/**
	 * @param int $transactionId
	 * @return array
	 * @throws Exception
	 */
	public function getTagsForTransaction($transactionId) {
		if(empty($transactionId)) {
			throw new Exception("Empty user id");
		}
		$query = $this->select()
				->setIntegrityCheck(false)
				->from(array('t' => $this->_name), array('id' => 't.id', 'name' => 't.name'))
				->joinInner(array('tt' => 'transaction_tags'), 'tt.id_tag = t.id', array())
				->where('tt.id_transaction = ?', $transactionId);
		return $this->getTagsFromQuery($query);
	}

    /**
     * @param int $userId
     * @return array
     * @throws Exception
     */
    public function getUsedTagsByUser($userId) {
        if(empty($userId)) {
            throw new Exception("Empty user id");
        }
	    $query = $this->select()
			    ->setIntegrityCheck(false)
			    ->from(array('t' => 'tags'), array('t.id as id', 't.name as name'))
			    ->joinInner(array('tt' => 'transaction_tags'), 'tt.id_tag = t.id', array())
			    ->where('user_owner = ?', $userId)
			    ->group('t.id')
			    ->order('t.name DESC');
		return $this->getTagsFromQuery($query);
    }

	private function getTagsFromQuery($query) {
		try {
			$rows = $this->fetchAll($query)->toArray();
			$tags = array();
			foreach($rows as $row) {
                $pos = strpos("'", $row['name']);
                if ($pos === false) {
                    $tags[$row['id']] = str_replace("'", "\\'", $row['name']);
                }
			}
		}
		catch(Exception $e) {
			error_log(__METHOD__.": ".$e->getMessage());
			$tags = array();
		}
		return $tags;
	}

    /**
     * @param int $userId
     * @param string $tag
     */
    public function deleteTag($userId, $tag) {
        try {
            $query = $this->select()
                ->where('name = ?', $tag)
                ->where('user_owner = ?', $userId);
            $tag = $this->fetchRow($query);
            $this->transactionTags->removeTagsByTagId($tag->id);
            $tag->delete();
        }
        catch(Exception $e) {
            error_log(__METHOD__.": ".$e->getMessage());
        }
    }

}
