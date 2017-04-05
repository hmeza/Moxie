<?php

/**
 * Favourites model.
 */
class Favourites extends Zend_Db_Table_Abstract {
	private $database;
	protected $_name = 'favourites';
	protected $_primary = 'id';

	public function __construct() {
		global $db;
		$this->database = $db;
		$this->_db = Zend_Registry::get('db');
	}
}
