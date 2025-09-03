<?php

namespace App\Services;

use Google_Client;
use Google_Service_Drive;
use Illuminate\Support\Facades\Log;

class GoogleDriveService
{
    protected $drive;

    public function __construct()
    {
        $client = new Google_Client();
        $client->setApplicationName('Drive Content App');
        $client->useApplicationDefaultCredentials();
        $client->addScope(Google_Service_Drive::DRIVE_READONLY);
        $client->setAuthConfig(storage_path(env('GOOGLE_DRIVE_CREDENTIALS')));

        $this->drive = new Google_Service_Drive($client);
    }

    public function fetchFileContent($fileId)
    {
        try {
            $file = $this->drive->files->get($fileId, ['fields' => 'mimeType']);
            $mimeType = $file->mimeType;

            if ($mimeType === 'application/vnd.google-apps.document') {
                $response = $this->drive->files->export($fileId, 'text/plain', ['alt' => 'media']);
                return $response->getBody()->getContents();
            } else {
                $response = $this->drive->files->get($fileId, ['alt' => 'media']);
                return $response->getBody()->getContents();
            }
        } catch (\Exception $e) {
            Log::error('Failed to fetch Google Drive file', ['file_id' => $fileId, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function listFiles()
    {
        try {
            $files = $this->drive->files->listFiles([
                'fields' => 'files(id, name, mimeType)',
            ]);
            return $files->getFiles();
        } catch (\Exception $e) {
            Log::error('Failed to list Google Drive files', ['error' => $e->getMessage()]);
            throw $e;
        }
    }
}
