<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Gallery;
use Illuminate\Http\Request;

class GalleryController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search');
        $galleries = Gallery::when($search, function ($query, $search) {
            return $query->where('description', 'like', "%{$search}%");
        })->paginate(10);

        return view('admin.galleries.index', compact('galleries'));
    }

    public function create()
    {
        return view('admin.galleries.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'description' => 'required|string',
            'media' => 'required|string', // Use validation for actual media type
        ]);

        Gallery::create($request->all());

        return redirect()->route('gallery.index');
    }

    public function edit(Gallery $gallery)
    {
        return view('admin.galleries.edit', compact('gallery'));
    }

    public function update(Request $request, Gallery $gallery)
    {
        $request->validate([
            'description' => 'required|string',
            'media' => 'required|string',
        ]);

        $gallery->update($request->all());

        return redirect()->route('gallery.index');
    }

    public function destroy(Gallery $gallery)
    {
        $gallery->delete();
        return redirect()->route('gallery.index');
    }
}
