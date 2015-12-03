<?php

use Phinx\Migration\AbstractMigration;

class Transactions extends AbstractMigration
{
    public function up() {
        $table = $this->table('incomes');
        $table->rename('transactions')
            ->save();
    }

    public function down() {
        $table = $this->table('transactions');
        $table->rename('incomes')
            ->save();
    }
}
