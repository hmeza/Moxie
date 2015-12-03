<?php

use Phinx\Migration\AbstractMigration;

class ExpensesToTransactions extends AbstractMigration
{
    public function up() {
        $this->query("insert into transactions(user_owner, amount, category, note, date, in_sum, income_update)"
        ." select user_owner, -amount, category, note, expense_date, in_sum, expense_update from expenses;");
    }

    public function down() {
        $this->query("delete from transactions where amount < 0");
    }
}
