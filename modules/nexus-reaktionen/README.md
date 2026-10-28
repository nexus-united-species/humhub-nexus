# N.E.X.U.S. Reaktionen

Emoji-Reaktionen wie bei Messengern unter Beitraegen und Kommentaren, neben dem Gefaellt-mir-Link von HumHub.

- Der Daumen (Like-Modul) bleibt unveraendert. Daneben gibt es einen Knopf mit einer kleinen Auswahl
  (Standard: Herz, Lachen, Staunen, Traurig, Danke, Feiern).
- Je Mensch und Beitrag/Kommentar hoechstens eine Reaktion; dasselbe Emoji nochmal anklicken nimmt sie zurueck.
- Unter dem Eintrag stehen die Reaktionen als kleine Chips mit Zaehler, im Hinweistext die Namen.
- Reaktionen loesen keine Benachrichtigung und keine E-Mail aus.
- Auswahl aendern: `emojis` in den Modul-Einstellungen (`common.php`, `'nexus-reaktionen' => ['emojis' => [...]]`).

Technik: eigene Tabelle `nexus_reaktion`, kein Eingriff in den HumHub-Kern. Das Skript (`resources/reaktionen.js`)
haengt sich an `.likeLinkContainer` und holt die Reaktionen einer Seite mit einer Anfrage (`liste/index`);
gesetzt wird ueber `reaktion/setzen` (gleiche Zugriffspruefung wie beim Gefaellt-mir-Link).

## Private Nachrichten (seit 1.1.0)

- Unter jeder Nachricht stehen "Antworten" und die Emoji-Auswahl. Reaktionen als Chips, wie bei Beitraegen
  (gleiche Tabelle, `object_model` = Nachrichten-Eintrag). Nur Teilnehmer der Unterhaltung duerfen reagieren;
  Teilnehmer, die in der Unterhaltung gesperrt sind, nicht. Keine Benachrichtigung, keine E-Mail.
- "Antworten" legt oberhalb des Schreibfelds eine Leiste mit Auszug der Originalnachricht an und haengt dem
  Formular das Feld `nexusAntwortAuf` an. Beim Speichern der neuen Nachricht merkt `Events::onNachrichtGespeichert`
  den Bezug (Tabelle `nexus_nachricht_antwort`) -- nur, wenn beide Nachrichten in derselben Unterhaltung liegen.
- Die Nachricht zeigt dann oben einen Auszug der Originalnachricht; Klick springt dorthin (falls geladen).
- Das Nachrichten-Modul (Marktplatz) wird NICHT veraendert: das Skript haengt sich an `.mail-conversation-entry`.
  Aendert ein Update dieses Markup, verschwinden die Zusaetze still, die Nachrichten selbst bleiben unberuehrt.
