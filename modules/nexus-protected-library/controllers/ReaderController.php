<?php

namespace nexus\modules\protectedLibrary\controllers;

use humhub\modules\space\models\Space;
use nexus\modules\protectedLibrary\models\Book;
use nexus\modules\protectedLibrary\models\Chapter;
use Yii;
use yii\web\Controller;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;

/**
 * Der eigentliche Lesebereich -- nur fuer Mitglieder des
 * Unterstuetzer-Space (SUPPORTER_SPACE_ID, dieselbe Umgebungsvariable wie
 * bei der Ko-fi-Bruecke) und Systemadministratoren.
 */
class ReaderController extends Controller
{
    public function beforeAction($action)
    {
        if (!$this->hatZugang()) {
            throw new ForbiddenHttpException('Dieser Bereich ist nur fuer Unterstuetzer:innen zugaenglich.');
        }

        return parent::beforeAction($action);
    }

    /**
     * Erste Ebene: Sprachauswahl. Zeigt immer alle drei Sprachen an, auch
     * wenn eine davon noch keine Inhalte hat -- die Gliederung soll von
     * Anfang an sichtbar sein (Vorgabe Josh 09.09.2026).
     */
    public function actionIndex()
    {
        $anzahlJeSprache = [];
        foreach (array_keys(Book::SPRACHEN) as $sprache) {
            $anzahlJeSprache[$sprache] = (int)Book::find()->where(['active' => true, 'language' => $sprache])->count();
        }

        return $this->render('index', ['anzahlJeSprache' => $anzahlJeSprache]);
    }

    /**
     * Zweite Ebene: Kategorie-Auswahl innerhalb einer Sprache (Romane,
     * Hörbücher, Videos, Bilder, Texte). Ebenfalls alle Kategorien
     * anzeigen, auch leere -- als Vorschau auf das, was noch kommt.
     */
    public function actionLanguage(string $language)
    {
        $this->requireGueltigeSprache($language);

        $anzahlJeTyp = [];
        foreach (array_keys(Book::TYPEN) as $typ) {
            $anzahlJeTyp[$typ] = (int)Book::find()->where(['active' => true, 'language' => $language, 'media_type' => $typ])->count();
        }

        return $this->render('language', [
            'sprache' => $language,
            'anzahlJeTyp' => $anzahlJeTyp,
        ]);
    }

    /**
     * Dritte Ebene: die eigentliche Buecherliste innerhalb Sprache + Kategorie.
     */
    public function actionCategory(string $language, string $mediaType)
    {
        $this->requireGueltigeSprache($language);
        if (!isset(Book::TYPEN[$mediaType])) {
            throw new NotFoundHttpException('Unbekannte Kategorie.');
        }

        $buecher = Book::find()
            ->where(['active' => true, 'language' => $language, 'media_type' => $mediaType])
            ->orderBy(['sort_order' => SORT_ASC, 'title' => SORT_ASC])
            ->all();

        return $this->render('category', [
            'sprache' => $language,
            'mediaType' => $mediaType,
            'buecher' => $buecher,
        ]);
    }

    private function requireGueltigeSprache(string $language): void
    {
        if (!isset(Book::SPRACHEN[$language])) {
            throw new NotFoundHttpException('Unbekannte Sprache.');
        }
    }

    public function actionBook(int $id)
    {
        $buch = $this->findBook($id);
        $ersteKapitel = Chapter::find()->where(['book_id' => $buch->id])->orderBy(['sort_order' => SORT_ASC])->one();

        if ($ersteKapitel === null) {
            throw new NotFoundHttpException('Dieses Buch hat noch keine Kapitel.');
        }

        return $this->redirect(['chapter', 'bookId' => $buch->id, 'chapterId' => $ersteKapitel->id]);
    }

    public function actionChapter(int $bookId, int $chapterId)
    {
        $buch = $this->findBook($bookId);
        $kapitel = Chapter::findOne(['id' => $chapterId, 'book_id' => $buch->id]);
        if ($kapitel === null) {
            throw new NotFoundHttpException('Kapitel nicht gefunden.');
        }

        $alleKapitel = Chapter::find()->where(['book_id' => $buch->id])->orderBy(['sort_order' => SORT_ASC])->all();

        $index = null;
        foreach ($alleKapitel as $i => $k) {
            if ($k->id === $kapitel->id) {
                $index = $i;
                break;
            }
        }

        $vorheriges = $index !== null && $index > 0 ? $alleKapitel[$index - 1] : null;
        $naechstes = $index !== null && $index < count($alleKapitel) - 1 ? $alleKapitel[$index + 1] : null;

        return $this->render('chapter', [
            'buch' => $buch,
            'kapitel' => $kapitel,
            'alleKapitel' => $alleKapitel,
            'vorheriges' => $vorheriges,
            'naechstes' => $naechstes,
        ]);
    }

    private function findBook(int $id): Book
    {
        $buch = Book::findOne(['id' => $id, 'active' => true]);
        if ($buch === null) {
            throw new NotFoundHttpException('Buch nicht gefunden.');
        }

        return $buch;
    }

    private function hatZugang(): bool
    {
        if (Yii::$app->user->isGuest) {
            return false;
        }

        $identity = Yii::$app->user->identity;
        if ($identity->isSystemAdmin()) {
            return true;
        }

        $spaceId = (int)getenv('SUPPORTER_SPACE_ID');
        if ($spaceId <= 0) {
            return false;
        }

        $space = Space::findOne(['id' => $spaceId]);
        return $space !== null && $space->isMember($identity->id);
    }
}
