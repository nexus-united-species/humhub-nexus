<?php

namespace nexus\modules\mitgliedsanfrage;

use Yii;

/** Texte der Einladungsseite (DE/EN/ES) */
class Texte
{
    private const TEXTE = [
        'de' => [
            'titel' => 'Du bist eingeladen!',
            'eingeladen' => 'Du wurdest in den Kreis „{kreis}“ eingeladen.',
            'erklaerung' => 'Gib hier deine E-Mail-Adresse ein. Danach legst du dein Konto an – nach der Bestätigung per E-Mail bist du automatisch im Kreis, egal auf welchem Gerät du die E-Mail öffnest.',
            'email' => 'Deine E-Mail-Adresse',
            'weiter' => 'Weiter',
            'schon_konto' => 'Ich habe schon ein Konto – anmelden',
            'bereit_titel' => 'Fast geschafft!',
            'bereit' => 'Lege jetzt dein Konto an. Wichtig: Verwende dabei genau diese E-Mail-Adresse:',
            'konto_anlegen' => 'Konto anlegen',
            'konto_da' => 'Zu dieser E-Mail-Adresse gibt es schon ein Konto. Melde dich einfach an – danach bist du im Kreis „{kreis}“.',
            'anmelden' => 'Anmelden',
            'ungueltig' => 'Dieser Einladungslink ist nicht mehr gültig. Bitte frag die Person, die dich eingeladen hat, nach einem neuen Link.',
            'email_falsch' => 'Bitte gib eine gültige E-Mail-Adresse ein.',
            'zu_oft' => 'Zu viele Versuche. Bitte versuche es später noch einmal.',
        ],
        'en' => [
            'titel' => 'You are invited!',
            'eingeladen' => 'You have been invited to the circle “{kreis}”.',
            'erklaerung' => 'Enter your email address here. Then create your account – after confirming by email you will automatically be in the circle, no matter on which device you open the email.',
            'email' => 'Your email address',
            'weiter' => 'Continue',
            'schon_konto' => 'I already have an account – sign in',
            'bereit_titel' => 'Almost done!',
            'bereit' => 'Now create your account. Important: use exactly this email address:',
            'konto_anlegen' => 'Create account',
            'konto_da' => 'There is already an account for this email address. Just sign in – afterwards you will be in the circle “{kreis}”.',
            'anmelden' => 'Sign in',
            'ungueltig' => 'This invitation link is no longer valid. Please ask the person who invited you for a new link.',
            'email_falsch' => 'Please enter a valid email address.',
            'zu_oft' => 'Too many attempts. Please try again later.',
        ],
        'es' => [
            'titel' => '¡Estás invitado!',
            'eingeladen' => 'Te han invitado al círculo «{kreis}».',
            'erklaerung' => 'Escribe aquí tu dirección de correo. Después crea tu cuenta: tras confirmarla por correo estarás automáticamente en el círculo, da igual en qué dispositivo abras el correo.',
            'email' => 'Tu dirección de correo',
            'weiter' => 'Continuar',
            'schon_konto' => 'Ya tengo una cuenta – iniciar sesión',
            'bereit_titel' => '¡Casi listo!',
            'bereit' => 'Ahora crea tu cuenta. Importante: usa exactamente esta dirección de correo:',
            'konto_anlegen' => 'Crear cuenta',
            'konto_da' => 'Ya existe una cuenta con esta dirección. Simplemente inicia sesión: después estarás en el círculo «{kreis}».',
            'anmelden' => 'Iniciar sesión',
            'ungueltig' => 'Este enlace de invitación ya no es válido. Pide a la persona que te invitó un enlace nuevo.',
            'email_falsch' => 'Escribe una dirección de correo válida.',
            'zu_oft' => 'Demasiados intentos. Inténtalo de nuevo más tarde.',
        ],
        'ru' => [
            'titel' => 'Вас пригласили!',
            'eingeladen' => 'Вас пригласили в круг «{kreis}».',
            'erklaerung' => 'Введите здесь свой адрес электронной почты. Затем создайте учётную запись — после подтверждения по почте вы автоматически окажетесь в круге, на каком бы устройстве вы ни открыли письмо.',
            'email' => 'Ваш адрес электронной почты',
            'weiter' => 'Далее',
            'schon_konto' => 'У меня уже есть учётная запись — войти',
            'bereit_titel' => 'Почти готово!',
            'bereit' => 'Теперь создайте учётную запись. Важно: используйте именно этот адрес электронной почты:',
            'konto_anlegen' => 'Создать учётную запись',
            'konto_da' => 'Для этого адреса уже есть учётная запись. Просто войдите — после этого вы окажетесь в круге «{kreis}».',
            'anmelden' => 'Войти',
            'ungueltig' => 'Эта ссылка-приглашение больше не действует. Пожалуйста, попросите пригласившего вас человека прислать новую ссылку.',
            'email_falsch' => 'Пожалуйста, введите действительный адрес электронной почты.',
            'zu_oft' => 'Слишком много попыток. Пожалуйста, попробуйте позже.',
        ],
    ];

    public static function t(string $schluessel, array $werte = []): string
    {
        $code = strtolower(explode('-', (string)Yii::$app->language)[0]);
        return strtr(self::TEXTE[$code][$schluessel] ?? self::TEXTE['en'][$schluessel] ?? $schluessel, $werte);
    }
}
