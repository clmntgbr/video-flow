<?php

namespace App\Repository;

use App\Entity\MediaPod;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MediaPod>
 */
class MediaPodRepository extends AbstractRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MediaPod::class);
    }

    public function create(array $data): MediaPod
    {
        $entity = new MediaPod();

        /** @var MediaPod $entity */
        $entity = $this->update($entity, $data);

        return $entity;
    }
}
