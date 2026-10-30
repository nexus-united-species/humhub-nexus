<?php

namespace nexus\modules\projekte\services;

/**
 * Hilfetexte der Projekt-Tafeln (kleines i-Symbol, siehe Hilfe.php) in allen Sprachen.
 * Zeilenumbruch im Text = neue Zeile im Hilfefenster.
 */
final class TexteHilfe
{
    public const TEXTE = [
        'de' => [
            'hilfe_label' => 'Hilfe',
            'hilfe_tafel_frage' => 'So funktioniert die Tafel',
            'hilfe_tafel' => 'Jede Kachel ist ein Arbeitsschritt und wandert von links nach rechts.
Offen – noch hat niemand angefangen.
In Arbeit – jemand kümmert sich darum.
Zur Abstimmung – fertig gedacht, jetzt ist die Meinung aller gefragt.
Fertig – erledigt.
Klicke auf eine Kachel, um ihren Inhalt zu sehen.',
            'hilfe_abstimmung_frage' => 'So wird hier abgestimmt',
            'hilfe_abstimmung_einfach' => '„Passt so“ heißt: Du bist einverstanden. „Ich habe eine Änderung“ heißt: Du würdest etwas anders machen – bitte schreib kurz, was und warum. Deine Meinung kannst du bis zum Ende der Runde noch ändern.',
            'hilfe_abstimmung_konsent' => 'Entschieden wird im Konsent: Angenommen ist der Vorschlag, wenn bis zum Ende der Runde niemand einen schwerwiegenden Einwand hat.
Ich stimme zu – einverstanden.
Zustimmung mit Bedenken – einverstanden, aber mit einer Sorge (bitte kurz beschreiben).
Schwerwiegender Einwand – das schadet unserem gemeinsamen Ziel. Es ist kein Nein für immer, sondern ein Hinweis, was noch fehlt (bitte begründen).
Ich enthalte mich – du legst dich nicht fest.',
            'hilfe_abstimmung_mehrheit' => 'Entschieden wird mit Mehrheit: Angenommen ist der Vorschlag, wenn es am Ende der Runde mehr Ja als Nein gibt. „Ich enthalte mich“ zählt nicht mit. Du kannst deine Stimme bis zum Ende der Runde noch ändern.',
            'hilfe_uebernehmen' => 'Du übernimmst diese Aufgabe. Dein Name steht dann auf der Kachel, und die Projektleitung bekommt eine Nachricht.',
            'hilfe_zur_abstimmung' => 'Die Kachel wandert in die Spalte „Zur Abstimmung“, und im Kreis erscheint ein Beitrag mit der Bitte um Rückmeldung. Wähle vorher, bis wann Rückmeldungen gesammelt werden. Vor dem Absenden wirst du noch einmal gefragt.',
            'hilfe_in_arbeit' => 'Die Kachel geht zurück nach „In Arbeit“ – zum Beispiel, wenn Änderungswünsche gekommen sind oder du sie aus Versehen zur Abstimmung gegeben hast.',
            'hilfe_als_fertig' => 'Die Aufgabe ist erledigt, die Kachel wandert nach „Fertig“. Das darf nur die Projektleitung.',
            'hilfe_wieder_oeffnen' => 'Die Kachel geht zurück nach „In Arbeit“, damit daran weitergearbeitet werden kann.',
            'hilfe_ziel' => 'Was soll am Ende herauskommen? Ein, zwei Sätze. Der Text steht oben auf der Tafel und erklärt allen, worum es geht.',
            'hilfe_leitung' => 'Die Projektleitung legt Kacheln an, weist Aufgaben zu, schiebt sie durch die Spalten und entscheidet über Vorschläge. Bis zu fünf Menschen können gemeinsam leiten.',
            'hilfe_projekt_frist' => 'Bis wann das Projekt fertig sein soll. Das Feld darf leer bleiben – dann steht „ohne Termin“.',
            'hilfe_regel' => 'So wird über Vorschläge entschieden:
Einfache Rückmeldung – „Passt so“ oder „Ich habe eine Änderung“; danach entscheidet die Leitung.
Konsent – angenommen, wenn bis zum Ende der Runde niemand einen schwerwiegenden Einwand hat.
Mehrheit – angenommen, wenn es mehr Ja als Nein gibt.
Unsicher? Nimm „Einfache Rückmeldung“. Du kannst es später ändern.',
            'hilfe_frist_tage' => 'Wie lange eine Abstimmungsrunde läuft, zum Beispiel 14 Tage. Zu kurze Fristen schließen Menschen aus, die nicht täglich ins Portal schauen.',
            'hilfe_weitergabe' => 'Freiwillig. Steht hier zum Beispiel „DAO“, gibt es zu jedem angenommenen Vorschlag eine Beschlussvorlage zum Weitergeben an dieses Gremium.',
            'hilfe_status' => '„Abgeschlossen“ legt das Projekt in die Liste der abgeschlossenen Projekte, damit die Übersicht des Kreises übersichtlich bleibt. Du kannst es später wieder aktivieren.',
            'hilfe_zusammenfassung' => 'Was soll sich ändern? Drei bis fünf Sätze, sodass man es auch ohne Anhang versteht.',
            'hilfe_begruendung' => 'Warum ist die Änderung nötig oder besser? Das hilft den anderen, sich ein Urteil zu bilden.',
            'hilfe_textaenderungen' => 'Für kleinere Änderungen: Nenne die Stelle (Dokument, Fassung, Kapitel), den bisherigen Text und den neuen Wortlaut. Das Portal zeigt den Unterschied farbig, und abgestimmt wird genau über diesen Wortlaut. Für größere Überarbeitungen lieber eine Datei hochladen.',
            'hilfe_dateien' => 'Für größere Überarbeitungen: Word (am besten mit „Änderungen nachverfolgen“), PDF, Markdown oder Bilder. Jedes Hochladen ist eine neue Fassung, ältere bleiben sichtbar. Ändert sich ein Vorschlag während der Abstimmung, beginnt eine neue Runde.',
            'hilfe_verantwortlich_kachel' => 'Wer sich um diese Aufgabe kümmert. Lässt du das Feld leer, steht auf der Kachel „Wer mag?“, und jedes Mitglied kann sie mit „Das übernehme ich“ übernehmen.',
            'hilfe_verantwortlich_vorschlag' => 'Wer den Vorschlag einbringt, ist automatisch dafür verantwortlich. Als Projektleitung kannst du das hier ändern.',
            'hilfe_hilft_mit' => 'Weitere Menschen, die mithelfen. Sie dürfen Teilschritte abhaken und Dateien hochladen.',
            'hilfe_frist' => 'Bis wann es erledigt sein soll. Einen Tag vorher bekommt die verantwortliche Person eine Erinnerung.',
            'hilfe_teilschritte' => 'Eine kleine Checkliste, ein Schritt pro Zeile. Die Häkchen sind nur eine Gedächtnisstütze – sie lösen nichts aus, auch keine Abstimmung.',
            'hilfe_unterlagen' => 'Links zu Dokumenten, ein Link pro Zeile in der Form „Titel | Adresse“. Dateien lädst du besser direkt hoch – dann sieht sie jeder sofort in der Kachel.',
            'hilfe_auswerten' => 'Hier hältst du als Leitung das Ergebnis der Runde fest.
Als angenommen festhalten – die Kachel wandert nach „Fertig“. Bei Konsent und Mehrheit gibt es diesen Knopf nur, wenn das Ergebnis es hergibt.
Als abgelehnt festhalten – die Kachel wandert ebenfalls nach „Fertig“.
Zurück zur Überarbeitung – zurück nach „In Arbeit“, der Vorschlag kann verbessert werden.
Beim Festhalten bekommen alle, die abgestimmt haben, eine Nachricht.',
            'hilfe_auswahl_sammeln' => 'Solange die Kachel „Offen“ oder „In Arbeit“ ist, kann jedes Kreismitglied Bild-Entwürfe einstellen. Sie bekommen die Buchstaben A, B, C … Zur Abstimmung braucht es mindestens einen Entwurf.',
            'hilfe_auswahl_abstimmen' => 'Unter jedem Entwurf wählst du:
Ich stimme zu – er gefällt dir.
Zustimmung mit Bedenken – geht, aber dir fällt etwas auf (bitte kurz schreiben).
Schwerwiegender Einwand – er geht für dich gar nicht (bitte kurz begründen).
Du musst nicht zu jedem etwas sagen und kannst deine Stimme bis zum Ende der Runde ändern.',
            'hilfe_auswahl_auswerten' => 'In Frage kommt nur ein Entwurf ohne schwerwiegenden Einwand und mit mindestens einer Zustimmung. Es gewinnt der mit den meisten Zustimmungen, bei Gleichstand wählt die Leitung.
„Entwurf … wählen“ macht die Kachel fertig, und alle Beteiligten bekommen eine Nachricht.
Trägt keiner, geht es mit „Zurück ins Sammeln“ weiter.',
        ],
        'en' => [
            'hilfe_label' => 'Help',
            'hilfe_tafel_frage' => 'How the board works',
            'hilfe_tafel' => 'Each card is one work step and moves from left to right.
Open – nobody has started yet.
In progress – someone is taking care of it.
For a vote – ready for everyone’s opinion.
Done – finished.
Click a card to see its content.',
            'hilfe_abstimmung_frage' => 'How voting works here',
            'hilfe_abstimmung_einfach' => '“Fine as it is” means you agree. “I have a change” means you would do something differently – please say briefly what and why. You can change your opinion until the round ends.',
            'hilfe_abstimmung_konsent' => 'Decisions are made by consent: a proposal is accepted if nobody raises a serious objection by the end of the round.
I agree – you agree.
Agree with concerns – you agree, but have a worry (please describe it briefly).
Serious objection – this would harm our shared goal. It is not a “no” forever, but a hint at what is still missing (please give a reason).
I abstain – you do not commit.',
            'hilfe_abstimmung_mehrheit' => 'Decisions are made by majority: a proposal is accepted if there are more yes than no votes at the end of the round. Abstentions do not count. You can change your vote until the round ends.',
            'hilfe_uebernehmen' => 'You take on this task. Your name appears on the card and the project lead receives a message.',
            'hilfe_zur_abstimmung' => 'The card moves to “For a vote” and a post asking for feedback appears in the circle. First choose until when feedback is collected. You will be asked once more before it is sent.',
            'hilfe_in_arbeit' => 'The card goes back to “In progress” – for example if change requests came in or it was put to a vote by mistake.',
            'hilfe_als_fertig' => 'The task is done and the card moves to “Done”. Only the project lead can do this.',
            'hilfe_wieder_oeffnen' => 'The card goes back to “In progress” so work can continue.',
            'hilfe_ziel' => 'What should come out at the end? One or two sentences. The text appears at the top of the board and tells everyone what it is about.',
            'hilfe_leitung' => 'The project lead creates cards, assigns tasks, moves them through the columns and decides on proposals. Up to five people can lead together.',
            'hilfe_projekt_frist' => 'By when the project should be finished. You can leave this empty – then it says “no deadline”.',
            'hilfe_regel' => 'How proposals are decided:
Simple feedback – “Fine as it is” or “I have a change”; the lead then decides.
Consent – accepted if nobody raises a serious objection by the end of the round.
Majority – accepted if there are more yes than no votes.
Not sure? Choose “Simple feedback”. You can change it later.',
            'hilfe_frist_tage' => 'How long a voting round runs, for example 14 days. Deadlines that are too short exclude people who do not check the portal every day.',
            'hilfe_weitergabe' => 'Optional. If it says, for example, “DAO”, every accepted proposal gets a decision template to pass on to that body.',
            'hilfe_status' => '“Completed” moves the project to the list of completed projects so the circle’s overview stays tidy. You can reactivate it later.',
            'hilfe_zusammenfassung' => 'What should change? Three to five sentences, so it can be understood without the attachment.',
            'hilfe_begruendung' => 'Why is the change needed or better? This helps others form a view.',
            'hilfe_textaenderungen' => 'For smaller changes: name the place (document, version, chapter), the current text and the new wording. The portal shows the difference in colour, and the vote is about exactly this wording. For larger revisions, upload a file instead.',
            'hilfe_dateien' => 'For larger revisions: Word (ideally with “track changes”), PDF, Markdown or images. Each upload is a new version; older ones stay visible. If a proposal changes during a vote, a new round starts.',
            'hilfe_verantwortlich_kachel' => 'Who takes care of this task. If you leave it empty, the card says “Who wants to?” and any member can take it on with “I’ll take this”.',
            'hilfe_verantwortlich_vorschlag' => 'Whoever submits the proposal is automatically responsible for it. As project lead you can change that here.',
            'hilfe_hilft_mit' => 'More people who help. They can tick off steps and upload files.',
            'hilfe_frist' => 'By when it should be done. The responsible person gets a reminder one day before.',
            'hilfe_teilschritte' => 'A small checklist, one step per line. The ticks are only a memory aid – they trigger nothing, not even a vote.',
            'hilfe_unterlagen' => 'Links to documents, one link per line in the form “Title | Address”. It is better to upload files directly – then everyone sees them right in the card.',
            'hilfe_auswerten' => 'Here you, as lead, record the result of the round.
Record as accepted – the card moves to “Done”. With consent and majority this button only appears if the result allows it.
Record as rejected – the card also moves to “Done”.
Back for revision – back to “In progress” so the proposal can be improved.
When a result is recorded, everyone who voted receives a message.',
            'hilfe_auswahl_sammeln' => 'While the card is “Open” or “In progress”, any circle member can add image drafts. They get the letters A, B, C … A vote needs at least one draft.',
            'hilfe_auswahl_abstimmen' => 'Under each draft you choose:
I agree – you like it.
Agree with concerns – it works, but something strikes you (please write briefly).
Serious objection – it does not work for you at all (please give a brief reason).
You do not have to comment on every draft and can change your vote until the round ends.',
            'hilfe_auswahl_auswerten' => 'Only a draft with no serious objection and at least one agreement is eligible. The one with the most agreements wins; if tied, the lead chooses.
“Choose draft …” completes the card and everyone involved receives a message.
If none is viable, continue with “Back to collecting”.',
        ],
        'es' => [
            'hilfe_label' => 'Ayuda',
            'hilfe_tafel_frage' => 'Cómo funciona el tablero',
            'hilfe_tafel' => 'Cada tarjeta es un paso de trabajo y avanza de izquierda a derecha.
Abierto – nadie ha empezado todavía.
En curso – alguien se encarga.
En votación – listo, ahora se pide la opinión de todos.
Terminado – hecho.
Haz clic en una tarjeta para ver su contenido.',
            'hilfe_abstimmung_frage' => 'Cómo se vota aquí',
            'hilfe_abstimmung_einfach' => '«Está bien» significa que estás de acuerdo. «Tengo un cambio» significa que harías algo distinto: cuenta brevemente qué y por qué. Puedes cambiar tu opinión hasta que termine la ronda.',
            'hilfe_abstimmung_konsent' => 'Se decide por consentimiento: la propuesta se acepta si al final de la ronda nadie tiene una objeción grave.
Estoy de acuerdo – conforme.
Conforme con reservas – conforme, pero con una preocupación (descríbela brevemente).
Objeción grave – esto perjudica nuestro objetivo común. No es un «no» para siempre, sino una pista de lo que aún falta (por favor, razónalo).
Me abstengo – no te posicionas.',
            'hilfe_abstimmung_mehrheit' => 'Se decide por mayoría: la propuesta se acepta si al final de la ronda hay más votos a favor que en contra. Las abstenciones no cuentan. Puedes cambiar tu voto hasta que termine la ronda.',
            'hilfe_uebernehmen' => 'Te encargas de esta tarea. Tu nombre aparece en la tarjeta y la dirección del proyecto recibe un mensaje.',
            'hilfe_zur_abstimmung' => 'La tarjeta pasa a «En votación» y en el círculo aparece una publicación pidiendo opinión. Elige antes hasta cuándo se recogen opiniones. Se te preguntará una vez más antes de enviarla.',
            'hilfe_in_arbeit' => 'La tarjeta vuelve a «En curso», por ejemplo si llegaron peticiones de cambio o se pasó a votación por error.',
            'hilfe_als_fertig' => 'La tarea está hecha y la tarjeta pasa a «Terminado». Solo puede hacerlo la dirección del proyecto.',
            'hilfe_wieder_oeffnen' => 'La tarjeta vuelve a «En curso» para poder seguir trabajando.',
            'hilfe_ziel' => '¿Qué debe resultar al final? Una o dos frases. El texto aparece arriba en el tablero y explica a todos de qué trata.',
            'hilfe_leitung' => 'La dirección del proyecto crea tarjetas, asigna tareas, las mueve por las columnas y decide sobre las propuestas. Hasta cinco personas pueden dirigir juntas.',
            'hilfe_projekt_frist' => 'Hasta cuándo debe estar terminado el proyecto. Puedes dejarlo vacío: entonces pone «sin fecha».',
            'hilfe_regel' => 'Cómo se decide sobre las propuestas:
Respuesta sencilla – «Está bien» o «Tengo un cambio»; después decide la dirección.
Consentimiento – se acepta si al final de la ronda nadie tiene una objeción grave.
Mayoría – se acepta si hay más síes que noes.
¿No estás seguro? Elige «Respuesta sencilla». Puedes cambiarlo más tarde.',
            'hilfe_frist_tage' => 'Cuánto dura una ronda de votación, por ejemplo 14 días. Plazos demasiado cortos excluyen a quienes no miran el portal a diario.',
            'hilfe_weitergabe' => 'Opcional. Si pone, por ejemplo, «DAO», cada propuesta aceptada tiene una plantilla de decisión para entregar a ese órgano.',
            'hilfe_status' => '«Terminado» pasa el proyecto a la lista de proyectos terminados para que la vista general del círculo siga clara. Puedes reactivarlo más tarde.',
            'hilfe_zusammenfassung' => '¿Qué debe cambiar? De tres a cinco frases, de modo que se entienda también sin el archivo adjunto.',
            'hilfe_begruendung' => '¿Por qué es necesario o mejor el cambio? Eso ayuda a los demás a formarse una opinión.',
            'hilfe_textaenderungen' => 'Para cambios pequeños: indica el lugar (documento, versión, capítulo), el texto actual y la nueva redacción. El portal muestra la diferencia en color y se vota exactamente esa redacción. Para revisiones grandes, mejor sube un archivo.',
            'hilfe_dateien' => 'Para revisiones grandes: Word (mejor con «control de cambios»), PDF, Markdown o imágenes. Cada subida es una nueva versión; las anteriores siguen visibles. Si una propuesta cambia durante la votación, empieza una nueva ronda.',
            'hilfe_verantwortlich_kachel' => 'Quién se ocupa de esta tarea. Si lo dejas vacío, la tarjeta dice «¿Quién quiere?» y cualquier miembro puede asumirla con «Me encargo yo».',
            'hilfe_verantwortlich_vorschlag' => 'Quien presenta la propuesta es automáticamente responsable de ella. Como dirección del proyecto puedes cambiarlo aquí.',
            'hilfe_hilft_mit' => 'Otras personas que ayudan. Pueden marcar pasos y subir archivos.',
            'hilfe_frist' => 'Hasta cuándo debe estar hecho. Un día antes la persona responsable recibe un recordatorio.',
            'hilfe_teilschritte' => 'Una pequeña lista de comprobación, un paso por línea. Las marcas son solo una ayuda para recordar: no activan nada, ni siquiera una votación.',
            'hilfe_unterlagen' => 'Enlaces a documentos, uno por línea con el formato «Título | Dirección». Es mejor subir los archivos directamente: así todos los ven al instante en la tarjeta.',
            'hilfe_auswerten' => 'Aquí, como dirección, registras el resultado de la ronda.
Registrar como aceptada – la tarjeta pasa a «Terminado». Con consentimiento y mayoría este botón solo aparece si el resultado lo permite.
Registrar como rechazada – la tarjeta también pasa a «Terminado».
Volver a revisión – vuelve a «En curso» para que la propuesta pueda mejorarse.
Al registrar el resultado, todos los que votaron reciben un mensaje.',
            'hilfe_auswahl_sammeln' => 'Mientras la tarjeta esté «Abierta» o «En curso», cualquier miembro del círculo puede añadir borradores de imagen. Reciben las letras A, B, C … Para votar hace falta al menos un borrador.',
            'hilfe_auswahl_abstimmen' => 'Debajo de cada borrador eliges:
Estoy de acuerdo – te gusta.
Conforme con reservas – sirve, pero algo te llama la atención (escríbelo brevemente).
Objeción grave – para ti no sirve en absoluto (razónalo brevemente).
No tienes que opinar sobre todos y puedes cambiar tu voto hasta el final de la ronda.',
            'hilfe_auswahl_auswerten' => 'Solo puede elegirse un borrador sin objeción grave y con al menos un voto de acuerdo. Gana el que más acuerdos tenga; en caso de empate elige la dirección.
«Elegir borrador …» termina la tarjeta y todos los participantes reciben un mensaje.
Si ninguno sirve, se continúa con «Volver a recoger».',
        ],
        'ru' => [
            'hilfe_label' => 'Справка',
            'hilfe_tafel_frage' => 'Как работает доска',
            'hilfe_tafel' => 'Каждая карточка — один рабочий шаг и движется слева направо.
Открыто — ещё никто не начал.
В работе — кто-то этим занимается.
На голосовании — готово, теперь нужно мнение всех.
Готово — выполнено.
Нажмите на карточку, чтобы увидеть её содержимое.',
            'hilfe_abstimmung_frage' => 'Как здесь голосуют',
            'hilfe_abstimmung_einfach' => '«Подходит» — вы согласны. «У меня есть изменение» — вы сделали бы иначе: коротко напишите, что и почему. Своё мнение можно менять до конца раунда.',
            'hilfe_abstimmung_konsent' => 'Решение принимается консентом: предложение принято, если к концу раунда ни у кого нет серьёзного возражения.
Я согласен — согласны.
Согласие с оговорками — согласны, но есть опасение (коротко опишите).
Серьёзное возражение — это вредит нашей общей цели. Это не «нет» навсегда, а подсказка, чего ещё не хватает (пожалуйста, обоснуйте).
Я воздерживаюсь — вы не определяетесь.',
            'hilfe_abstimmung_mehrheit' => 'Решение принимается большинством: предложение принято, если к концу раунда «за» больше, чем «против». Воздержавшиеся не учитываются. Свой голос можно изменить до конца раунда.',
            'hilfe_uebernehmen' => 'Вы берёте эту задачу. Ваше имя появится на карточке, а руководство проекта получит сообщение.',
            'hilfe_zur_abstimmung' => 'Карточка переходит в «На голосовании», а в круге появляется публикация с просьбой высказаться. Сначала выберите, до какого срока собирать мнения. Перед отправкой вас спросят ещё раз.',
            'hilfe_in_arbeit' => 'Карточка возвращается в «В работе» — например, если пришли пожелания об изменениях или её по ошибке поставили на голосование.',
            'hilfe_als_fertig' => 'Задача выполнена, карточка переходит в «Готово». Это может сделать только руководство проекта.',
            'hilfe_wieder_oeffnen' => 'Карточка возвращается в «В работе», чтобы можно было продолжить.',
            'hilfe_ziel' => 'Что должно получиться в итоге? Одно-два предложения. Текст показывается вверху доски и объясняет всем, о чём проект.',
            'hilfe_leitung' => 'Руководство проекта создаёт карточки, назначает задачи, перемещает их по столбцам и решает по предложениям. Руководить вместе могут до пяти человек.',
            'hilfe_projekt_frist' => 'К какому сроку проект должен быть готов. Поле можно оставить пустым — тогда будет написано «без срока».',
            'hilfe_regel' => 'Как принимаются решения по предложениям:
Простой отклик — «Подходит» или «У меня есть изменение»; затем решает руководство.
Консент — принято, если к концу раунда ни у кого нет серьёзного возражения.
Большинство — принято, если «за» больше, чем «против».
Не уверены? Выберите «Простой отклик». Позже это можно изменить.',
            'hilfe_frist_tage' => 'Сколько длится раунд голосования, например 14 дней. Слишком короткие сроки исключают тех, кто заходит в портал не каждый день.',
            'hilfe_weitergabe' => 'По желанию. Если написано, например, «DAO», то для каждого принятого предложения создаётся шаблон решения для передачи в этот орган.',
            'hilfe_status' => '«Завершён» переносит проект в список завершённых проектов, чтобы обзор круга оставался аккуратным. Позже его можно снова активировать.',
            'hilfe_zusammenfassung' => 'Что должно измениться? От трёх до пяти предложений — чтобы было понятно и без вложения.',
            'hilfe_begruendung' => 'Почему изменение нужно или лучше? Это помогает остальным составить мнение.',
            'hilfe_textaenderungen' => 'Для небольших изменений: укажите место (документ, версия, глава), прежний текст и новую формулировку. Портал показывает разницу цветом, голосуют именно за эту формулировку. Для больших правок лучше загрузить файл.',
            'hilfe_dateien' => 'Для больших правок: Word (лучше с «исправлениями»), PDF, Markdown или изображения. Каждая загрузка — новая версия, прежние остаются видны. Если предложение меняется во время голосования, начинается новый раунд.',
            'hilfe_verantwortlich_kachel' => 'Кто занимается этой задачей. Если оставить пустым, на карточке будет «Кто возьмётся?», и любой участник может взять её кнопкой «Беру на себя».',
            'hilfe_verantwortlich_vorschlag' => 'Кто вносит предложение, тот автоматически за него отвечает. Руководство проекта может изменить это здесь.',
            'hilfe_hilft_mit' => 'Другие люди, которые помогают. Они могут отмечать шаги и загружать файлы.',
            'hilfe_frist' => 'К какому сроку нужно сделать. За день до срока ответственный получит напоминание.',
            'hilfe_teilschritte' => 'Небольшой чек-лист, один шаг в строке. Галочки — лишь напоминание: они ничего не запускают, даже голосование.',
            'hilfe_unterlagen' => 'Ссылки на документы, по одной в строке в виде «Название | Адрес». Файлы лучше загружать напрямую — тогда все сразу увидят их в карточке.',
            'hilfe_auswerten' => 'Здесь руководство фиксирует результат раунда.
Зафиксировать как принятое — карточка переходит в «Готово». При консенте и большинстве эта кнопка появляется, только если результат это позволяет.
Зафиксировать как отклонённое — карточка тоже переходит в «Готово».
Вернуть на доработку — обратно в «В работе», предложение можно улучшить.
При фиксации результата все проголосовавшие получают сообщение.',
            'hilfe_auswahl_sammeln' => 'Пока карточка находится в «Открыто» или «В работе», любой участник круга может добавлять варианты изображений. Они получают буквы A, B, C … Для голосования нужен хотя бы один вариант.',
            'hilfe_auswahl_abstimmen' => 'Под каждым вариантом вы выбираете:
Я согласен — вариант вам нравится.
Согласие с оговорками — подходит, но что-то бросается в глаза (коротко напишите).
Серьёзное возражение — вариант вам совсем не подходит (коротко обоснуйте).
Не обязательно высказываться о каждом варианте, а голос можно менять до конца раунда.',
            'hilfe_auswahl_auswerten' => 'Выбрать можно только вариант без серьёзного возражения и хотя бы с одним согласием. Побеждает вариант с наибольшим числом согласий, при равенстве выбирает руководство.
«Выбрать вариант …» завершает карточку, и все участники получают сообщение.
Если ни один не подходит, продолжайте через «Назад к сбору».',
        ],
        'fr' => [
            'hilfe_label' => 'Aide',
            'hilfe_tafel_frage' => 'Comment fonctionne le tableau',
            'hilfe_tafel' => 'Chaque carte est une étape de travail et avance de gauche à droite.
Ouvert – personne n’a encore commencé.
En cours – quelqu’un s’en occupe.
Au vote – prêt, l’avis de tous est demandé.
Terminé – fait.
Cliquez sur une carte pour voir son contenu.',
            'hilfe_abstimmung_frage' => 'Comment on vote ici',
            'hilfe_abstimmung_einfach' => '« Ça convient » signifie que vous êtes d’accord. « J’ai une modification » signifie que vous feriez autrement : dites brièvement quoi et pourquoi. Vous pouvez changer d’avis jusqu’à la fin du tour.',
            'hilfe_abstimmung_konsent' => 'On décide par consentement : la proposition est acceptée si, à la fin du tour, personne n’a d’objection grave.
Je suis d’accord – d’accord.
Accord avec réserves – d’accord, mais avec une inquiétude (décrivez-la brièvement).
Objection grave – cela nuit à notre but commun. Ce n’est pas un « non » définitif, mais une indication de ce qui manque encore (merci de motiver).
Je m’abstiens – vous ne vous prononcez pas.',
            'hilfe_abstimmung_mehrheit' => 'On décide à la majorité : la proposition est acceptée s’il y a plus de oui que de non à la fin du tour. Les abstentions ne comptent pas. Vous pouvez changer votre vote jusqu’à la fin du tour.',
            'hilfe_uebernehmen' => 'Vous prenez cette tâche en charge. Votre nom apparaît sur la carte et la direction du projet reçoit un message.',
            'hilfe_zur_abstimmung' => 'La carte passe dans « Au vote » et une publication demandant des retours apparaît dans le cercle. Choisissez d’abord jusqu’à quand les avis sont recueillis. Une confirmation vous sera demandée avant l’envoi.',
            'hilfe_in_arbeit' => 'La carte retourne dans « En cours » – par exemple si des demandes de modification sont arrivées ou si elle a été mise au vote par erreur.',
            'hilfe_als_fertig' => 'La tâche est terminée et la carte passe dans « Terminé ». Seule la direction du projet peut le faire.',
            'hilfe_wieder_oeffnen' => 'La carte retourne dans « En cours » pour que le travail puisse continuer.',
            'hilfe_ziel' => 'Quel est le résultat attendu ? Une ou deux phrases. Le texte s’affiche en haut du tableau et explique à tous de quoi il s’agit.',
            'hilfe_leitung' => 'La direction du projet crée les cartes, attribue les tâches, les fait avancer dans les colonnes et décide des propositions. Jusqu’à cinq personnes peuvent diriger ensemble.',
            'hilfe_projekt_frist' => 'Date à laquelle le projet doit être terminé. Le champ peut rester vide – il est alors indiqué « sans échéance ».',
            'hilfe_regel' => 'Comment les propositions sont décidées :
Avis simple – « Ça convient » ou « J’ai une modification » ; ensuite la direction décide.
Consentement – acceptée si, à la fin du tour, personne n’a d’objection grave.
Majorité – acceptée s’il y a plus de oui que de non.
Un doute ? Choisissez « Avis simple ». Vous pourrez le changer plus tard.',
            'hilfe_frist_tage' => 'Durée d’un tour de vote, par exemple 14 jours. Des délais trop courts excluent ceux qui ne consultent pas le portail tous les jours.',
            'hilfe_weitergabe' => 'Facultatif. Si l’on indique par exemple « DAO », chaque proposition acceptée reçoit un modèle de décision à transmettre à cet organe.',
            'hilfe_status' => '« Terminé » place le projet dans la liste des projets terminés pour que la vue d’ensemble du cercle reste claire. Vous pourrez le réactiver plus tard.',
            'hilfe_zusammenfassung' => 'Qu’est-ce qui doit changer ? Trois à cinq phrases, pour que ce soit compréhensible même sans pièce jointe.',
            'hilfe_begruendung' => 'Pourquoi le changement est-il nécessaire ou meilleur ? Cela aide les autres à se faire un avis.',
            'hilfe_textaenderungen' => 'Pour de petites modifications : indiquez l’endroit (document, version, chapitre), le texte actuel et la nouvelle formulation. Le portail montre la différence en couleur, et le vote porte exactement sur cette formulation. Pour de plus grosses révisions, téléversez plutôt un fichier.',
            'hilfe_dateien' => 'Pour de plus grosses révisions : Word (de préférence avec le « suivi des modifications »), PDF, Markdown ou images. Chaque envoi est une nouvelle version ; les anciennes restent visibles. Si une proposition change pendant un vote, un nouveau tour commence.',
            'hilfe_verantwortlich_kachel' => 'Qui s’occupe de cette tâche. Si vous laissez vide, la carte indique « Qui veut ? » et tout membre peut la prendre avec « Je m’en charge ».',
            'hilfe_verantwortlich_vorschlag' => 'Qui soumet la proposition en est automatiquement responsable. En tant que direction du projet, vous pouvez le modifier ici.',
            'hilfe_hilft_mit' => 'D’autres personnes qui aident. Elles peuvent cocher des étapes et téléverser des fichiers.',
            'hilfe_frist' => 'Date limite. La veille, la personne responsable reçoit un rappel.',
            'hilfe_teilschritte' => 'Une petite liste de contrôle, une étape par ligne. Les coches ne sont qu’un aide-mémoire – elles ne déclenchent rien, pas même un vote.',
            'hilfe_unterlagen' => 'Liens vers des documents, un lien par ligne sous la forme « Titre | Adresse ». Mieux vaut téléverser les fichiers directement : tout le monde les voit alors tout de suite dans la carte.',
            'hilfe_auswerten' => 'Ici, en tant que direction, vous consignez le résultat du tour.
Consigner comme acceptée – la carte passe dans « Terminé ». Avec le consentement et la majorité, ce bouton n’apparaît que si le résultat le permet.
Consigner comme rejetée – la carte passe aussi dans « Terminé ».
Retour à la révision – retour dans « En cours » pour améliorer la proposition.
Lors de la consignation, tous ceux qui ont voté reçoivent un message.',
            'hilfe_auswahl_sammeln' => 'Tant que la carte est « Ouverte » ou « En cours », tout membre du cercle peut ajouter des propositions d’image. Elles reçoivent les lettres A, B, C … Un vote nécessite au moins une proposition.',
            'hilfe_auswahl_abstimmen' => 'Sous chaque proposition, vous choisissez :
Je suis d’accord – elle vous plaît.
Accord avec réserves – ça va, mais quelque chose vous frappe (écrivez-le brièvement).
Objection grave – elle ne convient pas du tout (motivez brièvement).
Vous n’êtes pas obligé de vous prononcer sur chacune et pouvez changer votre vote jusqu’à la fin du tour.',
            'hilfe_auswahl_auswerten' => 'Seule une proposition sans objection grave et avec au moins un accord peut être retenue. Celle qui a le plus d’accords l’emporte ; en cas d’égalité, la direction choisit.
« Choisir la proposition … » termine la carte et tous les participants reçoivent un message.
Si aucune ne convient, on continue avec « Retour à la collecte ».',
        ],
    ];
}
