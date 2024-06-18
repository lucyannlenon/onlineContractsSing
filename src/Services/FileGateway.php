<?php

namespace App\Services;

use Appwrite\AppwriteException;
use Appwrite\Client;
use Appwrite\InputFile;
use Appwrite\Services\Storage;

class FileGateway
{

    private Storage $fileStorage;
    private string $bucketId;

    public function __construct()
    {
        $client = new Client();
        $client->setEndpoint($_ENV['APPWRITE_URL']);
        $client->setProject($_ENV['APPWRITE_PROJECT_ID'])
            ->setKey($_ENV['APPWRITE_APP_KEY']);


        $this->fileStorage = new Storage($client);

        $this->bucketId = $_ENV['APPWRITE_BUCKET_ID'];
    }

    public function uploadPath(string $path, $fileId = null): string
    {
        $inputFile = InputFile::withPath($path);


        return $this->upload($inputFile, $fileId);
    }

    public function uploadData(string $data, $fileId = null): string
    {
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
        $url = $_ENV['APPWRITE_URL'];
        $projectId = $_ENV['APPWRITE_PROJECT_ID'];
        return sprintf("https://%s/v1/storage/buckets/%s/files/%s/download?project=%s", $url, $this->bucketId, $id, $projectId);
    }
}