<?php

namespace App\Services;

use Google\Client;
use Google\Service\Drive;
use Illuminate\Support\Facades\Log;

class GoogleDriveService
{
    protected Client $client;

    public function __construct()
    {
        $this->client = new Client;
        $this->client->setClientId(config('services.google_drive.client_id'));
        $this->client->setClientSecret(config('services.google_drive.client_secret'));
        $this->client->setRedirectUri(config('services.google_drive.redirect_uri'));
        $this->client->addScope(Drive::DRIVE_FILE);
        $this->client->setAccessType('offline');
    }

    public function getFileContent(string $fileId, string $accessToken): string
    {
        try {
            $this->client->setAccessToken($accessToken);
            $drive = new Drive($this->client);

            $response = $drive->files->get($fileId, [
                'alt' => 'media',
            ]);

            return $response->getBody()->getContents();
        } catch (\Exception $e) {
            Log::error('Error fetching Google Drive file content', [
                'file_id' => $fileId,
                'error' => $e->getMessage(),
            ]);
            throw new \Exception('Failed to fetch file from Google Drive: '.$e->getMessage());
        }
    }

    public function getFileMetadata(string $fileId, string $accessToken): array
    {
        try {
            $this->client->setAccessToken($accessToken);
            $drive = new Drive($this->client);

            $file = $drive->files->get($fileId, [
                'fields' => 'name,mimeType,size',
            ]);

            return [
                'name' => $file->getName(),
                'mimeType' => $file->getMimeType(),
                'size' => $file->getSize(),
            ];
        } catch (\Exception $e) {
            Log::error('Error fetching Google Drive file metadata', [
                'file_id' => $fileId,
                'error' => $e->getMessage(),
            ]);
            throw new \Exception('Failed to fetch file metadata: '.$e->getMessage());
        }
    }

    public function isTokenExpired(string $accessToken): bool
    {
        $this->client->setAccessToken($accessToken);

        return $this->client->isAccessTokenExpired();
    }

    public function refreshToken(string $refreshToken): array
    {
        $this->client->refreshToken($refreshToken);

        return $this->client->getAccessToken();
    }

    public function getAuthorizationUrl(): string
    {
        return $this->client->createAuthUrl();
    }
}
