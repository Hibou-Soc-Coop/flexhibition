<?php

namespace App\Http\Controllers;
use App\Models\Exhibition;
use App\Models\Media;
use App\Services\MediaService;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;


class ExhibitionController extends Controller
{
    public function index()
    {
        $exhibitions = Exhibition::with('museum')->get();

        return Inertia::render('backend/Exhibitions/Index', [
            'exhibitions' => $exhibitions,
        ]);
    }
    public function create()
    {
        //
    }
    public function store()
    {
        //
    }
    public function show($id)
    {
        //
    }

    public function update($id)
    {
        //
    }

    public function destroy($id)
    {
        $exhibition = Exhibition::findOrFail($id);
        $exhibition->delete();



        return redirect()->route('exhibitions.index')->with('success', 'Exhibition deleted successfully.');
    }
}

