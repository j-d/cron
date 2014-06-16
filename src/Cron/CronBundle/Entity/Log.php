<?php

namespace Cron\CronBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Logs
 *
 * @ORM\Table(name="logs", indexes={@ORM\Index(name="link", columns={"link"})})
 * @ORM\Entity
 */
class Log
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="jobId", type="bigint", nullable=false)
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $jobId;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=50, nullable=false)
     */
    private $name;

    /**
     * @var boolean
     *
     * @ORM\Column(name="priority", type="boolean", nullable=false)
     */
    private $priority;

    /**
     * @var string
     *
     * @ORM\Column(name="repeat", type="string", length=255, nullable=false)
     */
    private $repeat;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="due", type="datetime", nullable=true)
     */
    private $due;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="expired", type="datetime", nullable=true)
     */
    private $expired = null;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="performed", type="datetime", nullable=true)
     */
    private $performed = null;

    /**
     * @var string
     *
     * @ORM\Column(name="link", type="string", length=255, nullable=true)
     */
    private $link = null;

    public function __construct(Job $job, $expired = false)
    {
        $this->jobId    = $job->getId();
        $this->name     = $job->getName();
        $this->priority = $job->getPriority();
        $this->repeat   = $job->getRepeat();
        $this->due      = $job->getExpires();
        $this->link     = $job->getLink();

        if ($expired) {
            $this->expired = new \DateTime();
        } else {
            $this->performed = new \DateTime();
        }
    }
}
