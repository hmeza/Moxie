<?php

use Phinx\Migration\AbstractMigration;

class CategoriesOrder extends AbstractMigration
{
    public function change()
    {
        $this->table('categories')
            ->addColumn('order', 'integer', array('default' => 0, 'limit' => \Phinx\Db\Adapter\MysqlAdapter::INT_TINY))
            ->update();
    }
}
