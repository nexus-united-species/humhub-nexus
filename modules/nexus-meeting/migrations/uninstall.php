<?php

use humhub\components\Migration;

class uninstall extends Migration
{
    public function up()
    {
        $this->safeDropTable('nexus_meeting_tuer');
    }

    public function down()
    {
        echo "uninstall does not support migration down.\n";
        return false;
    }
}
