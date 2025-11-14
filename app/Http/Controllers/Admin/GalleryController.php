<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Gallery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class GalleryController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search');
        $filter_creator = $request->query('filter_creator');

        $galleries = Gallery::with('user')
            ->when($search, fn($q) => $q->where('description', 'like', "%{$search}%"))
            ->when($filter_creator, fn($q) => $q->where('created_by', $filter_creator))
            ->latest()
            ->paginate(10);

        $creators = Gallery::select('created_by')
            ->distinct()
            ->with('user')
            ->get()
            ->pluck('user.name', 'created_by');

        return view('admin.galleries.index', compact('galleries', 'creators'));
    }

    public function store(Request $request)
    {
        if (!Auth::check()) {
            return back()->with('error', 'You must be logged in.');
        }

        $request->validate([
            'description' => 'required|string|max:1000',
            'media.*'     => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ], [
            'media.*.required' => 'Please upload at least one image.',
            'media.*.image'    => 'Each file must be a valid image.',
            'media.*.mimes'    => 'Only JPEG, PNG, JPG, and GIF are allowed.',
            'media.*.max'      => 'Each image must not exceed 2MB.',
        ]);

        $mediaFiles = $request->file('media') ?? [];
        if (count($mediaFiles) < 1 || count($mediaFiles) > 10) {
            return back()->withErrors(['media' => 'Please upload between 1 and 10 images.']);
        }

        try {
            $mediaPaths = [];
            foreach ($mediaFiles as $file) {
                $path = $file->store('gallery', 'public');
                $mediaPaths[] = Storage::url($path);
            }

            Gallery::create([
                'description' => $request->description,
                'media'       => $mediaPaths,
                'created_by'  => Auth::id(),
            ]);

            return redirect()->route('gallery.index')->with('success', 'Gallery item created.');
        } catch (\Exception $e) {
            Log::error('Gallery creation failed: ' . $e->getMessage());
            return back()->withInput()->with('error', 'Failed to save.');
        }
    }

    public function update(Request $request, Gallery $gallery)
    {
        if (Auth::id() !== $gallery->created_by && !Auth::user()->hasRole('Admin')) {
            return back()->with('error', 'Unauthorized.');
        }

        $request->validate([
            'description' => 'required|string|max:1000',
            'new_media.*' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        try {
            $mediaPaths = $request->input('keep_images', []);

            if ($request->hasFile('new_media')) {
                $newFiles = $request->file('new_media');
                foreach ($newFiles as $file) {
                    $path = $file->store('gallery', 'public');
                    $mediaPaths[] = Storage::url($path);
                }
            }

            // Delete removed images
            $oldMedia = $gallery->media ?? [];
            $removed = array_diff($oldMedia, $mediaPaths);
            foreach ($removed as $path) {
                $diskPath = str_replace('/storage/', 'public/', $path);
                if (Storage::exists($diskPath)) {
                    Storage::delete($diskPath);
                }
            }

            if (count($mediaPaths) < 1 || count($mediaPaths) > 10) {
                return back()->withErrors(['media' => 'Please have 1–10 images.']);
            }

            $gallery->update([
                'description' => $request->description,
                'media'       => $mediaPaths,
            ]);

            return redirect()->route('gallery.index')->with('success', 'Gallery updated.');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Update failed.');
        }
    }

    public function destroy(Gallery $gallery)
    {
        if (Auth::id() !== $gallery->created_by && !Auth::user()->hasRole('Admin')) {
            return back()->with('error', 'Unauthorized.');
        }

        try {
            foreach ($gallery->media ?? [] as $media) {
                $path = str_replace('/storage/', 'public/', $media);
                if (Storage::exists($path)) {
                    Storage::delete($path);
                }
            }

            $gallery->delete();

            return redirect()->route('gallery.index')->with('success', 'Gallery item deleted.');
        } catch (\Exception $e) {
            Log::error('Gallery delete failed: ' . $e->getMessage());
            return back()->with('error', 'Delete failed.');
        }
    }
}