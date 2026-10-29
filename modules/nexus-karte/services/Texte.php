<?php

namespace nexus\modules\karte\services;

use Yii;

/**
 * Oberflaechentexte in Deutsch, Englisch, Spanisch und Russisch -- gleiche kleine Tabelle wie im
 * Arbeitsstunden-Modul (drei Sprachen, ein Ort). Fehlt eine Sprache, gilt Englisch.
 */
class Texte
{
    private const T = [
        'nav' => ['de' => 'Karte', 'en' => 'Map', 'es' => 'Mapa'],
        'titel' => ['de' => 'Gemeinschaften in deiner Nähe', 'en' => 'Communities near you', 'es' => 'Comunidades cerca de ti'],
        'einleitung' => [
            'de' => 'Hier siehst du, wo es schon N.E.X.U.S.-Gemeinschaften gibt. Tippe auf einen Punkt, um mehr zu erfahren und den Kreis der Gemeinschaft zu öffnen.',
            'en' => 'Here you can see where N.E.X.U.S. communities already exist. Tap a marker to learn more and open the community’s circle.',
            'es' => 'Aquí ves dónde ya existen comunidades N.E.X.U.S. Toca un punto para saber más y abrir el círculo de la comunidad.'],
        'ort_hinweis' => [
            'de' => 'Die Punkte zeigen nur den Ort oder die Region – nie eine Adresse.',
            'en' => 'Markers only show the town or region – never an address.',
            'es' => 'Los puntos solo muestran la localidad o la región, nunca una dirección.'],
        'liste' => ['de' => 'Alle Gemeinschaften', 'en' => 'All communities', 'es' => 'Todas las comunidades'],
        'zum_kreis' => ['de' => 'Zum Kreis', 'en' => 'Open circle', 'es' => 'Abrir círculo'],
        'keine' => [
            'de' => 'Noch ist keine Gemeinschaft auf der Karte eingetragen.',
            'en' => 'No community has been added to the map yet.',
            'es' => 'Aún no hay ninguna comunidad en el mapa.'],
        'gruenden_titel' => ['de' => 'Noch keine in deiner Nähe?', 'en' => 'None near you yet?', 'es' => '¿Aún no hay ninguna cerca de ti?'],
        'gruenden_text' => [
            'de' => 'Dann gründe deine eigene Gemeinschaft. Die Anleitung zeigt dir den Weg – vom ersten Kontakt bis zur Gemeinschaft vor Ort.',
            'en' => 'Then start your own community. The guide shows you the way – from the first contact to a community on the ground.',
            'es' => 'Entonces funda tu propia comunidad. La guía te muestra el camino, desde el primer contacto hasta la comunidad local.'],
        'gruenden_knopf' => ['de' => '🌱 Deine Gemeinschaft gründen', 'en' => '🌱 Start your community', 'es' => '🌱 Funda tu comunidad'],
        'hinweis_kreise' => [
            'de' => 'Gibt es schon eine Gemeinschaft in deiner Nähe?',
            'en' => 'Is there already a community near you?',
            'es' => '¿Ya hay una comunidad cerca de ti?'],
        'hinweis_link' => ['de' => 'Zur Karte der Gemeinschaften →', 'en' => 'Open the community map →', 'es' => 'Ver el mapa de comunidades →'],
        'karte_quelle' => ['de' => 'Kartendaten', 'en' => 'Map data', 'es' => 'Datos del mapa'],
        // Knoepfe unter der Karte auf www.nexus-terminal.org (Datenquelle /nexus-karte/karte/daten, 07.10.2026)
        'web_finden' => ['de' => 'Gemeinschaft in deiner Nähe finden', 'en' => 'Find a community near you', 'es' => 'Encuentra una comunidad cerca de ti'],
        'web_gruenden' => ['de' => 'Eigene Gemeinschaft gründen', 'en' => 'Start your own community', 'es' => 'Funda tu propia comunidad'],

        'gast_titel' => ['de' => 'Hier gibt es schon Gemeinschaften', 'en' => 'Communities already exist here', 'es' => 'Aquí ya existen comunidades'],
        'gast_text' => [
            'de' => 'Vielleicht ist schon eine in deiner Nähe. Registriere dich, um sie kennenzulernen – oder gründe deine eigene.',
            'en' => 'There may already be one near you. Register to get to know it – or start your own.',
            'es' => 'Quizá ya haya una cerca de ti. Regístrate para conocerla, o funda la tuya.'],
        'gast_popup' => [
            'de' => 'Nach der Anmeldung kannst du diese Gemeinschaft besuchen.',
            'en' => 'After signing in you can visit this community.',
            'es' => 'Tras iniciar sesión podrás visitar esta comunidad.'],
        'legende_gemeinschaft' => ['de' => 'Gemeinschaft', 'en' => 'Community', 'es' => 'Comunidad'],
        'legende_sucht' => ['de' => 'Sucht Mitwirkende', 'en' => 'Looking for fellow members', 'es' => 'Busca compañeros'],
        'sucht_titel' => ['de' => 'Sucht Mitwirkende für eine Gemeinschaft', 'en' => 'Looking for people to start a community', 'es' => 'Busca compañeros para una comunidad'],
        'sucht_liste' => ['de' => 'Diese Menschen suchen Mitwirkende', 'en' => 'These people are looking for fellow members', 'es' => 'Estas personas buscan compañeros'],
        'zum_profil' => ['de' => 'Zum Profil', 'en' => 'Open profile', 'es' => 'Ver perfil'],
        'sucht_box_titel' => ['de' => 'Du suchst Mitwirkende in deiner Region?', 'en' => 'Looking for people in your region?', 'es' => '¿Buscas compañeros en tu región?'],
        'sucht_box_text' => [
            'de' => 'Setz dich selbst auf die Karte. So sehen andere Mitglieder, dass in deiner Gegend jemand eine Gemeinschaft aufbauen möchte – und können sich bei dir melden.',
            'en' => 'Put yourself on the map. Other members will see that someone in your area wants to build a community – and can get in touch with you.',
            'es' => 'Ponte en el mapa. Así otros miembros verán que en tu zona alguien quiere crear una comunidad y podrán escribirte.'],
        'sucht_box_knopf' => ['de' => 'Mich auf die Karte setzen', 'en' => 'Put me on the map', 'es' => 'Ponerme en el mapa'],
        'sucht_box_aendern' => ['de' => 'Meinen Eintrag ändern', 'en' => 'Edit my entry', 'es' => 'Editar mi entrada'],
        's_titel' => ['de' => 'Ich suche Mitwirkende', 'en' => 'I am looking for fellow members', 'es' => 'Busco compañeros'],
        's_einleitung' => [
            'de' => 'Du möchtest in deiner Gegend eine Gemeinschaft aufbauen und suchst andere, die mitmachen? Trag hier deinen Ort ein – dann erscheinst du auf der Karte.',
            'en' => 'Want to build a community in your area and looking for others to join? Enter your town here – you will then appear on the map.',
            'es' => '¿Quieres crear una comunidad en tu zona y buscas a otras personas? Indica aquí tu localidad y aparecerás en el mapa.'],
        's_sichtbar_titel' => ['de' => 'Wer sieht das?', 'en' => 'Who can see this?', 'es' => '¿Quién lo ve?'],
        's_sichtbar' => [
            'de' => 'Alle angemeldeten Mitglieder sehen deinen Namen, den Ort und deinen Text. Besucher ohne Anmeldung sehen dich nicht. Der Punkt zeigt nur die Ortsmitte, nie deine Adresse. Du kannst den Eintrag jederzeit löschen.',
            'en' => 'All signed-in members see your name, the town and your text. Visitors who are not signed in do not see you. The marker only shows the town centre, never your address. You can delete the entry at any time.',
            'es' => 'Todos los miembros que han iniciado sesión ven tu nombre, la localidad y tu texto. Los visitantes sin sesión no te ven. El punto solo marca el centro de la localidad, nunca tu dirección. Puedes borrar la entrada cuando quieras.'],
        's_mein' => ['de' => 'Mein Eintrag', 'en' => 'My entry', 'es' => 'Mi entrada'],
        's_neu' => ['de' => 'Wo suchst du?', 'en' => 'Where are you looking?', 'es' => '¿Dónde buscas?'],
        's_ort_aendern' => ['de' => 'Ort ändern', 'en' => 'Change location', 'es' => 'Cambiar ubicación'],
        's_ort_hilfe' => ['de' => 'Nur Ort oder Region, z. B. „Rosenheim“ – keine Straße.', 'en' => 'Town or region only, e.g. “Rosenheim” – no street.', 'es' => 'Solo localidad o región, p. ej. «Rosenheim», sin calle.'],
        's_nachricht' => ['de' => 'Ein paar Worte dazu (freiwillig)', 'en' => 'A few words (optional)', 'es' => 'Unas palabras (opcional)'],
        's_nachricht_hilfe' => ['de' => 'Zum Beispiel: „Suche zwei, drei Leute im Raum Rosenheim für regelmäßige Treffen.“', 'en' => 'For example: “Looking for two or three people around Rosenheim for regular meetings.”', 'es' => 'Por ejemplo: «Busco a dos o tres personas en la zona de Rosenheim para reunirnos con regularidad.»'],
        's_text_speichern' => ['de' => 'Text speichern', 'en' => 'Save text', 'es' => 'Guardar texto'],
        's_eintragen' => ['de' => 'Mit diesem Ort eintragen', 'en' => 'Save with this place', 'es' => 'Guardar con este lugar'],
        's_gespeichert' => ['de' => 'Gespeichert – du stehst jetzt auf der Karte.', 'en' => 'Saved – you are now on the map.', 'es' => 'Guardado: ya apareces en el mapa.'],
        's_fehler' => ['de' => 'Das hat nicht geklappt. Bitte suche den Ort noch einmal (Text höchstens 300 Zeichen).', 'en' => 'That did not work. Please search for the place again (text max. 300 characters).', 'es' => 'No ha funcionado. Busca el lugar de nuevo (texto de 300 caracteres como máximo).'],
        's_loeschen' => ['de' => 'Meinen Eintrag löschen', 'en' => 'Delete my entry', 'es' => 'Borrar mi entrada'],
        's_loeschen_frage' => ['de' => 'Deinen Eintrag wirklich von der Karte nehmen?', 'en' => 'Really remove your entry from the map?', 'es' => '¿Quitar tu entrada del mapa?'],
        's_geloescht' => ['de' => 'Dein Eintrag ist gelöscht.', 'en' => 'Your entry has been deleted.', 'es' => 'Tu entrada se ha borrado.'],
        'menue_standort' => ['de' => 'Standort (Karte)', 'en' => 'Location (map)', 'es' => 'Ubicación (mapa)'],
        'verwalten' => ['de' => '📍 Standorte verwalten', 'en' => '📍 Manage locations', 'es' => '📍 Gestionar ubicaciones'],
        'v_titel' => ['de' => 'Standorte der Gemeinschaften', 'en' => 'Community locations', 'es' => 'Ubicaciones de las comunidades'],
        'v_einleitung' => [
            'de' => 'Trage hier den Ort deiner Gemeinschaft ein. Bitte nur den Ort oder die Region (z. B. „Traunstein“ oder „Teneriffa“) – keine Straße und keine Hausnummer. Der Punkt landet in der Ortsmitte.',
            'en' => 'Enter the location of your community here. Please only the town or region (e.g. “Traunstein” or “Tenerife”) – no street, no house number. The marker is placed at the town centre.',
            'es' => 'Indica aquí la ubicación de tu comunidad. Solo la localidad o la región (p. ej. «Traunstein» o «Tenerife»), sin calle ni número. El punto se coloca en el centro de la localidad.'],
        'v_aktuell' => ['de' => 'Aktueller Ort', 'en' => 'Current location', 'es' => 'Ubicación actual'],
        'v_kein_ort' => ['de' => 'noch kein Ort – erscheint nicht auf der Karte', 'en' => 'no location yet – not shown on the map', 'es' => 'sin ubicación: no aparece en el mapa'],
        'v_suchfeld' => ['de' => 'Ort oder Region', 'en' => 'Town or region', 'es' => 'Localidad o región'],
        'v_suchen' => ['de' => 'Ort suchen', 'en' => 'Search', 'es' => 'Buscar'],
        'v_treffer' => ['de' => 'Welcher Ort ist gemeint?', 'en' => 'Which place do you mean?', 'es' => '¿Qué lugar es?'],
        'v_uebernehmen' => ['de' => 'Diesen Ort eintragen', 'en' => 'Use this place', 'es' => 'Usar este lugar'],
        'v_anzeigename' => ['de' => 'So steht der Ort auf der Karte (änderbar)', 'en' => 'Name shown on the map (editable)', 'es' => 'Nombre que aparece en el mapa (editable)'],
        'v_nichts' => [
            'de' => 'Dazu wurde kein Ort gefunden. Bitte nur einen Ort oder eine Region eingeben – keine Straße.',
            'en' => 'No place found. Please enter only a town or region – no street.',
            'es' => 'No se encontró ningún lugar. Indica solo una localidad o región, sin calle.'],
        'v_suche_fehler' => [
            'de' => 'Die Ortssuche ist gerade nicht erreichbar. Bitte versuche es in ein paar Minuten noch einmal.',
            'en' => 'The place search is not available right now. Please try again in a few minutes.',
            'es' => 'La búsqueda no está disponible ahora. Inténtalo de nuevo en unos minutos.'],
        'v_gespeichert' => ['de' => 'Der Ort ist eingetragen.', 'en' => 'Location saved.', 'es' => 'Ubicación guardada.'],
        'v_fehler' => ['de' => 'Das hat nicht geklappt. Bitte suche den Ort noch einmal.', 'en' => 'That did not work. Please search for the place again.', 'es' => 'No ha funcionado. Busca el lugar de nuevo.'],
        'v_ort_entfernen' => ['de' => 'Ort entfernen', 'en' => 'Remove location', 'es' => 'Quitar ubicación'],
        'v_ort_entfernt' => ['de' => 'Der Ort ist entfernt – die Gemeinschaft steht nicht mehr auf der Karte.', 'en' => 'Location removed – the community is no longer on the map.', 'es' => 'Ubicación eliminada: la comunidad ya no aparece en el mapa.'],
        'v_zur_karte' => ['de' => '← Zur Karte', 'en' => '← Back to the map', 'es' => '← Volver al mapa'],
        // Nur Systemadmins sehen diesen Teil -- deshalb nur Deutsch.
        'a_titel' => ['de' => 'Welche Kreise sind Gemeinschaften? (nur Admins)'],
        'a_text' => ['de' => 'Nur ein Kreis, der hier als Gemeinschaft markiert ist, kann einen Ort bekommen und auf der Karte erscheinen. Arbeitskreise und Themenkreise bleiben unmarkiert.'],
        'a_neu' => ['de' => 'Dieser Kreis ist noch keine Gemeinschaft. Sobald du hier einen Ort einträgst, gilt er als Gemeinschaft und erscheint auf der Karte. Bei Arbeits- und Themenkreisen einfach nichts eintragen.'],
        'a_markieren' => ['de' => 'Als Gemeinschaft markieren'],
        'a_entfernen' => ['de' => 'Ist keine Gemeinschaft mehr'],
        'a_entfernen_frage' => ['de' => 'Markierung und Ort wirklich entfernen?'],
        'a_markiert' => ['de' => 'Der Kreis ist jetzt als Gemeinschaft markiert. Trage oben den Ort ein.'],
        'a_entfernt' => ['de' => 'Die Markierung ist entfernt.'],
    ];

    /** Russisch (seit 29.10.2026): eigene Tabelle, damit die drei anderen Sprachen unberuehrt bleiben. */
    private const RU = [
        'nav' => 'Карта',
        'titel' => 'Сообщества рядом с вами',
        'einleitung' => 'Здесь вы видите, где уже существуют сообщества N.E.X.U.S. Нажмите на точку, чтобы узнать больше и открыть круг сообщества.',
        'ort_hinweis' => 'Точки показывают только населённый пункт или регион — никогда адрес.',
        'liste' => 'Все сообщества',
        'zum_kreis' => 'Открыть круг',
        'keine' => 'На карте пока нет ни одного сообщества.',
        'gruenden_titel' => 'Рядом с вами пока нет сообщества?',
        'gruenden_text' => 'Тогда создайте своё сообщество. Руководство покажет вам путь — от первого контакта до сообщества на местах.',
        'gruenden_knopf' => '🌱 Создайте своё сообщество',
        'hinweis_kreise' => 'Есть ли уже сообщество рядом с вами?',
        'hinweis_link' => 'К карте сообществ →',
        'karte_quelle' => 'Данные карты',
        'web_finden' => 'Найти сообщество рядом с вами',
        'web_gruenden' => 'Создать собственное сообщество',
        'gast_titel' => 'Здесь уже есть сообщества',
        'gast_text' => 'Возможно, одно из них уже рядом с вами. Зарегистрируйтесь, чтобы познакомиться с ним, или создайте своё.',
        'gast_popup' => 'После входа вы сможете посетить это сообщество.',
        'legende_gemeinschaft' => 'Сообщество',
        'legende_sucht' => 'Ищет единомышленников',
        'sucht_titel' => 'Ищут единомышленников для сообщества',
        'sucht_liste' => 'Эти люди ищут единомышленников',
        'zum_profil' => 'Открыть профиль',
        'sucht_box_titel' => 'Ищете единомышленников в своём регионе?',
        'sucht_box_text' => 'Отметьте себя на карте. Так другие участники увидят, что в вашем районе кто-то хочет создать сообщество, и смогут с вами связаться.',
        'sucht_box_knopf' => 'Отметить меня на карте',
        'sucht_box_aendern' => 'Изменить мою запись',
        's_titel' => 'Я ищу единомышленников',
        's_einleitung' => 'Хотите создать сообщество в своём районе и ищете тех, кто присоединится? Укажите здесь свой населённый пункт — и вы появитесь на карте.',
        's_sichtbar_titel' => 'Кто это видит?',
        's_sichtbar' => 'Все вошедшие участники видят ваше имя, населённый пункт и ваш текст. Посетители без входа вас не видят. Точка показывает только центр населённого пункта, никогда ваш адрес. Вы можете удалить запись в любой момент.',
        's_mein' => 'Моя запись',
        's_neu' => 'Где вы ищете?',
        's_ort_aendern' => 'Изменить место',
        's_ort_hilfe' => 'Только населённый пункт или регион, например «Розенхайм» — без улицы.',
        's_nachricht' => 'Несколько слов (по желанию)',
        's_nachricht_hilfe' => 'Например: «Ищу двух-трёх человек в районе Розенхайма для регулярных встреч».',
        's_text_speichern' => 'Сохранить текст',
        's_eintragen' => 'Сохранить с этим местом',
        's_gespeichert' => 'Сохранено — теперь вы на карте.',
        's_fehler' => 'Не получилось. Пожалуйста, найдите место ещё раз (текст — не более 300 символов).',
        's_loeschen' => 'Удалить мою запись',
        's_loeschen_frage' => 'Действительно убрать вашу запись с карты?',
        's_geloescht' => 'Ваша запись удалена.',
        'menue_standort' => 'Местоположение (карта)',
        'verwalten' => '📍 Управление местами',
        'v_titel' => 'Места сообществ',
        'v_einleitung' => 'Укажите здесь место вашего сообщества. Пожалуйста, только населённый пункт или регион (например, «Траунштайн» или «Тенерифе») — без улицы и номера дома. Точка ставится в центре населённого пункта.',
        'v_aktuell' => 'Текущее место',
        'v_kein_ort' => 'места пока нет — на карте не отображается',
        'v_suchfeld' => 'Населённый пункт или регион',
        'v_suchen' => 'Найти место',
        'v_treffer' => 'Какое место вы имеете в виду?',
        'v_uebernehmen' => 'Выбрать это место',
        'v_anzeigename' => 'Так место отображается на карте (можно изменить)',
        'v_nichts' => 'Место не найдено. Пожалуйста, введите только населённый пункт или регион — без улицы.',
        'v_suche_fehler' => 'Поиск мест сейчас недоступен. Пожалуйста, попробуйте снова через несколько минут.',
        'v_gespeichert' => 'Место сохранено.',
        'v_fehler' => 'Не получилось. Пожалуйста, найдите место ещё раз.',
        'v_ort_entfernen' => 'Убрать место',
        'v_ort_entfernt' => 'Место убрано — сообщество больше не отображается на карте.',
        'v_zur_karte' => '← К карте',
    ];

    /** Französisch (seit 29.10.2026): eigene Tabelle, die anderen Sprachen bleiben unberührt. */
    private const FR = [
        'nav' => 'Carte',
        'titel' => 'Des communautés près de chez vous',
        'einleitung' => 'Ici, vous voyez où il existe déjà des communautés N.E.X.U.S. Touchez un point pour en savoir plus et ouvrir le cercle de la communauté.',
        'ort_hinweis' => 'Les points n’indiquent que la localité ou la région – jamais une adresse.',
        'liste' => 'Toutes les communautés',
        'zum_kreis' => 'Ouvrir le cercle',
        'keine' => 'Aucune communauté n’est encore enregistrée sur la carte.',
        'gruenden_titel' => 'Pas encore de communauté près de chez vous ?',
        'gruenden_text' => 'Alors fondez votre propre communauté. Le guide vous indique le chemin – du premier contact jusqu’à la communauté sur place.',
        'gruenden_knopf' => '🌱 Fonder votre communauté',
        'hinweis_kreise' => 'Y a-t-il déjà une communauté près de chez vous ?',
        'hinweis_link' => 'Voir la carte des communautés →',
        'karte_quelle' => 'Données cartographiques',
        'web_finden' => 'Trouver une communauté près de chez vous',
        'web_gruenden' => 'Fonder votre propre communauté',
        'gast_titel' => 'Il existe déjà des communautés ici',
        'gast_text' => 'Il y en a peut-être déjà une près de chez vous. Inscrivez-vous pour la découvrir – ou fondez la vôtre.',
        'gast_popup' => 'Après vous être connecté(e), vous pourrez rendre visite à cette communauté.',
        'legende_gemeinschaft' => 'Communauté',
        'legende_sucht' => 'Cherche des personnes pour s’engager',
        'sucht_titel' => 'Cherche des personnes pour fonder une communauté',
        'sucht_liste' => 'Ces personnes cherchent des compagnons d’engagement',
        'zum_profil' => 'Voir le profil',
        'sucht_box_titel' => 'Vous cherchez des personnes dans votre région ?',
        'sucht_box_text' => 'Placez-vous sur la carte. Les autres membres verront que quelqu’un dans votre région souhaite fonder une communauté – et pourront vous contacter.',
        'sucht_box_knopf' => 'Me placer sur la carte',
        'sucht_box_aendern' => 'Modifier mon entrée',
        's_titel' => 'Je cherche des personnes pour s’engager',
        's_einleitung' => 'Vous souhaitez fonder une communauté dans votre région et vous cherchez d’autres personnes pour vous accompagner ? Indiquez ici votre localité – vous apparaîtrez sur la carte.',
        's_sichtbar_titel' => 'Qui peut le voir ?',
        's_sichtbar' => 'Tous les membres connectés voient votre nom, la localité et votre texte. Les visiteurs non connectés ne vous voient pas. Le point n’indique que le centre de la localité, jamais votre adresse. Vous pouvez supprimer l’entrée à tout moment.',
        's_mein' => 'Mon entrée',
        's_neu' => 'Où cherchez-vous ?',
        's_ort_aendern' => 'Changer de lieu',
        's_ort_hilfe' => 'Uniquement la localité ou la région, p. ex. « Rosenheim » – pas de rue.',
        's_nachricht' => 'Quelques mots (facultatif)',
        's_nachricht_hilfe' => 'Par exemple : « Je cherche deux ou trois personnes dans la région de Rosenheim pour des rencontres régulières. »',
        's_text_speichern' => 'Enregistrer le texte',
        's_eintragen' => 'Enregistrer avec ce lieu',
        's_gespeichert' => 'Enregistré – vous apparaissez maintenant sur la carte.',
        's_fehler' => 'Ça n’a pas fonctionné. Cherchez à nouveau le lieu (texte : 300 caractères au maximum).',
        's_loeschen' => 'Supprimer mon entrée',
        's_loeschen_frage' => 'Vraiment retirer votre entrée de la carte ?',
        's_geloescht' => 'Votre entrée a été supprimée.',
        'menue_standort' => 'Emplacement (carte)',
        'verwalten' => '📍 Gérer les emplacements',
        'v_titel' => 'Emplacements des communautés',
        'v_einleitung' => 'Indiquez ici le lieu de votre communauté. Uniquement la localité ou la région (p. ex. « Traunstein » ou « Tenerife ») – pas de rue ni de numéro. Le point est placé au centre de la localité.',
        'v_aktuell' => 'Lieu actuel',
        'v_kein_ort' => 'pas encore de lieu – n’apparaît pas sur la carte',
        'v_suchfeld' => 'Localité ou région',
        'v_suchen' => 'Chercher le lieu',
        'v_treffer' => 'De quel lieu s’agit-il ?',
        'v_uebernehmen' => 'Utiliser ce lieu',
        'v_anzeigename' => 'Nom affiché sur la carte (modifiable)',
        'v_nichts' => 'Aucun lieu trouvé. Indiquez uniquement une localité ou une région – pas de rue.',
        'v_suche_fehler' => 'La recherche de lieux est momentanément indisponible. Réessayez dans quelques minutes.',
        'v_gespeichert' => 'Le lieu a été enregistré.',
        'v_fehler' => 'Ça n’a pas fonctionné. Cherchez à nouveau le lieu.',
        'v_ort_entfernen' => 'Retirer le lieu',
        'v_ort_entfernt' => 'Lieu retiré – la communauté n’apparaît plus sur la carte.',
        'v_zur_karte' => '← Retour à la carte',
    ];

    public static function sprache(): string
    {
        $code = strtolower(explode('-', (string)Yii::$app->language)[0]);
        return in_array($code, ['de', 'en', 'es', 'ru', 'fr'], true) ? $code : 'en';
    }

    public static function t(string $schluessel): string
    {
        $eintrag = self::T[$schluessel] ?? null;
        if ($eintrag === null) {
            return $schluessel;
        }
        $sprache = self::sprache();
        if ($sprache === 'ru' && isset(self::RU[$schluessel])) {
            return self::RU[$schluessel];
        }
        if ($sprache === 'fr' && isset(self::FR[$schluessel])) {
            return self::FR[$schluessel];
        }
        return $eintrag[$sprache] ?? $eintrag['en'] ?? $eintrag['de'];
    }
}
