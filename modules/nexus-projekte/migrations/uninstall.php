<?php

use humhub\components\Migration;

class uninstall extends Migration
{
    public function up()
    {
        foreach (['nexus_projekt_option', 'nexus_projekt_datei', 'nexus_projekt_aenderung', 'nexus_projekt_leitung', 'nexus_projekt_mitmacher', 'nexus_projekt_stimme', 'nexus_projekt_schritt', 'nexus_projekt_helfer', 'nexus_projekt_karte', 'nexus_projekt'] as $t) {
            $this->safeDropTable($t);
        }
    }

    public function down()
    {
        echo "uninstall does not support migration down.\n";
        return false;
    }
}
