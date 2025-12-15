<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppVersion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class AppVersionController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->get('search');
        Log::info('AppVersion index accessed', ['search' => $search]);

        $versions = AppVersion::query()
            ->when($search, fn($q) => $q->where('version_name', 'like', "%{$search}%"))
            ->orderByDesc('version_code')
            ->paginate(15);

        return view('admin.app-versions.index', compact('versions'));
    }

    public function store(Request $request)
    {
        Log::info('AppVersion store method called', [
            'input' => $request->except('apk_file'),
            'has_file' => $request->hasFile('apk_file'),
            'file_size' => $request->hasFile('apk_file') ? $request->file('apk_file')->getSize() : null,
        ]);

        try {
            $request->validate([
                'version_name' => 'required|string|max:255',
                'version_code' => 'required|integer|unique:app_versions,version_code',
                'whats_new' => 'nullable|string',
                'apk_file' => 'required|file|mimes:apk,ipa,zip,jar|max:1024000',
                'is_force_update' => 'required|boolean',
                'platform' => 'required|in:android,ios',
            ]);

            Log::info('Validation passed for store');

            $file = $request->file('apk_file');
            $originalName = $file->getClientOriginalName(); // e.g., childcare+.apk

            $path = $file->storeAs('apks', $originalName, 'public');
            $download_url = Storage::url($path);

            Log::info('File uploaded successfully with original name', [
                'original_name' => $originalName,
                'path' => $path,
                'url' => $download_url
            ]);

            $version = AppVersion::create([
                'version_name' => $request->version_name,
                'version_code' => $request->version_code,
                'whats_new' => $request->whats_new,
                'download_url' => $download_url,
                'is_force_update' => $request->is_force_update,
                'platform' => $request->platform,
            ]);

            Log::info('AppVersion created successfully', ['id' => $version->id]);

            return redirect()->route('admin.app-versions.index')
                ->with('success', 'App version created successfully.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('Validation failed in store', ['errors' => $e->errors()]);
            throw $e;
        } catch (\Exception $e) {
            Log::error('Error in AppVersion store', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->back()->with('error', 'Failed to create version: ' . $e->getMessage());
        }
    }

    public function update(Request $request, AppVersion $appVersion)
    {
        Log::info('AppVersion update method called', [
            'version_id' => $appVersion->id,
            'input' => $request->except('apk_file'),
            'has_file' => $request->hasFile('apk_file'),
        ]);

        try {
            $request->validate([
                'version_name' => 'required|string|max:255',
                'version_code' => 'required|integer|unique:app_versions,version_code,' . $appVersion->id,
                'whats_new' => 'nullable|string',
                'apk_file' => 'nullable|file|mimes:apk,ipa,zip,jar|max:1024000',
                'is_force_update' => 'required|boolean',
                'platform' => 'required|in:android,ios',
            ]);

            Log::info('Validation passed for update');

            $data = $request->only(['version_name', 'version_code', 'whats_new', 'is_force_update', 'platform']);

            if ($request->hasFile('apk_file')) {
                // Delete old file if exists
                if ($appVersion->download_url) {
                    $oldPath = ltrim(str_replace('/storage/', '', parse_url($appVersion->download_url, PHP_URL_PATH)), '/');
                    if (Storage::disk('public')->exists($oldPath)) {
                        Storage::disk('public')->delete($oldPath);
                        Log::info('Old file deleted', ['old_path' => $oldPath]);
                    }
                }

                $file = $request->file('apk_file');
                $originalName = $file->getClientOriginalName(); // Exact original name

                $path = $file->storeAs('apks', $originalName, 'public');
                $data['download_url'] = Storage::url($path);

                Log::info('New file uploaded with original name', [
                    'original_name' => $originalName,
                    'path' => $path
                ]);
            }

            $appVersion->update($data);
            Log::info('AppVersion updated successfully', ['id' => $appVersion->id]);

            return redirect()->route('admin.app-versions.index')
                ->with('success', 'App version updated successfully.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('Validation failed in update', ['errors' => $e->errors()]);
            throw $e;
        } catch (\Exception $e) {
            Log::error('Error in AppVersion update', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->back()->with('error', 'Failed to update version: ' . $e->getMessage());
        }
    }

    public function destroy(AppVersion $appVersion)
    {
        Log::info('AppVersion destroy called', ['id' => $appVersion->id]);

        try {
            if ($appVersion->download_url) {
                $oldPath = ltrim(str_replace('/storage/', '', parse_url($appVersion->download_url, PHP_URL_PATH)), '/');
                if (Storage::disk('public')->exists($oldPath)) {
                    Storage::disk('public')->delete($oldPath);
                    Log::info('File deleted on destroy', ['path' => $oldPath]);
                }
            }

            $appVersion->delete();
            Log::info('AppVersion deleted successfully', ['id' => $appVersion->id]);

            return redirect()->route('admin.app-versions.index')
                ->with('success', 'App version deleted successfully.');
        } catch (\Exception $e) {
            Log::error('Error deleting AppVersion', [
                'id' => $appVersion->id,
                'message' => $e->getMessage()
            ]);
            return redirect()->back()->with('error', 'Failed to delete version.');
        }
    }
}