<?php

namespace nexus\modules\projekte;

use humhub\modules\space\models\Space;
use humhub\modules\ui\menu\MenuLink;
use humhub\modules\user\models\User;
use nexus\modules\projekte\models\Karte;
use nexus\modules\projekte\models\Stimme;
use nexus\modules\projekte\services\MeineAufgaben;
use nexus\modules\projekte\services\Nachrichten;
use nexus\modules\projekte\services\Texte;
use nexus\modules\projekte\widgets\MeineAufgabenKasten;
use Throwable;
use Yii;

class Events
{
    private const MENUE_KREIS = 110;   // direkt unter "Stream" (100)
    private const MENUE_OBEN = 250;
    private const STARTSEITE = 150;

    public static function onKreisMenue($event): void
    {
        try {
            $kreis = $event->sender->space ?? null;
            if (!$kreis instanceof Space || Yii::$app->user->isGuest || !Module::istAn($kreis)) {
                return;
            }
            $event->sender->addEntry(new MenuLink([
                'label' => Texte::t('projekte'),
                'url' => $kreis->createUrl('/nexus-projekte/projekt/index'),
                'icon' => 'columns',
                'sortOrder' => self::MENUE_KREIS,
                'isActive' => self::aktiv('projekt'),
            ]));
        } catch (Throwable $e) {
            Yii::error('nexus-projekte: Kreis-Menue: ' . $e->getMessage(), 'nexus-projekte');
        }
    }

    /** "Meine Aufgaben" in der oberen Leiste -- mit Zahl, wenn etwas offen ist */
    public static function onObenMenue($event): void
    {
        try {
            $ich = Yii::$app->user->getIdentity();
            if (!$ich instanceof User) {
                return;
            }
            $anzahl = MeineAufgaben::anzahl($ich);
            $event->sender->addEntry(new MenuLink([
                'label' => Texte::t('meine_aufgaben') . ($anzahl > 0 ? " ($anzahl)" : ''),
                'url' => ['/nexus-projekte/meine/index'],
                'icon' => 'check-square-o',
                'sortOrder' => self::MENUE_OBEN,
                'isActive' => self::aktiv('meine'),
            ]));
        } catch (Throwable $e) {
            Yii::error('nexus-projekte: obere Leiste: ' . $e->getMessage(), 'nexus-projekte');
        }
    }

    public static function onStartseite($event): void
    {
        if (!Yii::$app->user->isGuest) {
            $event->sender->addWidget(MeineAufgabenKasten::class, [], ['sortOrder' => self::STARTSEITE]);
        }
    }

    /** Taeglich: Fristen und Abstimmungen erinnern -- jede Erinnerung genau einmal */
    public static function onTaeglich($event): void
    {
        $heute = date('Y-m-d');
        $morgen = date('Y-m-d', strtotime('+1 day'));
        try {
            $offen = fn() => Karte::find()->alias('k')->innerJoinWith('projekt p', false)
                ->where(['p.status' => 'aktiv'])->andWhere(['!=', 'k.spalte', 'fertig']);

            foreach ($offen()->andWhere(['k.frist' => $morgen])->andWhere(['or', ['k.erinnert_frist' => null], ['!=', 'k.erinnert_frist', $morgen]])->all() as $k) {
                if (self::istAn($k)) {
                    Nachrichten::fristMorgen($k);
                }
                $k->updateAttributes(['erinnert_frist' => $morgen]);
            }
            foreach ($offen()->andWhere(['<', 'k.frist', $heute])->andWhere(['or', ['k.erinnert_ueberfaellig' => null], ['!=', 'k.erinnert_ueberfaellig', new \yii\db\Expression('k.frist')]])->all() as $k) {
                if (self::istAn($k)) {
                    Nachrichten::ueberfaellig($k);
                }
                $k->updateAttributes(['erinnert_ueberfaellig' => $k->frist]);
            }
            foreach ($offen()->andWhere(['k.spalte' => 'abstimmung', 'k.abstimmung_bis' => $morgen])->andWhere(['<', 'k.erinnert_runde', new \yii\db\Expression('k.runde')])->all() as $k) {
                if (self::istAn($k)) {
                    $schon = array_map('intval', Stimme::find()->select('user_id')->where(['karte_id' => $k->id, 'runde' => $k->runde])->column());
                    // Leitung und Verantwortliche fragen nicht sich selbst
                    $fehlen = array_diff($k->projekt->beteiligteIds(), $schon, [(int)$k->verantwortlich_id], $k->projekt->leitungIds());
                    Nachrichten::abstimmungEndetMorgen($k, $fehlen === [] ? [] : User::find()->where(['id' => $fehlen])->all());
                }
                $k->updateAttributes(['erinnert_runde' => $k->runde]);
            }
        } catch (Throwable $e) {
            Yii::error('nexus-projekte: Erinnerungen: ' . $e->getMessage(), 'nexus-projekte');
        }
    }

    private static function istAn(Karte $k): bool
    {
        $kreis = $k->projekt->kreis;
        return $kreis instanceof Space && Module::istAn($kreis);
    }

    private static function aktiv(string $controller): bool
    {
        $c = Yii::$app->controller;
        return $c !== null && $c->module !== null && $c->module->id === 'nexus-projekte' && $c->id === $controller;
    }
}
