<?php


namespace Cxlblm\Pool;


class Config
{
    private $maxConn = 10;
    private $minConn = 5;

    private $activeConn = 100;

    private $maxWait = 1000;
    private $waitTime = 3;

    public function __construct(array $config = [])
    {
        foreach ($config as $key => $value) {
            $this->{'set' . ucfirst($key)}($value);
        }
    }

    public function has(string $key): bool
    {
        return ($this->{$key} ?? null) !== null;
    }

    /**
     * @param int $maxConn
     * @return Config
     */
    public function setMaxConn(int $maxConn): self
    {
        $this->maxConn = $maxConn;
        return $this;
    }

    /**
     * @return int
     */
    public function getMaxConn(): int
    {
        return $this->maxConn;
    }

    /**
     * @return int
     */
    public function getMinConn(): int
    {
        return $this->minConn;
    }

    /**
     * @param int $minConn
     */
    public function setMinConn(int $minConn): self
    {
        $this->minConn = $minConn;
        return $this;
    }

    /**
     * @param int $activeConn
     * @return Config
     */
    public function setActiveConn(int $activeConn): self
    {
        $this->activeConn = $activeConn;
        return $this;
    }

    /**
     * @return int
     */
    public function getActiveConn(): int
    {
        return $this->activeConn;
    }

    /**
     * @param int $maxWait
     * @return Config
     */
    public function setMaxWait(int $maxWait): Config
    {
        $this->maxWait = $maxWait;
        return $this;
    }

    /**
     * @return int
     */
    public function getMaxWait(): int
    {
        return $this->maxWait;
    }

    /**
     * @param int $waitTime
     * @return Config
     */
    public function setWaitTime(int $waitTime): Config
    {
        $this->waitTime = $waitTime;
        return $this;
    }

    /**
     * @return int
     */
    public function getWaitTime(): int
    {
        return $this->waitTime;
    }
}