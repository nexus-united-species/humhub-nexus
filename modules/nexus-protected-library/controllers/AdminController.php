<?php

namespace nexus\modules\protectedLibrary\controllers;

use nexus\modules\protectedLibrary\models\Book;
use nexus\modules\protectedLibrary\services\EpubImportService;
use Yii;
use humhub\components\access\ControllerAccess;
use humhub\components\Controller;
use yii\web\NotFoundHttpException;
use yii\web\UploadedFile;

class AdminController extends Controller
{
    /** Nur Systemadministratoren -- ueber HumHubs Zugriffsregeln (auch Gast-, Wartungs- und Kontoregeln). */
    protected function getAccessRules()
    {
        return [[ControllerAccess::RULE_ADMIN_ONLY]];
    }

    public function actionIndex()
    {
        $buecher = Book::find()->orderBy(['sort_order' => SORT_ASC, 'title' => SORT_ASC])->all();

        return $this->render('index', ['buecher' => $buecher]);
    }

    public function actionUpload()
    {
        if (Yii::$app->request->isPost) {
            $datei = UploadedFile::getInstanceByName('epub');
            if ($datei === null || $datei->error !== UPLOAD_ERR_OK) {
                Yii::$app->session->setFlash('error', 'Keine gueltige Datei hochgeladen.');
                return $this->render('upload');
            }

            if (strtolower($datei->extension) !== 'epub') {
                Yii::$app->session->setFlash('error', 'Nur .epub-Dateien werden unterstuetzt.');
                return $this->render('upload');
            }

            $tempPfad = Yii::getAlias('@runtime') . '/nexus-library-upload-' . uniqid() . '.epub';
            if (!$datei->saveAs($tempPfad)) {
                Yii::$app->session->setFlash('error', 'Datei konnte nicht zwischengespeichert werden.');
                return $this->render('upload');
            }

            $titel = trim((string)Yii::$app->request->post('title'));
            $autor = trim((string)Yii::$app->request->post('author'));
            $sprache = (string)Yii::$app->request->post('language', Book::SPRACHE_DE);
            $mediaType = (string)Yii::$app->request->post('media_type', Book::TYP_ROMAN);

            if (!isset(Book::SPRACHEN[$sprache]) || !isset(Book::TYPEN[$mediaType])) {
                Yii::$app->session->setFlash('error', 'Ungueltige Sprache oder Kategorie.');
                unlink($tempPfad);
                return $this->render('upload');
            }

            try {
                $buch = (new EpubImportService())->importieren(
                    $tempPfad,
                    $titel !== '' ? $titel : null,
                    $autor !== '' ? $autor : null,
                    $sprache,
                    $mediaType
                );
                Yii::$app->session->setFlash('success', "Buch \"{$buch->title}\" importiert (" . count($buch->chapters) . " Kapitel).");
                return $this->redirect(['index']);
            } catch (\Throwable $e) {
                Yii::$app->session->setFlash('error', 'Import fehlgeschlagen: ' . $e->getMessage());
                return $this->render('upload');
            } finally {
                // Die hochgeladene EPUB-Datei wird nach dem Import nicht
                // aufbewahrt -- nur die daraus gelesenen Kapitel bleiben.
                if (is_file($tempPfad)) {
                    unlink($tempPfad);
                }
            }
        }

        return $this->render('upload');
    }

    public function actionToggleActive(int $id)
    {
        $this->requirePost();
        $buch = $this->findBook($id);
        $buch->active = !$buch->active;
        $buch->save(false);

        Yii::$app->session->setFlash('success', $buch->active ? 'Buch sichtbar geschaltet.' : 'Buch verborgen.');
        return $this->redirect(['index']);
    }

    public function actionDelete(int $id)
    {
        $this->requirePost();
        $buch = $this->findBook($id);
        $titel = $buch->title;
        $buch->delete();

        Yii::$app->session->setFlash('success', "\"{$titel}\" geloescht.");
        return $this->redirect(['index']);
    }

    private function findBook(int $id): Book
    {
        $buch = Book::findOne($id);
        if ($buch === null) {
            throw new NotFoundHttpException('Buch nicht gefunden.');
        }

        return $buch;
    }

    private function requirePost(): void
    {
        if (!Yii::$app->request->isPost) {
            throw new \yii\web\MethodNotAllowedHttpException();
        }
    }
}
