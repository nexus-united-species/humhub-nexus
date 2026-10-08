<?php

namespace nexus\modules\teilen;

use humhub\modules\content\models\Content;
use nexus\modules\teilen\assets\TeilenAsset;
use nexus\modules\teilen\services\Inhalt;
use nexus\modules\teilen\services\Schaufenster;
use nexus\modules\teilen\services\Texte;
use nexus\modules\teilen\widgets\FreigabeLink;
use nexus\modules\teilen\widgets\SchaufensterLink;
use nexus\modules\teilen\widgets\TeilenLink;
use Throwable;
use Yii;
use yii\helpers\Url;

/**
 * Alle Haken nur fuer Angemeldete: Gaeste sehen im Portal ohnehin keine Beitraege; die
 * oeffentliche Leseseite (LesenController) ist eine eigene, schlichte Seite ohne diese Zeilen.
 * Jeder Haken faengt seine Fehler selbst -- ein Fehler hier darf nie den Stream zerlegen.
 */
class Events
{
    /** Steht in der Theme-Vorlage index_guest.php, wo der Schaufenster-Block hin soll. */
    private const PLATZHALTER_GAST = '<!-- nexus-schaufenster -->';
    private const KARTEN_GAST = 6;

    public static function onLinksInit($event): void
    {
        try {
            $record = $event->sender->object ?? null;
            if (Yii::$app->user->isGuest || !self::passt($record)) {
                return;
            }
            $event->sender->addWidget(TeilenLink::class, ['record' => $record], ['sortOrder' => 300]);
            if (Inhalt::darfSchaufenster(Yii::$app->user->getIdentity())) {
                $event->sender->addWidget(SchaufensterLink::class, ['record' => $record], ['sortOrder' => 310]);
            }
        } catch (Throwable $e) {
            Yii::error('nexus-teilen (Links): ' . $e->getMessage(), 'nexus-teilen');
        }
    }

    /**
     * Gast-Startseite des Portals (vor der Registrierung, mit der Karte): Platzhalter durch die
     * neuesten drei Schaufenster-Beitraege ersetzen. Gibt es keine, verschwindet der Platzhalter.
     */
    public static function onViewAfterRender($event): void
    {
        if (!str_ends_with((string)$event->viewFile, 'dashboard/index_guest.php')
            || !str_contains((string)$event->output, self::PLATZHALTER_GAST)) {
            return;
        }
        $block = '';
        try {
            $sprache = Texte::sprache();
            $karten = Schaufenster::karten($sprache, self::KARTEN_GAST);
            if ($karten !== []) {
                $block = Yii::$app->view->renderFile('@nexus-teilen/views/lesen/_gast_block.php', [
                    't' => Texte::alle($sprache),
                    'karten' => $karten,
                    'alle' => Url::to(['/nexus-teilen/lesen/schaufenster', 'sprache' => $sprache]),
                ]);
            }
        } catch (Throwable $e) {
            Yii::error('nexus-teilen (Gast-Startseite): ' . $e->getMessage(), 'nexus-teilen');
        }
        $event->output = str_replace(self::PLATZHALTER_GAST, $block, (string)$event->output);
    }

    public static function onControlsInit($event): void
    {
        try {
            $record = $event->sender->object ?? null;
            if (Yii::$app->user->isGuest || !self::passt($record)
                || !Inhalt::darfFreigeben($record, Yii::$app->user->getIdentity())) {
                return;
            }
            $event->sender->addWidget(FreigabeLink::class, ['record' => $record], ['sortOrder' => 450]);
        } catch (Throwable $e) {
            Yii::error('nexus-teilen (Menue): ' . $e->getMessage(), 'nexus-teilen');
        }
    }

    public static function onLayoutAddonInit($event): void
    {
        try {
            if (!Yii::$app->user->isGuest) {
                TeilenAsset::register(Yii::$app->view);
            }
        } catch (Throwable $e) {
            Yii::error('nexus-teilen (Skript): ' . $e->getMessage(), 'nexus-teilen');
        }
    }

    /** Nur veroeffentlichte Beitraege und Wiki-Seiten -- keine Entwuerfe, nichts aus dem Papierkorb. */
    private static function passt($record): bool
    {
        return Inhalt::unterstuetzt($record)
            && !$record->isNewRecord
            && (int)$record->content->state === Content::STATE_PUBLISHED;
    }
}
