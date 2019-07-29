<?php

namespace App\Repository;

use App\Entity\News;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method News|null find($id, $lockMode = null, $lockVersion = null)
 * @method News|null findOneBy(array $criteria, array $orderBy = null)
 * @method News[]    findAll()
 * @method News[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class NewsRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, News::class);
    }

    /**
     * @return News[] Returns an array of News objects
     */
    public function createOrUpdateByExternalId(array $items) : iterable
    {
        $ids = array_map(function ($item) {
            return $item['externalId'];
        }, $items);

        $fetchedList = $this->findBy(['externalId' => $ids]);

        $fetchedList = array_reduce($fetchedList, function ($carry, $item) {
            $carry[$item->getExternalId()] = $item;
            return $carry;
        }, []);

        foreach ($items as $item) {
            $newsItem = $fetchedList[$item['externalId']] ?? new News();
            $newsItem->applyData($item);

            $payload[] = $newsItem;
        }

        $entityManager = $this->getEntityManager();
        foreach ($payload as $item) $entityManager->persist($item);

        $entityManager->flush();

        return $payload;
    }
}
