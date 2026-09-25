<?php

namespace App\Http\Controllers;

use App\Services\Seo;
use Inertia\Inertia;
use Inertia\Response;

class WelcomeController extends Controller
{
    /**
     * Show the public landing page.
     *
     * SEO is built per request (not in the routes file) so the canonical and
     * social URLs always reflect the current APP_URL, even with route caching.
     */
    public function __invoke(): Response
    {
        return Inertia::render('Welcome', [
            'seo' => Seo::page(
                title: 'Davao Beyblade Battle League scores & standings',
                description: 'Live scores, standings and the title race for the Davao Beyblade Battle League (DBBL) Beyblade X round robin. See who leads Finals MVP, Burst God, Extreme Champion and more.',
            ),
        ]);
    }
}
