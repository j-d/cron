<?php

namespace Cron\CronBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Pending
 *
 * @ORM\Table(name="email_pending", indexes={@ORM\Index(name="added", columns={"added", "expires"}), @ORM\Index(name="notbefore", columns={"notBefore"})})
 * @ORM\Entity
 */
class PendingEmail
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
     * @var int
     *
     * @ORM\Column(name="priority", type="integer", nullable=false)
     */
    private $priority = self::PRIORITY_MEDIUM;

    /**
     * @var string
     *
     * @ORM\Column(name="fromName", type="text", nullable=false)
     */
    private $from;

    /**
     * @var string
     *
     * @ORM\Column(name="fromEmail", type="text", nullable=false)
     */
    private $fromEmail;

    /**
     * @var string
     *
     * @ORM\Column(name="dest", type="text", nullable=false)
     */
    private $destination;

    /**
     * @var string
     *
     * @ORM\Column(name="cc", type="text", nullable=true)
     */
    private $cc;

    /**
     * @var string
     *
     * @ORM\Column(name="bcc", type="text", nullable=true)
     */
    private $bcc;

    /**
     * @var string
     *
     * @ORM\Column(name="subject", type="text", nullable=false)
     */
    private $subject;

    /**
     * @var string
     *
     * @ORM\Column(name="textHTML", type="text", nullable=false)
     */
    private $textHtml;

    /**
     * @var string
     *
     * @ORM\Column(name="textPlain", type="text", nullable=false)
     */
    private $textPlain;

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
    private $notBefore;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="expires", type="datetime", nullable=true)
     */
    private $expires;

    /**
     * @var string
     *
     * @ORM\Column(name="callback", type="text", nullable=true)
     */
    private $callback;

    /**
     * @var string
     *
     * @ORM\Column(name="attachments", type="text", nullable=true)
     */
    private $attachments;

    /**
     * @var string
     *
     * @ORM\Column(name="embedded", type="text", nullable=true)
     */
    private $embedded;

    /**
     * @var int
     *
     * @ORM\Column(name="attempts", type="integer", nullable=true)
     */
    private $attempts = 0;

    public function __construct(
        $from,
        $fromEmail,
        $destination,
        $subject,
        $textPlain,
        $textHtml = null,
        $priority = self::PRIORITY_MEDIUM,
        $cc = null,
        $bcc = null,
        $notBefore = null,
        $expires = null,
        $callback = null,
        $attachments = null,
        $embedded = null
    ) {
        $this->from        = $from;
        $this->fromEmail   = $fromEmail;
        $this->destination = $destination;
        $this->subject     = $subject;
        $this->textPlain   = $textPlain;

        if (null === $this->textHtml) {
            $this->textHtml = $textPlain;
        }

        $this->priority    = $priority;
        $this->cc          = $cc;
        $this->bcc         = $bcc;
        $this->added       = new \DateTime();
        $this->notBefore   = $notBefore;
        $this->expires     = $expires;
        $this->callback    = $callback;
        $this->attachments = $attachments;
        $this->embedded    = $embedded;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return \DateTime
     */
    public function getAdded()
    {
        return $this->added;
    }

    /**
     * @return string
     */
    public function getAttachments()
    {
        return $this->attachments;
    }

    /**
     * @return string
     */
    public function getBcc()
    {
        return $this->bcc;
    }

    /**
     * @return string
     */
    public function getCallback()
    {
        return $this->callback;
    }

    /**
     * @return string
     */
    public function getCc()
    {
        return $this->cc;
    }

    /**
     * @return string
     */
    public function getDestination()
    {
        return $this->destination;
    }

    /**
     * @return string
     */
    public function getEmbedded()
    {
        return $this->embedded;
    }

    /**
     * @return \DateTime
     */
    public function getExpires()
    {
        return $this->expires;
    }

    /**
     * @return string
     */
    public function getFrom()
    {
        return $this->from;
    }

    /**
     * @return string
     */
    public function getFromEmail()
    {
        return $this->fromEmail;
    }

    /**
     * @return \DateTime
     */
    public function getNotBefore()
    {
        return $this->notBefore;
    }

    /**
     * @return int
     */
    public function getPriority()
    {
        return $this->priority;
    }

    /**
     * @return string
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * @return string
     */
    public function getTextHtml()
    {
        return $this->textHtml;
    }

    /**
     * @return string
     */
    public function getTextPlain()
    {
        return $this->textPlain;
    }

    public function increaseAttempts()
    {
        $this->attempts++;
    }

    /**
     * @return int
     */
    public function getAttempts()
    {
        return $this->attempts;
    }
}
