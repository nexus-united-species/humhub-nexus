<?php

namespace nexus\modules\teilen\services;

use nexus\modules\teilen\Module;
use Yii;

/**
 * Alle sichtbaren Texte in Deutsch, Englisch und Spanisch. Feste Texte statt Yii::t(): fuer
 * eigene Schluessel gibt es keine Uebersetzungsdateien, sie fielen sonst auf Englisch zurueck.
 */
class Texte
{
    private const TEXTE = [
        'de' => [
            'teilen' => 'Weitergeben',
            'oeffentlich_markierung' => 'Öffentlich lesbar – jeder mit dem Link kann diesen Inhalt lesen',
            'menue_an' => 'Öffentlich lesbar machen',
            'menue_aus' => 'Öffentliche Freigabe aufheben',
            'frage_an' => "Diesen Inhalt öffentlich lesbar machen?\n\nJeder, der den Link bekommt, kann ihn dann ohne Anmeldung lesen – auch Menschen außerhalb von N.E.X.U.S.\n\nNicht sichtbar sind: Name des Verfassers, Kommentare, der Kreis und alles andere im Portal.\n\nDu kannst die Freigabe jederzeit wieder aufheben.",
            'frage_aus' => "Öffentliche Freigabe aufheben?\n\nDer öffentliche Link funktioniert danach nicht mehr – auch dort nicht, wo er schon geteilt wurde.",
            'ist_an' => 'Jetzt öffentlich lesbar. Über „Weitergeben“ verschickst du ab sofort den öffentlichen Link.',
            'ist_aus' => 'Die öffentliche Freigabe ist aufgehoben.',
            'kopiert' => 'Link und Text sind kopiert – du kannst sie jetzt in Signal, WhatsApp, E-Mail usw. einfügen.',
            'intern_titel' => 'Nur für Mitglieder lesbar',
            'intern_text' => "Diesen Beitrag können nur angemeldete Mitglieder öffnen. Wer nicht im Portal angemeldet ist, sieht nur die Anmeldeseite.\n\nWillst du ihn öffentlich teilen, musst du ihn zuerst öffentlich stellen: oben rechts am Beitrag auf die drei Punkte „…“ klicken → „Öffentlich lesbar machen“ – oder gleich hier mit dem Knopf unten.",
            'intern_text_fremd' => "Diesen Beitrag können nur angemeldete Mitglieder öffnen. Wer nicht im Portal angemeldet ist, sieht nur die Anmeldeseite.\n\nÖffentlich stellen (über die drei Punkte „…“ → „Öffentlich lesbar machen“) können nur der Verfasser und Admins. Frag sie, wenn du ihn öffentlich teilen möchtest.",
            'knopf_oeffentlich' => 'Jetzt öffentlich lesbar machen',
            'knopf_mitglieder' => 'Nur an Mitglieder weitergeben',
            'fenster_titel' => 'Weitergeben',
            'fenster_teilen' => 'Weitergeben …',
            'fenster_kopieren' => 'Kopieren',
            'fenster_schliessen' => 'Schließen',
            'fehler' => 'Das hat nicht geklappt. Bitte noch einmal versuchen.',
            // Leseseite
            'aus_gemeinschaft' => 'Aus der N.E.X.U.S.-Gemeinschaft',
            'mitmachen' => 'Neugierig geworden? Bei N.E.X.U.S. gestalten Menschen gemeinsam eine neue Art des Zusammenlebens – menschlich, dezentral und auf echter Gemeinschaft aufgebaut.',
            'registrieren' => 'Mitmachen – kostenlos registrieren',
            'anmelden' => 'Schon Mitglied? Anmelden',
            'im_portal' => 'Im Portal öffnen',
            'automatisch' => 'Automatisch übersetzt aus dem %s.',
            'original' => 'Original anzeigen',
            'sprachname' => ['de' => 'Deutschen', 'en' => 'Englischen', 'es' => 'Spanischen'],
            'anhaenge' => 'Anhänge',
            'weg_titel' => 'Dieser Inhalt ist nicht mehr öffentlich',
            'weg_text' => 'Der Link wurde zurückgenommen oder der Inhalt gelöscht.',
            'kontakt' => 'Kontakt:',
            // Schaufenster
            'ein_mitglied' => 'ein Mitglied',
            'sf_knopf_an' => 'Ins Schaufenster',
            'sf_knopf_drin' => 'Im Schaufenster',
            'sf_knopf_titel' => 'Öffentliche Seite „Aus der N.E.X.U.S.-Gemeinschaft“ (nur Admins sehen diesen Knopf)',
            'sf_vorschau_titel' => 'So erscheint der Beitrag im öffentlichen Schaufenster',
            'sf_namen' => 'Bitte prüfen – diese Namen von Mitgliedern stehen im Text (gelb markiert). Gehören sie hinein, ist alles gut; sonst den Beitrag nicht freigeben oder erst bearbeiten:',
            'sf_keine_namen' => 'Keine Namen von Mitgliedern im Text gefunden.',
            'sf_bilder' => 'Bilder bitte selbst ansehen: Erkennbare Menschen nur mit deren Einverständnis.',
            'sf_stellen' => 'Ins Schaufenster stellen',
            'sf_abbrechen' => 'Abbrechen',
            'sf_raus_frage' => "Aus dem Schaufenster nehmen?\n\nDer Beitrag verschwindet von der öffentlichen Seite. Ein schon geteilter Link funktioniert weiter.",
            'sf_drin_meldung' => 'Steht jetzt im öffentlichen Schaufenster.',
            'sf_raus_meldung' => 'Aus dem Schaufenster genommen.',
            'sf_titel' => 'Aus der N.E.X.U.S.-Gemeinschaft',
            'sf_intro' => 'Einblicke in das, woran Menschen bei N.E.X.U.S. gemeinsam arbeiten – ausgewählt aus unserem Mitgliederportal.',
            'sf_weiterlesen' => 'Weiterlesen',
            'sf_alle' => 'Alle Beiträge aus der Gemeinschaft',
            'sf_leer' => 'Hier erscheinen bald ausgewählte Beiträge aus der Gemeinschaft.',
            'sf_aeltere' => 'Ältere Beiträge →',
            'sf_neuere' => '← Neuere Beiträge',
            'sf_mehr' => 'Mehr aus der Gemeinschaft',
        ],
        'en' => [
            'teilen' => 'Pass on',
            'oeffentlich_markierung' => 'Publicly readable – anyone with the link can read this',
            'menue_an' => 'Make publicly readable',
            'menue_aus' => 'Remove public access',
            'frage_an' => "Make this publicly readable?\n\nAnyone who gets the link can then read it without signing in – including people outside N.E.X.U.S.\n\nNot visible: the author's name, comments, the circle and everything else in the portal.\n\nYou can remove public access at any time.",
            'frage_aus' => "Remove public access?\n\nThe public link will stop working – also where it has already been shared.",
            'ist_an' => 'Now publicly readable. "Pass on" now sends the public link.',
            'ist_aus' => 'Public access has been removed.',
            'kopiert' => 'Link and text copied – you can now paste them into Signal, WhatsApp, email etc.',
            'intern_titel' => 'Readable for members only',
            'intern_text' => "Only signed-in members can open this post. Anyone not signed in to the portal only sees the login page.\n\nIf you want to share it publicly, you first have to make it public: click the three dots \"…\" at the top right of the post → \"Make publicly readable\" – or right here with the button below.",
            'intern_text_fremd' => "Only signed-in members can open this post. Anyone not signed in to the portal only sees the login page.\n\nOnly the author and admins can make it public (three dots \"…\" → \"Make publicly readable\"). Ask them if you want to share it publicly.",
            'knopf_oeffentlich' => 'Make publicly readable now',
            'knopf_mitglieder' => 'Pass on to members only',
            'fenster_titel' => 'Pass on',
            'fenster_teilen' => 'Pass on …',
            'fenster_kopieren' => 'Copy',
            'fenster_schliessen' => 'Close',
            'fehler' => 'That did not work. Please try again.',
            'aus_gemeinschaft' => 'From the N.E.X.U.S. community',
            'mitmachen' => 'Curious? At N.E.X.U.S. people are shaping a new way of living together – human, decentralised and grounded in real community.',
            'registrieren' => 'Join – register for free',
            'anmelden' => 'Already a member? Sign in',
            'im_portal' => 'Open in the portal',
            'automatisch' => 'Automatically translated from %s.',
            'original' => 'Show original',
            'sprachname' => ['de' => 'German', 'en' => 'English', 'es' => 'Spanish'],
            'anhaenge' => 'Attachments',
            'weg_titel' => 'This content is no longer public',
            'weg_text' => 'The link has been withdrawn or the content deleted.',
            'kontakt' => 'Contact:',
            'ein_mitglied' => 'a member',
            'sf_knopf_an' => 'To showcase',
            'sf_knopf_drin' => 'In showcase',
            'sf_knopf_titel' => 'Public page “From the N.E.X.U.S. community” (only admins see this button)',
            'sf_vorschau_titel' => 'This is how the post appears in the public showcase',
            'sf_namen' => 'Please check – these member names appear in the text (highlighted). If they belong there, all is fine; otherwise do not publish or edit the post first:',
            'sf_keine_namen' => 'No member names found in the text.',
            'sf_bilder' => 'Please check images yourself: recognisable people only with their consent.',
            'sf_stellen' => 'Put in showcase',
            'sf_abbrechen' => 'Cancel',
            'sf_raus_frage' => "Remove from the showcase?\n\nThe post disappears from the public page. A link that was already shared keeps working.",
            'sf_drin_meldung' => 'Now in the public showcase.',
            'sf_raus_meldung' => 'Removed from the showcase.',
            'sf_titel' => 'From the N.E.X.U.S. community',
            'sf_intro' => 'Insights into what people at N.E.X.U.S. are working on together – selected from our members’ portal.',
            'sf_weiterlesen' => 'Read more',
            'sf_alle' => 'All posts from the community',
            'sf_leer' => 'Selected posts from the community will appear here soon.',
            'sf_aeltere' => 'Older posts →',
            'sf_neuere' => '← Newer posts',
            'sf_mehr' => 'More from the community',
        ],
        'es' => [
            'teilen' => 'Reenviar',
            'oeffentlich_markierung' => 'Lectura pública – cualquiera con el enlace puede leer esto',
            'menue_an' => 'Hacer legible públicamente',
            'menue_aus' => 'Quitar acceso público',
            'frage_an' => "¿Hacer este contenido legible públicamente?\n\nCualquiera que reciba el enlace podrá leerlo sin iniciar sesión – también personas fuera de N.E.X.U.S.\n\nNo se ve: el nombre del autor, los comentarios, el círculo ni nada más del portal.\n\nPuedes quitar el acceso público en cualquier momento.",
            'frage_aus' => "¿Quitar el acceso público?\n\nEl enlace público dejará de funcionar – también donde ya se haya compartido.",
            'ist_an' => 'Ahora es legible públicamente. «Reenviar» envía a partir de ahora el enlace público.',
            'ist_aus' => 'Se ha quitado el acceso público.',
            'kopiert' => 'Enlace y texto copiados – ahora puedes pegarlos en Signal, WhatsApp, correo, etc.',
            'intern_titel' => 'Solo legible para miembros',
            'intern_text' => "Solo los miembros con sesión iniciada pueden abrir esta publicación. Quien no haya iniciado sesión en el portal solo ve la página de acceso.\n\nSi quieres compartirla públicamente, primero tienes que hacerla pública: haz clic en los tres puntos «…» arriba a la derecha de la publicación → «Hacer legible públicamente» – o directamente aquí con el botón de abajo.",
            'intern_text_fremd' => "Solo los miembros con sesión iniciada pueden abrir esta publicación. Quien no haya iniciado sesión en el portal solo ve la página de acceso.\n\nSolo el autor y los administradores pueden hacerla pública (tres puntos «…» → «Hacer legible públicamente»). Pídeselo si quieres compartirla públicamente.",
            'knopf_oeffentlich' => 'Hacer legible públicamente ahora',
            'knopf_mitglieder' => 'Reenviar solo a miembros',
            'fenster_titel' => 'Reenviar',
            'fenster_teilen' => 'Reenviar …',
            'fenster_kopieren' => 'Copiar',
            'fenster_schliessen' => 'Cerrar',
            'fehler' => 'No ha funcionado. Por favor, inténtalo de nuevo.',
            'aus_gemeinschaft' => 'De la comunidad N.E.X.U.S.',
            'mitmachen' => '¿Te despierta curiosidad? En N.E.X.U.S. las personas construyen juntas una nueva forma de convivencia – humana, descentralizada y basada en una comunidad real.',
            'registrieren' => 'Participar – registro gratuito',
            'anmelden' => '¿Ya eres miembro? Iniciar sesión',
            'im_portal' => 'Abrir en el portal',
            'automatisch' => 'Traducido automáticamente del %s.',
            'original' => 'Ver original',
            'sprachname' => ['de' => 'alemán', 'en' => 'inglés', 'es' => 'español'],
            'anhaenge' => 'Archivos adjuntos',
            'weg_titel' => 'Este contenido ya no es público',
            'weg_text' => 'El enlace ha sido retirado o el contenido eliminado.',
            'kontakt' => 'Contacto:',
            'ein_mitglied' => 'un miembro',
            'sf_knopf_an' => 'Al escaparate',
            'sf_knopf_drin' => 'En el escaparate',
            'sf_knopf_titel' => 'Página pública «De la comunidad N.E.X.U.S.» (solo los administradores ven este botón)',
            'sf_vorschau_titel' => 'Así aparece la publicación en el escaparate público',
            'sf_namen' => 'Por favor, revisa – estos nombres de miembros aparecen en el texto (resaltados). Si deben estar, todo bien; si no, no la publiques o edítala antes:',
            'sf_keine_namen' => 'No se encontraron nombres de miembros en el texto.',
            'sf_bilder' => 'Revisa tú las imágenes: personas reconocibles solo con su consentimiento.',
            'sf_stellen' => 'Poner en el escaparate',
            'sf_abbrechen' => 'Cancelar',
            'sf_raus_frage' => "¿Quitar del escaparate?\n\nLa publicación desaparece de la página pública. Un enlace ya compartido sigue funcionando.",
            'sf_drin_meldung' => 'Ahora está en el escaparate público.',
            'sf_raus_meldung' => 'Quitado del escaparate.',
            'sf_titel' => 'De la comunidad N.E.X.U.S.',
            'sf_intro' => 'Una mirada a lo que las personas de N.E.X.U.S. construyen juntas – seleccionado de nuestro portal de miembros.',
            'sf_weiterlesen' => 'Leer más',
            'sf_alle' => 'Todas las publicaciones de la comunidad',
            'sf_leer' => 'Pronto aparecerán aquí publicaciones seleccionadas de la comunidad.',
            'sf_aeltere' => 'Publicaciones anteriores →',
            'sf_neuere' => '← Publicaciones más recientes',
            'sf_mehr' => 'Más de la comunidad',
        ],
    ];

    /** Sprache der Oberflaeche: angemeldete Menschen ihre Einstellung, Gaeste ihr Browser. Sonst Englisch. */
    public static function sprache(): string
    {
        $code = strtolower(explode('-', (string)Yii::$app->language)[0]);
        return in_array($code, Module::SPRACHEN, true) ? $code : 'en';
    }

    /** @return array<string, mixed> */
    public static function alle(?string $sprache = null): array
    {
        $sprache ??= self::sprache();
        if (!isset(self::TEXTE[$sprache])) {
            $sprache = 'en';
        }
        // Fusszeilen-Links (Impressum, Datenschutz ...) kommen aus der Moduleinstellung "fussLinks".
        return self::TEXTE[$sprache] + ['fuss_links' => Module::instanz()->fussLinks($sprache)];
    }

    public static function get(string $schluessel, ?string $sprache = null)
    {
        return self::alle($sprache)[$schluessel];
    }
}
