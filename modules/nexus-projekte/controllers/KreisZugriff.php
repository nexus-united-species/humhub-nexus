<?php

namespace nexus\modules\projekte\controllers;

use humhub\modules\space\models\Space;
use humhub\modules\user\models\User;
use nexus\modules\projekte\models\Karte;
use nexus\modules\projekte\models\Projekt;
use nexus\modules\projekte\services\Texte;
use Yii;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;

/**
 * Gemeinsame Hilfen der Projekt-Steuerungen: Jede Aktion prueft, dass Projekt und Kachel zu DIESEM Kreis
 * gehoeren -- eine fremde Nummer in der Adresse fuehrt zu "nicht gefunden", nicht in einen anderen Kreis.
 */
trait KreisZugriff
{
    protected function kreis(): Space
    {
        return $this->contentContainer;
    }

    protected function ich(): User
    {
        return Yii::$app->user->getIdentity();
    }

    protected function pruefe(bool $erlaubt): void
    {
        if (!$erlaubt) {
            throw new ForbiddenHttpException(Texte::t('nur_mitglieder'));
        }
    }

    protected function projekt(int $id): Projekt
    {
        $projekt = Projekt::findOne(['id' => $id, 'space_id' => $this->kreis()->id]);
        if ($projekt === null) {
            throw new NotFoundHttpException();
        }
        $projekt->populateRelation('kreis', $this->kreis());
        return $projekt;
    }

    protected function karte(Projekt $projekt, int $id): Karte
    {
        $karte = Karte::findOne(['id' => $id, 'projekt_id' => $projekt->id]);
        if ($karte === null) {
            throw new NotFoundHttpException();
        }
        $karte->populateRelation('projekt', $projekt);
        return $karte;
    }

    protected function karteImKreis(int $id): Karte
    {
        $karte = Karte::findOne(['id' => $id]);
        if ($karte === null) {
            throw new NotFoundHttpException();
        }
        return $this->karte($this->projekt((int)$karte->projekt_id), $id);
    }

    /**
     * Personen aus der Personenauswahl (GUIDs) -- nur Mitglieder dieses Kreises.
     * @return User[]
     */
    protected function personen(array $guids): array
    {
        $guids = array_values(array_filter(array_map('strval', $guids)));
        if ($guids === []) {
            return [];
        }
        $kreis = $this->kreis();
        return array_values(array_filter(
            User::find()->where(['guid' => $guids, 'status' => User::STATUS_ENABLED])->all(),
            fn(User $u) => $kreis->isMember($u->id) || $u->isSystemAdmin()
        ));
    }

    /** "Titel | https://..." oder nur die Adresse, eine je Zeile */
    protected function linksAus(string $text): array
    {
        $liste = [];
        foreach (preg_split('/\R/', $text) as $zeile) {
            $teile = array_map('trim', explode('|', $zeile, 2));
            if (count($teile) === 2) {
                $liste[] = ['titel' => $teile[0], 'url' => $teile[1]];
            } elseif ($teile[0] !== '') {
                $liste[] = ['titel' => '', 'url' => $teile[0]];
            }
        }
        return $liste;
    }
}
