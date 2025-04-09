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

        $rssXml = simplexml_load_string($rssContent, 'SimpleXMLElement', LIBXML_NOCDATA);

        if ($rssXml === false) {
            return response()->json(['error' => 'No se pudo obtener el XML'], 500);
        }

        return $rssXml;
    }
}
