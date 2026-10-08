<?php

use humhub\components\Migration;

class uninstall extends Migration
{
    public function up()
    {
        if ($this->db->getTableSchema('nexus_teilen_freigabe', true) !== null) {
            $this->dropTable('nexus_teilen_freigabe');
        }
    }
}
