<?php

namespace Cron\CronBundle\Repository;

use Cron\CronBundle\Entity\Job;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class JobRepository extends ServiceEntityRepository
{
    function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Job::class);
    }

    /**
     * @return Job[]
     */
    public function findExpiredJobs()
    {
        $em = $this->getEntityManager();

        $query = $em->createQuery(
            '
            SELECT j'.'
            FROM CronBundle:Job j
            WHERE
                j.expires < :date
            '
        );

        $query->setParameter('date', new \DateTime());

        return $query->getResult();
    }

    /**
     * @param int $maxResults
     *
     * @return Job[]
     */
    public function findNextJobs($maxResults = 1)
    {
        $em = $this->getEntityManager();

        $query = $em->createQuery(
            '
            SELECT j'.'
            FROM CronBundle:Job j
            WHERE
                (j.expires IS NULL OR j.expires > :now) AND
                (j.notBefore IS NULL OR j.notBefore < :now)
            ORDER BY
                j.priority DESC,
                j.expires ASC,
                j.added
            '
        );

        $query->setParameter('now', new \DateTime());

        $query->setMaxResults($maxResults);

        return $query->getResult();
    }
}