<?php

namespace Cron\CronBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Archived
 *
 * @ORM\Table(name="email_archived")
 * @ORM\Entity
 */
class ArchivedEmail
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="bigint", nullable=false)
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var boolean
     *
     * @ORM\Column(name="priority", type="boolean", nullable=false)
     */
    private $priority = PendingEmail::PRIORITY_MEDIUM;

    /**
     * @var string
     *
     * @ORM\Column(name="from", type="text", nullable=false)
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
     * @ORM\Column(name="callback", type="text", nullable=false)
     */
    private $callback;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="sent", type="datetime", nullable=true)
     */
    private $sent;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="expired", type="datetime", nullable=true)
     */
    private $expired;

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

    public function __construct(
        $from,
        $fromEmail,
        $destination,
        $subject,
        $textPlain,
        $textHtml = null,
        $priority = PendingEmail::PRIORITY_MEDIUM,
        $cc = null,
        $bcc = null,
        $notBefore = null,
        $expires = null,
        $expired = null,
        $sent = null,
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
        $this->sent        = $sent;
        $this->expired     = $expired;
        $this->notBefore   = $notBefore;
        $this->expires     = $expires;
        $this->callback    = $callback;
        $this->attachments = $attachments;
        $this->embedded    = $embedded;
    }
}
