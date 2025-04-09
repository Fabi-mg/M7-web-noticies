<?php

namespace App\Http\Controllers;

use App\Models\news;
use Illuminate\Http\Request;

class newsController extends Controller
{
    public function index()
    {
        return news::all();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => ['required'],
            'newsUrl' => ['required'],
            'imageUrl' => ['required'],
            'themes_id' => ['required', 'exists:themes'],
        ]);

        return news::create($data);
    }

    public function show(news $news)
    {
        return $news;
    }

    public function update(Request $request, news $news)
    {
        $data = $request->validate([
            'title' => ['required'],
            'newsUrl' => ['required'],
            'imageUrl' => ['required'],
            'themes_id' => ['required', 'exists:themes'],
        ]);

        $news->update($data);

        return $news;
    }

    public function destroy(news $news)
    {
        $news->delete();

        return response()->json();
    }
}
