# nexus-karte

🇩🇪 [Deutsch](#deutsch) · 🇬🇧 [English](#english)

## Deutsch

**Gemeinschaftskarte:** Eine Landkarte zeigt alle Kreise (Spaces), die ein Admin als Gemeinschaft markiert hat, mit Ort und Link zum Kreis. Mitglieder, die noch eine Gemeinschaft in ihrer Nähe suchen, können sich als „Suchende“ eintragen.

- Nur Ort/Region, nie eine Adresse: Die Ortssuche (OpenStreetMap/Nominatim) nimmt nur Orte und Regionen an, Koordinaten werden auf ~1 km gerundet, die Karte zoomt nicht näher heran.
- Kartenbilder laufen über den eigenen Server – fremde Dienste sehen nicht, wer die Karte ansieht.
- Optional: eine öffentliche Datenquelle (`/nexus-karte/karte/daten`), mit der die eigene Webseite die Karte zeigen kann.

**Voraussetzungen:** HumHub 1.18+, ausgehende Verbindung zu `tile.openstreetmap.org` und `nominatim.openstreetmap.org`. Optional: Wiki-Modul (Knopf „Gemeinschaft gründen“).

**Einstellungen** (`protected/config/common.php`, alle optional):

```php
'modules' => [
    'nexus-karte' => [
        'webseiten' => ['https://example.org'],          // eigene Webseiten: Kartenbilder + Datenquelle
        'registrierenUrl' => 'https://example.org/join', // Ziel "Gemeinschaft gründen" für Gäste (Standard: Anmeldeseite)
        'wikiGruenden' => 68,                            // ID der Wiki-Seite mit der Anleitung zum Gründen
        'absender' => 'MeineKarte/1.0 (+https://example.org)', // Absenderangabe für OpenStreetMap
    ],
],
```

## English

**Community map:** a map showing every circle (space) that an admin has marked as a community, with its location and a link. Members still looking for a community nearby can add themselves as "seekers".

- Location/region only, never an address: the place search (OpenStreetMap/Nominatim) accepts only places and regions, coordinates are rounded to ~1 km, and the map does not zoom in further.
- Map tiles are proxied through your own server, so third parties never see who views the map.
- Optional: a public data source (`/nexus-karte/karte/daten`) so your website can show the map.

**Requirements:** HumHub 1.18+, outgoing access to `tile.openstreetmap.org` and `nominatim.openstreetmap.org`. Optional: wiki module (button "Start a community").

**Settings** (`protected/config/common.php`, all optional): see the example above – `webseiten` (your websites allowed to use tiles and data), `registrierenUrl` (target of "start a community" for guests, default: login page), `wikiGruenden` (wiki page ID with the how-to), `absender` (user agent sent to OpenStreetMap).

The user interface is available in German, English and Spanish.
