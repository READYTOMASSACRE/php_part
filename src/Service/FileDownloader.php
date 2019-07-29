<?php

namespace App\Service;

use Symfony\Component\HttpClient\HttpClient;
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

    /**
     * Returns default file directory
     * 
     * @return string
     */
    public function getTargetDirectory() : string
    {
        return $this->targetDirectory;
    }

    /**
     * Download file from $source
     * 
     * @param string $source
     * @param string|null $destination
     * 
     * @return string Returns destination path on server
     */
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

    /**
     * Get safe filname from $source
     * 
     * @param string $source
     * 
     * @return string Returns filename
     */
    protected function getFilename(string $source) : string
    {
        $file = new \SplFileInfo($source);
        $originalFilename = pathinfo($file->getFilename(), PATHINFO_FILENAME);
        $safeFilename = transliterator_transliterate('Any-Latin; Latin-ASCII; [^A-Za-z0-9_] remove; Lower()', $originalFilename);
        $fileName = $safeFilename.'.'.$file->getExtension();

        return $fileName;
    }
}