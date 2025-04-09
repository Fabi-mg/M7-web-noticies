<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class RssController extends Controller
{
    const RSS_URL = 'https://trends.google.com/trending/rss?geo=ES';

    public function getRss()
    {

        $rssContent = file_get_contents($this::RSS_URL);

        if ($rssContent === false) {
            return response()->json(['error' => 'No se pudo obtener el RSS'], 500);
        }

        return response($rssContent, 200)
            ->header('Content-Type', 'application/xml');
    }
}
