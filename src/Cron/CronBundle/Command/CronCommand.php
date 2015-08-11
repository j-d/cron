<?php

namespace Cron\CronBundle\Command;

use Common\Bundle\DevBundle\Command\CommonCommand;

use Cron\CronBundle\Entity\Job;
use Cron\CronBundle\Entity\JobRepository;
use Cron\CronBundle\Entity\Log;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CronCommand extends CommonCommand
{
    protected function configure()
    {
        $this
            ->setName('cron:run')
            ->setDescription('This command will process the cron jobs');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var EntityManager $em */
        $em = $this->getContainer()->get('doctrine')->getManager();

        $jobRepository = new JobRepository($em);

        $this->removeExpiredJobs();

        $nextJobs = $jobRepository->findNextJobs(5);

        foreach ($nextJobs as $job) {
            $em->getConnection()->beginTransaction();

            if (null !== $job->getRepeat()) {
                $em->persist($this->getRepeatedJob($job));
            }

            $em->persist(new Log($job));
            $em->remove($job);
            $em->flush();

            $em->getConnection()->commit();

            $this->addLogLine(
                sprintf(
                    '%s > Processing %s',
                    date('Y-m-d H:i:s'),
                    $job->getName()
                )
            );

            try {
                $this->processCommand($output, $job->getScript());
            } catch (\Exception $caught) {
                // Something went wrong, but continue ... 
            }
        }
    }

    /**
     * @param Job $originalJob
     *
     * @return Job
     */
    private function getRepeatedJob(Job $originalJob) {
        $notBefore = null === $originalJob->getNotBefore()
            ? time()
            : $originalJob->getNotBefore()->getTimestamp();

        $delta = strtotime($originalJob->getRepeat(), $notBefore) - $notBefore;

        $now = time();

        $nextTime = $notBefore + $delta;

        while ($nextTime < $now) {
            $nextTime += $delta; // Crashing risk!
        }

        $nextTimeObject = new \DateTime();
        $nextTimeObject->setTimestamp($nextTime);

        if (null !== $originalJob->getExpires())  {
            $nextExpire = new \DateTime();

            // Expiring jobs
            if (null === $originalJob->getNotBefore()) {
                $nextExpire->setTimestamp($nextTime + abs($originalJob->getAdded()->getTimestamp() - $originalJob->getExpires()->getTimestamp()));
            } else {
                $nextExpire->setTimestamp($nextTime + abs($originalJob->getNotBefore()->getTimestamp() - $originalJob->getExpires()->getTimestamp()));
            }
        } else {
            $nextExpire = null;
        }

        return new Job(
            $originalJob->getName(),
            $originalJob->getScript(),
            $originalJob->getPriority(),
            $nextTimeObject,
            $nextExpire,
            $originalJob->getRepeat(),
            $originalJob->getLink()
        );
    }

    /**
     * Add a line to the log
     *
     * @param string $line
     */
    private function  addLogLine($line)
    {
        // TODO: Change to log
        
        echo $line . "\n";
    }

    /**
     * Removes the jobs that have expired, creating a new one for the recurring ones
     */
    private function removeExpiredJobs()
    {
        /** @var EntityManager $em */
        $em = $this->getContainer()->get('doctrine')->getManager();

        $jobRepository = new JobRepository($em);

        $expiredJobs = $jobRepository->findExpiredJobs();

        foreach ($expiredJobs as $expiredJob) {
            if (null !== $expiredJob->getRepeat()) {
                $em->persist(new Log($expiredJob, true));
                $em->persist($this->getRepeatedJob($expiredJob));
            }

            $em->remove($expiredJob);

            $this->addLogLine(
                sprintf(
                    '%s > Expired Job %s - Due %s',
                    date('Y-m-d H:i:s'),
                    $expiredJob->getName(),
                    $expiredJob->getExpires()->format('Y-m-d H:i:s')
                )
            );
        }

        $em->flush();
    }

    /**
     * @param OutputInterface $output
     * @param string          $script
     */
    private function processCommand(OutputInterface $output, $script)
    {
        if ('http' === substr($script, 0, 4)) {
            $context = stream_context_create(
                array(
                    'http' => array(
                        'timeout' => 20 * 60
                    ),
                    'https' => array(
                        'timeout' => 20 * 60
                    )
                )
            );

            $lines = file($script, 0, $context);

            if (is_array($lines)) {
                foreach ($lines as $line) {
                    $this->addLogLine($line);
                }
            }
        } else {
            $commandDetails = explode(' ', $script, 2);
            $command        = isset($commandDetails[0]) ? $commandDetails[0] : $script;
            $allOptions     = isset($commandDetails[1]) ? explode('--', $commandDetails[1]) : array();
            $options        = array();

            foreach ($allOptions as $option) {
                if ('' === $option) {
                    continue;
                }

                $optionDetails = explode('=', $option, 2);

                if (1 === count($optionDetails)) {
                    // It could also be a space
                    $optionDetails = explode(' ', $option, 2);
                }

                if (1 < count($optionDetails)) {
                    $options[$optionDetails[0]] = $optionDetails[1];
                } else {
                    $options[$optionDetails[0]] = true;
                }
            }

            $this->subCommand($output, $command, array(), $options);
        }
    }
}
