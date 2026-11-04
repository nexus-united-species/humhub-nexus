<?php

use humhub\components\Migration;

class uninstall extends Migration
{
    public function up()
    {
        foreach (['nexus_arbeitszeit', 'nexus_benachrichtigung_knopf', 'nexus_benachrichtigung'] as $tabelle) {
            if ($this->db->getTableSchema($tabelle, true) !== null) {
                $this->dropTable($tabelle);
            }
        }
    }
}
