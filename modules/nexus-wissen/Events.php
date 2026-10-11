<?php

namespace nexus\modules\wissen;

use humhub\modules\ui\menu\MenuLink;
use nexus\modules\wissen\models\Artikel;
use nexus\modules\wissen\services\Nachrichten;
use Throwable;
use Yii;

class Events
{
    /** Ein Menuepunkt je Sammlung des Kreises -- Vorgabe direkt unter "Stream" (sortOrder 100). */
    public static function onKreisMenue($event): void
    {
        try {
            $modul = Module::instanz();
            $menue = $event->sender;
            $kreis = $menue->space ?? null;
            if ($modul === null || $kreis === null) {
                return;
            }
            $ich = Yii::$app->user->getIdentity();
            $aktiv = Yii::$app->controller !== null && Yii::$app->controller->module !== null
                && Yii::$app->controller->module->id === 'nexus-wissen' ? (string)Yii::$app->request->get('s') : null;
            foreach ($modul->sammlungenIn($kreis) as $sammlung) {
                if (!$sammlung->darfLesen($ich)) {
                    continue;
                }
                $menue->addEntry(new MenuLink([
                    'label' => $sammlung->name(),
                    'url' => $sammlung->url(),
                    'icon' => $sammlung->symbol(),
                    'sortOrder' => $sammlung->menuePosition(),
                    'isActive' => $aktiv === $sammlung->kennung,
                ]));
            }
        } catch (Throwable $e) {
            Yii::error('nexus-wissen: Menue-Eintrag fehlgeschlagen: ' . $e->getMessage(), 'nexus-wissen');
        }
    }

    /** Taeglich: faellige Wiedervorlagen einmal an die Bearbeitenden der Sammlung melden. */
    public static function onTaeglich($event): void
    {
        $modul = Module::instanz();
        if ($modul === null) {
            return;
        }
        $heute = date('Y-m-d');
        foreach ($modul->alleSammlungen() as $sammlung) {
            try {
                if ($sammlung->kreis() === null) {
                    continue;
                }
                $faellig = Artikel::inSammlung($sammlung->kennung)
                    ->andWhere(['<=', 'wiedervorlage', $heute])
                    ->andWhere(['or', ['erinnert_am' => null], ['<', 'erinnert_am', new \yii\db\Expression('wiedervorlage')]])
                    ->orderBy('wiedervorlage')->limit(30)->all();
                if ($faellig === []) {
                    continue;
                }
                Nachrichten::pruefungenFaellig($faellig, $sammlung);
                Artikel::updateAll(['erinnert_am' => $heute], ['id' => array_map(fn($a) => $a->id, $faellig)]);
            } catch (Throwable $e) {
                Yii::error('nexus-wissen: Wiedervorlage ' . $sammlung->kennung . ' fehlgeschlagen: ' . $e->getMessage(), 'nexus-wissen');
            }
        }
    }
}
