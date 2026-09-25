<?php

namespace App\Http\Controllers;

use App\Models\PlayerScore;
use App\Services\Seo;
use Illuminate\Http\Response;

class SeoController extends Controller
{
    /**
     * Admin paths that crawlers should skip. Guests are redirected to sign-in
     * there anyway. Auth pages stay crawlable so their noindex tag is seen.
     *
     * @var list<string>
     */
    private const DISALLOWED_PATHS = [
        '/dashboard',
        '/players',
        '/scores',
        '/audit-logs',
        '/settings',
    ];

    /**
     * Serve robots.txt with an absolute sitemap URL for the current APP_URL.
     */
    public function robots(): Response
    {
        $lines = ['User-agent: *'];

        foreach (self::DISALLOWED_PATHS as $path) {
            $lines[] = "Disallow: {$path}";
        }

        $lines[] = '';
        $lines[] = 'Sitemap: '.Seo::url('sitemap.xml');

        return response(implode("\n", $lines)."\n", 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }

    /**
     * Serve a sitemap of the public pages. `lastmod` follows the most recent
     * score change so crawlers know when the standings were updated.
     */
    public function sitemap(): Response
    {
        $lastModified = PlayerScore::max('updated_at');
        $lastmod = ($lastModified ? now()->parse($lastModified) : now())->toAtomString();

        $urls = [
            ['loc' => Seo::url('/'), 'changefreq' => 'weekly', 'priority' => '1.0'],
            ['loc' => Seo::url('standings'), 'changefreq' => 'daily', 'priority' => '0.8'],
        ];

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";

        foreach ($urls as $url) {
            $xml .= '  <url>'."\n";
            $xml .= '    <loc>'.e($url['loc']).'</loc>'."\n";
            $xml .= '    <lastmod>'.$lastmod.'</lastmod>'."\n";
            $xml .= '    <changefreq>'.$url['changefreq'].'</changefreq>'."\n";
            $xml .= '    <priority>'.$url['priority'].'</priority>'."\n";
            $xml .= '  </url>'."\n";
        }

        $xml .= '</urlset>'."\n";

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }
}
