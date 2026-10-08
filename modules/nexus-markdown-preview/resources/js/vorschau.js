/**
 * Fuegt neben jedem Link auf eine .md-Datei einen kleinen "Vorschau"-Knopf
 * ein, der die Datei formatiert anzeigt statt nur zum Download anzubieten.
 *
 * Erkennung ueber den Linkziel-Pfad (/file/file/download?guid=...), NICHT
 * ueber eine bestimmte umgebende Container-Struktur: HumHub verlinkt
 * Dateien an mehreren, strukturell verschiedenen Stellen ueber immer
 * dieselbe Hilfsfunktion FileHelper::createLink() -> File::getUrl()
 * (siehe file/libs/FileHelper.php, file/models/File.php) --
 *   - generisches FilePreview-Widget (Beitrags-Anhaenge, private
 *     Nachrichten): humhub.file.js, Preview.template.file
 *   - Kreis-"Dateien" (cfiles-Modul, eigenstaendiges Marktplatz-Modul,
 *     nicht Kern): widgets/views/fileSystemItem.php
 *   - cfiles-Beitrag im Stream: widgets/views/wallEntryFile.php
 * Ein erster Anlauf, der nur die generische FilePreview-Struktur abgesucht
 * hat, griff deshalb im Kreis-"Dateien"-Reiter (cfiles) gar nicht -- Josh'
 * Meldung 15.09.2026, Kreis 3, Verzeichnis AETHER. Ueber den Linkziel-Pfad
 * zu gehen deckt alle drei Stellen gleichzeitig ab, unabhaengig von der
 * jeweiligen umgebenden Markup-Struktur, und bleibt auch richtig, falls
 * HumHub oder cfiles ihr Markup kuenftig aendern.
 */
(function ($) {
    'use strict';

    var MD_ENDUNG = /\.md$/i;
    var DOWNLOAD_ROUTE = '/file/file/download';
    var VORSCHAU_ROUTE = '/nexus-markdown-preview/preview/render';

    function guidAusHref(href) {
        var treffer = href && href.match(/[?&]guid=([^&]+)/);
        return treffer ? decodeURIComponent(treffer[1]) : null;
    }

    function vorschauLinkErzeugen(guid) {
        return $('<a>', {
            'class': 'nexus-md-preview-link',
            href: VORSCHAU_ROUTE + '?guid=' + encodeURIComponent(guid),
            target: '_blank',
            title: 'Formatierte Vorschau anzeigen',
            css: {marginLeft: '8px', whiteSpace: 'nowrap'}
        }).html('<i class="fa fa-eye"></i> Vorschau');
    }

    function verarbeiten() {
        $('a[href*="' + DOWNLOAD_ROUTE + '"]').not('[data-nexus-md-checked]').each(function () {
            var $link = $(this).attr('data-nexus-md-checked', '1');
            var name = $.trim($link.text());
            var guid = guidAusHref($link.attr('href'));

            if (!guid || !name || !MD_ENDUNG.test(name)) {
                return;
            }

            $link.after(vorschauLinkErzeugen(guid));
        });
    }

    $(function () {
        verarbeiten();

        var timer = null;
        var beobachter = new MutationObserver(function () {
            clearTimeout(timer);
            timer = setTimeout(verarbeiten, 150);
        });

        beobachter.observe(document.body, {childList: true, subtree: true});
    });
})(jQuery);
