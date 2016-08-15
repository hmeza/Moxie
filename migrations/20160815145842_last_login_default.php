<?php

use Phinx\Migration\AbstractMigration;

class LastLoginDefault extends AbstractMigration
{
    public function change()
    {
        $this->table('users')
            ->removeColumn('last_login')
            ->addColumn('last_login', 'timestamp', array('null' => false, 'default' => 'CURRENT_TIMESTAMP'))
            ->update();
    }
}
