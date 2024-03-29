<?php

namespace Cron\CronBundle\Command;

use Cron\CronBundle\Entity\Job;
use Cron\CronBundle\Entity\Log;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\PhpExecutableFinder;

class CronCommand extends Command
{
    /**
     * @var EntityManagerInterface
     */
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('cron:run')
            ->setDescription('This command will process the cron jobs');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $jobRepository = $this->em->getRepository(Job::class);

        $this->removeExpiredJobs();

        $nextJobs = $jobRepository->findNextJobs(5);

        foreach ($nextJobs as $job) {
            $this->em->getConnection()->beginTransaction();

            if (null !== $job->getRepeat()) {
                $this->em->persist($this->getRepeatedJob($job));
            }

            $this->em->persist(new Log($job));
            $this->em->remove($job);
            $this->em->flush();

            $this->em->getConnection()->commit();

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

        return 0;
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
        $jobRepository = $this->em->getRepository(Job::class);

        $expiredJobs = $jobRepository->findExpiredJobs();

        foreach ($expiredJobs as $expiredJob) {
            if (null !== $expiredJob->getRepeat()) {
                $this->em->persist(new Log($expiredJob, true));
                $this->em->persist($this->getRepeatedJob($expiredJob));
            }

            $this->em->remove($expiredJob);

            $this->addLogLine(
                sprintf(
                    '%s > Expired Job %s - Due %s',
                    date('Y-m-d H:i:s'),
                    $expiredJob->getName(),
                    $expiredJob->getExpires()->format('Y-m-d H:i:s')
                )
            );
        }

        $this->em->flush();
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
            $php = new PhpExecutableFinder();

            $process = new Process(
                sprintf(
                    '%s %s %s %s',
                    'WIN' === strtoupper(substr(PHP_OS, 0, 3))? '' : 'exec',
                    $php->find(),
                    $_SERVER['argv'][0],
                    $script
                )
            );
            $process->setTimeout(0);
            $process->run();
            $output->write($process->getOutput());
            $output->write($process->getErrorOutput());
        }
    }
}
