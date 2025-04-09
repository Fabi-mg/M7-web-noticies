<?php

namespace App\Console\Commands;

use App\Http\Controllers\RssController;
use App\Models\news;
use App\Models\themes;
use Illuminate\Console\Command;

class CreateNewsCommand extends Command
{
    private RssController $rssController;
    protected $signature = 'news:create'; // php artisan news:create

    protected $description = 'Crear noticias automáticamente cada hora';

    public function __construct
    (
        RssController $rssController
    ) {
        parent::__construct();
        $this->rssController = $rssController;
    }

    public function handle()
    {
        $this->info('Empezando a crear noticias automáticamente!');
        $rawXML = $this->rssController->getRss();
        $totalThemes = 0;
        foreach ($rawXML->channel->item as $item) {
            $traffic = (int) rtrim($item->xpath('ht:approx_traffic')[0], '+');
            $mainImageUrl = $item->xpath('ht:picture')[0];
            $theme = themes::firstOrCreate([
                'title' => $item->title
            ], [
                'traffic' => $traffic,
                'publicationDate' => $item->pubDate,
                'imageUrl' => $mainImageUrl
            ]);

            $createdNews = [];
            $newsItem = $item->xpath('ht:news_item');
            foreach ($newsItem as $newItem) {
                $new = news::firstOrCreate([
                    'title' => $newItem->xpath('ht:news_item_title')[0]
                ], [
                    'newsUrl' => $newItem->xpath('ht:news_item_url')[0],
                    'imageUrl' => $newItem->xpath('ht:news_item_picture')[0],
                    'themes_id' => $theme->id,
                ]);

                if ($new->wasRecentlyCreated) {
                    $createdNews[] = $new;
                }
            }

            $action = null;
            if ($theme->wasRecentlyCreated) {
                $action = "creado";
            } else if (count($createdNews) > 0) {
                $action = "actualizado";
            }

            if (isset($action)) {
                $totalThemes++;
                $this->info('Se ha ' . $action . ' el tema ' . $theme->title . ' con un total de ' . count($createdNews) . ' notícias');
            }
        }
        $this->info('Se han creado/modificado un total de ' . $totalThemes . ' temas.');
        $this->info('Final del command.');
    }
}
