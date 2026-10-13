<?php

use humhub\components\Migration;

class m261013_160000_geteilt extends Migration
{
    public function safeUp()
    {
        // Zuletzt geteilter Stand eines Moments -- derselbe Stand soll nicht zweimal im Kreis landen
        $this->safeAddColumn('nexus_abzeichen_moment', 'geteilt', $this->integer()->notNull()->defaultValue(0));
    }

    public function safeDown()
    {
        $this->safeDropColumn('nexus_abzeichen_moment', 'geteilt');
    }
}
