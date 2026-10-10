<?php

namespace nexus\modules\gesundheit\services;

use Yii;

/**
 * Feste Oberflaechentexte (DE/EN/ES). Die Artikel selbst sind deutsch.
 */
class Texte
{
    private const TEXTE = [
        'de' => [
            'nav' => 'Gesundheitswissen',
            'titel' => 'Gesundheitswissen',
            'untertitel' => '%d geprüfte Artikel · Heilpflanzen, Ernährung, Beschwerden und mehr',
            'suche_platzhalter' => 'Beschwerde, Pflanze oder Stichwort, z. B. Bauchweh',
            'suchen' => 'Suchen',
            'treffer' => '%d Treffer für „%s“',
            'keine_treffer' => 'Zu „%s“ haben wir noch keinen Artikel gefunden. Versuche ein anderes Wort oder stöbere in den Themen.',
            'themen' => 'Themen',
            'schlagworte' => 'Häufige Schlagworte',
            'alle_artikel' => 'Alle Artikel',
            'artikel_im_thema' => '%d Artikel',
            'zurueck' => 'Gesundheitswissen',
            'inhalt' => 'Inhalt',
            'auf_einen_blick' => 'Auf einen Blick',
            'thema' => 'Thema',
            'geprueft' => 'Geprüft am',
            'naechste_pruefung' => 'Nächste Prüfung',
            'quellen' => 'Quellen',
            'belege' => '%d Belege',
            'siehe_auch' => 'Siehe auch',
            'hinweis' => 'Dieser Artikel dient dem Wissen und ersetzt keinen ärztlichen Rat. Bei ernsten oder anhaltenden Beschwerden bitte ärztlich abklären lassen – im Notfall 112.',
            'sprechen' => 'Im Kreis darüber sprechen',
            'sprechen_text' => 'Deine Gedanken oder Erfahrungen zu diesem Artikel – erscheint als Beitrag im Kreis:',
            'ergaenzen' => 'Ich möchte etwas ergänzen',
            'aendern' => 'Ich möchte etwas ändern',
            'vorschlag_text' => 'Was sollte ergänzt oder geändert werden? Gerne mit Quelle.',
            'absenden' => 'Absenden',
            'abbrechen' => 'Abbrechen',
            'vorschlag_danke' => 'Danke! Dein Vorschlag ist bei der Redaktion angekommen.',
            'beitrag_danke' => 'Dein Beitrag steht jetzt im Kreis.',
            'zu_kurz' => 'Bitte schreib ein paar Worte mehr (mindestens 10 Zeichen).',
            'nur_mitglieder' => 'Vorschläge und Beiträge sind für Mitglieder des Kreises möglich.',
            'bearbeiten' => 'Bearbeiten',
            'versionen' => 'Versionen',
            'vorschlaege' => 'Vorschläge',
            'nicht_freigegeben' => 'Noch nicht freigegeben – nur Admins sehen das Gesundheitswissen.',
            'freigeben' => 'Für alle Mitglieder freigeben',
            'zurueckziehen' => 'Freigabe zurücknehmen',
            'gespeichert' => 'Gespeichert ✓',
        ],
        'en' => [
            'nav' => 'Health knowledge',
            'titel' => 'Health knowledge',
            'untertitel' => '%d reviewed articles · medicinal plants, nutrition, complaints and more (in German)',
            'suche_platzhalter' => 'Complaint, plant or keyword (German), e.g. Bauchweh',
            'suchen' => 'Search',
            'treffer' => '%d results for “%s”',
            'keine_treffer' => 'No article found for “%s” yet. Try another word or browse the topics.',
            'themen' => 'Topics',
            'schlagworte' => 'Common keywords',
            'alle_artikel' => 'All articles',
            'artikel_im_thema' => '%d articles',
            'zurueck' => 'Health knowledge',
            'inhalt' => 'Contents',
            'auf_einen_blick' => 'At a glance',
            'thema' => 'Topic',
            'geprueft' => 'Reviewed on',
            'naechste_pruefung' => 'Next review',
            'quellen' => 'Sources',
            'belege' => '%d references',
            'siehe_auch' => 'See also',
            'hinweis' => 'This article is for information only and does not replace medical advice. For serious or persistent complaints, please see a doctor – in an emergency call 112.',
            'sprechen' => 'Talk about it in the circle',
            'sprechen_text' => 'Your thoughts or experiences on this article – appears as a post in the circle:',
            'ergaenzen' => 'I would like to add something',
            'aendern' => 'I would like to change something',
            'vorschlag_text' => 'What should be added or changed? A source is welcome.',
            'absenden' => 'Send',
            'abbrechen' => 'Cancel',
            'vorschlag_danke' => 'Thank you! Your suggestion has reached the editors.',
            'beitrag_danke' => 'Your post is now in the circle.',
            'zu_kurz' => 'Please write a few more words (at least 10 characters).',
            'nur_mitglieder' => 'Suggestions and posts are open to members of the circle.',
            'bearbeiten' => 'Edit',
            'versionen' => 'Versions',
            'vorschlaege' => 'Suggestions',
            'nicht_freigegeben' => 'Not released yet – only admins can see the health knowledge.',
            'freigeben' => 'Release for all members',
            'zurueckziehen' => 'Withdraw release',
            'gespeichert' => 'Saved ✓',
        ],
        'es' => [
            'nav' => 'Saber de salud',
            'titel' => 'Saber de salud',
            'untertitel' => '%d artículos revisados · plantas medicinales, alimentación, molestias y más (en alemán)',
            'suche_platzhalter' => 'Molestia, planta o palabra clave (en alemán), p. ej. Bauchweh',
            'suchen' => 'Buscar',
            'treffer' => '%d resultados para «%s»',
            'keine_treffer' => 'Aún no hay ningún artículo sobre «%s». Prueba otra palabra o explora los temas.',
            'themen' => 'Temas',
            'schlagworte' => 'Palabras clave frecuentes',
            'alle_artikel' => 'Todos los artículos',
            'artikel_im_thema' => '%d artículos',
            'zurueck' => 'Saber de salud',
            'inhalt' => 'Contenido',
            'auf_einen_blick' => 'De un vistazo',
            'thema' => 'Tema',
            'geprueft' => 'Revisado el',
            'naechste_pruefung' => 'Próxima revisión',
            'quellen' => 'Fuentes',
            'belege' => '%d referencias',
            'siehe_auch' => 'Véase también',
            'hinweis' => 'Este artículo es informativo y no sustituye el consejo médico. Ante molestias graves o persistentes, consulta a un médico; en caso de emergencia, llama al 112.',
            'sprechen' => 'Hablar de ello en el círculo',
            'sprechen_text' => 'Tus ideas o experiencias sobre este artículo – aparece como publicación en el círculo:',
            'ergaenzen' => 'Quiero añadir algo',
            'aendern' => 'Quiero cambiar algo',
            'vorschlag_text' => '¿Qué debería añadirse o cambiarse? Con fuente, si es posible.',
            'absenden' => 'Enviar',
            'abbrechen' => 'Cancelar',
            'vorschlag_danke' => '¡Gracias! Tu propuesta ha llegado a la redacción.',
            'beitrag_danke' => 'Tu publicación ya está en el círculo.',
            'zu_kurz' => 'Escribe algunas palabras más (al menos 10 caracteres).',
            'nur_mitglieder' => 'Las propuestas y publicaciones están abiertas a los miembros del círculo.',
            'bearbeiten' => 'Editar',
            'versionen' => 'Versiones',
            'vorschlaege' => 'Propuestas',
            'nicht_freigegeben' => 'Aún no publicado – solo los administradores lo ven.',
            'freigeben' => 'Publicar para todos los miembros',
            'zurueckziehen' => 'Retirar publicación',
            'gespeichert' => 'Guardado ✓',
        ],
    ];

    /** Themennamen (die Artikel sind deutsch, die Namen der Themen auch uebersetzt). */
    private const THEMEN = [
        'de' => ['01_Ernaehrung' => 'Ernährung und Nährstoffe', '02_Heilpflanzen' => 'Heilpflanzen und ihre Anwendung', '03_Bewegung' => 'Bewegung, Schlaf und Erholung', '04_Beschwerden' => 'Beschwerden und Erkrankungen', '05_Vorsorge' => 'Vorsorge und sicherer Medikamentenumgang', '06_Gesundheitswissen' => 'Gesundheitswissen verstehen'],
        'en' => ['01_Ernaehrung' => 'Nutrition and nutrients', '02_Heilpflanzen' => 'Medicinal plants and their use', '03_Bewegung' => 'Exercise, sleep and recovery', '04_Beschwerden' => 'Complaints and illnesses', '05_Vorsorge' => 'Prevention and safe use of medicines', '06_Gesundheitswissen' => 'Understanding health information'],
        'es' => ['01_Ernaehrung' => 'Alimentación y nutrientes', '02_Heilpflanzen' => 'Plantas medicinales y su uso', '03_Bewegung' => 'Movimiento, sueño y descanso', '04_Beschwerden' => 'Molestias y enfermedades', '05_Vorsorge' => 'Prevención y uso seguro de medicamentos', '06_Gesundheitswissen' => 'Entender la información de salud'],
    ];

    public const SYMBOLE = ['01_Ernaehrung' => 'cutlery', '02_Heilpflanzen' => 'leaf', '03_Bewegung' => 'bed', '04_Beschwerden' => 'stethoscope', '05_Vorsorge' => 'shield', '06_Gesundheitswissen' => 'book'];

    public static function sprache(): string
    {
        $code = strtolower(explode('-', (string)Yii::$app->language)[0]);
        return isset(self::TEXTE[$code]) ? $code : 'en';
    }

    public static function t(string $schluessel): string
    {
        return self::TEXTE[self::sprache()][$schluessel] ?? self::TEXTE['de'][$schluessel] ?? $schluessel;
    }

    public static function thema(string $code): string
    {
        return self::THEMEN[self::sprache()][$code] ?? self::THEMEN['de'][$code] ?? $code;
    }

    /** @return array<string, string> */
    public static function themen(): array
    {
        return self::THEMEN[self::sprache()];
    }
}
