<?php

namespace nexus\modules\gesundheit;

use humhub\modules\ui\menu\MenuLink;
use nexus\modules\gesundheit\models\Artikel;
use nexus\modules\gesundheit\services\Nachrichten;
use nexus\modules\gesundheit\services\Texte;
use Throwable;
use Yii;

class Events
{
    /** Menuepunkt im Kreis -- direkt unter "Stream" (sortOrder 100). */
    public static function onKreisMenue($event): void
    {
        try {
            $modul = Module::instanz();
            $menue = $event->sender;
            $kreis = $menue->space ?? null;
            if ($kreis === null || (int)$kreis->id !== (int)$modul->kreis) {
                return;
            }
            if (!$modul->darfLesen(Yii::$app->user->getIdentity())) {
                return;
            }
            $menue->addEntry(new MenuLink([
                'label' => Texte::t('nav'),
                'url' => $kreis->createUrl('/nexus-gesundheit/wissen/index'),
                'icon' => 'heartbeat',
                'sortOrder' => 105,
                'isActive' => Yii::$app->controller !== null && Yii::$app->controller->module !== null
                    && Yii::$app->controller->module->id === 'nexus-gesundheit',
            ]));
        } catch (Throwable $e) {
            Yii::error('nexus-gesundheit: Menue-Eintrag fehlgeschlagen: ' . $e->getMessage(), 'nexus-gesundheit');
        }
    }

    /** Taeglich: faellige Wiedervorlagen einmal an die Admins melden. */
    public static function onTaeglich($event): void
    {
        try {
            $modul = Module::instanz();
            $kreis = $modul->kreis();
            if ($kreis === null) {
                return;
            }
            $heute = date('Y-m-d');
            $faellig = Artikel::find()
                ->where(['<=', 'wiedervorlage', $heute])
                ->andWhere(['or', ['erinnert_am' => null], ['<', 'erinnert_am', new \yii\db\Expression('wiedervorlage')]])
                ->orderBy('wiedervorlage')->limit(30)->all();
            if ($faellig === []) {
                return;
            }
            Nachrichten::pruefungenFaellig($faellig, $kreis);
            Artikel::updateAll(['erinnert_am' => $heute], ['id' => array_map(fn($a) => $a->id, $faellig)]);
        } catch (Throwable $e) {
            Yii::error('nexus-gesundheit: Wiedervorlage fehlgeschlagen: ' . $e->getMessage(), 'nexus-gesundheit');
        }
    }
}
