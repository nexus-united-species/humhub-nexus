<?php

use humhub\components\Migration;

class uninstall extends Migration
{
    public function up()
    {
        $this->safeDropTable('nexus_gesundheit_vorschlag');
        $this->safeDropTable('nexus_gesundheit_version');
        $this->safeDropTable('nexus_gesundheit_wort');
        $this->safeDropTable('nexus_gesundheit_artikel');
    }

    public function down()
    {
        echo "uninstall does not support migration down.\n";
        return false;
    }
}
