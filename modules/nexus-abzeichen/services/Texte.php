<?php

namespace nexus\modules\abzeichen\services;

use Yii;

/** Feste Oberflaechentexte (DE/EN/ES); Namen und Texte der Abzeichen kommen aus den Einstellungen. */
class Texte
{
    private const TEXTE = [
        'de' => [
            'abzeichen' => 'Abzeichen',
            'einleitung' => 'Abzeichen zeigen, wofür du einstehst – als Rahmen um dein Profilbild. Niemand prüft das nach, es gibt keine Rangfolge, und du kannst jedes Abzeichen jederzeit wieder abnehmen.',
            'bekennen' => 'Ich bekenne mich dazu',
            'loesen' => 'Rahmen abnehmen',
            'du_zeigst' => 'Du zeigst dieses Abzeichen an deinem Profilbild.',
            'bekannt' => 'Danke! Dein Profilbild trägt jetzt den Rahmen.',
            'geloest' => 'Abgenommen – ohne Nachfrage, ohne Spur. Du bist jederzeit wieder willkommen.',
            'hinweis_bekenntnis' => 'Gespeichert wird nur, dass du den Rahmen zeigst – kein Datum, keine Nummer, keine Reihenfolge.',
            'momente' => 'Deine Momente',
            'momente_hinweis' => 'Nur du siehst das. Es gibt keine Rangliste – und eine ruhigere Woche ist völlig in Ordnung.',
            'noch_keine' => 'Noch keine – aber das kommt.',
            'mehr' => 'Mehr lesen',
            'zurueck' => 'Alle Abzeichen',
            'weiter' => 'Schön',
            'vorschau' => 'So sieht dein Profilbild dann aus',
            'abzeichen_eins' => 'Abzeichen',
        ],
        'en' => [
            'abzeichen' => 'Badges',
            'einleitung' => 'Badges show what you stand for – as a frame around your profile picture. Nobody checks, there is no ranking, and you can remove any badge at any time.',
            'bekennen' => 'I commit to this',
            'loesen' => 'Remove frame',
            'du_zeigst' => 'You show this badge on your profile picture.',
            'bekannt' => 'Thank you! Your profile picture now carries the frame.',
            'geloest' => 'Removed – no questions asked, no trace. You are welcome back any time.',
            'hinweis_bekenntnis' => 'We only store that you show the frame – no date, no number, no order.',
            'momente' => 'Your moments',
            'momente_hinweis' => 'Only you can see this. There is no ranking – and a quieter week is perfectly fine.',
            'noch_keine' => 'None yet – but they will come.',
            'mehr' => 'Read more',
            'zurueck' => 'All badges',
            'weiter' => 'Nice',
            'vorschau' => 'This is how your profile picture will look',
            'abzeichen_eins' => 'Badge',
        ],
        'es' => [
            'abzeichen' => 'Insignias',
            'einleitung' => 'Las insignias muestran lo que defiendes – como un marco alrededor de tu foto de perfil. Nadie lo comprueba, no hay clasificación y puedes quitar cualquier insignia cuando quieras.',
            'bekennen' => 'Me comprometo',
            'loesen' => 'Quitar el marco',
            'du_zeigst' => 'Muestras esta insignia en tu foto de perfil.',
            'bekannt' => '¡Gracias! Tu foto de perfil lleva ahora el marco.',
            'geloest' => 'Quitado – sin preguntas, sin rastro. Siempre serás bienvenido de nuevo.',
            'hinweis_bekenntnis' => 'Solo guardamos que muestras el marco – sin fecha, sin número, sin orden.',
            'momente' => 'Tus momentos',
            'momente_hinweis' => 'Solo tú lo ves. No hay clasificación – y una semana más tranquila está perfectamente bien.',
            'noch_keine' => 'Todavía ninguno – pero llegarán.',
            'mehr' => 'Leer más',
            'zurueck' => 'Todas las insignias',
            'weiter' => 'Qué bien',
            'vorschau' => 'Así se verá tu foto de perfil',
            'abzeichen_eins' => 'Insignia',
        ],
    ];

    public static function t(string $schluessel): string
    {
        $code = strtolower(explode('-', (string)Yii::$app->language)[0]);
        return self::TEXTE[$code][$schluessel] ?? self::TEXTE['en'][$schluessel] ?? $schluessel;
    }

    /** Text aus den Einstellungen: String oder ['de' => ..., 'en' => ...] */
    public static function waehle($wert): string
    {
        if (is_string($wert)) {
            return $wert;
        }
        if (!is_array($wert) || $wert === []) {
            return '';
        }
        $code = strtolower(explode('-', (string)Yii::$app->language)[0]);
        return (string)($wert[$code] ?? $wert['en'] ?? reset($wert));
    }
}
