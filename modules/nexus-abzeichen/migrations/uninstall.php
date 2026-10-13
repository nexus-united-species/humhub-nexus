<?php

use humhub\components\Migration;

class uninstall extends Migration
{
    public function up()
    {
        foreach (['nexus_abzeichen_moment', 'nexus_abzeichen_aktiv', 'nexus_abzeichen_bekenntnis'] as $t) {
            $this->safeDropTable($t);
        }
    }

    public function down()
    {
        echo "uninstall does not support migration down.\n";
        return false;
    }
}
