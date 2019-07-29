<?php

namespace App\Service\Parser;

use App\Entity\News;
use Symfony\Contracts\HttpClient\ResponseInterface;

interface ParserInterface
{
    /**
     * Returns payload from $source
     * 
     * @param string $source
     * 
     * @return array Returns payload
     */
    public function getPayload(string $source) : array;
}