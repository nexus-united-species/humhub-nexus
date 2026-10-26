<?php

/**
 * Verwaltung fuer die Finanzverantwortlichen.
 *
 * @var $stand nexus\modules\finanzen\services\Stand
 * @var $monat string
 * @var $vorschlag array
 * @var $offeneVerteilung nexus\modules\finanzen\models\Verteilung|null
 * @var $verteilungen nexus\modules\finanzen\models\Verteilung[]
 * @var $eingaenge nexus\modules\finanzen\models\Eingang[]
 * @var $ausgaben nexus\modules\finanzen\models\Ausgabe[]
 * @var $rueckblick string
 * @var $ich humhub\modules\user\models\User
 * @var $meldung string|null
 */

use humhub\helpers\Html;
use humhub\modules\user\models\User;
use nexus\modules\finanzen\models\Eingang;
use nexus\modules\finanzen\services\Geld;
use nexus\modules\finanzen\services\Texte;
use yii\helpers\Url;

$summen = $stand->summen($monat);
$name = static fn(?int $id) => $id ? (User::findOne($id)?->displayName ?? '?') : '–';
$knopf = static function (string $route, array $param, string $text, string $klasse = 'btn btn-light btn-sm', ?string $frage = null) use ($monat): string {
    $html = Html::beginForm(Url::to(array_merge([$route], $param)), 'post', $frage ? ['data-bestaetigen' => $frage, 'style' => 'display:inline'] : ['style' => 'display:inline']);
    return $html . Html::hiddenInput('monat', $monat) . Html::submitButton(Html::encode($text), ['class' => $klasse]) . Html::endForm();
};
$quellen = ['ueberweisung' => 'Überweisung', 'stripe' => 'Stripe (direkt, nicht über Ko-fi)', 'bar' => 'Bar', 'sonstiges' => 'Sonstiges', 'start' => 'Anfangsbestand'];
?>
<div class="container">
<div class="panel panel-default nx-fin">
    <div class="panel-body">
        <div class="nx-fin-titelzeile">
            <h1><i class="fa fa-cog"></i> Finanzierung verwalten</h1>
            <span>
                <a class="btn btn-light btn-sm" href="<?= Html::encode(Url::to(['/nexus-finanzen/ampel/index', 'monat' => $monat])) ?>">Zur Ampel</a>
                <?php if ($ich->isSystemAdmin()) : ?><a class="btn btn-light btn-sm" href="<?= Html::encode(Url::to(['einstellungen'])) ?>">Einstellungen</a><?php endif; ?>
            </span>
        </div>
        <?php if (!nexus\modules\finanzen\Module::instanz()->fuerAlle()) : ?><div class="nx-fin-hinweis">Noch nicht für alle sichtbar: Menüpunkt, Ampel und öffentliche Zahlen sehen nur die Finanzverantwortlichen. Freischalten in den Einstellungen (Systemadmin).</div><?php endif; ?>
        <?php if ($meldung) : ?><div class="nx-fin-meldung"><?= Html::encode($meldung) ?></div><?php endif; ?>

        <nav class="nx-fin-monate">
            <a href="<?= Html::encode(Url::to(['index', 'monat' => Geld::vorMonat($monat)])) ?>">← früher</a>
            <strong><?= Html::encode(Geld::monatName($monat)) ?></strong>
            <a href="<?= Html::encode(Url::to(['index', 'monat' => Geld::nachMonat($monat)])) ?>">später →</a>
        </nav>
        <div class="nx-fin-summen">
            <?php foreach (['im_monat', 'eingegangen', 'reserviert', 'ausgegeben', 'frei'] as $s) : ?>
                <div><span><?= Html::encode(Texte::t($s, 'de')) ?></span><strong><?= Html::encode(Geld::zeigen($summen[$s], true)) ?></strong></div>
            <?php endforeach; ?>
        </div>
        <?php if ($stand->fremdwaehrungAnzahl() > 0) : ?>
            <div class="nx-fin-hinweis">⚠️ <?= (int)$stand->fremdwaehrungAnzahl() ?> Ko-fi-Eingang/Eingänge nicht in Euro – zählen erst, wenn sie von Hand in Euro erfasst werden.</div>
        <?php endif; ?>

        <?php /* ── Verteilung ── */ ?>
        <h2 id="verteilung">1. Freies Geld verteilen</h2>
        <p class="nx-fin-leise">Das System schlägt nach Rangfolge vor, was aus dem Topf an welche freigegebene Kachel geht (je Kachel höchstens das, was fehlt). Erst mit „Bestätigen“ wird gebucht. Steckt Geld für die eigene Vergütung darin, muss eine zweite Person freigeben.</p>
        <?php if ($offeneVerteilung !== null) : ?>
            <div class="nx-fin-kasten">
                <strong>Vorschlag für <?= Html::encode(Geld::monatName($offeneVerteilung->monat)) ?>: <?= Html::encode(Geld::zeigen((int)$offeneVerteilung->summe, true)) ?></strong>
                <?php if ($offeneVerteilung->status === 'wartet_zweite') : ?>
                    <div class="nx-fin-hinweis">Bestätigt von <?= Html::encode($name((int)$offeneVerteilung->bestaetigt_von)) ?> – wartet auf die Freigabe einer zweiten Person.</div>
                <?php endif; ?>
                <ul>
                    <?php foreach ($offeneVerteilung->posten() as $p) : $k = $stand->kachel((int)$p['kachel_id']); ?>
                        <li><?= Html::encode($k?->titel ?? '?') ?>: <?= Html::encode(Geld::zeigen((int)$p['betrag'], true)) ?><?= $k?->beguenstigt_id ? ' <span class="nx-fin-marke">Vergütung ' . Html::encode($name((int)$k->beguenstigt_id)) . '</span>' : '' ?></li>
                    <?php endforeach; ?>
                </ul>
                <?= $knopf('bestaetigen', ['id' => $offeneVerteilung->id], $offeneVerteilung->status === 'wartet_zweite' ? 'Als zweite Person freigeben' : 'Bestätigen und buchen', 'btn btn-primary btn-sm', 'Diese Verteilung buchen?') ?>
                <?= $knopf('verwerfen', ['id' => $offeneVerteilung->id], 'Verwerfen') ?>
            </div>
        <?php elseif ($vorschlag !== []) : ?>
            <p>Im Topf: <strong><?= Html::encode(Geld::zeigen($stand->topf(), true)) ?></strong> – möglich sind <?= count($vorschlag) ?> Zuweisungen über <?= Html::encode(Geld::zeigen(array_sum(array_column($vorschlag, 'betrag')), true)) ?>.</p>
            <?= $knopf('vorschlagen', [], 'Vorschlag für ' . Geld::monatName($monat) . ' erstellen', 'btn btn-primary btn-sm') ?>
        <?php else : ?>
            <p class="nx-fin-leise">Nichts zu verteilen: Im Topf ist kein freies Geld, oder alle freigegebenen Kacheln sind für diesen Monat gedeckt.</p>
        <?php endif; ?>
        <?php if ($verteilungen !== []) : ?>
            <details><summary>Gebuchte Verteilungen</summary><ul>
                <?php foreach ($verteilungen as $v) : ?>
                    <li><?= Html::encode(Geld::monatName($v->monat)) ?>: <?= Html::encode(Geld::zeigen((int)$v->summe, true)) ?> – bestätigt von <?= Html::encode($name((int)$v->bestaetigt_von)) ?><?= $v->zweite_von ? ', freigegeben von ' . Html::encode($name((int)$v->zweite_von)) : '' ?> (<?= Html::encode(Yii::$app->formatter->asDate($v->updated_at)) ?>)</li>
                <?php endforeach; ?>
            </ul></details>
        <?php endif; ?>

        <?php /* ── Kacheln ── */ ?>
        <h2 id="kacheln">2. Kacheln</h2>
        <p><a class="btn btn-light btn-sm" href="<?= Html::encode(Url::to(['kachel'])) ?>"><i class="fa fa-plus"></i> Neue Kachel</a></p>
        <div class="nx-fin-tabelle"><table class="table table-sm">
            <thead><tr><th>Rang</th><th>Kachel</th><th>Bereich</th><th>Betrag</th><th>Einordnung</th><th>Stand <?= Html::encode(Geld::monatName($monat)) ?></th><th></th></tr></thead>
            <tbody>
            <?php foreach ($stand->kacheln() as $k) : $f = $stand->farbe($k, $monat); ?>
                <tr>
                    <td><?= (int)$k->rang ?></td>
                    <td><strong><?= Html::encode($k->titel) ?></strong><?= $k->gruppe ? '<br><span class="nx-fin-leise">' . Html::encode($k->gruppe) . '</span>' : '' ?><?= $k->beguenstigt_id ? '<br><span class="nx-fin-marke">Vergütung ' . Html::encode($name((int)$k->beguenstigt_id)) . '</span>' : '' ?><?= $k->oeffentlich ? '' : '<br><span class="nx-fin-marke">nicht öffentlich</span>' ?></td>
                    <td><?= Html::encode(['grund' => 'Grundbetrieb', 'optional' => 'Optional', 'entwicklung' => 'Entwicklung'][$k->bereich]) ?></td>
                    <td><?= Html::encode(Geld::zeigen((int)$k->betrag, true)) ?><br><span class="nx-fin-leise"><?= $k->istLaufend() ? 'je Monat' : 'einmalig' ?></span></td>
                    <td><?= Html::encode(Texte::t('einordnung_' . $k->einordnung, 'de')) ?><?= $k->zusatz ? '<br>' . Html::encode(Texte::t('zusatz_' . $k->zusatz, 'de')) : '' ?></td>
                    <td><span class="nx-fin-punkt nx-fin-<?= $f ?>"></span> <?= Html::encode(Texte::t('farbe_' . $f, 'de')) ?><br><span class="nx-fin-leise"><?= Html::encode(Geld::zeigen($stand->gedeckt($k, $monat), true)) ?> gedeckt<?= $stand->ausgegeben($k) ? ', ' . Html::encode(Geld::zeigen($stand->ausgegeben($k), true)) . ' ausgegeben' : '' ?></span></td>
                    <td><a class="btn btn-light btn-sm" href="<?= Html::encode(Url::to(['kachel', 'id' => $k->id])) ?>">Bearbeiten</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table></div>

        <?php /* ── Eingaenge ── */ ?>
        <h2 id="eingaenge">3. Eingänge</h2>
        <p class="nx-fin-leise">Ko-fi-Zahlungen kommen automatisch (frei verfügbar). Ko-fi läuft über Stripe – diese Zahlungen hier NICHT noch einmal als Stripe eintragen. Von Hand: Überweisungen, direkte Stripe-Zahlungen, Bargeld. Zweckgebundenes Geld geht sofort in seine Kachel.</p>
        <?= Html::beginForm(Url::to(['eingang']), 'post', ['class' => 'nx-fin-formular']) ?>
            <?= Html::hiddenInput('monat', $monat) ?>
            <label>Datum <input class="form-control" type="date" name="datum" value="<?= date('Y-m-d') ?>" required></label>
            <label>Betrag (€) <input class="form-control" name="betrag" inputmode="decimal" placeholder="25,00" required></label>
            <label>Quelle <select class="form-control" name="quelle"><?php foreach ($quellen as $w => $t) : ?><option value="<?= $w ?>"><?= Html::encode($t) ?></option><?php endforeach; ?></select></label>
            <label>Zweck <select class="form-control" name="zweck"><option value="0">frei verfügbar (Topf)</option><?php foreach ($stand->kacheln() as $k) : ?><option value="<?= (int)$k->id ?>"><?= Html::encode($k->titel) ?></option><?php endforeach; ?></select></label>
            <label>Referenz (intern) <input class="form-control" name="referenz" maxlength="190" placeholder="z. B. Kontoauszug 10/2026"></label>
            <label>Notiz (intern) <input class="form-control" name="notiz" maxlength="255"></label>
            <button type="submit" class="btn btn-primary btn-sm">Eingang erfassen</button>
        <?= Html::endForm() ?>
        <div class="nx-fin-tabelle"><table class="table table-sm">
            <thead><tr><th>Datum</th><th>Betrag</th><th>Quelle</th><th>Zweck</th><th>Referenz / Notiz</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($eingaenge as $e) : ?>
                <tr>
                    <td><?= Html::encode(Yii::$app->formatter->asDate($e->datum)) ?></td>
                    <td><?= $e->waehrung === 'EUR' ? Html::encode(Geld::zeigen((int)$e->betrag, true)) : Html::encode(number_format($e->betrag / 100, 2, ',', '.') . ' ' . $e->waehrung) . ' ⚠️' ?></td>
                    <td><?= Html::encode($e->quelle === 'kofi' ? 'Ko-fi' : ($quellen[$e->quelle] ?? $e->quelle)) ?></td>
                    <td><?= Html::encode($e->zweck?->titel ?? 'frei') ?></td>
                    <td class="nx-fin-leise"><?= Html::encode(trim(($e->referenz ?? '') . ' ' . ($e->notiz ?? ''))) ?></td>
                    <td><?= $e->quelle !== 'kofi' ? $knopf('eingang-loeschen', ['id' => $e->id], '×', 'btn btn-link btn-sm', 'Diesen Eingang löschen?') : '' ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table></div>

        <?php /* ── Ausgaben ── */ ?>
        <h2 id="ausgaben">4. Ausgaben</h2>
        <p class="nx-fin-leise">Was tatsächlich bezahlt wurde. Auszahlungen aus einer Vergütungs-Kachel zählen erst, wenn eine zweite Person sie freigibt – nicht wer sie einträgt und nicht wer sie bekommt.</p>
        <?= Html::beginForm(Url::to(['ausgabe']), 'post', ['class' => 'nx-fin-formular']) ?>
            <?= Html::hiddenInput('monat', $monat) ?>
            <label>Kachel <select class="form-control" name="kachel" required><?php foreach ($stand->kacheln() as $k) : ?><option value="<?= (int)$k->id ?>"><?= Html::encode($k->titel) ?></option><?php endforeach; ?></select></label>
            <label>Datum <input class="form-control" type="date" name="datum" value="<?= date('Y-m-d') ?>" required></label>
            <label>Betrag (€) <input class="form-control" name="betrag" inputmode="decimal" required></label>
            <label>Bezahlt aus
                <select class="form-control" name="bezahlt">
                    <option value="topf">dem Spendentopf</option>
                    <option value="vorgestreckt">meiner eigenen Tasche (vorgestreckt)</option>
                </select>
            </label>
            <label class="nx-fin-haken-zeile"><span><input type="checkbox" name="geschaetzt" value="1"> Betrag ist geschätzt</span></label>
            <label>Beleg (intern) <input class="form-control" name="beleg" maxlength="190" placeholder="z. B. Rechnung Nr."></label>
            <label>Notiz (intern) <input class="form-control" name="notiz" maxlength="255"></label>
            <button type="submit" class="btn btn-primary btn-sm">Ausgabe erfassen</button>
        <?= Html::endForm() ?>
        <p class="nx-fin-leise">„Vorgestreckt“ heißt: du hast es selbst bezahlt, der Spendentopf bleibt unberührt. Es erscheint auf der Ampel als „vorgestreckt“, bis es einmal erstattet wird. Eine Vergütung kann nicht vorgestreckt gebucht werden.</p>
        <div class="nx-fin-tabelle"><table class="table table-sm">
            <thead><tr><th>Datum</th><th>Kachel</th><th>Betrag</th><th>Status</th><th>Beleg / Notiz</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($ausgaben as $a) : ?>
                <tr>
                    <td><?= Html::encode(Yii::$app->formatter->asDate($a->datum)) ?></td>
                    <td><?= Html::encode($a->kachel?->titel ?? '?') ?></td>
                    <td><?= Html::encode(Geld::zeigen((int)$a->betrag, true)) ?><?= $a->geschaetzt ? ' <span class="nx-fin-marke">geschätzt</span>' : '' ?></td>
                    <td>
                        <?php if ($a->vorgestreckt_von !== null) : ?>
                            <span class="nx-fin-marke nx-fin-vorgestreckt">vorgestreckt von <?= Html::encode($name((int)$a->vorgestreckt_von)) ?></span>
                        <?php elseif ($a->status === 'offen') : ?>
                            <span class="nx-fin-marke nx-fin-offen">wartet auf zweite Freigabe</span>
                        <?php else : ?>
                            freigegeben von <?= Html::encode($name((int)$a->freigegeben_von)) ?>
                        <?php endif; ?>
                        <br><span class="nx-fin-leise">eingetragen von <?= Html::encode($name((int)$a->erfasst_von)) ?></span>
                    </td>
                    <td class="nx-fin-leise"><?= Html::encode(trim(($a->beleg ?? '') . ' ' . ($a->notiz ?? ''))) ?></td>
                    <td>
                        <?php if ($a->status === 'offen' && (int)$a->erfasst_von !== (int)$ich->id && (int)$a->kachel?->beguenstigt_id !== (int)$ich->id) : ?>
                            <?= $knopf('ausgabe-freigeben', ['id' => $a->id], 'Freigeben', 'btn btn-primary btn-sm', 'Diese Auszahlung als zweite Person freigeben?') ?>
                        <?php endif; ?>
                        <?php if ($a->vorgestreckt_von !== null) : ?>
                            <details class="nx-fin-aendern"><summary>ändern</summary>
                                <?= Html::beginForm(Url::to(['ausgabe-aendern', 'id' => $a->id]), 'post', ['class' => 'nx-fin-formular']) ?>
                                    <?= Html::hiddenInput('monat', $monat) ?>
                                    <label>Datum <input class="form-control" type="date" name="datum" value="<?= Html::encode($a->datum) ?>" required></label>
                                    <label>Betrag (€) <input class="form-control" name="betrag" inputmode="decimal" value="<?= Html::encode(number_format((int)$a->betrag / 100, 2, ',', '')) ?>" required></label>
                                    <label>Notiz <input class="form-control" name="notiz" maxlength="255" value="<?= Html::encode((string)$a->notiz) ?>"></label>
                                    <label class="nx-fin-haken-zeile"><span><input type="checkbox" name="geschaetzt" value="1"<?= $a->geschaetzt ? ' checked' : '' ?>> geschätzt</span></label>
                                    <button type="submit" class="btn btn-primary btn-sm">Speichern</button>
                                <?= Html::endForm() ?>
                            </details>
                        <?php endif; ?>
                        <?= $knopf('ausgabe-loeschen', ['id' => $a->id], '×', 'btn btn-link btn-sm', 'Diese Ausgabe löschen?') ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table></div>

        <?php /* ── Rueckblick ── */ ?>
        <h2 id="rueckblick">5. Monatsrückblick <?= Html::encode(Geld::monatName($monat)) ?></h2>
        <p class="nx-fin-leise">Ein paar verständliche Sätze für alle Mitglieder: Was kam herein, was wurde gedeckt, was fehlt, was wurde umgesetzt. Erscheint auf der Ampel-Seite unter diesem Monat.</p>
        <?= Html::beginForm(Url::to(['rueckblick']), 'post') ?>
            <?= Html::hiddenInput('monat', $monat) ?>
            <textarea class="form-control" name="text" rows="5" maxlength="10000" aria-label="Monatsrückblick"><?= Html::encode($rueckblick) ?></textarea>
            <div style="margin-top:.5rem"><button type="submit" class="btn btn-light btn-sm">Rückblick speichern</button></div>
        <?= Html::endForm() ?>
    </div>
</div>
</div>
