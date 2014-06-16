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
        $em = $this->getContainer()->get('doctrine')->getEntityManager();

        $jobRepository = new JobRepository($em);

        $expiredJobs = $jobRepository->findExpiredJobs();

        foreach ($expiredJobs as $expiredJob) {
            if (null !== $expiredJob->getRepeat()) {
                $em->persist(new Log($expiredJob, true));
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

        $nextJobs = $jobRepository->findNextJobs(5);

        foreach ($nextJobs as $job) {
            if (null !== $job->getRepeat()) {
                $repeatedJob = $this->getRepeatedJob($job);

                $em->persist($repeatedJob);
                $em->flush();
            }

            $em->persist(new Log($job));
            $em->remove($job);
            $em->flush();

            $this->addLogLine(
                sprintf(
                    '%s > Processing %s',
                    date('Y-m-d H:i:s'),
                    $job->getName()
                )
            );

            $this->subCommand($output, $job->getScript());
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
            : strtotime($originalJob->getNotBefore());

        $delta = strtotime($originalJob->getRepeat(), $notBefore) - $notBefore;

        $now = time();

        $nextTime = $notBefore + $delta;

        while ($nextTime < $now) {
            $nextTime += $delta; // Crashing risk!
        }

        $nextExpire = null !== $originalJob->getExpires()
            ? $nextTime + abs(strtotime($notBefore) - strtotime($notBefore))
            : null;

        return new Job(
            $originalJob->getName(),
            $originalJob->getScript(),
            $originalJob->getPriority(),
            $notBefore,
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
        echo $line . "\n";
    }
}
