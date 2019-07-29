<?php

namespace App\Service\Parser;

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