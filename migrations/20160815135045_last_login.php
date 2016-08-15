<?php

use Phinx\Migration\AbstractMigration;

class LastLogin extends AbstractMigration
{
    public function change()
    {
        $this->table('users')
            ->addColumn('last_login', 'timestamp', array('null' => false))
            ->update();
    }
}
