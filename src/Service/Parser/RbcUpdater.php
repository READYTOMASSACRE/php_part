<?php

namespace App\Service\Parser;

use App\Entity\News;
use App\Repository\NewsRepository;
use Doctrine\ORM\EntityManagerInterface;

class RbcUpdater
{
    /**
     * @var App\Service\Parser\RbcParser
     */
    private $parser;

    /**
     * @var \App\Repository\NewsRepository
     */
    private $repository;

    /**
     * @var \Doctrine\ORM\EntityManagerInterface
     */
    private $em;

    /**
     * @param string $targetDirectory destination on server
     * @param string $dbDirectory destination for db
     * @param \App\Service\FileDownloader $fileDownloader
     */
    public function __construct(
        RbcParser $parser,
        NewsRepository $repository,
        EntityManagerInterface $em
    ){
        $this->parser = $parser;
        $this->repository = $repository;
        $this->em = $em;
    }

    public function getParser() : RbcParser
    {
        return $this->parser;
    }

    public function getData(string $source) : void
    {
        $payload = $this->parser->getPayload($source);

        $this->saveData($payload);
    }

    /**
     * Save data from $items
     * 
     * @param array $items
     * @param int $limit
     * 
     * @return News[] Returns an array of News objects
     */
    protected function saveData(array $items, int $limit = 15) : self
    {
        $ids = array_map(function ($item) {
            return $item['externalId'];
        }, $items);

        $fetchedList = $this->repository->findBy(['externalId' => $ids], ['publishDate' => 'desc'], $limit);

        $fetchedList = array_reduce($fetchedList, function ($carry, $item) {
            $carry[$item->getExternalId()] = $item;
            return $carry;
        }, []);


        $payload = [];
        foreach ($items as $item) {
            $newsItem = $fetchedList[$item['externalId']] ?? new News();

            $newsItem
                ->setTitle($item['title'])
                ->setTag($item['tag'])
                ->setPublishDate($item['publishDate'])
                ->setHref($item['href'])
                ->setDescription($item['description'])
                ->setImage($item['image'])
                ->setExternalId($item['externalId']);

            $payload[] = $newsItem;
        }

        foreach ($payload as $item) $this->em->persist($item);

        $this->em->flush();

        return $this;
    }
}