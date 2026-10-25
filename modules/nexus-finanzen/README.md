# nexus-finanzen

🇩🇪 [Deutsch](#deutsch) · 🇬🇧 [English](#english)

## Deutsch

**Finanzierungs-Ampel für Gemeinschaften:** zeigt allen Mitgliedern, was die Gemeinschaft jeden Monat braucht, welche einmaligen Entwicklungsschritte finanziert werden sollen – und was davon schon gedeckt ist.

- **Drei Töpfe, nie vermischt:** Grundbetrieb (laufend, jeden Monat neu), optionale Dienste (laufend, nur wenn gemeinsam getragen) und Entwicklungsfonds (einmalig, Summe der freigegebenen Kacheln gedeckelt).
- **Ampel je Kachel:** grau = geplant / zu prüfen, rot = offen, gelb = teilweise gedeckt, grün = gedeckt (nicht: bezahlt). Dazu Einordnung (bestätigt, geschätzt, Planung, Rückstellung, Reserve) und Zusatz (in Umsetzung, bezahlt, abgeschlossen, pausiert). Gruppen fassen Kacheln nur für die Anzeige zusammen.
- **Eingänge:** Ko-fi automatisch (über `nexus-supporter-bridge`, jede Zahlung genau einmal; Ko-fi-Zahlungen nicht zusätzlich als Stripe erfassen), alles andere von Hand. Frei → Topf, zweckgebunden → sofort in die Kachel.
- **Verteilung:** Das System schlägt nach Rang vor (je Kachel höchstens, was fehlt), eine finanzverantwortliche Person bestätigt. **Vier-Augen-Prinzip:** Geht Geld an eine Kachel, aus der die bestätigende Person vergütet wird, muss eine zweite Person freigeben – ebenso bei Auszahlungen aus Vergütungs-Kacheln.
- **Überschuss** einer laufenden Kachel geht in den nächsten Monat derselben Kachel; freies Geld bleibt im Topf. Einmaliges Geld verfällt nie.
- Eingegangen, reserviert, ausgegeben und frei werden immer getrennt gezeigt. Monatsrückblick in einfachen Worten.
- **Öffentliche Zahlen** ohne Namen, Referenzen oder Notizen: `GET /nexus-finanzen/oeffentlich/stand?monat=YYYY-MM` (JSON, Beträge in Cent), im Browser nur für die eingestellten Webseiten (CORS).

**Einrichten:** Modul einschalten, dann als Systemadmin unter Finanzierung → Verwalten → Einstellungen: Finanzverantwortliche (Benutzernamen), Webseiten, Deckel des Entwicklungsfonds, Link „Mithelfen“. Private Nachrichten an die Verantwortlichen über `nexus-arbeitszeit` (sonst entfallen nur diese).

## English

**Funding traffic light for communities:** shows all members what the community needs every month, which one-off development steps should be funded – and how much is already covered.

- **Three pots, never mixed:** basic operation (recurring), optional services (recurring, only if carried together) and a development fund (one-off, capped).
- **Traffic light per tile:** grey = planned / to be checked, red = open, yellow = partly covered, green = covered (not: paid).
- **Income:** Ko-fi automatically (via `nexus-supporter-bridge`), everything else by hand; free money goes to the pot, earmarked money straight to its tile.
- **Distribution:** proposed by rank, confirmed by a finance officer. **Four-eyes rule:** money for a tile that pays the confirming person, and payouts from such tiles, need a second person.
- **Public figures** without names: `GET /nexus-finanzen/oeffentlich/stand` (JSON, amounts in cents), CORS only for configured websites.

**Setup:** enable the module, then as system admin under Funding → Manage → Settings.
