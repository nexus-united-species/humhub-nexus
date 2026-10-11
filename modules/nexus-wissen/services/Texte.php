<?php

namespace nexus\modules\wissen\services;

use Yii;

/**
 * Feste Oberflaechentexte (DE/EN/ES). Name, Beschreibung, Themen und Hinweis einer Sammlung kommen
 * aus ihren Einstellungen (Sammlung.php); die Artikel bleiben in ihrer Originalsprache.
 */
class Texte
{
    private const TEXTE = [
        'de' => [
            'wissen' => 'Wissen',
            'anzahl' => '%d Artikel',
            'suche_platzhalter' => 'Suchbegriff oder Stichwort',
            'suchen' => 'Suchen',
            'treffer' => '%d Treffer für „%s“',
            'keine_treffer' => 'Zu „%s“ haben wir noch keinen Artikel gefunden. Versuche ein anderes Wort oder stöbere in den Themen.',
            'themen' => 'Themen',
            'schlagworte' => 'Häufige Schlagworte',
            'schlagworte_artikel' => 'Schlagworte',
            'artikel_im_thema' => '%d Artikel',
            'inhalt' => 'Inhalt',
            'auf_einen_blick' => 'Auf einen Blick',
            'thema' => 'Thema',
            'geprueft' => 'Geprüft am',
            'naechste_pruefung' => 'Nächste Prüfung',
            'quellen' => 'Quellen',
            'belege' => '%d Belege',
            'siehe_auch' => 'Siehe auch',
            'sprechen' => 'Im Kreis darüber sprechen',
            'sprechen_text' => 'Deine Gedanken oder Erfahrungen zu diesem Artikel – erscheint als Beitrag im Kreis:',
            'ergaenzen' => 'Ich möchte etwas ergänzen',
            'aendern' => 'Ich möchte etwas ändern',
            'vorschlag_text' => 'Was sollte ergänzt oder geändert werden? Gerne mit Quelle.',
            'absenden' => 'Absenden',
            'abbrechen' => 'Abbrechen',
            'vorschlag_danke' => 'Danke! Dein Vorschlag ist bei der Redaktion angekommen.',
            'zu_kurz' => 'Bitte schreib ein paar Worte mehr (mindestens 10 Zeichen).',
            'nur_mitglieder' => 'Vorschläge und Beiträge sind für Mitglieder des Kreises möglich.',
            'bearbeiten' => 'Bearbeiten',
            'versionen' => 'Versionen',
            'vorschlaege' => 'Vorschläge',
            'nicht_freigegeben' => 'Noch nicht freigegeben – nur die Redaktion sieht diese Sammlung.',
            'freigeben' => 'Für alle Mitglieder freigeben',
            'zurueckziehen' => 'Freigabe zurücknehmen',
            'gespeichert' => 'Gespeichert ✓',
            'leer' => 'Diese Sammlung enthält noch keine Artikel.',
        ],
        'en' => [
            'wissen' => 'Knowledge',
            'anzahl' => '%d articles',
            'suche_platzhalter' => 'Search term or keyword',
            'suchen' => 'Search',
            'treffer' => '%d results for “%s”',
            'keine_treffer' => 'No article found for “%s” yet. Try another word or browse the topics.',
            'themen' => 'Topics',
            'schlagworte' => 'Common keywords',
            'schlagworte_artikel' => 'Keywords',
            'artikel_im_thema' => '%d articles',
            'inhalt' => 'Contents',
            'auf_einen_blick' => 'At a glance',
            'thema' => 'Topic',
            'geprueft' => 'Reviewed on',
            'naechste_pruefung' => 'Next review',
            'quellen' => 'Sources',
            'belege' => '%d references',
            'siehe_auch' => 'See also',
            'sprechen' => 'Talk about it in the space',
            'sprechen_text' => 'Your thoughts or experiences on this article – appears as a post in the space:',
            'ergaenzen' => 'I would like to add something',
            'aendern' => 'I would like to change something',
            'vorschlag_text' => 'What should be added or changed? A source is welcome.',
            'absenden' => 'Send',
            'abbrechen' => 'Cancel',
            'vorschlag_danke' => 'Thank you! Your suggestion has reached the editors.',
            'zu_kurz' => 'Please write a few more words (at least 10 characters).',
            'nur_mitglieder' => 'Suggestions and posts are open to members of the space.',
            'bearbeiten' => 'Edit',
            'versionen' => 'Versions',
            'vorschlaege' => 'Suggestions',
            'nicht_freigegeben' => 'Not released yet – only the editors can see this collection.',
            'freigeben' => 'Release for all members',
            'zurueckziehen' => 'Withdraw release',
            'gespeichert' => 'Saved ✓',
            'leer' => 'This collection has no articles yet.',
        ],
        'es' => [
            'wissen' => 'Saber',
            'anzahl' => '%d artículos',
            'suche_platzhalter' => 'Término de búsqueda o palabra clave',
            'suchen' => 'Buscar',
            'treffer' => '%d resultados para «%s»',
            'keine_treffer' => 'Aún no hay ningún artículo sobre «%s». Prueba otra palabra o explora los temas.',
            'themen' => 'Temas',
            'schlagworte' => 'Palabras clave frecuentes',
            'schlagworte_artikel' => 'Palabras clave',
            'artikel_im_thema' => '%d artículos',
            'inhalt' => 'Contenido',
            'auf_einen_blick' => 'De un vistazo',
            'thema' => 'Tema',
            'geprueft' => 'Revisado el',
            'naechste_pruefung' => 'Próxima revisión',
            'quellen' => 'Fuentes',
            'belege' => '%d referencias',
            'siehe_auch' => 'Véase también',
            'sprechen' => 'Hablar de ello en el círculo',
            'sprechen_text' => 'Tus ideas o experiencias sobre este artículo – aparece como publicación en el círculo:',
            'ergaenzen' => 'Quiero añadir algo',
            'aendern' => 'Quiero cambiar algo',
            'vorschlag_text' => '¿Qué debería añadirse o cambiarse? Con fuente, si es posible.',
            'absenden' => 'Enviar',
            'abbrechen' => 'Cancelar',
            'vorschlag_danke' => '¡Gracias! Tu propuesta ha llegado a la redacción.',
            'zu_kurz' => 'Escribe algunas palabras más (al menos 10 caracteres).',
            'nur_mitglieder' => 'Las propuestas y publicaciones están abiertas a los miembros del círculo.',
            'bearbeiten' => 'Editar',
            'versionen' => 'Versiones',
            'vorschlaege' => 'Propuestas',
            'nicht_freigegeben' => 'Aún no publicado – solo la redacción ve esta colección.',
            'freigeben' => 'Publicar para todos los miembros',
            'zurueckziehen' => 'Retirar publicación',
            'gespeichert' => 'Guardado ✓',
            'leer' => 'Esta colección aún no tiene artículos.',
        ],
    ];

    public static function sprache(): string
    {
        $code = strtolower(explode('-', (string)Yii::$app->language)[0]);
        return isset(self::TEXTE[$code]) ? $code : 'en';
    }

    public static function t(string $schluessel): string
    {
        return self::TEXTE[self::sprache()][$schluessel] ?? self::TEXTE['en'][$schluessel] ?? $schluessel;
    }

    /**
     * Ein Text aus den Einstellungen: String, oder ['de' => ..., 'en' => ...] -- dann die gewuenschte
     * Sprache (Vorgabe: Oberflaechensprache), sonst Englisch, sonst der erste Eintrag.
     */
    public static function waehle($wert, ?string $sprache = null): string
    {
        if (is_string($wert)) {
            return $wert;
        }
        if (!is_array($wert) || $wert === []) {
            return '';
        }
        $code = $sprache ?? strtolower(explode('-', (string)Yii::$app->language)[0]);
        return (string)($wert[$code] ?? $wert['en'] ?? reset($wert));
    }
}
