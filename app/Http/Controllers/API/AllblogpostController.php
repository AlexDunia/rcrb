<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\AllblogpostResource;
use App\Models\Allblogpost;
use App\Services\GoogleDriveService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AllblogpostController extends Controller
{
    protected $driveService;

    public function __construct(GoogleDriveService $driveService)
    {
        $this->driveService = $driveService;
    }

    public function index()
    {
        $posts = Allblogpost::all();
        return AllblogpostResource::collection($posts);
    }

    public function getCaptions(Request $request)
    {
        $driveUrl = $request->input('url');
        if (empty($driveUrl)) {
            Log::error('URL is required', ['url' => $driveUrl]);
            return response()->json(['error' => 'URL is required.'], 400);
        }

        $fileId = $this->extractFileId($driveUrl);
        if (empty($fileId)) {
            Log::error('Invalid Google Drive URL', ['url' => $driveUrl]);
            return response()->json(['error' => 'Invalid Google Drive URL.'], 400);
        }

        try {
            $content = $this->driveService->fetchFileContent($fileId);
            Log::info('File content fetched successfully', ['file_id' => $fileId]);
            return response()->json(['content' => $content]);
        } catch (\Exception $e) {
            Log::error('Failed to process request', ['file_id' => $fileId, 'error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to fetch file content: ' . $e->getMessage()], 500);
        }
    }

private function extractFileId(string $url): ?string
{
    $parts = parse_url($url);
    if (!isset($parts['path'])) {
        Log::debug('No path in URL', ['url' => $url]);
        return null;
    }

    $segments = explode('/', $parts['path']);
    foreach ($segments as $index => $segment) {
        // Handle /file/d/FILE_ID/view and /document/d/FILE_ID/edit
        if ($segment === 'd' && isset($segments[$index + 1])) {
            return $segments[$index + 1];
        }
    }

    if (isset($parts['query'])) {
        parse_str($parts['query'], $query);
        if (isset($query['id'])) {
            return $query['id'];
        }
    }

    Log::debug('Failed to extract file ID', ['url' => $url]);
    return null;
}
}
