<?php

use humhub\components\Migration;

class uninstall extends Migration
{
    public function up()
    {
        $this->safeDropTable('nexus_wissen_sammlung');
        $this->safeDropTable('nexus_wissen_thema');
        $this->safeDropTable('nexus_wissen_vorschlag');
        $this->safeDropTable('nexus_wissen_version');
        $this->safeDropTable('nexus_wissen_wort');
        $this->safeDropTable('nexus_wissen_artikel');
    }

    public function down()
    {
        echo "uninstall does not support migration down.\n";
        return false;
    }
}
