<?php

use Phinx\Migration\AbstractMigration;

class SharedExpensesSheets extends AbstractMigration
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
    	$this->table('shared_expenses_sheets')
    	->addColumn('user_owner', 'integer')
    	->addColumn('name', 'string')
    	->addColumn('unique_id', 'string')
    	->addTimestamps()
    	->addColumn('closed_at', 'datetime', array('null' => true, 'default' => null))
    	->addForeignKey('user_owner', 'users', 'id')
    	->create();
    	
    	$this->table('shared_expenses_sheet_users')
    	->addColumn('id_sheet', 'integer')
    	->addColumn('id_user', 'integer', array('default' => null))
    	->addColumn('email', 'string', array('default' => ''))
    	->addTimestamps()
    	->addForeignKey('id_sheet', 'shared_expenses_sheets', 'id')
    	->addForeignKey('id_user', 'users', 'id')
    	->create();
    	
    	$this->table('shared_expenses')
    	->addColumn('id_sheet', 'integer')
    	->addColumn('id_sheet_user', 'integer')
    	->addColumn('amount', 'decimal', array('precision' => 10, 'scale' => 2))
    	->addColumn('note', 'string')
    	->addColumn('date', 'datetime')
    	->addColumn('copied', 'integer', array('default' => 0))
    	->addForeignKey('id_sheet', 'shared_expenses_sheets', 'id')
    	->addForeignKey('id_sheet_user', 'shared_expenses_sheet_users', 'id')
    	->create();
    }
}
