<?php

use Phinx\Migration\AbstractMigration;

class NullableSharedExpensesSheetUser extends AbstractMigration
{
	public function up() {
		$this->query("alter table shared_expenses_sheet_users modify id_user int(11) null");
	}
	
	public function down() {
		$this->query("alter table shared_expenses_sheet_users modify id_user int(11) not null");
	}
}
