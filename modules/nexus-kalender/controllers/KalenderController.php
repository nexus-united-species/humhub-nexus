<?php

namespace nexus\modules\kalender\controllers;

use humhub\components\access\ControllerAccess;
use humhub\components\Controller;
use humhub\modules\post\models\Post;
use humhub\modules\post\permissions\CreatePost;
use humhub\modules\space\models\Membership;
use humhub\modules\space\models\Space;
use nexus\modules\kalender\Module;
use nexus\modules\kalender\services\Texte;
use Yii;
use yii\helpers\Url;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/** Die Seite mit dem Kalender, die Datei selbst, das Deckblatt und das Teilen in einen Kreis. */
class KalenderController extends Controller
{
    private const FLASH = 'nx-kalender';

    /** Nur fuer die Kalender-Datei: Sie braucht Skripte und Stile direkt im HTML, laedt aber nichts von aussen. */
    private const CSP = "default-src 'none'; script-src 'unsafe-inline'; style-src 'unsafe-inline'; img-src data:; font-src data:; frame-ancestors 'self'";

    protected function getAccessRules()
    {
        return [
            [ControllerAccess::RULE_LOGGED_IN_ONLY],
            [ControllerAccess::RULE_POST => ['teilen']],
        ];
    }

    public function actionIndex()
    {
        $modul = Module::instanz();
        return $this->render('index', [
            'modul' => $modul,
            'vorhanden' => is_file($modul->datei('index.html')),
            'teilenKreise' => $modul->entwurf ? [] : $this->teilenKreise(),
            'meldung' => Yii::$app->session->getFlash(self::FLASH),
        ]);
    }

    /** Die Kalender-Datei selbst, fuer den Rahmen auf der Seite und fuer "In neuem Tab oeffnen" */
    public function actionInhalt()
    {
        $antwort = $this->dateiAntwort('index.html', 'text/html; charset=utf-8');
        // Erst unmittelbar vor dem Senden: HumHub setzt seine eigenen Sicherheitsregeln (mit Nonce) zusaetzlich, und
        // damit liefe die Datei nicht. Die Regeln gelten nur fuer diese eine Antwort.
        $antwort->on(Response::EVENT_AFTER_PREPARE, static function ($event) {
            $kopf = $event->sender->headers;
            foreach ($kopf->toArray() as $name => $wert) {
                if (stripos($name, 'content-security-policy') !== false) {
                    $kopf->remove($name);
                }
            }
            $kopf->set('Content-Security-Policy', self::CSP);
            $kopf->set('X-Robots-Tag', 'noindex, nofollow');
        });
        return $antwort;
    }

    public function actionDeckblatt()
    {
        return $this->dateiAntwort('deckblatt.jpg', 'image/jpeg');
    }

    public function actionTeilen()
    {
        $modul = Module::instanz();
        $ziel = null;
        foreach ($modul->entwurf ? [] : $this->teilenKreise() as $kreis) {
            if ((int)$kreis->id === (int)Yii::$app->request->post('kreis')) {
                $ziel = $kreis;
            }
        }
        if ($ziel === null) {
            Yii::$app->session->setFlash(self::FLASH, Texte::t('teilen_fehler'));
            return $this->redirect(['index']);
        }
        $satz = trim(mb_substr((string)Yii::$app->request->post('text'), 0, 1000));
        $post = new Post($ziel);
        $post->message = '📅 **' . Texte::titel() . "**\n\n"
            . ($satz !== '' ? $satz . "\n\n" : '')
            . (is_file($modul->datei('deckblatt.jpg')) ? '![' . Texte::titel() . '](' . Url::to(['deckblatt'], true) . ")\n\n" : '')
            . '[' . Texte::t('ansehen') . '](' . Url::to(['index'], true) . ')';
        if (!$post->save()) {
            Yii::$app->session->setFlash(self::FLASH, Texte::t('teilen_fehler'));
            return $this->redirect(['index']);
        }
        return $this->redirect($post->content->getUrl());
    }

    private function dateiAntwort(string $name, string $typ): Response
    {
        $pfad = Module::instanz()->datei($name);
        if (!is_file($pfad)) {
            throw new NotFoundHttpException();
        }
        $antwort = Yii::$app->response;
        // Kein Zwischenspeichern ohne Nachfrage: Eine neue Fassung soll sofort ankommen, und unveraendert spart die
        // Nachfrage die rund 11 MB.
        $kennung = '"' . md5(filemtime($pfad) . '-' . filesize($pfad)) . '"';
        $antwort->headers->set('ETag', $kennung);
        $antwort->headers->set('Cache-Control', 'private, no-cache');
        if (Yii::$app->request->headers->get('If-None-Match') === $kennung) {
            $antwort->statusCode = 304;
            return $antwort;
        }
        return $antwort->sendFile($pfad, $name, ['inline' => true, 'mimeType' => $typ]);
    }

    /** @return Space[] Kreise, in denen die Person Mitglied ist und schreiben darf */
    private function teilenKreise(): array
    {
        $kreise = Space::find()
            ->innerJoin('space_membership m', 'm.space_id = space.id')
            ->where(['m.user_id' => (int)Yii::$app->user->id, 'm.status' => Membership::STATUS_MEMBER, 'space.status' => Space::STATUS_ENABLED])
            ->orderBy(['space.sort_order' => SORT_ASC, 'space.name' => SORT_ASC])
            ->all();
        return array_values(array_filter($kreise, static fn(Space $k) => $k->getPermissionManager()->can(new CreatePost())));
    }
}
