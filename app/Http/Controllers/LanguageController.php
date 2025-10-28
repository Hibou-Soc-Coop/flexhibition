<?php

namespace App\Http\Controllers;

use App\Helpers\LanguageHelper;
use App\Models\Language;
use Inertia\Inertia;
use Illuminate\Http\Request;

class LanguageController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index() {
        $primaryLanguage = LanguageHelper::getPrimaryLanguage();
        $languages = Language::all();

        return Inertia::render('backend/Languages/Index', [
            'primaryLanguage' => $primaryLanguage,
            'languages' => $languages,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create() {
        return Inertia::render('backend/Languages/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request) {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|size:2|unique:languages,code',
        ]);

        Language::create($validatedData);
        return redirect()->route('languages.index')->with('success', 'Lingua creata con successo.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id) {
        $language = Language::findOrFail($id);

        return Inertia::render('backend/Languages/Show', [
            'language' => $language,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id) {
        $language = Language::findOrFail($id);

        return Inertia::render('backend/Languages/Edit', [
            'language' => $language,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id) {
        $language = Language::findOrFail($id);

        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|size:2|unique:languages,code,' . $language->id,
        ]);

        $language->update($validatedData);

        return redirect()->route('languages.index')->with('success', 'Lingua aggiornata con successo.');
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id) {
        $language = Language::findOrFail($id);
        $language->delete();

        return redirect()->route('languages.index')->with('success', 'Lingua eliminata con successo.');
    }
}
