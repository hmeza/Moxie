<?php

use Phinx\Migration\AbstractMigration;

class SharedExpensesCurrency extends AbstractMigration
{
    public function change()
    {
        $this->table('shared_expenses_sheets')
            ->addColumn('currency', 'char', array('length' => 3, 'default' => 'eur'))
            ->addColumn('change', 'float', array('default' => 1))
            ->update();
        $this->table('shared_expenses')
            ->addColumn('currency', 'char', array('length' => 3, 'default' => 'eur'))
            ->update();
    }
}
