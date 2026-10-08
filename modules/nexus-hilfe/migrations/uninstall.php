<?php

use humhub\components\Migration;

class uninstall extends Migration
{
    public function up()
    {
        if ($this->db->getTableSchema('nexus_hilfe_anfrage', true) !== null) {
            $this->dropTable('nexus_hilfe_anfrage');
        }
    }
}
