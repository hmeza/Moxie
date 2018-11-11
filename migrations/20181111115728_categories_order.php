<?php

use Phinx\Migration\AbstractMigration;

class CategoriesOrder extends AbstractMigration
{
    public function up()
    {
        $this->table('categories')
            ->addColumn('order', 'integer', array('default' => 0, 'limit' => \Phinx\Db\Adapter\MysqlAdapter::INT_TINY))
            ->update();

        try {
            $data = $this->query("SELECT id FROM users");
            foreach ($data as $user) {
                $order = 0;
                $query = "SELECT c1.id , CONCAT(COALESCE(c2.name, ''), c1.name) real_name "
                ." FROM categories c1 INNER JOIN categories c2 ON c1.parent = c2.id AND c1.parent IS NOT NULL "
                ." WHERE c1.user_owner = " . $user['id'] . " ORDER BY `real_name` ASC";
                $cat_data = $this->query($query);
                foreach ($cat_data as $cat) {
                    $this->query("UPDATE categories SET `order` = " . $order++ . " WHERE id = " . $cat['id']);
                }
            }
        }
        catch(Exception $e) {
            echo $e->getMessage();
            $this->down();
            throw $e;
        }
    }

    public function down() {
        $this->table('categories')->removeColumn('order');
    }
}
