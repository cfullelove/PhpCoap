<?php

namespace PhpCoap;

use React\EventLoop\Tick\FutureTickQueue;
use React\EventLoop\Tick\NextTickQueue;
use React\EventLoop\Timer\Timer;
use React\EventLoop\Timer\TimerInterface;
use React\EventLoop\Timer\Timers;


class StreamSocketSelectLoop extends \React\EventLoop\StreamSelectLoop
{
	private $readSockets = [];
	private $writeSockets = [];
	private $readSockListeners = [];
	private $writeSockListeners = [];
    private $readStreams = [];
    private $readListeners = [];
    private $writeStreams = [];
    private $writeListeners = [];


    const MICROSECONDS_PER_SECOND = 1000000;

    private $nextTickQueue;
    private $futureTickQueue;
    private $timers;
    private $running;

    public function __construct()
    {
        $this->nextTickQueue = new NextTickQueue($this);
        $this->futureTickQueue = new FutureTickQueue($this);
        $this->timers = new Timers();
    }


    /**
     * {@inheritdoc}
     */
    public function addReadStream($stream, callable $listener)
    {
        $key = (int) $stream;

        if (!isset($this->readStreams[$key])) {
            $this->readStreams[$key] = $stream;
            $this->readListeners[$key] = $listener;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function addWriteStream($stream, callable $listener)
    {
        $key = (int) $stream;

        if (!isset($this->writeStreams[$key])) {
            $this->writeStreams[$key] = $stream;
            $this->writeListeners[$key] = $listener;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function removeReadStream($stream)
    {
        $key = (int) $stream;

        unset(
            $this->readStreams[$key],
            $this->readListeners[$key]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function removeWriteStream($stream)
    {
        $key = (int) $stream;

        unset(
            $this->writeStreams[$key],
            $this->writeListeners[$key]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function removeStream($stream)
    {
        $this->removeReadStream($stream);
        $this->removeWriteStream($stream);
    }

	function addReadSocket( $sock, callable $listener )
	{
		$key = (int) $sock;

		if ( ! isset( $this->readSockets[ $key ] ) )
		{
			$this->readSockets[$key] = $sock;
			$this->readSockListeners[$key] = $listener;
		}

	}

	function addWriteSocket( $sock, callable $listener )
	{
		$key = (int) $sock;

		if ( ! isset( $this->writeSockets[ $key ] ) )
		{
			$this->writeSockets[$key] = $sock;
			$this->writeSockListeners[$key] = $listener;
		}
	}

	function removeReadSocket( $sock )
	{
		$key = (int) $sock;

		unset(
			$this->readSockets[$key],
			$this->readSockListeners[$key]
		);
	}

	function removeWriteSocket( $sock )
	{
		$key = (int) $sock;

		unset(
			$this->writeSockets[$key],
			$this->writeSockListeners[$key]
		);
	}

    /**
     * {@inheritdoc}
     */
    public function addTimer($interval, callable $callback)
    {
        $timer = new Timer($this, $interval, $callback, false);

        $this->timers->add($timer);

        return $timer;
    }

    /**
     * {@inheritdoc}
     */
    public function addPeriodicTimer($interval, callable $callback)
    {
        $timer = new Timer($this, $interval, $callback, true);

        $this->timers->add($timer);

        return $timer;
    }

    /**
     * {@inheritdoc}
     */
    public function cancelTimer(TimerInterface $timer)
    {
        $this->timers->cancel($timer);
    }

    /**
     * {@inheritdoc}
     */
    public function isTimerActive(TimerInterface $timer)
    {
        return $this->timers->contains($timer);
    }

    /**
     * {@inheritdoc}
     */
    public function nextTick(callable $listener)
    {
        $this->nextTickQueue->add($listener);
    }

    /**
     * {@inheritdoc}
     */
    public function futureTick(callable $listener)
    {
        $this->futureTickQueue->add($listener);
    }

    /**
     * {@inheritdoc}
     */
    public function tick()
    {
        $this->nextTickQueue->tick();

        $this->futureTickQueue->tick();

        $this->timers->tick();

        $this->waitForStreamActivity(0);
    }


    public function run()
    {
        $this->running = true;

        while ($this->running) {
            $this->nextTickQueue->tick();

            $this->futureTickQueue->tick();

            $this->timers->tick();

            // Next-tick or future-tick queues have pending callbacks ...
            if (!$this->running || !$this->nextTickQueue->isEmpty() || !$this->futureTickQueue->isEmpty()) {
                $timeout = 0;

            // There is a pending timer, only block until it is due ...
            } elseif ($scheduledAt = $this->timers->getFirst()) {
                $timeout = $scheduledAt - $this->timers->getTime();
                if ($timeout < 0) {
                    $timeout = 0;
                } else {
                    $timeout *= self::MICROSECONDS_PER_SECOND;
                }

            // The only possible event is stream activity, so wait forever ...
            } elseif ($this->readStreams || $this->writeStreams || $this->readSockets || $this->writeSockets ) {
                $timeout = null;

            // There's nothing left to do ...
            } else {
                break;
            }

            $this->waitForStreamActivity(0);
            $this->waitForSocketActivity(0);
        }
    }

    private function waitForStreamActivity($timeout)
    {
        $read  = $this->readStreams;
        $write = $this->writeStreams;

        $this->streamSelect($read, $write, $timeout);


        foreach ($read as $stream) {
            $key = (int) $stream;

            if (isset($this->readListeners[$key])) {
                call_user_func($this->readListeners[$key], $stream, $this);
            }
        }

        foreach ($write as $stream) {
            $key = (int) $stream;

            if (isset($this->writeListeners[$key])) {
                call_user_func($this->writeListeners[$key], $stream, $this);
            }
        }
    }

   private function waitForSocketActivity($timeout)
    {
        $read  = $this->readSockets;
        $write = $this->writeSockets;

        $this->socketSelect($read, $write, $timeout);

        foreach ($read as $sock) {
            $key = (int) $sock;

            if (isset($this->readSockListeners[$key])) {
                call_user_func($this->readSockListeners[$key], $sock, $this);
            }
        }

        foreach ($write as $sock) {
            $key = (int) $sock;

            if (isset($this->writeSockListeners[$key])) {
                call_user_func($this->writeSockListeners[$key], $sock, $this);
            }
        }
    }

    protected function socketSelect(array &$read, array &$write, $timeout)
    {
        if ($read || $write) {
            $except = null;
            return socket_select($read, $write, $except, $timeout === null ? null : 0, $timeout);
        }

        usleep($timeout);

        return 0;
    }


}

?>