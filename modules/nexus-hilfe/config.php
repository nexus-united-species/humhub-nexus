<?php

// Keine Ereignisse: Die Links auf die Hilfe-Seite stehen in der Theme-Vorlage (Kopfzeile) und auf
// der Gast-Startseite; Nova (nexus-community-assistant) ruft services/Support direkt auf.
return [
    'id' => 'nexus-hilfe',
    'class' => 'nexus\modules\hilfe\Module',
    'namespace' => 'nexus\modules\hilfe',
];
