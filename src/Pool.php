<?php


namespace Cxlblm\Pool;


use Cxlblm\Pool\Exception\{ManyConsumerException, TimeoutException};
use Swoole\Coroutine;
use Swoole\Coroutine\{Channel, Context};

class Pool
{
    /**
     * @var \Closure
     */
    private $connCreate;
    /**
     * @var Channel
     */
    private $pool;
    /**
     * @var int
     */
    private $count = 0;
    /**
     * @var Config
     */
    private $config;

    public function __construct(\Closure $callable, ?Config $config = null)
    {
        $this->initConfig($config);
        $this->connCreate = $callable;
        $this->initConnPool();
    }

    /**
     * @param Config|null $config
     */
    private function initConfig(?Config$config = null)
    {
        if (null === $config) {
            $config = $this->config = new Config();
        }
        $this->config = $config;
    }

    /**
     *
     */
    private function initConnPool(): void
    {
        $this->pool = new Channel($this->config->getMaxConn());
        for ($i = 0; $i < $this->config->getMinConn(); ++$i) {
            $this->pushByChannel($this->create());
            ++$this->count;
        }
    }

    /**
     * @param $conn
     */
    private function pushByChannel($conn)
    {
        $r = $this->pool->push($conn, $this->config->getMaxWait());
        if (false === $r) {
            throw new TimeoutException('Pool Push Timeout');
        }
    }

    /**
     * @return int
     */
    private function getSelfId(): int
    {
        return spl_object_id($this);
    }

    /**
     * @return mixed
     * @throws \Exception
     */
    private function context(): Context
    {
        $context = Coroutine::getContext();
        if (!$context) {
            throw new \Exception('Not in swoole coroutine');
        }
        return $context;
    }

    /**
     * @return mixed
     * @throws \Exception
     */
    public function get()
    {
        $context = $this->context();
        if (!$this->canConsumer()) {
            throw new ManyConsumerException();
        }

        if ($this->pool->isEmpty() && $this->canCreate()) {
            $conn = $this->create();
        } else {
            $conn = $this->popByChannel();
        }

        $context[$this->getSelfId()] = $conn;

        Coroutine::defer(function () {
            $this->free();
        });

        return $conn;
    }

    /**
     * @return bool
     */
    private function canConsumer(): bool
    {
        $maxWait = $this->config->getMaxWait();
        return $maxWait <= 0 || $maxWait > $this->pool->stats()['consumer_num'];
    }

    /**
     * @return bool
     */
    private function canCreate(): bool
    {
        return $this->config->getMaxConn() > $this->count;
    }

    /**
     * @return mixed
     */
    private function popByChannel()
    {
        $conn = $this->pool->pop($this->config->getMaxWait());
        if (false === $conn) {
            throw new TimeoutException('Pool Pop Timeout');
        }
        return $conn;
    }

    /**
     * @param null $conn
     * @throws \Exception
     */
    public function free($conn = null): void
    {
        if ($conn === null) {
            $context = $this->context();
            $conn = $context[$this->getSelfId()] ?? null;
        }

        if (isset($context[$this->getSelfId()])) {
            unset($context[$this->getSelfId()]);
        }

        if ($conn !== null) {
            $r = $this->pool->push($conn, $this->config->getMaxWait());
            if (false === $r) {
                // TODO: 是否需要处理异常情况呢
            }
        }
    }

    public function create()
    {
        return $this->connCreate->call($this);
    }

    public function getConfig(): Config
    {
        return $this->config;
    }
}
