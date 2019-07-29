<?php

namespace App\Service;

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Component\Filesystem\Filesystem;

class FileDownloader
{
    private $targetDirectory;
    private $filesystem;

    public function __construct(string $targetDirectory, Filesystem $filesystem)
    {
        $this->targetDirectory = $targetDirectory;
        $this->filesystem = $filesystem;

        $this->httpClient = HttpClient::create();
    }

    public function getTargetDirectory()
    {
        return $this->targetDirectory;
    }

    public function download(string $source, string $destination) : string
    {
        $destination = $destination ?? $this->getTargetDirectory();
        $this->filesystem->mkdir($destination, 0700);

        $fileName = $destination . $this->getFilename($source);

        $response = $this->httpClient->request('GET', $source, ['buffer' => true]);
        $fileHandler = fopen($fileName, 'w');

        foreach ($this->httpClient->stream($response) as $chunk) {
            fwrite($fileHandler, $chunk->getContent());
        }

        return $fileName;
    }

    protected function getFilename(string $source) : string
    {
        $file = new \SplFileInfo($source);
        $originalFilename = pathinfo($file->getFilename(), PATHINFO_FILENAME);
        $safeFilename = transliterator_transliterate('Any-Latin; Latin-ASCII; [^A-Za-z0-9_] remove; Lower()', $originalFilename);
        $fileName = $safeFilename.'.'.$file->getExtension();

        return $fileName;
    }
}