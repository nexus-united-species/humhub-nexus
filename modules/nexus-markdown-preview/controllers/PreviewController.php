<?php

namespace humhub\modules\nexusMarkdownPreview\controllers;

use cebe\markdown\GithubMarkdown;
use humhub\modules\file\models\File;
use Yii;
use yii\helpers\HtmlPurifier;
use yii\web\Controller;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;

/**
 * Rendert eine hochgeladene .md-Datei als formatierte HTML-Seite, statt sie
 * nur zum Herunterladen anzubieten -- HumHubs eingebaute inlineMimeTypes
 * (file/Module.php) kennen nur PDF/GIF/PNG/JPEG, Markdown fehlt dort
 * komplett und wuerde sonst nur als Rohtext im Browser landen.
 *
 * Sicherheit: cebe/markdown laesst eingebettetes rohes HTML im Quelltext
 * standardmaessig durch (wie die meisten Markdown-Parser). Ohne Reinigung
 * koennte eine hochgeladene .md-Datei Skript-Code einschleusen, der beim
 * Ansehen durch ein ANDERES Mitglied ausgefuehrt wuerde -- deshalb IMMER
 * durch HtmlPurifier (dieselbe Bibliothek, die auch HumHubs eigener
 * Rich-Text-Konverter nutzt) schicken, bevor irgendwas angezeigt wird.
 * Zugriff wird zusaetzlich ueber File::canView() geprueft -- der
 * "Vorschau"-Knopf im Frontend (vorschau.js) ist nur Komfort, die eigentliche
 * Berechtigungspruefung passiert hier, serverseitig, fuer jeden Aufruf.
 */
class PreviewController extends Controller
{
    public function actionRender(string $guid)
    {
        $file = File::findOne(['guid' => $guid]);
        if ($file === null || !$this->istMarkdown($file->file_name)) {
            throw new NotFoundHttpException('Keine Markdown-Datei gefunden.');
        }

        if (!$file->canView(Yii::$app->user->identity)) {
            throw new ForbiddenHttpException('Keine Berechtigung fuer diese Datei.');
        }

        $pfad = $file->store->get();
        if (!is_file($pfad)) {
            throw new NotFoundHttpException('Datei nicht gefunden.');
        }

        $rohHtml = (new GithubMarkdown())->parse((string)file_get_contents($pfad));

        return $this->render('render', [
            'dateiname' => $file->file_name,
            'html' => HtmlPurifier::process($rohHtml),
            'downloadUrl' => $file->getUrl(),
        ]);
    }

    private function istMarkdown(string $dateiname): bool
    {
        return (bool)preg_match('/\.md$/i', $dateiname);
    }
}
