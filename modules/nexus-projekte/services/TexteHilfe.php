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
        ],
    ];
}
