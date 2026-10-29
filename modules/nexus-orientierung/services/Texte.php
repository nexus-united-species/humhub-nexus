<?php

namespace nexus\modules\orientierung\services;

use Yii;

/** Oberflaechentexte (DE/EN/ES) */
class Texte
{
    private const TEXTE = [
        'de' => [
            'menue' => 'Beiträge & Kommentare',
            'titel_ich' => 'Deine Beiträge & Kommentare',
            'titel' => 'Beiträge & Kommentare von {name}',
            'einleitung' => 'Neueste zuerst. Du siehst nur, was du auch sonst lesen darfst.',
            'beitrag' => 'Beitrag',
            'kommentar' => 'Kommentar',
            'zu' => 'zu',
            'profil' => 'Profil von {name}',
            'aeltere' => 'Ältere anzeigen',
            'neuere' => 'Neuere anzeigen',
            'keine' => 'Hier gibt es noch nichts zu sehen.',
        ],
        'en' => [
            'menue' => 'Posts & comments',
            'titel_ich' => 'Your posts & comments',
            'titel' => 'Posts & comments by {name}',
            'einleitung' => 'Newest first. You only see what you are allowed to read anyway.',
            'beitrag' => 'Post',
            'kommentar' => 'Comment',
            'zu' => 'on',
            'profil' => 'Profile of {name}',
            'aeltere' => 'Show older',
            'neuere' => 'Show newer',
            'keine' => 'Nothing to see here yet.',
        ],
        'es' => [
            'menue' => 'Publicaciones y comentarios',
            'titel_ich' => 'Tus publicaciones y comentarios',
            'titel' => 'Publicaciones y comentarios de {name}',
            'einleitung' => 'Lo más reciente primero. Solo ves lo que también puedes leer en otro lugar.',
            'beitrag' => 'Publicación',
            'kommentar' => 'Comentario',
            'zu' => 'sobre',
            'profil' => 'Perfil de {name}',
            'aeltere' => 'Ver anteriores',
            'neuere' => 'Ver más recientes',
            'keine' => 'Todavía no hay nada que ver.',
        ],
        'ru' => [
            'menue' => 'Публикации и комментарии',
            'titel_ich' => 'Ваши публикации и комментарии',
            'titel' => 'Публикации и комментарии: {name}',
            'einleitung' => 'Сначала самые новые. Вы видите только то, что вам и так разрешено читать.',
            'beitrag' => 'Публикация',
            'kommentar' => 'Комментарий',
            'zu' => 'к',
            'profil' => 'Профиль: {name}',
            'aeltere' => 'Показать более старые',
            'neuere' => 'Показать более новые',
            'keine' => 'Здесь пока нечего показывать.',
        ],
        'fr' => [
            'menue' => 'Publications et commentaires',
            'titel_ich' => 'Tes publications et commentaires',
            'titel' => 'Publications et commentaires de {name}',
            'einleitung' => 'Les plus récents d’abord. Tu ne vois que ce que tu as de toute façon le droit de lire.',
            'beitrag' => 'Publication',
            'kommentar' => 'Commentaire',
            'zu' => 'sur',
            'profil' => 'Profil de {name}',
            'aeltere' => 'Afficher les plus anciens',
            'neuere' => 'Afficher les plus récents',
            'keine' => 'Il n’y a encore rien à voir ici.',
        ],
    ];

    public static function t(string $schluessel, array $werte = []): string
    {
        $code = strtolower(explode('-', (string)Yii::$app->language)[0]);
        $text = self::TEXTE[$code][$schluessel] ?? self::TEXTE['en'][$schluessel] ?? $schluessel;
        return strtr($text, $werte);
    }
}
