<?php

namespace nexus\modules\supporterBridge\models\forms;

use humhub\modules\user\models\User;
use nexus\modules\supporterBridge\models\Supporter;
use yii\base\Model;

/**
 * Formular fuer "Manuellen Unterstuetzer anlegen" -- nutzt HumHubs eigenes
 * Personenauswahlfeld (UserPickerField, dieselbe Komponente wie beim
 * Einladen in einen Space: Namen tippen, aus Vorschlagsliste waehlen)
 * statt einer manuell eingetippten E-Mail-Adresse. Vorteil gegenueber der
 * E-Mail-Eingabe: es wird ein konkretes, existierendes Konto ausgewaehlt,
 * kein Tippfehler-anfaelliger Textabgleich noetig (Vorgabe Josh 09.09.2026:
 * "geht das auch ueber den Nutzernamen wie bei den anderen Funktionen").
 */
class ManualSupporterForm extends Model
{
    // UserPickerField liefert immer ein Array von GUIDs (auch bei
    // maxSelection=1) -- siehe humhub\modules\space\models\forms\InviteForm
    // als Referenzimplementierung im HumHub-Kern.
    public $invite;

    public $status = Supporter::STATUS_MANUAL;

    public $expires_at;

    public $note;

    public function rules()
    {
        return [
            [['invite'], 'required'],
            [['invite'], 'validateInvite'],
            [['status'], 'in', 'range' => [Supporter::STATUS_MANUAL, Supporter::STATUS_LIFETIME, Supporter::STATUS_ACTIVE]],
            [['expires_at', 'note'], 'string'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'invite' => 'HumHub-Konto',
            'status' => 'Zugangsart',
            'expires_at' => 'Ablaufdatum',
            'note' => 'Grund / Notiz',
        ];
    }

    public function validateInvite($attribute, $params)
    {
        if ($this->getUser() === null) {
            $this->addError($attribute, 'Bitte ein Konto aus der Vorschlagsliste auswaehlen.');
        }
    }

    public function getUser(): ?User
    {
        $guids = (array)$this->invite;
        $guid = reset($guids);
        if (!$guid) {
            return null;
        }

        return User::findOne(['guid' => $guid, 'status' => User::STATUS_ENABLED]);
    }
}
