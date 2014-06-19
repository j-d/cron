<?php

namespace Cron\CronBundle\Entity;

use Doctrine\ORM\EntityRepository;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManager;

class PendingEmailRepository extends EntityRepository
{
    /**
     * @param EntityManager|ObjectManager $em The EntityManager to use.
     */
    function __construct($em)
    {
        $metadata = $em->getClassMetadata('CronBundle:PendingEmail');

        parent::__construct($em, $metadata);
    }

    /**
     * @return PendingEmail[]
     */
    public function findExpiredEmails()
    {
        $em = $this->getEntityManager();

        $query = $em->createQuery(
            '
            SELECT e'.'
            FROM CronBundle:PendingEmail e
            WHERE
                e.expires < :date
            '
        );

        $query->setParameter('date', new \DateTime());

        return $query->getResult();
    }

    /**
     * @param int $maxResults
     *
     * @return PendingEmail[]
     */
    public function findNextEmails($maxResults = 1)
    {
        $em = $this->getEntityManager();

        $query = $em->createQuery(
            '
            SELECT e'.'
            FROM CronBundle:PendingEmail e
            WHERE
                e.expires > :now AND
                e.notBefore IS NULL OR e.notBefore < :now
            ORDER BY
                e.priority DESC,
                e.expires ASC,
                e.added
            '
        );

        $query->setParameter('now', new \DateTime());

        $query->setMaxResults($maxResults);

        return $query->getResult();
    }
}