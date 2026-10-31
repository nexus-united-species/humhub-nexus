<?php

namespace nexus\modules\kalender\services;

use nexus\modules\kalender\Module;
use Yii;

/** Oberflaechentexte in Deutsch, Englisch, Spanisch, Russisch und Franzoesisch; fehlt eine Sprache, gilt Englisch. */
class Texte
{
    private const T = [
        'de' => [
            'kalender' => 'Kalender',
            'ansehen' => 'Kalender ansehen und durchblättern',
            'neuer_tab' => 'In neuem Tab öffnen',
            'bestellen' => 'Bestellen',
            'entwurf' => 'Das ist noch ein Entwurf. Bitte noch nicht weitergeben.',
            'fehlt' => 'Der Kalender ist gerade nicht verfügbar.',
            'teilen' => 'In einen Kreis teilen',
            'teilen_kreis' => 'In welchen Kreis?',
            'teilen_satz' => 'Ein eigener Satz dazu (freiwillig)',
            'teilen_hinweis' => 'Das wird dein eigener Beitrag in diesem Kreis. Lesen können den Kalender alle Angemeldeten.',
            'teilen_senden' => 'Teilen',
            'teilen_fehler' => 'Das Teilen hat nicht geklappt.',
        ],
        'en' => [
            'kalender' => 'Calendar',
            'ansehen' => 'View and leaf through the calendar',
            'neuer_tab' => 'Open in new tab',
            'bestellen' => 'Order',
            'entwurf' => 'This is still a draft. Please do not pass it on yet.',
            'fehlt' => 'The calendar is not available right now.',
            'teilen' => 'Share in a circle',
            'teilen_kreis' => 'Which circle?',
            'teilen_satz' => 'A sentence of your own (optional)',
            'teilen_hinweis' => 'This becomes your own post in that circle. All signed-in members can read the calendar.',
            'teilen_senden' => 'Share',
            'teilen_fehler' => 'Sharing did not work.',
        ],
        'es' => [
            'kalender' => 'Calendario',
            'ansehen' => 'Ver y hojear el calendario',
            'neuer_tab' => 'Abrir en una pestaña nueva',
            'bestellen' => 'Pedir',
            'entwurf' => 'Todavía es un borrador. Por favor, no lo compartas aún.',
            'fehlt' => 'El calendario no está disponible en este momento.',
            'teilen' => 'Compartir en un círculo',
            'teilen_kreis' => '¿En qué círculo?',
            'teilen_satz' => 'Una frase tuya (opcional)',
            'teilen_hinweis' => 'Será tu propia publicación en ese círculo. Todos los miembros con sesión iniciada pueden leer el calendario.',
            'teilen_senden' => 'Compartir',
            'teilen_fehler' => 'No se ha podido compartir.',
        ],
        'ru' => [
            'kalender' => 'Календарь',
            'ansehen' => 'Посмотреть и полистать календарь',
            'neuer_tab' => 'Открыть в новой вкладке',
            'bestellen' => 'Заказать',
            'entwurf' => 'Это пока черновик. Пожалуйста, пока не передавайте его дальше.',
            'fehlt' => 'Календарь сейчас недоступен.',
            'teilen' => 'Поделиться в круге',
            'teilen_kreis' => 'В каком круге?',
            'teilen_satz' => 'Ваши слова (по желанию)',
            'teilen_hinweis' => 'Это станет вашей записью в этом круге. Календарь могут читать все вошедшие участники.',
            'teilen_senden' => 'Поделиться',
            'teilen_fehler' => 'Поделиться не удалось.',
        ],
        'fr' => [
            'kalender' => 'Calendrier',
            'ansehen' => 'Voir et feuilleter le calendrier',
            'neuer_tab' => 'Ouvrir dans un nouvel onglet',
            'bestellen' => 'Commander',
            'entwurf' => 'Ceci est encore un brouillon. Merci de ne pas encore le transmettre.',
            'fehlt' => 'Le calendrier n’est pas disponible pour le moment.',
            'teilen' => 'Partager dans un cercle',
            'teilen_kreis' => 'Dans quel cercle ?',
            'teilen_satz' => 'Une phrase de ta part (facultatif)',
            'teilen_hinweis' => 'Cela devient ta propre publication dans ce cercle. Tous les membres connectés peuvent lire le calendrier.',
            'teilen_senden' => 'Partager',
            'teilen_fehler' => 'Le partage n’a pas fonctionné.',
        ],
    ];

    public static function t(string $schluessel): string
    {
        $sprache = substr((string)Yii::$app->language, 0, 2);
        return self::T[$sprache][$schluessel] ?? self::T['en'][$schluessel] ?? $schluessel;
    }

    public static function titel(): string
    {
        $modul = Module::instanz();
        return trim(self::t('kalender') . ' ' . ($modul !== null ? $modul->jahr : ''));
    }
}
