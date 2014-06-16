<?php

namespace Cron\CronBundle\Entity;

use Doctrine\ORM\EntityRepository;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManager;

class JobRepository extends EntityRepository
{
    /**
     * @param EntityManager|ObjectManager $em The EntityManager to use.
     */
    function __construct($em)
    {
        $metadata = $em->getClassMetadata('CronBundle:Job');

        parent::__construct($em, $metadata);
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
                j.expires > :now AND
                j.notBefore IS NULL OR j.notBefore < :now
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