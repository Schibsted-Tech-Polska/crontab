<?php

namespace Crontab\Manager;

use Crontab\Model\Job;
use DateTime;

/**
 * Manager
 */
interface JobManagerInterface
{
    /**
     * Get job
     *
     * @param string $id id
     *
     * @return Job
     */
    public function getJob($id);

    /**
     * Set job
     *
     * @param string $id  id
     * @param Job    $job job
     *
     * @return bool
     */
    public function setJob($id, Job $job);

    /**
     * Get jobs
     *
     * @return Job[]
     */
    public function getJobs();

    /**
     * Set jobs
     *
     * @param Job[] $jobs jobs
     *
     * @return bool
     */
    public function setJobs(array $jobs);

    /**
     * Get due jobs /active, with selected type and with due date/
     *
     * @param int      $type     type
     * @param DateTime $dateTime dateTime
     *
     * @return Job[]
     */
    public function getDueJobs($type, DateTime $dateTime = null);

    /**
     * Add job
     *
     * @param Job $job job
     *
     * @return bool
     */
    public function addJob(Job $job);

    /**
     * Remove job
     *
     * @param string $id id
     *
     * @return bool
     */
    public function removeJob($id);
}
