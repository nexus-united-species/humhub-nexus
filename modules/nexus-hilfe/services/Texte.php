<?php

namespace nexus\modules\hilfe\services;

use Yii;

/** Texte der Hilfe-Seite in DE/EN/ES (feste Texte, wie in den anderen N.E.X.U.S.-Modulen). */
class Texte
{
    public const THEMEN = ['konto', 'fehler', 'frage', 'sonstiges', 'nova'];

    private const T = [
        'de' => [
            'titel' => 'Hilfe & Support',
            'intro' => 'Hier findest du schnell Antworten – und wenn nicht, hilft dir unser Support-Team.',
            'anleitungen' => 'Anleitungen',
            'anleitungen_text' => 'Schritt-für-Schritt-Anleitungen für die wichtigsten Dinge im Portal:',
            'nova' => 'Frag Nova',
            'nova_text' => 'Nova ist unser KI-Assistent und beantwortet Fragen zu N.E.X.U.S. und zum Portal – rund um die Uhr. Kann Nova nicht helfen, gibt Nova deine Frage an das Support-Team weiter.',
            'nova_knopf' => 'Nachricht an Nova schreiben',
            'formular' => 'Anfrage an das Support-Team',
            'formular_text' => 'Beschreib kurz, worum es geht. Ein Mensch aus dem Support-Team meldet sich innerhalb von 24 Stunden.',
            'formular_gast' => 'Du kommst nicht ins Portal? Dann schreib uns hier – wir antworten per E-Mail innerhalb von 24 Stunden.',
            'thema' => 'Worum geht es?',
            'themen' => ['konto' => 'Anmeldung oder Konto', 'fehler' => 'Etwas funktioniert nicht', 'frage' => 'Frage zu N.E.X.U.S.', 'sonstiges' => 'Sonstiges', 'nova' => 'Von Nova weitergegeben'],
            'beschreibung' => 'Deine Nachricht',
            'beschreibung_hilfe' => 'Je genauer, desto schneller können wir helfen: Was wolltest du tun, was ist passiert?',
            'bild' => 'Bildschirmfoto (freiwillig)',
            'name' => 'Dein Name',
            'email' => 'Deine E-Mail-Adresse (für unsere Antwort)',
            'senden' => 'Anfrage senden',
            'danke_titel' => 'Danke – deine Anfrage ist angekommen',
            'danke_text' => 'Deine Anfrage hat die Nummer #%d. Ein Mensch aus dem Support-Team meldet sich innerhalb von 24 Stunden bei dir.',
            'danke_nachricht' => 'Du bekommst unsere Antwort als Nachricht im Portal (oben beim Briefumschlag) und per E-Mail.',
            'danke_gast' => 'Wir antworten dir per E-Mail.',
            'zurueck' => 'Zurück zur Hilfe',
            'fehler_text' => 'Bitte schreib mindestens ein paar Worte, damit wir helfen können.',
            'fehler_email' => 'Bitte gib Namen und eine gültige E-Mail-Adresse an, damit wir antworten können.',
            'fehler_bild' => 'Das Bild ist zu groß oder kein Bild (erlaubt: JPG, PNG, WebP bis 10 MB).',
            'fehler_viele' => 'Du hast gerade schon mehrere Anfragen gesendet. Bitte warte etwas, wir melden uns.',
            'fehler_allgemein' => 'Das hat leider nicht geklappt. Bitte versuch es gleich noch einmal.',
            'bestaetigung' => "Hallo %s, deine Anfrage #%d ist beim Support-Team angekommen. Ein Mensch aus dem Team meldet sich innerhalb von 24 Stunden bei dir.\n\nDein N.E.X.U.S. Support-Team",
        ],
        'en' => [
            'titel' => 'Help & support',
            'intro' => 'Find answers quickly here – and if not, our support team will help you.',
            'anleitungen' => 'Guides',
            'anleitungen_text' => 'Step-by-step guides for the most important things in the portal:',
            'nova' => 'Ask Nova',
            'nova_text' => 'Nova is our AI assistant and answers questions about N.E.X.U.S. and the portal – around the clock. If Nova cannot help, Nova passes your question on to the support team.',
            'nova_knopf' => 'Write a message to Nova',
            'formular' => 'Request to the support team',
            'formular_text' => 'Briefly describe your issue. A person from the support team will get back to you within 24 hours.',
            'formular_gast' => "Can't get into the portal? Write to us here – we will reply by email within 24 hours.",
            'thema' => 'What is it about?',
            'themen' => ['konto' => 'Sign-in or account', 'fehler' => 'Something does not work', 'frage' => 'Question about N.E.X.U.S.', 'sonstiges' => 'Other', 'nova' => 'Passed on by Nova'],
            'beschreibung' => 'Your message',
            'beschreibung_hilfe' => 'The more precise, the faster we can help: what did you want to do, what happened?',
            'bild' => 'Screenshot (optional)',
            'name' => 'Your name',
            'email' => 'Your email address (for our reply)',
            'senden' => 'Send request',
            'danke_titel' => 'Thank you – your request has arrived',
            'danke_text' => 'Your request has the number #%d. A person from the support team will get back to you within 24 hours.',
            'danke_nachricht' => 'You will get our reply as a message in the portal (envelope at the top) and by email.',
            'danke_gast' => 'We will reply to you by email.',
            'zurueck' => 'Back to help',
            'fehler_text' => 'Please write at least a few words so we can help.',
            'fehler_email' => 'Please enter your name and a valid email address so we can reply.',
            'fehler_bild' => 'The image is too large or not an image (allowed: JPG, PNG, WebP up to 10 MB).',
            'fehler_viele' => 'You have just sent several requests. Please wait a little, we will get back to you.',
            'fehler_allgemein' => 'Unfortunately that did not work. Please try again in a moment.',
            'bestaetigung' => "Hello %s, your request #%d has reached the support team. A person from the team will get back to you within 24 hours.\n\nYour N.E.X.U.S. support team",
        ],
        'es' => [
            'titel' => 'Ayuda y soporte',
            'intro' => 'Aquí encuentras respuestas rápidas – y si no, te ayuda nuestro equipo de soporte.',
            'anleitungen' => 'Guías',
            'anleitungen_text' => 'Guías paso a paso para lo más importante del portal:',
            'nova' => 'Pregunta a Nova',
            'nova_text' => 'Nova es nuestro asistente de IA y responde preguntas sobre N.E.X.U.S. y el portal – a cualquier hora. Si Nova no puede ayudar, pasa tu pregunta al equipo de soporte.',
            'nova_knopf' => 'Escribir un mensaje a Nova',
            'formular' => 'Solicitud al equipo de soporte',
            'formular_text' => 'Describe brevemente de qué se trata. Una persona del equipo de soporte te responderá en 24 horas.',
            'formular_gast' => '¿No puedes entrar en el portal? Escríbenos aquí – te responderemos por correo en 24 horas.',
            'thema' => '¿De qué se trata?',
            'themen' => ['konto' => 'Acceso o cuenta', 'fehler' => 'Algo no funciona', 'frage' => 'Pregunta sobre N.E.X.U.S.', 'sonstiges' => 'Otro', 'nova' => 'Transmitido por Nova'],
            'beschreibung' => 'Tu mensaje',
            'beschreibung_hilfe' => 'Cuanto más preciso, más rápido podemos ayudar: ¿qué querías hacer, qué pasó?',
            'bild' => 'Captura de pantalla (opcional)',
            'name' => 'Tu nombre',
            'email' => 'Tu correo electrónico (para nuestra respuesta)',
            'senden' => 'Enviar solicitud',
            'danke_titel' => 'Gracias – tu solicitud ha llegado',
            'danke_text' => 'Tu solicitud tiene el número #%d. Una persona del equipo de soporte te responderá en 24 horas.',
            'danke_nachricht' => 'Recibirás nuestra respuesta como mensaje en el portal (sobre arriba) y por correo.',
            'danke_gast' => 'Te responderemos por correo electrónico.',
            'zurueck' => 'Volver a la ayuda',
            'fehler_text' => 'Escribe al menos unas palabras para que podamos ayudarte.',
            'fehler_email' => 'Indica tu nombre y un correo válido para que podamos responder.',
            'fehler_bild' => 'La imagen es demasiado grande o no es una imagen (permitido: JPG, PNG, WebP hasta 10 MB).',
            'fehler_viele' => 'Acabas de enviar varias solicitudes. Espera un poco, te responderemos.',
            'fehler_allgemein' => 'Lamentablemente no ha funcionado. Inténtalo de nuevo en un momento.',
            'bestaetigung' => "Hola %s, tu solicitud #%d ha llegado al equipo de soporte. Una persona del equipo te responderá en 24 horas.\n\nTu equipo de soporte de N.E.X.U.S.",
        ],
    ];

    public static function sprache(?string $code = null): string
    {
        $code = strtolower(explode('-', (string)($code ?? Yii::$app->language))[0]);
        return isset(self::T[$code]) ? $code : 'de';
    }

    /** @return array<string, mixed> */
    public static function alle(?string $code = null): array
    {
        return self::T[self::sprache($code)];
    }

    /** Thema fuer das Team immer auf Deutsch. */
    public static function themaDeutsch(string $thema): string
    {
        return self::T['de']['themen'][$thema] ?? $thema;
    }
}
