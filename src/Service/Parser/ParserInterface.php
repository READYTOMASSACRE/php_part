<?php

namespace App\Service\Parser;

use App\Entity\News;
use Symfony\Contracts\HttpClient\ResponseInterface;

interface ParserInterface
{
    /**
     * @return array
     */
    public function getPayload(string $source) : array;
}