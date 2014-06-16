<?php

namespace Cron\CronBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Jobs
 *
 * @ORM\Table(name="cron_jobs", indexes={@ORM\Index(name="link", columns={"link"})})
 * @ORM\Entity
 */
class Job
{
    const PRIORITY_LOW         = 1;
    const PRIORITY_MEDIUM_LOW  = 2;
    const PRIORITY_MEDIUM      = 3;
    const PRIORITY_MEDIUM_HIGH = 4;
    const PRIORITY_HIGH        = 5;

    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="bigint", nullable=false)
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=50, nullable=false)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="script", type="string", length=255, nullable=false)
     */
    private $script;

    /**
     * @var int
     *
     * @ORM\Column(name="priority", type="integer", nullable=false)
     */
    private $priority = self::PRIORITY_MEDIUM;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="added", type="datetime", nullable=false)
     */
    private $added;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="notBefore", type="datetime", nullable=true)
     */
    private $notBefore = null;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="expires", type="datetime", nullable=true)
     */
    private $expires = null;

    /**
     * @var string
     *
     * @ORM\Column(name="repeatAt", type="string", length=255, nullable=true)
     */
    private $repeat = null;

    /**
     * @var string
     *
     * @ORM\Column(name="link", type="string", length=255, nullable=true)
     */
    private $link = null;

    public function __construct($name, $script, $priority, $notBefore, $expires, $repeat, $link)
    {
        $this->name      = $name;
        $this->script    = $script;
        $this->priority  = $priority;
        $this->notBefore = $notBefore;
        $this->expires   = $expires;
        $this->repeat    = $repeat;
        $this->link      = $link;
        $this->added     = new \DateTime();
    }

    /**
     * @param \DateTime $added
     *
     * @return $this
     */
    public function setAdded(\DateTime $added)
    {
        $this->added = $added;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getAdded()
    {
        return $this->added;
    }

    /**
     * @param \DateTime $expires
     *
     * @return $this
     */
    public function setExpires($expires)
    {
        $this->expires = $expires;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getExpires()
    {
        return $this->expires;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $link
     *
     * @return $this
     */
    public function setLink($link)
    {
        $this->link = $link;

        return $this;
    }

    /**
     * @return string
     */
    public function getLink()
    {
        return $this->link;
    }

    /**
     * @param string $name
     *
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param \DateTime $notBefore
     */
    public function setNotBefore($notBefore)
    {
        $this->notBefore = $notBefore;
    }

    /**
     * @return \DateTime
     */
    public function getNotBefore()
    {
        return $this->notBefore;
    }

    /**
     * @param boolean $priority
     */
    public function setPriority($priority)
    {
        $this->priority = $priority;
    }

    /**
     * @return int
     */
    public function getPriority()
    {
        return $this->priority;
    }

    /**
     * @param string $repeat
     */
    public function setRepeat($repeat)
    {
        $this->repeat = $repeat;
    }

    /**
     * @return string|null
     */
    public function getRepeat()
    {
        return $this->repeat;
    }

    /**
     * @param string $script
     */
    public function setScript($script)
    {
        $this->script = $script;
    }

    /**
     * @return string
     */
    public function getScript()
    {
        return $this->script;
    }
}
