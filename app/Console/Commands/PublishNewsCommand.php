<?php

namespace App\Console\Commands;

use App\Models\news;
use App\Models\themes;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Services\WordpressService;

class PublishNewsCommand extends Command
{
    private string $googleApiKey;
    protected $signature = 'noticias:generar'; // php artisan noticias:generar
    protected $description = 'Genera una noticia a partir de un briefing y la publica en WordPress usando Gemini.';

    protected WordpressService $wordpressService;

    public function __construct(WordpressService $wordpressService)
    {
        parent::__construct();
        $this->wordpressService = $wordpressService;
        $this->googleApiKey = config('services.google_api.key');
    }

    public function handle()
    {
        // Primera noticia que no està publicada
        $theme = themes::firstWhere('published', false);
        if (!$theme) {
            $this->info('No unpublished theme found.');
            return 0;
        }
        $this->info("S'ha trobat el tema " . $theme->id . " per publicar");

        // Agafem les seves notíces
        $news = news::where('themes_id', $theme->id)->get();
//        $this->info($news);

        // Preparem el contingut del prompt
        $promptData = [
            'titulo' => $theme->title,
            'fecha' => $theme->publicationDate,
            'imagenPrincipal' => $theme->imageUrl ?? null,
            'noticias' => []
        ];

        foreach ($news as $newInfo) {
            $promptData['noticias'][] = [
                'titulo' => $newInfo->title,
                'url' => $newInfo->newsUrl,
                'imagen' => $newInfo->imageUrl ?? null,
            ];
            $this->info("Noticia amb ID " . $newInfo->id);
        }

        // Generem el prompt que s'enviarà a Gemini
        $prompt = $this->crearPrompt($promptData);
//        $this->info("Prompt per Gemini: " . $prompt);

        // Fa la petició
        $response = $this->llamarApiGemini($prompt);

        if (!$response['success']) {
            $this->error("❌ Error al generar contenido con Gemini: " . $response['error']);
            return 1;
        }

        $contenidoGenerado = $response['content'] ?? null;
        $this->info("Contenido generado correctamente");
//        $this->info("Contenido Generado: " . $contenidoGenerado);

        if (!$contenidoGenerado) {
            $this->error('No se pudo generar contenido con Gemini.');
            return 1;
        }

        $contenidoGenerado = $this->limparContenido($contenidoGenerado);

        $contenidoGenerado = $this->applyWordPressStyles($contenidoGenerado);

        $resultado = $this->extractTitle($contenidoGenerado);
        $titulo = $resultado['title'];
        $contenidoGenerado = $resultado['content'];

        // Es publica a wordpress
        $post = $this->wordpressService->doPost([
            'title' => $titulo ?? $theme->title,
            'content' => $contenidoGenerado,
            'status' => 'publish',
        ]);

        // En cas d'exit es marca com a publicada
        if ($post['success']) {
            $this->info("✅ Publicado con éxito. ID: " . $post['data']['id']);
            $theme->update(['published' => true]);
        } else {
            $this->error("❌ Error al publicar en WordPress: " . $post['error']);
        }

        return 0;
    }

    private function crearPrompt(array $promptData): string
    {
        $titulo = $promptData['titulo'];
        $imagenPrincipal = $promptData['imagenPrincipal'] ?? 'Sin imagen';
        $fecha = $promptData['fecha'];
        $noticias = $promptData['noticias'];

        $texto = <<<EOT
Actúa como un periodista digital. Tu tarea es leer las siguientes noticias, extraídas desde sus URLs, y redactar una única noticia periodística que combine lo más importante de todas.

El título de la noticia debe estar relacionado con: "$titulo".
La imagen principal debe ser: "$imagenPrincipal".
La fecha de publicación debe ser: "$fecha".

Estas son las noticias que puedes usar como fuente:
EOT;

        foreach ($noticias as $n) {
            $texto .= "\n\n- Título: {$n['titulo']}";
            $texto .= "\n  URL: {$n['url']}";
            if (!empty($n['imagen'])) {
                $texto .= "\n  Imagen sugerida: {$n['imagen']}";
            }
        }

        $texto .= <<<EOT

Tu tarea es visitar cada enlace y usar el contenido real que encuentres para redactar una noticia original. No copies literalmente los textos.

Devuelve el resultado en formato HTML listo para WordPress:

- Un <h1> como título principal.
- Un <h2> con un subtítiulo.
- Párrafos con etiquetas <p>.
- Para las imagenes usa las tres que te sugiero.
- No pongas encabezados como "Fuente".
- Escribe con tono formal y periodístico.
- Escribe en español.

EOT;

        return $texto;
    }

    private function llamarApiGemini(string $prompt): array
    {
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash-001:generateContent?key=' . $this->googleApiKey;

        $headers = [
            'Content-Type' => 'application/json',
        ];

        $body = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt]
                    ]
                ]
            ]
        ];

        $response = Http::withHeaders($headers)->post($url, $body);

        if ($response->successful()) {
            $data = $response->json();
            $contenidoGenerado = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;

            if ($contenidoGenerado) {
                return ['success' => true, 'content' => $contenidoGenerado];
            } else {
                return ['success' => false, 'error' => 'No se pudo generar contenido.'];
            }
        } else {
            return ['success' => false, 'error' => $response->body()];
        }
    }

    private function limparContenido(string $contenidoGenerado): string
    {
        $contenidoGenerado = str_replace("```html", "", $contenidoGenerado);
        $contenidoGenerado = str_replace("```", "", $contenidoGenerado);

        return $contenidoGenerado;
    }

    public function applyWordPressStyles(string $content): string
    {
        // Transformar el título h2
        $content = preg_replace(
            '/<h2>(.*?)<\/h2>/s',
            '<h2 class="wp-block-heading has-large-font-size">$1</h2>',
            $content
        );

        // Transformar imágenes con alineación centrada
        $content = preg_replace(
            '/<img src="([^"]+)"([^>]*)>/s',
            '<figure class="wp-block-image aligncenter size-large"><img src="$1"$2 /></figure>',
            $content
        );

        // Transformar párrafos
        $content = preg_replace(
            '/<p>(.*?)<\/p>/s',
            '<p class="has-text-align-justify">$1</p>',
            $content
        );

        return $content;
    }

    function extractTitle(string $content): array
    {
        if (preg_match('/<h1>(.*?)<\/h1>/s', $content, $matches)) {
            $title = $matches[1];
            $contentWithoutTitle = preg_replace('/<h1>.*?<\/h1>\s*/s', '', $content, 1);

            return [
                'title' => $title,
                'content' => $contentWithoutTitle
            ];
        }

        return [
            'title' => null,
            'content' => $content
        ];
    }
}
