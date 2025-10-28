<?php

namespace App\Http\Controllers;

use App\Helpers\LanguageHelper;
use App\Models\Language;
use App\Models\Media;
use Inertia\Inertia;
use Illuminate\Http\Request;

class MediaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index() {
        $primaryLanguage = LanguageHelper::getPrimaryLanguage();
        $mediaItems = Media::all();

        return Inertia::render('backend/Media/Index', [
            'primaryLanguage' => $primaryLanguage,
            'mediaItems' => $mediaItems,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create() {
        return Inertia::render('backend/Media/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request) {
        $validated = $request->validate([
            'type' => 'required|in:image,video,audio',
            'file' => 'required|file|max:51200', // 50MB max
            'title' => 'required|array',
            'title.*' => 'required|string|max:255',
            'description' => 'nullable|array',
            'description.*' => 'nullable|string',
        ]);

        // Store the file
        $file = $request->file('file');
        $path = $file->store('media', 'public');

        // Prepare URL array with translations
        $languages = Language::all();
        $url = [];
        foreach ($languages as $language) {
            $url[$language->code] = $path;
        }

        // Create media record
        $media = Media::create([
            'type' => $validated['type'],
            'url' => $url,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? [],
        ]);

        return redirect()->route('media.index')->with('success', 'Media creato con successo.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id) {
        $mediaItem = Media::findOrFail($id);

        return Inertia::render('backend/Media/Show', [
            'mediaItem' => $mediaItem,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id) {
 
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id) {

    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id) {
        $mediaItem = Media::findOrFail($id);
        $mediaItem->delete();

        return redirect()->route('media.index')->with('success', 'Media eliminato con successo.');
    }
}
