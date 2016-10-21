<?php

namespace Crontab\Model;

use DateInterval;
use DateTime;
use Hashids\Hashids;

class Job
{
    /** @const type: single */
    const TYPE_SINGLE = 1;

    /** @const type: multiple */
    const TYPE_MULTIPLE = 2;

    /** @const types */
    const TYPES = [
        self::TYPE_SINGLE => 'single',
        self::TYPE_MULTIPLE => 'multiple',
    ];

    /** @const status: never started */
    const STATUS_NEVER_STARTED = 1;

    /** @const status: in progress */
    const STATUS_IN_PROGRESS = 2;

    /** @const status: done */
    const STATUS_DONE = 3;

    /** @const statuses */
    const STATUSES = [
        self::STATUS_NEVER_STARTED => 'never started',
        self::STATUS_IN_PROGRESS => 'in progress',
        self::STATUS_DONE => 'done',
    ];

    /** @var string */
    protected $id;

    /** @var string */
    protected $expression;

    /** @var string */
    protected $command;

    /** @var int */
    protected $type;

    /** @var bool */
    protected $active;

    /** @var string */
    protected $comment;

    /** @var DateTime */
    protected $startedAt;

    /** @var DateTime */
    protected $endedAt;

    /** @var DateTime */
    protected $createdAt;

    /** @var DateTime */
    protected $updatedAt;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->id = $this->generateId();
    }

    /**
     * Return id
     *
     * @return string
     */
    public function __toString()
    {
        return strval($this->getId());
    }

    /**
     * Get id
     *
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get expression
     *
     * @return string
     */
    public function getExpression()
    {
        return $this->expression;
    }

    /**
     * Set expression
     *
     * @param string $expression expression
     *
     * @return self
     */
    public function setExpression($expression)
    {
        $this->expression = $expression;

        return $this;
    }

    /**
     * Get command
     *
     * @return string
     */
    public function getCommand()
    {
        return $this->command;
    }

    /**
     * Set command
     *
     * @param string $command command
     *
     * @return self
     */
    public function setCommand($command)
    {
        $this->command = $command;

        return $this;
    }

    /**
     * Get type
     *
     * @return int
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set type
     *
     * @param int $type type
     *
     * @return self
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get active
     *
     * @return bool
     */
    public function getActive()
    {
        return $this->active;
    }

    /**
     * Is active
     *
     * @return bool
     */
    public function isActive()
    {
        $ret = ($this->getActive() == true);

        return $ret;
    }

    /**
     * Set active
     *
     * @param bool $active active
     *
     * @return self
     */
    public function setActive($active)
    {
        $this->active = $active;

        return $this;
    }

    /**
     * Get comment
     *
     * @return string
     */
    public function getComment()
    {
        return $this->comment;
    }

    /**
     * Set comment
     *
     * @param string $comment
     *
     * @return self
     */
    public function setComment($comment)
    {
        $this->comment = $comment;

        return $this;
    }

    /**
     * Get started at
     *
     * @return DateTime
     */
    public function getStartedAt()
    {
        return $this->startedAt;
    }

    /**
     * Set started at
     *
     * @param DateTime $startedAt started at
     *
     * @return self
     */
    public function setStartedAt($startedAt)
    {
        $this->startedAt = $startedAt;

        return $this;
    }

    /**
     * Get ended at
     *
     * @return DateTime
     */
    public function getEndedAt()
    {
        return $this->endedAt;
    }

    /**
     * Set ended at
     *
     * @param DateTime $endedAt ended at
     *
     * @return self
     */
    public function setEndedAt($endedAt)
    {
        $this->endedAt = $endedAt;

        return $this;
    }

    /**
     * Get created at
     *
     * @return DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Set created at
     *
     * @param DateTime $createdAt created at
     *
     * @return self
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Get updated at
     *
     * @return DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * Set updated at
     *
     * @param DateTime $updatedAt updated at
     *
     * @return self
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    // ----------

    /**
     * Get status
     *
     * @return int
     */
    public function getStatus()
    {
        if ($this->getStartedAt() == null) {
            $status = self::STATUS_NEVER_STARTED;
        } elseif ($this->getStartedAt() > $this->getEndedAt()) {
            $status = self::STATUS_IN_PROGRESS;
        } else {
            $status = self::STATUS_DONE;
        }

        return $status;
    }

    /**
     * Get duration
     *
     * @return DateInterval|null
     */
    public function getDuration()
    {
        if (($this->getStartedAt() !== null) && ($this->getEndedAt() !== null) &&
            ($this->getEndedAt() >= $this->getStartedAt())
        ) {
            $duration = $this->getEndedAt()
                ->diff($this->getStartedAt())
            ;
        } else {
            $duration = null;
        }

        return $duration;
    }

    /**
     * Generate id
     *
     * @param string $salt   salt
     * @param int    $length length
     *
     * @return string
     */
    protected function generateId($salt = '9c1c01dc3ac1445a500251fc34a15d3e75a849df', $length = 12)
    {
        $hashids = new Hashids($salt, $length);

        $number = mt_rand(100, 900) . str_replace('.', '', microtime(true));
        $id = $hashids->encode($number);

        return $id;
    }
}
