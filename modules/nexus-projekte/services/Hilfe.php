<?php

namespace nexus\modules\projekte\services;

use humhub\helpers\Html;

/**
 * Kleines i-Symbol mit Hilfetext: am PC beim Daraufzeigen, am Handy per Antippen (resources/js/projekte.js).
 * Die Texte stehen in TexteHilfe (alle Sprachen). Fehlt ein Text, erscheint nichts.
 */
final class Hilfe
{
    private static int $zaehler = 0;

    /**
     * @param string      $schluessel  Text 'hilfe_<schluessel>'
     * @param string|null $beschriftung  Text-Schluessel fuer eine sichtbare Beschriftung neben dem Symbol
     */
    public static function info(string $schluessel, ?string $beschriftung = null): string
    {
        $text = Texte::t('hilfe_' . $schluessel);
        if ($text === 'hilfe_' . $schluessel) {
            return '';
        }
        $id = 'nx-pj-hilfe-' . (++self::$zaehler);
        $label = Html::encode(Texte::t('hilfe_label'));
        $kopf = $beschriftung !== null ? ' <span class="nx-pj-info-beschriftung">' . Html::encode(Texte::t($beschriftung)) . '</span>' : '';
        return '<span class="nx-pj-info">'
            . '<button type="button" class="nx-pj-info-knopf" aria-expanded="false" aria-controls="' . $id . '" aria-label="' . $label . '" title="' . $label . '">'
            . '<i class="fa fa-info-circle" aria-hidden="true"></i>' . $kopf . '</button>'
            . '<span class="nx-pj-info-text" id="' . $id . '" role="tooltip">' . nl2br(Html::encode($text), false) . '</span>'
            . '</span>';
    }
}
