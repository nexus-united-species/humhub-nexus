# nexus-kalender

Zeigt einen fertig gestalteten Kalender zum Umblättern: eine einzige HTML-Datei, in der Bilder, Schriften und Code
stecken. Lesen dürfen alle Angemeldeten; die Datei liegt außerhalb des öffentlichen Bereichs.

## Einrichten

1. Ordner anlegen (Vorgabe `/data/nexus-kalender`, muss für den Webserver lesbar sein), darin `index.html` und
   optional `deckblatt.jpg`.
2. Modul einschalten.
3. Einstellungen unter `'modules' => ['nexus-kalender' => [...]]`:
   - `kreise`: Kreise (Space-IDs) mit Menüpunkt
   - `jahr`: Jahr im Titel
   - `ordner`: anderer Ablageort
   - `entwurf`: Vorgabe `true`: Hinweis „Entwurf“ und kein Teilen in Kreise
   - `bestellLink`: Adresse für den Knopf „Bestellen“

Neue Fassung: `index.html` ersetzen. Die Adresse bleibt, die neue Fassung kommt beim nächsten Öffnen an.

Die Sicherheitsregeln der Kalender-Datei stehen in `KalenderController::CSP` und gelten nur für diese Antwort. Die Datei
muss ohne Inhalte von außen auskommen.
