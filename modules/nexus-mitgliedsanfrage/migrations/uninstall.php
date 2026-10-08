<?php

use humhub\components\Migration;

class uninstall extends Migration
{
    public function up()
    {
        if ($this->db->getTableSchema('nexus_mitgliedsanfrage', true) !== null) {
            $this->dropTable('nexus_mitgliedsanfrage');
        }
    }
}
