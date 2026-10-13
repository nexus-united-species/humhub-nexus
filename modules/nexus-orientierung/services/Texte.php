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
    ];

    public static function t(string $schluessel, array $werte = []): string
    {
        $code = strtolower(explode('-', (string)Yii::$app->language)[0]);
        $text = self::TEXTE[$code][$schluessel] ?? self::TEXTE['en'][$schluessel] ?? $schluessel;
        return strtr($text, $werte);
    }
}
