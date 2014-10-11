<?php

namespace Cron\CronBundle\Command;

use CCR\Core\System;
use Common\Bundle\DevBundle\Command\CommonCommand;

use Cron\CronBundle\Entity\ArchivedEmail;
use Cron\CronBundle\Entity\PendingEmailRepository;
use Doctrine\ORM\EntityManager;
use PHPMailer;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ProcessEmailQueueCommand extends CommonCommand
{
    protected function configure()
    {
        $this
            ->setName('cron:email:run')
            ->setDescription('This command will process the email queue jobs');
    }

    private function right($string, $long)
    {
        return substr($string, sizeof($string) - $long - 1, $long);
    }

    private function rightFrom($string, $ch)
    {
        if (strpos($string, $ch) === false) {
            return $string;
        } else {
            return $this->right($string, strlen($string) - strpos($string, $ch) - strlen($ch));
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var EntityManager $em */
        $em = $this->getContainer()->get('doctrine')->getManager();

        $this->removeExpiredEmails();

        $pendingEmailRepository = new PendingEmailRepository($em);

        $invalidEmails = $pendingEmailRepository->findBy(array('destination' => ''));

        foreach ($invalidEmails as $invalidEmail) {
            $em->remove($invalidEmail);
        }

        $em->flush();

        $nextEmails = $pendingEmailRepository->findNextEmails(5);

        foreach ($nextEmails as $email) {
            $mail = new PHPMailer();

            $smtpConnectionDetails = System::getSystem()->getConfiguration()->getSMTPConnectionDetails();

            $mail->Priority = $email->getPriority() >= 4;
            $mail->IsSMTP();
            $mail->Host     = $smtpConnectionDetails['host'];
            $mail->Port     = $smtpConnectionDetails['port'];
            $mail->From     = $email->getFromEmail();
            $mail->FromName = $email->getFrom();

            if ($email->getDestination() != '') {
                foreach (explode('|', $email->getDestination()) as $dest) {
                    $mail->AddAddress($dest);
                }
            }

            if (null != $email->getCc()) {
                foreach (explode('|', $email->getCc()) as $el) {
                    $mail->AddCC($el);
                }
            }

            if (null != $email->getBcc()) {
                foreach (explode('|', $email->getBcc()) as $el) {
                    $mail -> AddBCC($el);
                }
            }

            if (null != $email->getAttachments()) {
                foreach (explode('|', $email->getAttachments()) as $el) {
                    if (file_exists($el)) {
                        $mail -> AddAttachment($el, $this->rightFrom($el, 'emailTEMP_'));
                    }
                }
            }

            $mail->WordWrap = 50;
            $mail->IsHTML(true);

            if (null != $email->getEmbedded()) {
                foreach (explode('|', $email->getEmbedded()) as $pic) {
                    $filename = end(explode('/', $pic));
                    $mail->AddEmbeddedImage($pic, $filename);
                }
            }

            $mail->Subject = $email->getSubject();
            $mail->Body    = $email->getTextHtml();
            $mail->AltBody = $email->getTextPlain();

            if (!$mail->Send()) {
                echo $mail->ErrorInfo;

                $email->increaseAttempts();

                if ($email->getAttempts() < 10) {
                    $em->persist($email);
                } else {
                    $em->remove($email);
                }

                $em->flush();
                continue;
            } else {
                $em->persist(
                    new ArchivedEmail(
                        $email->getFrom(),
                        $email->getFromEmail(),
                        $email->getDestination(),
                        $email->getSubject(),
                        $email->getTextPlain(),
                        $email->getTextHtml(),
                        $email->getPriority(),
                        $email->getCc(),
                        $email->getBcc(),
                        $email->getNotBefore(),
                        $email->getExpires(),
                        null,
                        new \DateTime(),
                        $email->getCallback(),
                        $email->getAttachments(),
                        $email->getEmbedded()
                    )
                );
            }

            if (null != $email->getAttachments()) {
                foreach (explode('|', $email->getAttachments()) as $el) {
                    if (file_exists($el)) {
                        unlink($el);
                    }
                }
            }

            $mail->ClearAddresses();

            $output->writeln(
                sprintf(
                    '%s > Email sent Id: %s To: %s  ',
                    date('Y-m-d H:i:s'),
                    $email->getId(),
                    $email->getDestination()
                )
            );

            $em->remove($email);
            $em->flush();
        }
    }

    private function removeExpiredEmails()
    {
        /** @var EntityManager $em */
        $em = $this->getContainer()->get('doctrine')->getManager();

        $pendingEmailRepository = new PendingEmailRepository($em);

        $expiredEmails = $pendingEmailRepository->findExpiredEmails();

        foreach ($expiredEmails as $expiredEmail) {
            $em->persist(
                new ArchivedEmail(
                    $expiredEmail->getFrom(),
                    $expiredEmail->getFromEmail(),
                    $expiredEmail->getDestination(),
                    $expiredEmail->getSubject(),
                    $expiredEmail->getTextPlain(),
                    $expiredEmail->getTextHtml(),
                    $expiredEmail->getPriority(),
                    $expiredEmail->getCc(),
                    $expiredEmail->getBcc(),
                    $expiredEmail->getNotBefore(),
                    $expiredEmail->getExpires(),
                    new \DateTime(),
                    null,
                    $expiredEmail->getCallback(),
                    $expiredEmail->getAttachments(),
                    $expiredEmail->getEmbedded()
                )
            );

            $em->remove($expiredEmail);
        }

        $em->flush();
    }
}
