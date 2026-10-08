<?php

namespace nexus\modules\karte\services;

use humhub\helpers\Html;
use nexus\modules\karte\assets\KarteAsset;
use Yii;
use yii\helpers\Json;
use yii\helpers\Url;
use yii\web\View;

/**
 * Die Karte der Gemeinschaften fuer Besucher ohne Anmeldung -- nur Name und Ort. Gemeinsam
 * genutzt von der Gast-Startseite (Events::karteFuerGaeste) und der oeffentlichen Seite
 * "Aus der N.E.X.U.S.-Gemeinschaft" im Modul nexus-teilen (Josh, 04.10.2026).
 */
class GastKarte
{
    /** Fertiger Block (Ueberschrift, Text, Karte, Hinweis) oder null, wenn es keine Gemeinschaften gibt. */
    public static function html(): ?string
    {
        $punkte = KartenDaten::punkteFuerGaeste();
        if ($punkte === []) {
            return null;
        }
        $karte = Html::tag('div', '', [
            'id' => 'nexus-karte',
            'class' => 'nka-karte',
            'role' => 'application',
            'aria-label' => Texte::t('gast_titel'),
            'data-punkte' => Json::encode($punkte),
            'data-kachel' => Url::to(['/nexus-karte/karte/kachel']),
            'data-min-zoom' => Kacheln::MIN_ZOOM,
            'data-max-zoom' => Kacheln::MAX_ZOOM,
            'data-gast-hinweis' => Texte::t('gast_popup'),
            'data-quelle' => Texte::t('karte_quelle'),
        ]);
        return Html::tag(
            'section',
            Html::tag('h2', '🗺️ ' . Html::encode(Texte::t('gast_titel')), ['class' => 'nka-gast-titel'])
            . Html::tag('p', Html::encode(Texte::t('gast_text')), ['class' => 'nka-gast-text'])
            . $karte
            . Html::tag('p', Html::encode(Texte::t('ort_hinweis')), ['class' => 'nka-klein']),
            ['class' => 'nka nka-gast']
        );
    }

    /**
     * Stylesheets und Skripte der Karte als fertige Tags -- fuer Seiten mit eigenem, schlichtem
     * Rahmen, die HumHubs Asset-Ausgabe nicht nutzen (die oeffentliche Schaufenster-Seite).
     *
     * @return array{kopf: string, ende: string}
     */
    public static function tags(View $view): array
    {
        $paket = KarteAsset::register($view);
        $kopf = '';
        foreach ($paket->css as $css) {
            $kopf .= Html::cssFile($paket->baseUrl . '/' . $css) . "\n";
        }
        $ende = '';
        foreach ($paket->js as $js) {
            $ende .= Html::jsFile($paket->baseUrl . '/' . $js) . "\n";
        }
        return ['kopf' => $kopf, 'ende' => $ende];
    }
}
