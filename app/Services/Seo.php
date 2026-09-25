<?php

namespace App\Services;

/**
 * Builds the `seo` page prop that the root Blade template renders into the
 * initial HTML (title, description, robots, canonical and social tags).
 */
class Seo
{
    public const DEFAULT_DESCRIPTION = 'Scores and standings for the Davao Beyblade Battle League (DBBL) Beyblade X round robin.';

    /**
     * Metadata for a public page.
     *
     * @return array{title: string, description: string, robots: string, canonical: string|null, url: string}
     */
    public static function page(string $title, string $description, string $path = '/', bool $index = true): array
    {
        $url = self::url($path);

        return [
            'title' => $title,
            'description' => $description,
            'robots' => $index ? 'index, follow' : 'noindex, follow',
            // Only indexable pages declare a canonical URL.
            'canonical' => $index ? $url : null,
            'url' => $url,
        ];
    }

    /**
     * Safe default for every other page (admin, auth, settings): keep it out
     * of search results.
     *
     * @return array{title: null, description: string, robots: string, canonical: null, url: null}
     */
    public static function defaults(): array
    {
        return [
            'title' => null,
            'description' => self::DEFAULT_DESCRIPTION,
            'robots' => 'noindex, nofollow',
            'canonical' => null,
            'url' => null,
        ];
    }

    /**
     * Absolute URL built from APP_URL, so it's stable regardless of the
     * request's Host header.
     */
    public static function url(string $path = '/'): string
    {
        $base = rtrim((string) config('app.url'), '/');
        $path = ltrim($path, '/');

        return $path === '' ? $base.'/' : $base.'/'.$path;
    }
}
