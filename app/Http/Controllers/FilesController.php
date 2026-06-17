<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\File;

class FilesController extends Controller
{
    /**
     * Serve user avatar files directly from storage.
     *
     * @param string $filename
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function handleFile($filename)
    {
        // Prevent directory traversal attacks using basename
        $safeFilename = basename($filename);
        $path = storage_path('app/public/avatars/' . $safeFilename);

        if (!File::exists($path)) {
            abort(404);
        }

        return response()->file($path);
    }
}
