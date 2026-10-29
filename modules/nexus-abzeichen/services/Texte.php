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
            'momente_hinweis' => 'Nur du siehst das – außer du teilst es selbst. Es gibt keine Rangliste, und eine ruhigere Woche ist völlig in Ordnung.',
            'noch_keine' => 'Noch keine – aber das kommt.',
            'mehr' => 'Mehr lesen',
            'zurueck' => 'Alle Abzeichen',
            'weiter' => 'Schön',
            'neue_stufe' => 'Neue Stufe erreicht: {stufe}! 🌟',
            'teilen' => 'Mit der Gemeinschaft teilen',
            'teilen_wo' => 'Erscheint als dein Beitrag in „{kreis}“. Nur wenn du möchtest.',
            'notiz' => 'Ein paar eigene Worte (freiwillig)',
            'senden' => 'Jetzt teilen',
            'geteilt_ok' => 'Geteilt – danke, dass du andere mitfreuen lässt!',
            'teilen_fehler' => 'Das hat nicht geklappt – vielleicht schon geteilt?',
            'geteilt' => 'Geteilt',
            'naechste' => 'Nächste Stufe: {stufe} bei {wert}',
            'alle_stufen' => 'Alle Stufen erreicht – danke!',
            'stufen' => 'Stufen',
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
            'momente_hinweis' => 'Only you can see this – unless you share it yourself. There is no ranking, and a quieter week is perfectly fine.',
            'noch_keine' => 'None yet – but they will come.',
            'mehr' => 'Read more',
            'zurueck' => 'All badges',
            'weiter' => 'Nice',
            'neue_stufe' => 'New level reached: {stufe}! 🌟',
            'teilen' => 'Share with the community',
            'teilen_wo' => 'Appears as your post in “{kreis}”. Only if you want.',
            'notiz' => 'A few words of your own (optional)',
            'senden' => 'Share now',
            'geteilt_ok' => 'Shared – thank you for letting others celebrate with you!',
            'teilen_fehler' => 'That did not work – maybe already shared?',
            'geteilt' => 'Shared',
            'naechste' => 'Next level: {stufe} at {wert}',
            'alle_stufen' => 'All levels reached – thank you!',
            'stufen' => 'Levels',
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
            'momente_hinweis' => 'Solo tú lo ves, salvo que lo compartas. No hay clasificación y una semana más tranquila está perfectamente bien.',
            'noch_keine' => 'Todavía ninguno – pero llegarán.',
            'mehr' => 'Leer más',
            'zurueck' => 'Todas las insignias',
            'weiter' => 'Qué bien',
            'neue_stufe' => '¡Nuevo nivel alcanzado: {stufe}! 🌟',
            'teilen' => 'Compartir con la comunidad',
            'teilen_wo' => 'Aparece como tu publicación en «{kreis}». Solo si quieres.',
            'notiz' => 'Unas palabras tuyas (opcional)',
            'senden' => 'Compartir ahora',
            'geteilt_ok' => 'Compartido – ¡gracias por dejar que otros lo celebren contigo!',
            'teilen_fehler' => 'No ha funcionado – ¿quizá ya lo compartiste?',
            'geteilt' => 'Compartido',
            'naechste' => 'Siguiente nivel: {stufe} con {wert}',
            'alle_stufen' => 'Todos los niveles alcanzados – ¡gracias!',
            'stufen' => 'Niveles',
            'vorschau' => 'Así se verá tu foto de perfil',
            'abzeichen_eins' => 'Insignia',
        ],
        'ru' => [
            'abzeichen' => 'Значки',
            'einleitung' => 'Значки показывают, за что вы стоите, — в виде рамки вокруг фото профиля. Никто это не проверяет, рейтинга нет, и любой значок можно снять в любой момент.',
            'bekennen' => 'Я с этим согласен(на)',
            'loesen' => 'Снять рамку',
            'du_zeigst' => 'Вы показываете этот значок на фото профиля.',
            'bekannt' => 'Спасибо! Теперь на вашем фото профиля есть рамка.',
            'geloest' => 'Снято — без вопросов и без следа. Вы всегда можете вернуться.',
            'hinweis_bekenntnis' => 'Сохраняется только то, что вы показываете рамку, — без даты, без номера, без очередности.',
            'momente' => 'Ваши моменты',
            'momente_hinweis' => 'Это видите только вы — если не поделитесь сами. Рейтинга нет, а спокойная неделя — совершенно нормально.',
            'noch_keine' => 'Пока нет — но они появятся.',
            'mehr' => 'Читать далее',
            'zurueck' => 'Все значки',
            'weiter' => 'Здорово',
            'neue_stufe' => 'Новый уровень достигнут: {stufe}! 🌟',
            'teilen' => 'Поделиться с сообществом',
            'teilen_wo' => 'Появится как ваша публикация в «{kreis}». Только если вы хотите.',
            'notiz' => 'Несколько ваших слов (по желанию)',
            'senden' => 'Поделиться сейчас',
            'geteilt_ok' => 'Готово — спасибо, что позволяете другим порадоваться вместе с вами!',
            'teilen_fehler' => 'Не получилось — возможно, вы уже поделились?',
            'geteilt' => 'Опубликовано',
            'naechste' => 'Следующий уровень: {stufe} при {wert}',
            'alle_stufen' => 'Все уровни пройдены — спасибо!',
            'stufen' => 'Уровни',
            'vorschau' => 'Так будет выглядеть ваше фото профиля',
            'abzeichen_eins' => 'Значок',
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
