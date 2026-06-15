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

namespace LukeWaite\LaravelQueueAwsBatch\Contracts;

/**
 * Should return an array representing the contents of the ecsPropertiesOverride
 * property documented in the AWS Batch SubmitJob API reference.
 *
 * Use this interface for jobs submitted to Fargate-based job queues.
 * For EC2-based job queues, use JobContainerOverrides instead.
 *
 * In the event of no overrides, should return null.
 *
 * https://docs.aws.amazon.com/batch/latest/APIReference/API_SubmitJob.html
 *
 * [
 *   "taskProperties": [
 *     [
 *       "containers": [
 *         [
 *           "name": "string",
 *           "command": ["string"],
 *           "environment": [
 *             [
 *               "name": "string",
 *               "value": "string"
 *             ]
 *           ],
 *           "resourceRequirements": [
 *             [
 *               "type": "MEMORY|VCPU",
 *               "value": "string"
 *             ]
 *           ]
 *         ]
 *       ]
 *     ]
 *   ]
 * ]
 *
 * Interface MultiContainerJobOverrides
 */
interface MultiContainerJobOverrides
{
    public function getBatchEcsPropertiesOverride(): ?array;
}
