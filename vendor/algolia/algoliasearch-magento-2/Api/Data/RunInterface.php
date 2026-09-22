<?php

namespace Algolia\AlgoliaSearch\Api\Data;

/**
 * Run Data Interface
 *
 * @api
 */
interface RunInterface
{
    public const TABLE_NAME = 'algoliasearch_queue_log';

    /**#@+
     * Constants for keys of data array. Identical to the name of the getter in snake case
     */
    public const FIELD_RUN_ID = 'id';
    public const FIELD_STARTED = 'started';
    public const FIELD_DURATION = 'duration';
    public const FIELD_PROCESSED_JOBS = 'processed_jobs';
    public const FIELD_WITH_EMPTY_QUEUE = 'with_empty_queue';
    /**#@-*/
}
