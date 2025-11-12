<?php

namespace LukeWaite\LaravelQueueAwsBatch\Tests;

use LukeWaite\LaravelQueueAwsBatch\Exceptions\UnsupportedException;
use LukeWaite\LaravelQueueAwsBatch\Jobs\BatchJob;
use Mockery\Adapter\Phpunit\MockeryTestCase as TestCase;
use Mockery as m;
use Mockery\MockInterface;

class BatchJobTest extends TestCase
{
    protected \stdClass $job;

    protected MockInterface $batchQueue;

    protected BatchJob $batchJob;

    protected function setUp(): void
    {
        parent::setUp();

        $this->job = new \stdClass();
        $this->job->payload = '{"job":"foo","data":["data"]}';
        $this->job->id = 4;
        $this->job->queue = 'default';
        $this->job->attempts = 1;

        $this->batchQueue = m::mock('LukeWaite\LaravelQueueAwsBatch\Queues\BatchQueue');

        $this->batchJob = new BatchJob(
            new \Illuminate\Container\Container(),
            $this->batchQueue,
            $this->job,
            'testConnection',
            'defaultQueue',
        );
    }

    public function testReleaseDoesntDeleteButDoesUpdate()
    {
        $this->batchQueue->shouldReceive('release')->once();
        $this->batchQueue->shouldNotReceive('deleteReserved');

        $this->batchJob->release(0);
    }

    public function testThrowsExceptionOnReleaseWIthDelay()
    {
        $this->expectException(UnsupportedException::class);
        $this->expectExceptionMessage('The BatchJob does not support releasing back onto the queue with a delay');

        $this->batchQueue->shouldNotReceive('release');
        $this->batchQueue->shouldNotReceive('deleteReserved');

        $this->batchJob->release(10);
    }
}
