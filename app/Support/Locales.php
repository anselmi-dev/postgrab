<?php

namespace App\Support;

final class Locales
{
    /** @var list<string> */
    public const CODES = ['es', 'en', 'pt', 'fr', 'de'];

    /** @var array<string, string> */
    private const HTML = [
        'es' => 'es',
        'en' => 'en',
        'pt' => 'pt-BR',
        'fr' => 'fr',
        'de' => 'de',
    ];

    /** @var array<string, string> */
    private const OG = [
        'es' => 'es_AR',
        'en' => 'en_US',
        'pt' => 'pt_BR',
        'fr' => 'fr_FR',
        'de' => 'de_DE',
    ];

    /** @var array<string, string> */
    private const LABELS = [
        'es' => 'Español',
        'en' => 'English',
        'pt' => 'Português',
        'fr' => 'Français',
        'de' => 'Deutsch',
    ];

    /** @return list<string> */
    public static function codes(): array
    {
        return self::CODES;
    }

    public static function supported(string $locale): bool
    {
        return in_array($locale, self::CODES, true);
    }

    public static function default(): string
    {
        $locale = (string) config('app.locale');

        return self::supported($locale) ? $locale : 'es';
    }

    public static function hreflang(string $locale): string
    {
        return self::HTML[$locale] ?? self::HTML[self::default()];
    }

    public static function og(string $locale): string
    {
        return self::OG[$locale] ?? self::OG[self::default()];
    }

    public static function label(string $locale): string
    {
        return self::LABELS[$locale] ?? strtoupper($locale);
    }

    /**
     * @param  array<string, mixed>  $query
     */
    public static function url(?string $locale = null, array $query = []): string
    {
        if ($locale === null || ! self::supported($locale)) {
            $locale = self::supported(app()->getLocale()) ? app()->getLocale() : self::default();
        }

        return route('home.locale', ['locale' => $locale] + $query);
    }
}
