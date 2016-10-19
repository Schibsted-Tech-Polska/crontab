<?php

namespace Crontab\Manager;

use Cron\CronExpression;
use Crontab\Model\Job;
use DateTime;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;

/**
 * Manager
 */
class JobManager implements JobManagerInterface
{
    /** @var string */
    protected $idPrefix = 'ids';

    /** @var string */
    protected $jobPrefix = 'job';

    /** @var CacheItemPoolInterface */
    protected $pool;

    /**
     * Constructor
     *
     * @param CacheItemPoolInterface $pool pool
     */
    public function __construct(CacheItemPoolInterface $pool)
    {
        $this->pool = $pool;
    }

    /**
     * Get job
     *
     * @param string $id id
     *
     * @return Job
     */
    public function getJob($id)
    {
        $key = $this->generateKeyForId($id);
        $job = $this->loadCacheItem($key);

        return $job;
    }

    /**
     * Set job
     *
     * @param string $id  id
     * @param Job    $job job
     *
     * @return bool
     */
    public function setJob($id, Job $job)
    {
        $key = $this->generateKeyForId($id);
        $this->saveCacheItem($key, $job, true);
        $res = $this->pool->commit();

        return $res;
    }

    /**
     * Get jobs
     *
     * @return Job[]
     */
    public function getJobs()
    {
        $ids = $this->loadIdsForJobs();

        $keys = $this->generateKeyForIds($ids);
        $jobs = $this->loadCacheItems($keys);

        return $jobs;
    }

    /**
     * Set jobs
     *
     * @param Job[] $jobs jobs
     *
     * @return bool
     */
    public function setJobs(array $jobs)
    {
        $ids = $this->saveIdsForJobs($jobs, true);

        $keys = $this->generateKeyForIds($ids);
        $this->saveCacheItems($keys, $jobs, true);
        $res = $this->pool->commit();

        return $res;
    }

    /**
     * Get due jobs /active, with selected type and with due date/
     *
     * @param int      $type     type
     * @param DateTime $dateTime dateTime
     *
     * @return Job[]
     */
    public function getDueJobs($type, DateTime $dateTime = null)
    {
        $dueJobs = [];
        if ($dateTime === null) {
            $dateTime = new DateTime();
        }

        $jobs = $this->getJobs();
        foreach ($jobs as $job) {
            if (($job->isActive()) && ($job->getType() == $type)) {
                $cronExpression = CronExpression::factory($job->getExpression());
                if ($cronExpression->isDue($dateTime)) {
                    $dueJobs[] = $job;
                }
            }
        }

        return $dueJobs;
    }

    /**
     * Add job
     *
     * @param Job $job job
     *
     * @return bool
     */
    public function addJob(Job $job)
    {
        $id = $this->addIdForJob($job, true);

        $key = $this->generateKeyForId($id);
        $this->saveCacheItem($key, $job, true);
        $res = $this->pool->commit();

        return $res;
    }

    /**
     * Remove job
     *
     * @param string $id id
     *
     * @return bool
     */
    public function removeJob($id)
    {
        $job = $this->getJob($id);
        $id = $this->removeIdForJob($job, true);

        $key = $this->generateKeyForId($id);
        $this->pool->deleteItem($key);
        $res = $this->pool->commit();

        return $res;
    }

    // ----------

    /**
     * Load cache item
     *
     * @param string $key          key
     * @param mixed  $defaultValue default value
     *
     * @return mixed
     */
    protected function loadCacheItem($key, $defaultValue = null)
    {
        $item = $this->pool->getItem($key);
        if ($item->isHit()) {
            $cacheItem = $item->get();
        } else {
            $cacheItem = $defaultValue;
        }

        return $cacheItem;
    }

    /**
     * Save cache item
     *
     * @param string $key      key
     * @param mixed  $value    value
     * @param bool   $deferred deferred
     *
     * @return bool
     */
    protected function saveCacheItem($key, $value, $deferred = false)
    {
        $item = $this->pool->getItem($key)
            ->set($value)
        ;
        if ($deferred) {
            $res = $this->pool->saveDeferred($item);
        } else {
            $res = $this->pool->save($item);
        }

        return $res;
    }

    /**
     * Load cache items
     *
     * @param array $keys         keys
     * @param mixed $defaultValue default value
     *
     * @return array
     */
    protected function loadCacheItems($keys, $defaultValue = null)
    {
        $cacheItems = [];

        $items = $this->pool->getItems($keys);
        /** @var CacheItemInterface $item */
        foreach ($items as $item) {
            if ($item->isHit()) {
                $cacheItems[] = $item->get();
            } else {
                $cacheItems[] = $defaultValue;
            }
        }

        return $cacheItems;
    }

    /**
     * Save cache items
     *
     * @param array $keys     keys
     * @param array $values   values
     * @param bool  $deferred deferred
     *
     * @return bool
     */
    protected function saveCacheItems($keys, $values, $deferred = false)
    {
        $res = true;

        foreach ($keys as $key) {
            $value = current($values);
            $res = ($res && $this->saveCacheItem($key, $value, $deferred));
            next($values);
        }

        return $res;
    }

    /**
     * Load ids for jobs
     *
     * @return array
     */
    protected function loadIdsForJobs()
    {
        $ids = $this->loadCacheItem($this->idPrefix, []);

        return $ids;
    }

    /**
     * Save ids for jobs
     *
     * @param array $jobs     jobs
     * @param bool  $deferred deferred
     *
     * @return array
     */
    protected function saveIdsForJobs(array $jobs, $deferred = false)
    {
        // @TODO: check what will happened, when we'll leave widowed jobs???
        $ids = $this->getJobIds($jobs);

        $this->saveCacheItem($this->idPrefix, $ids, $deferred);

        return $ids;
    }

    /**
     * Add id for job
     *
     * @param Job  $job      job
     * @param bool $deferred deferred
     *
     * @return string
     */
    protected function addIdForJob(Job $job, $deferred = false)
    {
        $id = $this->getJobId($job);

        $jobs = $this->getJobs();
        $jobs[] = $job;
        $jobs = array_unique($jobs);
        $this->saveIdsForJobs($jobs, $deferred);

        return $id;
    }

    /**
     * Remove id for job
     *
     * @param Job  $job      job
     * @param bool $deferred deferred
     *
     * @return string
     */
    protected function removeIdForJob(Job $job, $deferred = false)
    {
        $id = $this->getJobId($job);

        $jobs = $this->getJobs();
        $jobs = array_diff($jobs, [$job]);
        $this->saveIdsForJobs($jobs, $deferred);

        return $id;
    }

    /**
     * Get job id
     *
     * @param Job $job job
     *
     * @return string
     */
    protected function getJobId($job)
    {
        $id = $job->getId();

        return $id;
    }

    /**
     * Get job ids
     *
     * @param Job[] $jobs jobs
     *
     * @return array
     */
    protected function getJobIds(array $jobs)
    {
        $ids = [];

        foreach ($jobs as $job) {
            $ids[] = $this->getJobId($job);
        }

        return $ids;
    }

    /**
     * Generate key for id
     *
     * @param string $id id
     *
     * @return string
     */
    protected function generateKeyForId($id)
    {
        $key = sprintf('%s.%s', $this->jobPrefix, $id);

        return $key;
    }

    /**
     * Generate key for ids
     *
     * @param array $ids ids
     *
     * @return array
     */
    protected function generateKeyForIds(array $ids)
    {
        $keys = [];

        foreach ($ids as $id) {
            $keys[] = $this->generateKeyForId($id);
        }

        return $keys;
    }
}
