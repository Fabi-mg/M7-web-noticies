<?php

namespace App\Http\Controllers;

use App\Models\themes;
use Illuminate\Http\Request;

class themesController extends Controller
{
    public function index()
    {
        return themes::all();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => ['required'],
            'traffic' => ['required', 'integer'],
            'imageUrl' => ['required'],
            'publicationDate' => ['required', 'date'],
        ]);

        return themes::create($data);
    }

    public function show(themes $themes)
    {
        return $themes;
    }

    public function update(Request $request, themes $themes)
    {
        $data = $request->validate([
            'title' => ['required'],
            'traffic' => ['required', 'integer'],
            'imageUrl' => ['required'],
            'publicationDate' => ['required', 'date'],
        ]);

        $themes->update($data);

        return $themes;
    }

    public function destroy(themes $themes)
    {
        $themes->delete();

        return response()->json();
    }
}
