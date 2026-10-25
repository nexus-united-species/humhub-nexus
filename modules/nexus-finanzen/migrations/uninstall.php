<?php

use humhub\components\Migration;

class uninstall extends Migration
{
    public function up()
    {
        foreach (['nexus_fin_rueckblick', 'nexus_fin_verteilung', 'nexus_fin_ausgabe', 'nexus_fin_zuweisung', 'nexus_fin_eingang', 'nexus_fin_kachel'] as $t) {
            $this->safeDropTable($t);
        }
    }

    public function down()
    {
        echo "uninstall does not support migration down.\n";
        return false;
    }
}
