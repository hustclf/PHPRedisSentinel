# PHPRedisSentinel

a high performance redis-sentinel client for php based on phpredis extension.
(Inspired by <a href="https://github.com/huyanping/redis-sentinel">redis-sentinel</a>)

## advantages
### 1. singleton pattern
### 2. connecting pool
### 3. support multi methods
* ping
* masters
* master
* slaves
* sentinels
* getMasterAddrByName
* reset
* failOver
* ckquorum
* flushConfig
* monitor
* remove
* set
* getLastError
* clearLastError
* info

## examples
```php
        $policy = [
            'servers' => [
                [
                    'host' => '127.0.0.1',
                    'port' => '26379',
                ],
                [
                    'host' => '127.0.0.1',
                    'port' => '26380',
                ],
                [
                    'host' => '127.0.0.1',
                    'port' => '26381',
                ],
            ]
        ];

        $sentinel = RedisSentinel::getInstance($policy);

        // ping
        $ret = $sentinel->ping();

        // get all monitored masters
        $ret = $sentinel->masters();

        // get master by master name
        $ret = $sentinel->master('mymaster');

        // get slaves
        $ret = $sentinel->slaves('mymaster');

        // get sentinels
        $ret = $sentinel->sentinels('mymaster');

        // get master address by master name
        $ret = $sentinel->getMasterAddrByName('mymaster');

        // and so on
        var_dump($ret);
```

