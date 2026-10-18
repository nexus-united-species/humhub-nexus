<?php

namespace nexus\modules\themen;

use humhub\modules\content\components\ContentContainerActiveRecord;
use humhub\modules\topic\models\Topic;
use nexus\modules\themen\assets\ThemenAsset;
use Throwable;
use Yii;

/**
 * Themen wie bei Telegram (Josh, 18.10.2026: "Der Feed ist unordentlich, man findet Beiträge zu Themen
 * nicht"). Baut auf HumHubs eigenen Themen (Topics) auf -- die gab es schon, waren aber versteckt:
 * - Themen-Leiste ueber dem Feed jedes Kreises, der Themen hat: ein Klick filtert den Feed.
 * - Beim Schreiben eine sichtbare Auswahl "Thema" mit der Vorgabe "Allgemein".
 * Themen anlegen, umbenennen und sortieren die Kreis-Admins wie gehabt in HumHub (Kreis -> Zahnrad ->
 * Einstellungen -> Themen); Mitglieder koennen keine neuen Themen anlegen (HumHub-Recht AddTopic).
 */
class Module extends \humhub\components\Module
{
    /** Name des Vorgabe-Themas, das einmalig in jedem Kreis mit Themen angelegt wird */
    public $allgemeinName = '💬 Allgemein';

    public function getName()
    {
        return 'N.E.X.U.S. Topic Bar';
    }

    public function getDescription()
    {
        return 'Topic bar above the space stream and a visible topic choice when posting.';
    }

    public static function onLayoutAddonInit($event): void
    {
        try {
            if (!Yii::$app->user->isGuest) {
                ThemenAsset::register(Yii::$app->view);
            }
        } catch (Throwable $e) {
            Yii::error('nexus-themen: ' . $e->getMessage(), 'nexus-themen');
        }
    }

    /**
     * Thema beim Bearbeiten eines Beitrags aendern (Rueckmeldung 18.10.2026: "falsches Thema gewaehlt,
     * kann man nachtraeglich nicht aendern"). HumHubs Bearbeiten-Formular kennt keine Themen -- unser
     * Skript schickt "nexusThema" mit. Nur in der Bearbeiten-Seite von Beitraegen (dort prueft HumHub
     * vorher canEdit), nur Themen desselben Kreises; leer = nichts aendern.
     */
    public static function onBeitragGespeichert($event): void
    {
        try {
            $app = Yii::$app;
            if (!$app->request instanceof \yii\web\Request || $app->controller === null
                || $app->controller->getRoute() !== 'post/post/edit' || !$app->request->isPost) {
                return;
            }
            $id = (int)$app->request->post('nexusThema');
            $beitrag = $event->sender;
            if ($id <= 0 || !$beitrag instanceof \humhub\modules\post\models\Post || !$beitrag->content->canEdit()) {
                return;
            }
            $thema = Topic::findOne(['id' => $id, 'contentcontainer_id' => $beitrag->content->contentcontainer_id]);
            if ($thema !== null) {
                Topic::attach($beitrag, [$thema]);   // ersetzt die bisherigen Themen des Beitrags
            }
        } catch (Throwable $e) {
            Yii::error('nexus-themen: Thema beim Bearbeiten nicht gesetzt: ' . $e->getMessage(), 'nexus-themen');
        }
    }

    /** @return Topic[] in der Reihenfolge, die die Kreis-Admins eingestellt haben */
    public function themen(ContentContainerActiveRecord $kreis): array
    {
        return Topic::find()->where(['contentcontainer_id' => $kreis->contentcontainer_id])
            ->orderBy(['sort_order' => SORT_ASC, 'name' => SORT_ASC])->all();
    }

    /**
     * Das Vorgabe-Thema "Allgemein": wird EINMAL je Kreis angelegt, sobald der Kreis Themen hat.
     * Loeschen die Kreis-Admins es spaeter, bleibt es geloescht -- dann gibt es keine Vorgabe.
     */
    public function allgemein(ContentContainerActiveRecord $kreis, array $themen): ?Topic
    {
        $schluessel = 'allgemein.' . $kreis->contentcontainer_id;
        $id = (int)$this->settings->get($schluessel, 0);
        if ($id > 0) {
            foreach ($themen as $t) {
                if ((int)$t->id === $id) {
                    return $t;
                }
            }
            return null;
        }
        if ($themen === []) {
            return null;
        }
        foreach ($themen as $t) {
            if (trim(preg_replace('/[^\p{L}]+/u', '', mb_strtolower($t->name))) === 'allgemein') {
                $this->settings->set($schluessel, $t->id);
                return $t;
            }
        }
        $kleinste = min(array_map(fn(Topic $t) => (int)$t->sort_order, $themen));
        $neu = new Topic(['name' => $this->allgemeinName, 'contentcontainer_id' => $kreis->contentcontainer_id, 'sort_order' => $kleinste - 1]);
        if (!$neu->save()) {
            Yii::error('nexus-themen: "Allgemein" nicht angelegt: ' . json_encode($neu->errors), 'nexus-themen');
            return null;
        }
        $this->settings->set($schluessel, $neu->id);
        return $neu;
    }
}
