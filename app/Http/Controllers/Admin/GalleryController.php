<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Gallery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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
            'description' => 'required|string|max:1000',
            'media.*' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048', // Each file must be an image, max 2MB
        ], [
            'media.*.required' => 'Please upload at least one image.',
            'media.*.image' => 'Each file must be a valid image.',
            'media.*.mimes' => 'Only JPEG, PNG, JPG, and GIF formats are allowed.',
            'media.*.max' => 'Each image must not exceed 2MB.',
        ]);

        // Ensure 1 to 10 images are uploaded
        $mediaFiles = $request->file('media') ?? [];
        if (count($mediaFiles) < 1 || count($mediaFiles) > 10) {
            return back()->withInput()->withErrors(['media' => 'Please upload between 1 and 10 images.']);
        }

        try {
            // Store images and collect their paths
            $mediaPaths = [];
            foreach ($mediaFiles as $file) {
                $path = $file->store('gallery', 'public'); // Store in storage/public/gallery
                $mediaPaths[] = Storage::url($path); // Get URL for the stored image
            }

            // Create gallery item
            Gallery::create([
                'description' => $request->description,
                'media' => $mediaPaths,
            ]);

            return redirect()->route('gallery.index')->with('success', 'Gallery item created successfully.');
        } catch (\Exception $e) {
            return back()->withInput()->withErrors(['error' => 'Failed to create gallery item: ' . $e->getMessage()]);
        }
    }

    public function edit(Gallery $gallery)
    {
        return view('admin.galleries.edit', compact('gallery'));
    }

    public function update(Request $request, Gallery $gallery)
    {
        $request->validate([
            'description' => 'required|string|max:1000',
            'media.*' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        // Handle media updates
        $mediaPaths = $gallery->media ?? [];
        if ($request->hasFile('media')) {
            // Delete old images
            foreach ($gallery->media as $oldMedia) {
                $path = str_replace('/storage/', 'public/', $oldMedia);
                Storage::delete($path);
            }

            // Store new images
            $mediaPaths = [];
            foreach ($request->file('media') as $file) {
                $path = $file->store('gallery', 'public');
                $mediaPaths[] = Storage::url($path);
            }

            // Ensure 1 to 10 images
            if (count($mediaPaths) < 1 || count($mediaPaths) > 10) {
                return back()->withInput()->withErrors(['media' => 'Please upload between 1 and 10 images.']);
            }
        }

        // Update gallery item
        $gallery->update([
            'description' => $request->description,
            'media' => $mediaPaths,
        ]);

        return redirect()->route('gallery.index')->with('success', 'Gallery item updated successfully.');
    }

    public function destroy(Gallery $gallery)
    {
        // Delete associated images
        foreach ($gallery->media ?? [] as $media) {
            $path = str_replace('/storage/', 'public/', $media);
            Storage::delete($path);
        }

        $gallery->delete();
        return redirect()->route('gallery.index')->with('success', 'Gallery item deleted successfully.');
    }
}