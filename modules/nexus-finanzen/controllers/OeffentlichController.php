<?php

namespace nexus\modules\finanzen\controllers;

use humhub\components\Controller;
use nexus\modules\finanzen\Module;
use nexus\modules\finanzen\services\Geld;
use nexus\modules\finanzen\services\Stand;
use Yii;
use yii\web\MethodNotAllowedHttpException;
use yii\web\Response;

/**
 * Oeffentliche Zahlen fuer die eigene Webseite ("Mithelfen"): GET /nexus-finanzen/oeffentlich/stand?monat=YYYY-MM
 * Nur Summen und die als oeffentlich markierten Kacheln -- keine Namen, keine Referenzen, keine Notizen, keine
 * einzelnen Eingaenge. Im Browser abrufen duerfen nur die eingestellten Webseiten (CORS).
 */
class OeffentlichController extends Controller
{
    public function actionStand(?string $monat = null)
    {
        if (!Yii::$app->request->isGet) {
            throw new MethodNotAllowedHttpException();
        }
        if (!Module::instanz()->fuerAlle()) {
            throw new \yii\web\NotFoundHttpException();
        }
        $antwort = Yii::$app->response;
        $antwort->format = Response::FORMAT_JSON;
        $herkunft = (string)Yii::$app->request->headers->get('Origin');
        if (in_array($herkunft, Module::instanz()->webseiten(), true)) {
            $antwort->headers->set('Access-Control-Allow-Origin', $herkunft);
        }
        $antwort->headers->set('Vary', 'Origin');
        header_remove('Pragma');
        header_remove('Expires');
        $antwort->headers->set('Cache-Control', 'public, max-age=300');
        $antwort->headers->set('X-Robots-Tag', 'noindex');

        $monat = Geld::monat($monat);
        $stand = new Stand();
        $summen = $stand->summen($monat);
        $bereiche = [];
        foreach (['grund', 'optional', 'entwicklung'] as $b) {
            $kacheln = [];
            foreach ($stand->kacheln($b) as $k) {
                if (!$k->oeffentlich) {
                    continue;
                }
                $kacheln[] = [
                    'titel' => $k->titel,
                    'gruppe' => $k->gruppe,
                    'beschreibung' => $k->beschreibung,
                    'art' => $k->art,
                    'betrag_cent' => (int)$k->betrag,
                    'gedeckt_cent' => min((int)$k->betrag, $stand->gedeckt($k, $monat)),
                    'prozent' => $stand->prozent($k, $monat),
                    'ampel' => $stand->farbe($k, $monat),
                    'einordnung' => $k->einordnung,
                    'zusatz' => $k->zusatz,
                    // Vorgestreckt = aus eigener Tasche bezahlt, noch nicht erstattet (ohne Namen)
                    'bezahlt_cent' => $stand->bezahlt($k, $monat)['betrag'] ?? 0,
                    'vorgestreckt_cent' => $stand->vorgestreckt($k, $monat)['betrag'] ?? 0,
                    'vorgestreckt_geschaetzt' => $stand->vorgestreckt($k, $monat)['geschaetzt'] ?? false,
                ];
            }
            $bereiche[$b] = $stand->bereich($b, $monat, true) + ['kacheln' => $kacheln];
        }
        return [
            'monat' => $monat,
            'stand' => date('c'),
            'waehrung' => 'EUR',
            'hinweis' => 'Betraege in Cent. ampel: grau = geplant/zu pruefen, rot = offen, gelb = teilweise, gruen = gedeckt (nicht: bezahlt). ziel = alle Kacheln, freigegeben = beschlossene, gedeckt zaehlt hoechstens den Bedarf.',
            'summen' => [
                'eingegangen_cent' => $summen['eingegangen'],
                'eingegangen_monat_cent' => $summen['im_monat'],
                'reserviert_cent' => $summen['reserviert'],
                'ausgegeben_cent' => $summen['ausgegeben'],
                'frei_cent' => $summen['frei'],
                'vorgestreckt_cent' => $summen['vorgestreckt'],
                'bezahlt_gesamt_cent' => $summen['bezahlt_gesamt'],
                'bezahlt_monat_cent' => $summen['bezahlt_monat'],
                'vorgestreckt_monat_cent' => $summen['vorgestreckt_monat'],
            ],
            'entwicklungsdeckel_cent' => Module::instanz()->entwicklungsDeckel(),
            'bereiche' => $bereiche,
        ];
    }
}
