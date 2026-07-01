<?php

namespace App\Services;

use Appwrite\AppwriteException;
use Appwrite\Client;
use Appwrite\InputFile;
use Appwrite\Services\Storage;

class FileGateway
{

    private ?Storage $fileStorage = null;
    private ?string $bucketId = null;
    private ?string $localStorageDir;

    public function __construct()
    {
        $this->localStorageDir = $_ENV['APPWRITE_LOCAL_STORAGE_DIR'] ?? null;
        if ($this->localStorageDir) {
            return;
        }

        $client = new Client();
        $client->setEndpoint($_ENV['APPWRITE_URL']);
        $client->setProject($_ENV['APPWRITE_PROJECT_ID'])
            ->setKey($_ENV['APPWRITE_APP_KEY']);


        $this->fileStorage = new Storage($client);

        $this->bucketId = $_ENV['APPWRITE_BUCKET_ID'];
    }

    public function uploadPath(string $path, $fileId = null): string
    {
        if ($this->localStorageDir) {
            $fileId ??= uniqid('d' . time());
            $target = $this->localPath($fileId);
            copy($path, $target);
            return $this->getDsn($fileId);
        }

        $inputFile = InputFile::withPath($path);


        return $this->upload($inputFile, $fileId);
    }

    public function uploadData(string $data, $fileId = null): string
    {
        if ($this->localStorageDir) {
            $fileId ??= uniqid('d' . time());
            file_put_contents($this->localPath($fileId), $data);
            return $this->getDsn($fileId);
        }

        $inputFile = InputFile::withData($data);
        return $this->upload($inputFile, $fileId);
    }

    private function upload(InputFile $inputFile, $fileId = null): string
    {
        if ($fileId == null)
            $fileId = uniqid('d' . time());

        $this->fileStorage->createFile($this->bucketId, $fileId, $inputFile);

        return $this->getDsn($fileId);
    }

    private function getDsn(string $fileId): string
    {
        if ($this->localStorageDir) {
            return 'local://' . $this->localPath($fileId);
        }

        $service = "appwrite";
        $url = $_ENV['APPWRITE_URL'];
        $projectId = $_ENV['APPWRITE_PROJECT_ID'];

        return "{$service}://{$url}?projectId={$projectId}&bucketId={$this->bucketId}&fileId={$fileId}";
    }

    /**
     * @throws AppwriteException
     */
    public function downloadByFileID(string $fileId): string
    {
        return $this->fileStorage->getFileDownload($this->bucketId, $fileId);
    }

    public function getUrlView(string $id)
    {
        if ($this->localStorageDir) {
            return 'local://' . $this->localPath($id);
        }

        $url = $_ENV['APPWRITE_URL'];
        $projectId = $_ENV['APPWRITE_PROJECT_ID'];
        return sprintf("https://%s/v1/storage/buckets/%s/files/%s/download?project=%s", $url, $this->bucketId, $id, $projectId);
    }

    private function localPath(string $fileId): string
    {
        if (!is_dir($this->localStorageDir)) {
            mkdir($this->localStorageDir, 0775, true);
        }

        return rtrim($this->localStorageDir, '/') . '/' . $fileId . '.pdf';
    }
}
