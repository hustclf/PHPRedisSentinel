<?php
class RedisSentinel
{
	protected $connectPool = [];
	protected $serverNode  = [];

	protected $defaultSentinel = [
		'host' => '127.0.0.1',
		'port' => '26379'
	];

	protected $defaultPolicy = [
		'servers'  => [],
		'timeout'  => 0,
		'password' => '',
	];

	protected static $commands = [
		'set',
		'reset',
		'master',
		'slaves',
		'remove',
		'masters',
		'monitor',
		'failOver',
		'ckquorum',
		'sentinels',
		'checkQuorum',
		'flushconfig',
		'get-master-addr-by-name',
	];

	protected static $instance = null;

	public static function getInstance(array $policy = [])
	{
		if (!static::$instance instanceof static) {
			static::$instance = new static($policy);
		}
		return static::$instance;
	}

	private function __construct(array $policy = [])
	{
		if (!extension_loaded('redis')) {
			throw new \Exception('Redis extension is not loaded.');
		}

		$policy = array_merge($this->defaultPolicy, $policy);
		if (!empty($policy['servers'])) {
			$policy['servers'][] = $this->defaultSentinel;
		}

		$this->defaultPolicy = $policy;
		
		$this->initSentinel();
	}

	protected function initSentinel()
	{
		while (list(, $server) = each($this->defaultPolicy['servers'])) {
			$node = $server['host'] . ':' . $server['port'];
			
			if (!isset($this->serverNode[$node])) {
				$this->serverNode[$node] = $server;
			} else {
				throw new \Exception('The node "' . $node . '" exists.');
			}
		}
	}

	protected function getConn()
	{
		$bool = false;

		foreach ($this->serverNode as $node => $server) {
			if (isset($this->connectPool[$node])) {
				$bool = true;
				break;
			}

			$conn = new \Redis();
			$bool = $conn->connect($server['host'], $server['port'], $this->defaultPolicy['timeout']);

			if ($bool) {
				if (isset($server['password']) && $server['password'] != '') {
					$conn->auth($server['password']);
				} elseif ($this->defaultPolicy['password'] != '') {
					$conn->auth($this->defaultPolicy['password']);
				}
				
				$this->connectPool[$node] = $conn;
				break;
			}
		}

		if (!$bool) {
			throw new \Exception('Connect redis sentinels all failed!');
		}

		return $this->connectPool[$node];
	}

	protected function parseResult(array $data)
	{
		$ret   = [];
		$count = count($data);

		for ($i = 0; $i < $count;) {
			$record = $data[$i];
			if (is_array($record)) {
				$ret[] = $this->parseResult($record);
				$i++;
			} else {
				$ret[$record] = $data[$i + 1];
				$i += 2;
			}
		}

		return $ret;
	}

	public function __call($name, $arguments)
	{
		$name = strtolower($name);

		if ($name === 'getmasteraddrbyname') {
			$name = 'get-master-addr-by-name';
		}

		if (in_array($name, static::$commands)) {
			$arguments = array_merge(['SENTINEL', $name], $arguments);
			$ret       = call_user_func_array([$this->getConn(), 'rawCommand'], $arguments);

			switch ($name) {
				case 'slaves':
				case 'master':
				case 'masters':
				case 'sentinels':
					$ret = $this->parseResult($ret);
					break;

				case 'failover':
					$ret = $ret === 'OK';
					break;

				case 'get-master-addr-by-name':
					$ret = [
						'ip'   => $ret[0],
						'port' => $ret[1]
					];
					break;
			}

			return $ret;
		}

		return call_user_func_array([$this->getConn(), $name], $arguments);
	}
}
