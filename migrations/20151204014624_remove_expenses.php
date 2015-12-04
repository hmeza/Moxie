<?php

use Phinx\Migration\AbstractMigration;

class RemoveExpenses extends AbstractMigration
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
    public function up()
    {
        $this->table('expenses')->drop();
    }

    public function down() {
        $this->query("CREATE TABLE `expenses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_owner` int(11) NOT NULL,
  `amount` decimal(10,2) DEFAULT '0.00',
  `category` int(11) DEFAULT NULL,
  `note` tinytext,
  `expense_date` datetime DEFAULT NULL,
  `in_sum` tinyint(4) DEFAULT '1',
  `expense_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_owner` (`user_owner`)
) ENGINE=InnoDb DEFAULT CHARSET=utf8");
    }
}
