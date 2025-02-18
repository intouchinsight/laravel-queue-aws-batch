<?php

/**
 * Laravel Queue for AWS Batch.
 *
 * @author    Luke Waite <lwaite@gmail.com>
 * @copyright 2017 Luke Waite
 * @license   http://www.opensource.org/licenses/mit-license.php MIT
 *
 * @link      https://github.com/lukewaite/laravel-queue-aws-batch
 */

namespace LukeWaite\LaravelQueueAwsBatch\Console;

use Illuminate\Console\Command;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Foundation\Exceptions\Handler;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\QueueManager;
use Illuminate\Queue\Worker;
use Illuminate\Queue\WorkerOptions;
use LukeWaite\LaravelQueueAwsBatch\Exceptions\JobNotFoundException;
use LukeWaite\LaravelQueueAwsBatch\Exceptions\UnsupportedException;
use LukeWaite\LaravelQueueAwsBatch\Queues\BatchQueue;

class QueueWorkBatchCommand extends Command
{
    protected $name = 'queue:work-batch';

    protected $description = 'Run a Job for the AWS Batch queue';

    protected $signature = 'queue:work-batch
                            {connection : The name of the queue connection to work}
                            {job_id : The job id in the database}
                            {--memory=128 : The memory limit in megabytes}
                            {--timeout=60 : The number of seconds a child process can run}
                            {--force : Force the worker to run even in maintenance mode}
                            {--tries= : Number of times to attempt a job before logging it failed}';

    protected $manager;

    protected $exceptions;

    protected $worker;

    protected $cache;

    public function __construct(QueueManager $manager, Worker $worker, Handler $exceptions, Cache $cache)
    {
        parent::__construct();

        $this->manager = $manager;
        $this->worker = $worker;
        $this->exceptions = $exceptions;
        $this->cache = $cache;
    }

    public function handle()
    {
        $this->laravel['events']->listen(JobFailed::class, function ($event) {
            $this->logFailedJob($event);
        });

        try {
            $this->runJob();
        } catch (\Throwable $e) {
            $this->exceptions->report($e);
            throw $e;
        }
    }

    // TOOD: Refactor out the logic here into an extension of the Worker class
    protected function runJob()
    {
        $connectionName = $this->argument('connection');
        $jobId = $this->argument('job_id');

        /** @var BatchQueue $connection */
        $connection = $this->manager->connection($connectionName);

        if (!$connection instanceof BatchQueue) {
            throw new UnsupportedException('queue:work-batch can only be run on batch queues');
        }

        $job = $connection->getJobById($jobId);

        // If we're able to pull a job off of the stack, we will process it and
        // then immediately return back out.
        if (!is_null($job)) {
            $this->worker->process(
                $this->manager->getName($connectionName),
                $job,
                $this->gatherWorkerOptions()
            );

            return;
        }

        // If we hit this point, we haven't processed our job
        throw new JobNotFoundException('No job was returned');
    }

    /**
     * Gather all of the queue worker options as a single object.
     *
     * @return \Illuminate\Queue\WorkerOptions
     */
    protected function gatherWorkerOptions()
    {
        return new WorkerOptions(
            name: $this->argument('connection'),
            memory: $this->option('memory'),
            timeout: $this->option('timeout'),
            maxTries: $this->option('tries'),
        );
    }

    /**
     * Store a failed job event.
     *
     * @param  \Illuminate\Queue\Events\JobFailed  $event
     * @return void
     */
    protected function logFailedJob(JobFailed $event)
    {
        $this->laravel['queue.failer']->log(
            $event->connectionName,
            $event->job->getQueue(),
            $event->job->getRawBody(),
            $event->exception
        );
    }
}
