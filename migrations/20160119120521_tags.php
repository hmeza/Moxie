<?php

use Phinx\Migration\AbstractMigration;

class Tags extends AbstractMigration
{
    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     *
     * More information on writing migrations is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-abstractmigration-class
     *
     * The following commands can be used in this method and Phinx will
     * automatically reverse them when rolling back:
     *
     *    createTable
     *    renameTable
     *    addColumn
     *    renameColumn
     *    addIndex
     *    addForeignKey
     *
     * Remember to call "create()" or "update()" and NOT "save()" when working
     * with the Table class.
     */
    public function change()
    {
	$this->table('tags')
		->addColumn('user_owner', 'integer')
		->addColumn('name', 'string')
		->addTimestamps()
		->addForeignKey('user_owner', 'users', 'id')
		->create();

	$this->table('transaction_tags')
		->addColumn('id_transaction', 'integer')
		->addColumn('id_tag', 'integer')
		->addTimestamps()
		->addForeignKey('id_transaction', 'transactions', 'id')
		->addForeignKey('id_tag', 'tags', 'id')
		->create();
    }
}
