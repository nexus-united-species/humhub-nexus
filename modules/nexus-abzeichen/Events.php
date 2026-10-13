<?php

namespace nexus\modules\abzeichen;

use humhub\helpers\Html;
use humhub\modules\ui\menu\MenuLink;
use humhub\modules\user\models\User;
use nexus\modules\abzeichen\assets\AbzeichenAsset;
use nexus\modules\abzeichen\services\Aktivzeit;
use nexus\modules\abzeichen\services\Bekenntnisse;
use nexus\modules\abzeichen\services\Texte;
use Throwable;
use Yii;
use yii\helpers\Json;
use yii\web\Application as WebApplication;

class Events
{
    /** Ab dieser Bildbreite bekommt das Profilbild zusaetzlich das kleine Siegel (darunter nur den Rahmen) */
    private const SIEGEL_AB_PIXEL = 32;
    private const MENUE_POSITION = 650;

    /**
     * Jede Seite: aktive Zeit zaehlen, Stylesheet laden, faellige Feier zeigen.
     * Gezaehlt wird nur echtes Tun: normale Seitenaufrufe, Seitenwechsel ohne Neuladen (PJAX) und
     * Absenden. Reine Hintergrund-Abfragen zaehlen nicht.
     */
    public static function onSeite($event): void
    {
        try {
            if (!Yii::$app instanceof WebApplication || Yii::$app->user->isGuest) {
                return;
            }
            $modul = Module::instanz();
            $ich = Yii::$app->user->getIdentity();
            if ($modul === null || !$ich instanceof User || Yii::$app->controller !== $event->sender) {
                return;
            }
            $anfrage = Yii::$app->request;
            $istPjax = $anfrage->headers->has('X-PJAX');
            $istSeite = !$anfrage->isAjax || $istPjax;
            if (!$modul->istAusnahme((int)$ich->id) && ($istSeite || $anfrage->isPost)) {
                Aktivzeit::zaehlen((int)$ich->id);
            }
            if (!$anfrage->isAjax) {
                AbzeichenAsset::register(Yii::$app->view);
                self::feierZeigen((int)$ich->id);
            }
        } catch (Throwable $e) {
            Yii::error('nexus-abzeichen: ' . $e->getMessage(), 'nexus-abzeichen');
        }
    }

    /** Kurzer goldener Moment -- nur bei vollem Seitenaufbau, damit das Skript sicher ausgefuehrt wird */
    private static function feierZeigen(int $userId): void
    {
        foreach (Aktivzeit::feiernAbholen($userId) as $f) {
            $daten = Json::htmlEncode([
                'text' => $f['moment']->feier(),
                'zaehler' => $f['moment']->name() . ': ' . $f['anzahl'],
                'symbol' => $f['moment']->symbol(),
                'knopf' => Texte::t('weiter'),
            ]);
            Yii::$app->view->registerJs("window.nexusAbzeichenFeier && window.nexusAbzeichenFeier($daten);");
        }
    }

    /** Rahmen und Siegel um das Profilbild -- nur das erste Abzeichen des Menschen in der eingestellten Reihenfolge */
    public static function onProfilbild($event): void
    {
        try {
            $bild = $event->sender;
            $mensch = $bild->user ?? null;
            $modul = Module::instanz();
            if (!$mensch instanceof User || $modul === null || !is_string($event->result) || $event->result === '') {
                return;
            }
            $seine = Bekenntnisse::von((int)$mensch->id);
            if ($seine === []) {
                return;
            }
            foreach ($modul->alleAbzeichen() as $abz) {
                if (!in_array($abz->kennung, $seine, true)) {
                    continue;
                }
                $siegel = (int)($bild->width ?? 0) >= self::SIEGEL_AB_PIXEL
                    ? '<span class="nx-abz-siegel" aria-hidden="true"><i class="fa fa-' . Html::encode($abz->symbol()) . '"></i></span>'
                    : '';
                $event->result = '<span class="nx-abz-rahmen" style="--nx-abz-farbe:' . Html::encode($abz->farbe()) . '" title="'
                    . Html::encode($abz->name()) . '">' . $event->result . $siegel . '</span>';
                return;
            }
        } catch (Throwable $e) {
            Yii::error('nexus-abzeichen: Profilbild: ' . $e->getMessage(), 'nexus-abzeichen');
        }
    }

    public static function onKontoMenue($event): void
    {
        $modul = Module::instanz();
        if ($modul === null || Yii::$app->user->isGuest || $modul->alleAbzeichen() === []) {
            return;
        }
        $event->sender->addEntry(new MenuLink([
            'label' => Texte::t('abzeichen'),
            'url' => ['/nexus-abzeichen/abzeichen/index'],
            'icon' => 'star-o',
            'sortOrder' => self::MENUE_POSITION,
        ]));
    }

    public static function onTaeglich($event): void
    {
        $modul = Module::instanz();
        if ($modul !== null) {
            Aktivzeit::aufraeumen(max(2, (int)$modul->wochenAufbewahren));
        }
    }
}
