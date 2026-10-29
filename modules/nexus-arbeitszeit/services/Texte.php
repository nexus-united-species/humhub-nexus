<?php

namespace nexus\modules\arbeitszeit\services;

use Yii;

/**
 * Oberflaechentexte in Deutsch, Englisch, Spanisch -- in der Sprache des Lesers (wie der Rest des
 * Portals). Bewusst eine kleine Tabelle statt Yii::t() mit Uebersetzungsdateien: drei Sprachen,
 * ein Ort, leicht zu pflegen. Fehlt eine Sprache, gilt Englisch.
 */
class Texte
{
    private const T = [
        'titel' => ['de' => 'Meine Arbeitsstunden', 'en' => 'My working hours', 'es' => 'Mis horas de trabajo'],
        'einleitung' => [
            'de' => 'Du hast etwas für N.E.X.U.S. getan? Trag hier ein, wie lange und was – egal ob Texte schreiben, recherchieren, organisieren oder programmieren. Ein Admin prüft den Eintrag und gibt ihn frei.',
            'en' => 'Did something for N.E.X.U.S.? Enter here how long and what you did – writing, research, organising or coding. An admin checks the entry and approves it.',
            'es' => '¿Hiciste algo por N.E.X.U.S.? Anota aquí cuánto tiempo y qué hiciste: escribir, investigar, organizar o programar. Un administrador revisa y aprueba la entrada.'],
        'tipp' => [
            'de' => '💡 Noch einfacher: Schreib dem Assistenten eine private Nachricht, z. B. „Heute 2 Stunden am Newsletter für Kreis 7 gearbeitet“ – er trägt es für dich ein.',
            'en' => '💡 Even easier: send the assistant a private message, e.g. “Worked 2 hours on the newsletter for Circle 7 today” – it will enter it for you.',
            'es' => '💡 Aún más fácil: envía un mensaje privado al asistente, p. ej. «Hoy trabajé 2 horas en el boletín para el Círculo 7», y lo anotará por ti.'],
        'freigegeben_gesamt' => ['de' => 'Freigegeben insgesamt', 'en' => 'Approved in total', 'es' => 'Aprobadas en total'],
        'diesen_monat' => ['de' => 'Freigegeben diesen Monat', 'en' => 'Approved this month', 'es' => 'Aprobadas este mes'],
        'wartet' => ['de' => 'Wartet auf Freigabe', 'en' => 'Waiting for approval', 'es' => 'Pendientes de aprobación'],
        'std' => ['de' => 'Std.', 'en' => 'h', 'es' => 'h'],
        'neu' => ['de' => 'Neue Stunden eintragen', 'en' => 'Enter new hours', 'es' => 'Anotar horas nuevas'],
        'stunden' => ['de' => 'Wie viele Stunden?', 'en' => 'How many hours?', 'es' => '¿Cuántas horas?'],
        'stunden_hilfe' => ['de' => 'z. B. 2 oder 1,5 (halbe Stunden gehen auch)', 'en' => 'e.g. 2 or 1.5 (half hours are fine)', 'es' => 'p. ej. 2 o 1,5 (también medias horas)'],
        'datum' => ['de' => 'An welchem Tag?', 'en' => 'On which day?', 'es' => '¿Qué día?'],
        'kreis' => ['de' => 'Für welchen Kreis?', 'en' => 'For which circle?', 'es' => '¿Para qué círculo?'],
        'kreis_waehlen' => ['de' => '– bitte auswählen –', 'en' => '– please choose –', 'es' => '– elige, por favor –'],
        'taetigkeit' => ['de' => 'Was hast du gemacht?', 'en' => 'What did you do?', 'es' => '¿Qué hiciste?'],
        'taetigkeit_hilfe' => ['de' => 'Ein bis zwei Sätze genügen, z. B. „Newsletter für Oktober geschrieben“.', 'en' => 'One or two sentences are enough, e.g. “Wrote the October newsletter”.', 'es' => 'Una o dos frases bastan, p. ej. «Escribí el boletín de octubre».'],
        'speichern' => ['de' => 'Eintragen', 'en' => 'Submit', 'es' => 'Anotar'],
        'gespeichert' => ['de' => 'Danke! Deine Stunden sind eingetragen und warten auf Freigabe.', 'en' => 'Thank you! Your hours are recorded and waiting for approval.', 'es' => '¡Gracias! Tus horas están anotadas y pendientes de aprobación.'],
        'fehler' => ['de' => 'Bitte prüfe deine Angaben:', 'en' => 'Please check your entries:', 'es' => 'Revisa tus datos:'],
        'fehler_stunden' => ['de' => 'Stunden: bitte eine Zahl zwischen 0,25 und 16.', 'en' => 'Hours: please a number between 0.25 and 16.', 'es' => 'Horas: un número entre 0,25 y 16.'],
        'fehler_datum' => ['de' => 'Tag: bitte ein Datum, nicht in der Zukunft.', 'en' => 'Day: please a date, not in the future.', 'es' => 'Día: una fecha que no esté en el futuro.'],
        'fehler_kreis' => ['de' => 'Kreis: bitte einen deiner Kreise auswählen.', 'en' => 'Circle: please choose one of your circles.', 'es' => 'Círculo: elige uno de tus círculos.'],
        'fehler_text' => ['de' => 'Tätigkeit: bitte kurz beschreiben (höchstens 500 Zeichen).', 'en' => 'Activity: please describe briefly (max. 500 characters).', 'es' => 'Actividad: descríbela brevemente (máx. 500 caracteres).'],
        'meine_eintraege' => ['de' => 'Meine Einträge', 'en' => 'My entries', 'es' => 'Mis entradas'],
        'keine' => ['de' => 'Noch keine Einträge – leg gleich los!', 'en' => 'No entries yet – get started!', 'es' => 'Aún no hay entradas: ¡empieza ya!'],
        'st_offen' => ['de' => '⏳ wartet auf Freigabe', 'en' => '⏳ waiting for approval', 'es' => '⏳ pendiente'],
        'st_freigegeben' => ['de' => '✅ freigegeben', 'en' => '✅ approved', 'es' => '✅ aprobada'],
        'st_ruecksprache' => ['de' => '💬 Rücksprache', 'en' => '💬 please get in touch', 'es' => '💬 consulta pendiente'],
        'loeschen' => ['de' => 'Löschen', 'en' => 'Delete', 'es' => 'Borrar'],
        'loeschen_frage' => ['de' => 'Diesen Eintrag wirklich löschen?', 'en' => 'Really delete this entry?', 'es' => '¿Borrar esta entrada?'],
        'geloescht' => ['de' => 'Eintrag gelöscht.', 'en' => 'Entry deleted.', 'es' => 'Entrada borrada.'],
        'freigabe_link' => ['de' => 'Stunden freigeben', 'en' => 'Approve hours', 'es' => 'Aprobar horas'],
        'kein_kreis' => ['de' => 'Du bist noch in keinem Kreis Mitglied. Tritt zuerst einem Kreis bei, dann kannst du Stunden eintragen.', 'en' => 'You are not a member of any circle yet. Join a circle first, then you can enter hours.', 'es' => 'Aún no eres miembro de ningún círculo. Únete primero a uno para poder anotar horas.'],
    ];

    /** Russisch (seit 29.10.2026): eigene Tabelle, die drei anderen Sprachen bleiben unberuehrt. */
    private const RU = [
        'titel' => 'Мои рабочие часы',
        'einleitung' => 'Сделали что-то для N.E.X.U.S.? Запишите здесь, сколько времени и что именно — будь то тексты, исследования, организация или программирование. Администратор проверит запись и подтвердит её.',
        'tipp' => '💡 Ещё проще: напишите ассистенту личное сообщение, например: «Сегодня 2 часа работал(а) над рассылкой для Круга 7» — он внесёт запись за вас.',
        'freigegeben_gesamt' => 'Подтверждено всего',
        'diesen_monat' => 'Подтверждено в этом месяце',
        'wartet' => 'Ожидает подтверждения',
        'std' => 'ч',
        'neu' => 'Записать новые часы',
        'stunden' => 'Сколько часов?',
        'stunden_hilfe' => 'например, 2 или 1,5 (можно и полчаса)',
        'datum' => 'В какой день?',
        'kreis' => 'Для какого круга?',
        'kreis_waehlen' => '— пожалуйста, выберите —',
        'taetigkeit' => 'Что вы сделали?',
        'taetigkeit_hilfe' => 'Достаточно одного-двух предложений, например: «Написал(а) рассылку на октябрь».',
        'speichern' => 'Записать',
        'gespeichert' => 'Спасибо! Ваши часы записаны и ожидают подтверждения.',
        'fehler' => 'Пожалуйста, проверьте данные:',
        'fehler_stunden' => 'Часы: пожалуйста, число от 0,25 до 16.',
        'fehler_datum' => 'День: пожалуйста, дата, не в будущем.',
        'fehler_kreis' => 'Круг: пожалуйста, выберите один из ваших кругов.',
        'fehler_text' => 'Деятельность: пожалуйста, опишите кратко (не более 500 символов).',
        'meine_eintraege' => 'Мои записи',
        'keine' => 'Записей пока нет — начните прямо сейчас!',
        'st_offen' => '⏳ ожидает подтверждения',
        'st_freigegeben' => '✅ подтверждено',
        'st_ruecksprache' => '💬 нужно уточнить',
        'loeschen' => 'Удалить',
        'loeschen_frage' => 'Действительно удалить эту запись?',
        'geloescht' => 'Запись удалена.',
        'freigabe_link' => 'Подтвердить часы',
        'kein_kreis' => 'Вы пока не состоите ни в одном круге. Сначала вступите в круг, затем сможете записывать часы.',
    ];

    /** Französisch (seit 29.10.2026): eigene Tabelle, die anderen Sprachen bleiben unberührt. */
    private const FR = [
        'titel' => 'Mes heures de travail',
        'einleitung' => 'Tu as fait quelque chose pour N.E.X.U.S. ? Indique ici combien de temps et quoi – rédiger des textes, faire des recherches, organiser ou programmer. Un administrateur vérifie l’entrée et la valide.',
        'tipp' => '💡 Encore plus simple : envoie un message privé à l’assistant, p. ex. « Aujourd’hui, 2 heures sur la lettre d’information du Cercle 7 » – il l’enregistre pour toi.',
        'freigegeben_gesamt' => 'Validées au total',
        'diesen_monat' => 'Validées ce mois-ci',
        'wartet' => 'En attente de validation',
        'std' => 'h',
        'neu' => 'Saisir de nouvelles heures',
        'stunden' => 'Combien d’heures ?',
        'stunden_hilfe' => 'p. ex. 2 ou 1,5 (les demi-heures sont possibles)',
        'datum' => 'Quel jour ?',
        'kreis' => 'Pour quel cercle ?',
        'kreis_waehlen' => '– choisis, s’il te plaît –',
        'taetigkeit' => 'Qu’as-tu fait ?',
        'taetigkeit_hilfe' => 'Une ou deux phrases suffisent, p. ex. « Rédigé la lettre d’information d’octobre ».',
        'speichern' => 'Enregistrer',
        'gespeichert' => 'Merci ! Tes heures sont enregistrées et en attente de validation.',
        'fehler' => 'Vérifie tes indications :',
        'fehler_stunden' => 'Heures : un nombre entre 0,25 et 16.',
        'fehler_datum' => 'Jour : une date, pas dans le futur.',
        'fehler_kreis' => 'Cercle : choisis l’un de tes cercles.',
        'fehler_text' => 'Activité : décris-la brièvement (500 caractères au maximum).',
        'meine_eintraege' => 'Mes entrées',
        'keine' => 'Aucune entrée pour l’instant – lance-toi !',
        'st_offen' => '⏳ en attente de validation',
        'st_freigegeben' => '✅ validée',
        'st_ruecksprache' => '💬 à clarifier',
        'loeschen' => 'Supprimer',
        'loeschen_frage' => 'Vraiment supprimer cette entrée ?',
        'geloescht' => 'Entrée supprimée.',
        'freigabe_link' => 'Valider des heures',
        'kein_kreis' => 'Tu n’es encore membre d’aucun cercle. Rejoins d’abord un cercle, tu pourras ensuite saisir des heures.',
    ];

    public static function sprache(): string
    {
        $code = strtolower(explode('-', (string)Yii::$app->language)[0]);
        return in_array($code, ['de', 'en', 'es', 'ru', 'fr'], true) ? $code : 'en';
    }

    public static function t(string $schluessel): string
    {
        $eintrag = self::T[$schluessel] ?? null;
        if ($eintrag === null) {
            return $schluessel;
        }
        if (self::sprache() === 'ru' && isset(self::RU[$schluessel])) {
            return self::RU[$schluessel];
        }
        if (self::sprache() === 'fr' && isset(self::FR[$schluessel])) {
            return self::FR[$schluessel];
        }
        return $eintrag[self::sprache()] ?? $eintrag['en'];
    }
}
