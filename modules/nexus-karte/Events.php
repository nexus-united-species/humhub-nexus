<?php

namespace nexus\modules\karte;

use humhub\helpers\Html;
use humhub\modules\ui\menu\MenuLink;
use nexus\modules\karte\assets\KarteAsset;
use nexus\modules\karte\models\Standort;
use nexus\modules\karte\services\GastKarte;
use nexus\modules\karte\services\KartenDaten;
use nexus\modules\karte\services\Texte;
use Throwable;
use Yii;
use yii\helpers\Url;

class Events
{
    /** Steht in themes/NEXUS/.../index_guest.php zwischen Willkommenstext und Fusszeile. */
    private const PLATZHALTER_GAST = '<!-- nexus-karte -->';

    /**
     * Bewusst das kurze Wort "Karte" (Josh, 30.09.2026: die Navigation wird sonst zu voll).
     * Ohne PJAX: die Karte braucht ihr eigenes Skript, ein ganz normaler Seitenaufruf ist am
     * zuverlaessigsten.
     */
    public static function onTopMenuInit($event): void
    {
        try {
            if (Yii::$app->user->isGuest) {
                return;
            }
            $event->sender->addEntry(new MenuLink([
                'label' => Texte::t('nav'),
                'url' => Url::to(['/nexus-karte/karte/index']),
                'icon' => 'map-marker',
                'sortOrder' => 260,
                'pjaxEnabled' => false,
                'isActive' => Yii::$app->controller !== null && Yii::$app->controller->module !== null
                    && Yii::$app->controller->module->id === 'nexus-karte',
            ]));
        } catch (Throwable $e) {
            // Ein Fehler hier darf nie die ganze Navigation (und damit jede Seite) kaputt machen.
            Yii::error('nexus-karte: Menue-Eintrag fehlgeschlagen: ' . $e->getMessage(), 'nexus-karte');
        }
    }

    /**
     * Zahnrad-Menue eines Kreises: "Standort". Die Kreis-Leitung sieht ihn nur bei einer
     * Gemeinschaft; ein Systemadmin bei jedem Kreis (dort kann er ihn zur Gemeinschaft erklaeren).
     */
    public static function onKreisMenue($event): void
    {
        try {
            $space = $event->sender->space ?? null;
            $ich = Yii::$app->user->isGuest ? null : Yii::$app->user->getIdentity();
            if ($space === null || $ich === null) {
                return;
            }
            $istGemeinschaft = Standort::find()->where(['space_id' => $space->id])->exists();
            if (!$ich->isSystemAdmin() && !($istGemeinschaft && KartenDaten::darfOrtAendern($ich, $space))) {
                return;
            }
            $event->sender->addEntry(new MenuLink([
                'label' => Texte::t('menue_standort'),
                'url' => Url::to(['/nexus-karte/verwaltung/index', 'kreis' => $space->id]),
                'icon' => 'map-marker',
                'sortOrder' => 450,
                'pjaxEnabled' => false,
            ]));
        } catch (Throwable $e) {
            Yii::error('nexus-karte: Eintrag im Kreis-Menue fehlgeschlagen: ' . $e->getMessage(), 'nexus-karte');
        }
    }

    /** Gast-Willkommensseite: Karte an den Platzhalter setzen. Kreise-Uebersicht: Hinweisbalken auf die Karte. */
    public static function onViewAfterRender($event): void
    {
        $datei = (string)$event->viewFile;
        if (str_ends_with($datei, 'dashboard/index_guest.php')) {
            self::karteFuerGaeste($event);
            return;
        }
        if (!str_ends_with($datei, 'space/views/spaces/index.php')) {
            return;
        }
        try {
            if (Yii::$app->user->isGuest) {
                return;
            }
            $balken = Html::tag(
                'div',
                '🗺️ ' . Html::encode(Texte::t('hinweis_kreise')) . ' '
                . Html::a(Html::encode(Texte::t('hinweis_link')), Url::to(['/nexus-karte/karte/index']), ['data-pjax-prevent' => '1', 'style' => 'font-weight:600;white-space:nowrap']),
                ['class' => 'panel panel-default', 'style' => 'padding:12px 16px;border-left:4px solid #D4AF37']
            );
            $event->output = $balken . $event->output;
        } catch (Throwable $e) {
            Yii::error('nexus-karte: Hinweis auf der Kreise-Seite fehlgeschlagen: ' . $e->getMessage(), 'nexus-karte');
        }
    }

    /**
     * Gast-Willkommensseite (Theme NEXUS, index_guest.php): den Platzhalter durch die Karte
     * ersetzen. Gaeste bekommen nur Name und Ort (KartenDaten::punkteFuerGaeste). Ohne
     * Gemeinschaften bleibt die Seite, wie sie war.
     */
    private static function karteFuerGaeste($event): void
    {
        if (!str_contains((string)$event->output, self::PLATZHALTER_GAST)) {
            return;
        }
        try {
            $block = GastKarte::html();
            if ($block === null) {
                return;
            }
            KarteAsset::register(Yii::$app->view);
            $event->output = str_replace(self::PLATZHALTER_GAST, $block, (string)$event->output);
        } catch (Throwable $e) {
            Yii::error('nexus-karte: Karte auf der Gastseite fehlgeschlagen: ' . $e->getMessage(), 'nexus-karte');
        }
    }
}
