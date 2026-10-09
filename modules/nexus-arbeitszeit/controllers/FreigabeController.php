<?php

namespace nexus\modules\arbeitszeit\controllers;

use humhub\components\access\ControllerAccess;
use humhub\modules\admin\components\Controller;
use nexus\modules\arbeitszeit\models\Eintrag;
use humhub\modules\space\models\Space;
use nexus\modules\arbeitszeit\services\NextcloudBericht;
use nexus\modules\arbeitszeit\services\ZeitService;
use Yii;
use yii\web\NotFoundHttpException;

/**
 * Freigabe-Seite: offene Eintraege freigeben / um Ruecksprache bitten, Uebersicht je Person und
 * Kreis, Excel-Export. Nur fuer Admins (HumHub-Administratorgruppe, Josh: "jeder mit Adminrolle").
 */
class FreigabeController extends Controller
{
    /**
     * Admin-Grundklasse von HumHub (Verwaltungsbereich, Admin-Menue). Deren Standardregel waere die
     * Berechtigung \"Einstellungen verwalten\" -- hier bewusst enger: nur Systemadministratoren.
     */
    protected function getAccessRules()
    {
        return [[ControllerAccess::RULE_ADMIN_ONLY]];
    }

    public function actionIndex(?string $monat = null)
    {
        $monat = $this->monat($monat);
        $freigegeben = Eintrag::find()->where(['status' => Eintrag::STATUS_FREIGEGEBEN]);
        if ($monat !== 'alle') {
            $freigegeben->andWhere(['between', 'datum', $monat . '-01', date('Y-m-t', strtotime($monat . '-01'))]);
        }
        return $this->render('index', [
            'monat' => $monat,
            'offen' => Eintrag::find()->where(['status' => Eintrag::STATUS_OFFEN])->orderBy(['created_at' => SORT_ASC])->all(),
            'ruecksprache' => Eintrag::find()->where(['status' => Eintrag::STATUS_RUECKSPRACHE])->orderBy(['geprueft_am' => SORT_DESC])->limit(30)->all(),
            'jePerson' => (clone $freigegeben)->select(['user_id', 'name_extern', 'summe' => 'SUM(stunden)', 'anzahl' => 'COUNT(*)'])->groupBy(['user_id', 'name_extern'])->orderBy(['summe' => SORT_DESC])->asArray()->all(),
            'imZeitraum' => $this->imZeitraum($monat)->with(['user', 'space'])->orderBy(['datum' => SORT_DESC, 'id' => SORT_DESC])->limit(300)->all(),
            'jeKreis' => (clone $freigegeben)->select(['space_id', 'summe' => 'SUM(stunden)'])->groupBy('space_id')->orderBy(['summe' => SORT_DESC])->asArray()->all(),
            'monate' => $this->monateMitEintraegen(),
        ]);
    }

    public function actionFreigeben(int $id)
    {
        $this->forcePostRequest();
        ZeitService::freigeben($this->offenerEintrag($id), Yii::$app->user->getIdentity());
        Yii::$app->session->setFlash('nexus-az-ok', 'Freigegeben – die Person bekommt eine Nachricht.');
        return $this->redirect(['index']);
    }

    public function actionRuecksprache(int $id)
    {
        $this->forcePostRequest();
        ZeitService::ruecksprache($this->offenerEintrag($id), Yii::$app->user->getIdentity(), (string)Yii::$app->request->post('grund'));
        Yii::$app->session->setFlash('nexus-az-ok', 'Rückfrage gesendet – die Person bekommt eine Nachricht.');
        return $this->redirect(['index']);
    }

    /**
     * Excel-taugliche CSV: Semikolon, Komma als Dezimalzeichen, UTF-8 mit BOM -- so oeffnet ein
     * deutsches Excel die Datei per Doppelklick mit richtigen Umlauten und Spalten.
     */
    public function actionExport(?string $monat = null)
    {
        $monat = $this->monat($monat);
        $abfrage = $this->imZeitraum($monat)->with(['user', 'space', 'pruefer'])->orderBy(['datum' => SORT_ASC, 'id' => SORT_ASC]);
        $zeilen = [['Datum', 'Name', 'Kreis', 'Stunden', 'Tätigkeit', 'Status', 'Eingetragen am', 'Eingetragen über', 'Geprüft von', 'Geprüft am', 'Rückfrage']];
        foreach ($abfrage->all() as $e) {
            $zeilen[] = [
                ZeitService::datumText($e->datum), $e->name(), $e->space->name ?? '–',
                number_format((float)$e->stunden, 2, ',', ''), $e->beschreibung,
                ['offen' => 'wartet', 'freigegeben' => 'freigegeben', 'ruecksprache' => 'Rücksprache'][$e->status] ?? $e->status,
                date('d.m.Y H:i', strtotime($e->created_at)), $e->quelle === 'assistent' ? 'Assistent' : 'Formular',
                $e->pruefer->displayName ?? '', $e->geprueft_am ? date('d.m.Y H:i', strtotime($e->geprueft_am)) : '', $e->rueckfrage ?? '',
            ];
        }
        $csv = "\xEF\xBB\xBF";
        foreach ($zeilen as $zeile) {
            $csv .= implode(';', array_map(fn($w) => '"' . str_replace('"', '""', (string)$w) . '"', $zeile)) . "\r\n";
        }
        return Yii::$app->response->sendContentAsFile($csv, 'NEXUS_Arbeitsstunden_' . ($monat === 'alle' ? 'gesamt' : $monat) . '.csv', ['mimeType' => 'text/csv']);
    }

    /**
     * Nachtraegliche Korrektur durch einen Admin (Josh, 28.09.2026: "angenommen es muss nachtraeglich
     * etwas veraendert werden"). Geaendert wird HIER, nicht in der Excel -- die zieht automatisch nach.
     * Jede Aenderung landet mit Datum und Namen im Verlauf des Eintrags.
     */
    public function actionBearbeiten(int $id)
    {
        $eintrag = Eintrag::findOne(['id' => $id]);
        if ($eintrag === null || $eintrag->status === Eintrag::STATUS_ENTWURF) {
            throw new NotFoundHttpException('Eintrag nicht gefunden.');
        }
        $fehler = [];
        if (Yii::$app->request->isPost) {
            $r = Yii::$app->request;
            $vorher = ['stunden' => $eintrag->stundenText(), 'datum' => ZeitService::datumText($eintrag->datum), 'kreis' => $eintrag->space->name ?? '–',
                'beschreibung' => $eintrag->beschreibung, 'status' => $eintrag->status];
            $eintrag->stunden = str_replace(',', '.', trim((string)$r->post('stunden')));
            $eintrag->datum = (string)$r->post('datum');
            $eintrag->space_id = (int)$r->post('space_id') ?: null;
            $eintrag->beschreibung = (string)$r->post('beschreibung');
            $status = (string)$r->post('status');
            if (in_array($status, [Eintrag::STATUS_OFFEN, Eintrag::STATUS_FREIGEGEBEN, Eintrag::STATUS_RUECKSPRACHE], true)) {
                $eintrag->status = $status;
            }
            if ($eintrag->space_id !== null && Space::findOne(['id' => $eintrag->space_id]) === null) {
                $fehler[] = 'Kreis nicht gefunden.';
            }
            if ($eintrag->validate() && !$fehler) {
                $nachher = ['stunden' => $eintrag->stundenText(), 'datum' => ZeitService::datumText($eintrag->datum),
                    'kreis' => Space::findOne(['id' => $eintrag->space_id])->name ?? '–', 'beschreibung' => $eintrag->beschreibung, 'status' => $eintrag->status];
                $namen = ['stunden' => 'Stunden', 'datum' => 'Tag', 'kreis' => 'Kreis', 'beschreibung' => 'Tätigkeit', 'status' => 'Status'];
                $aenderungen = [];
                foreach ($namen as $feld => $name) {
                    if ((string)$vorher[$feld] !== (string)$nachher[$feld]) {
                        $aenderungen[] = $feld === 'beschreibung' ? 'Tätigkeit geändert' : "$name {$vorher[$feld]} → {$nachher[$feld]}";
                    }
                }
                if ($aenderungen) {
                    $admin = Yii::$app->user->getIdentity();
                    if ($vorher['status'] !== $eintrag->status && $eintrag->status !== Eintrag::STATUS_OFFEN) {
                        $eintrag->geprueft_von = $admin->id;
                        $eintrag->geprueft_am = date('Y-m-d H:i:s');
                    }
                    $eintrag->protokolliere($admin->displayName, implode('; ', $aenderungen));
                    $eintrag->save(false);
                    NextcloudBericht::vormerken();
                    Yii::$app->session->setFlash('nexus-az-ok', 'Gespeichert: ' . implode('; ', $aenderungen) . '. Die Excel-Datei zieht automatisch nach.');
                } else {
                    Yii::$app->session->setFlash('nexus-az-ok', 'Keine Änderung.');
                }
                return $this->redirect(['index']);
            }
            foreach (array_keys($eintrag->errors) as $feld) {
                $fehler[] = ['stunden' => 'Stunden: bitte eine Zahl zwischen 0,25 und 16.', 'datum' => 'Tag: bitte ein Datum, nicht in der Zukunft.',
                    'beschreibung' => 'Tätigkeit: bitte kurz beschreiben (höchstens 500 Zeichen).'][$feld] ?? $feld;
            }
        }
        return $this->render('bearbeiten', [
            'eintrag' => $eintrag,
            'fehler' => $fehler,
            'kreise' => Space::find()->where(['status' => Space::STATUS_ENABLED])->orderBy(['sort_order' => SORT_ASC, 'name' => SORT_ASC])->all(),
        ]);
    }

    public function actionEntfernen(int $id)
    {
        $this->forcePostRequest();
        $eintrag = Eintrag::findOne(['id' => $id]);
        if ($eintrag === null) {
            throw new NotFoundHttpException('Eintrag nicht gefunden.');
        }
        Yii::info('nexus-arbeitszeit: Eintrag ' . $id . ' (' . $eintrag->name() . ', ' . $eintrag->stundenText() . ' Std., ' . $eintrag->datum
            . ') geloescht von ' . Yii::$app->user->getIdentity()->displayName, 'nexus-arbeitszeit');
        $eintrag->delete();
        NextcloudBericht::vormerken();
        Yii::$app->session->setFlash('nexus-az-ok', 'Eintrag gelöscht. Die Excel-Datei zieht automatisch nach.');
        return $this->redirect(['index']);
    }

    private function imZeitraum(string $monat)
    {
        $abfrage = Eintrag::find()->where(['!=', 'status', Eintrag::STATUS_ENTWURF]);
        if ($monat !== 'alle') {
            $abfrage->andWhere(['between', 'datum', $monat . '-01', date('Y-m-t', strtotime($monat . '-01'))]);
        }
        return $abfrage;
    }

    private function offenerEintrag(int $id): Eintrag
    {
        $eintrag = Eintrag::findOne(['id' => $id, 'status' => Eintrag::STATUS_OFFEN]);
        if ($eintrag === null) {
            throw new NotFoundHttpException('Eintrag nicht gefunden oder schon bearbeitet.');
        }
        return $eintrag;
    }

    private function monat(?string $monat): string
    {
        return ($monat === 'alle' || ($monat !== null && preg_match('/^\d{4}-\d{2}$/', $monat))) ? $monat : date('Y-m');
    }

    private function monateMitEintraegen(): array
    {
        $monate = Eintrag::find()->select(['m' => "DATE_FORMAT(datum, '%Y-%m')"])->distinct()->orderBy(['m' => SORT_DESC])->column();
        $monate[] = date('Y-m');
        $monate = array_values(array_unique($monate));
        rsort($monate);
        return $monate;
    }
}
