<?php

use humhub\components\Migration;

class uninstall extends Migration
{
    public function up()
    {
        foreach (['nexus_gemeinschaft_ort', 'nexus_karte_suchende'] as $tabelle) {
            if ($this->db->getTableSchema($tabelle, true) !== null) {
                $this->dropTable($tabelle);
            }
        }
    }
}
