<?php


namespace Test;


use Cxlblm\Pool\Pool;
use PHPUnit\Framework\TestCase;

class PoolTest extends TestCase
{
    private function poolLength()
    {
        return function () {
            return $this->pool->length();
        };
    }

    public function testInit()
    {
        $pool = new Pool(function () {
            return new \StdClass();
        });
        $poolLength = $this->poolLength();
        $r = $poolLength->call($pool);
        $this->assertEquals($pool->getConfig()->getMinConn(), $r);
    }

    public function testGet()
    {
        $pool = new Pool(function () {
            return new \StdClass();
        });
        $r = $pool->get();
        $this->assertInstanceOf(\StdClass::class, $r);
        $poolLength = $this->poolLength();
        $this->assertEquals($pool->getConfig()->getMinConn() - 1, $poolLength->call($pool));

    }

    public function testConcurrent()
    {
        $pool = new Pool(function () {
            return new \StdClass();
        });

        $waitGroup = new \Swoole\Coroutine\WaitGroup();
        $waitGroup->add(200);

        for ($i = 0; $i < 200; ++$i) {
            go(function () use ($pool, $waitGroup) {
                $pool->get();
                \Swoole\Coroutine::sleep(mt_rand(1, 4));
                $pool->free();
                $waitGroup->done();
            });
        }

        $waitGroup->wait();

        $count = function () {
            return $this->count;
        };

        $this->assertEquals($pool->getConfig()->getMaxConn(), $count->call($pool));
        $this->assertEquals($pool->getConfig()->getMaxConn(), $this->poolLength()->call($pool));
    }

    public function testConcurrentAutoFree()
    {
        $pool = new Pool(function () {
            return new \StdClass();
        });

        $waitGroup = new \Swoole\Coroutine\WaitGroup();
        $waitGroup->add(200);

        for ($i = 0; $i < 200; ++$i) {
            go(function () use ($pool, $waitGroup) {
                $pool->get();
                \Swoole\Coroutine::sleep(mt_rand(1, 4));
                $waitGroup->done();
            });
        }

        $waitGroup->wait();

        \Swoole\Coroutine::sleep(4);

        $count = function () {
            return $this->count;
        };

        $this->assertEquals($pool->getConfig()->getMaxConn(), $count->call($pool));
        $this->assertEquals($pool->getConfig()->getMaxConn(), $this->poolLength()->call($pool));
    }
}
