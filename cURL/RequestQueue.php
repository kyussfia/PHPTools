<?php

namespace App\Util\cURL;

use Symfony\Component\EventDispatcher\EventDispatcher;

class RequestQueue extends EventDispatcher implements \Countable
{
    /**
     * @var array Default options for new Requests attached to RequestsQueue
     */
    protected $defaultOptions = null;

    /**
     * @var resource cURL multi handler
     */
    protected $mh;

    /**
     * @var Request[] Array of requests attached
     */
    protected $queue = array();

    /**
     * @var array Array of requests added to curl multi handle
     */
    protected $running = array();

    /**
     * @var int Limit of the simultaneously performed requests.
     */
    protected $requestLimit; //on null there is no limit

    /**
     * Initializes curl_multi handler
     */
    public function __construct($limit = 5)
    {
        $this->mh = curl_multi_init();
        $this->requestLimit = $limit;
    }

    /**
     * Destructor, closes curl_multi handler
     *
     * @return void
     */
    public function __destruct()
    {
        if (isset($this->mh)) {
            curl_multi_close($this->mh);
        }
    }

    /**
     * Returns cURL\Options object with default request's options
     *
     * @return array
     */
    public function getDefaultOptions()
    {
        if (!isset($this->defaultOptions)) {
            $this->defaultOptions = array();
        }
        return $this->defaultOptions;
    }

    /**
     * Overrides default options with given Options
     *
     * @param array $defaultOptions New options
     * @return void
     */
    public function setDefaultOptions(array $defaultOptions)
    {
        $this->defaultOptions = $defaultOptions;
    }

    /**
     * Clear and delete all of the elements in the queue.
     * @return $this
     */
    public function clearQueue()
    {
        $this->queue = array();
        return $this;
    }

    /**
     * Get cURL multi handle
     *
     * @return resource
     */
    public function getHandle()
    {
        return $this->mh;
    }

    /**
     * @return bool The limit given and we've reached the limit.
     */
    private function isRequestLimitReached()
    {
        return null !== $this->requestLimit && count($this->running) == $this->requestLimit;
    }

    /**
     * Attach request to queue.
     *
     * @param Request $request Request to add
     * @return self
     */
    public function attach(Request $request)
    {
        $this->queue[$request->getUID()] = $request;
        return $this;
    }

    /**
     * Detach request from queue.
     *
     * @param Request $request Request to remove
     * @return self
     */
    public function detach(Request $request)
    {
        unset($this->queue[$request->getUID()]);
        return $this;
    }

    /**
     * Processes handles which are ready and removes them from pool.
     *
     * @return int Amount of requests completed
     */
    protected function read()
    {
        $n = 0;
        while ($info = curl_multi_info_read($this->mh)) {
            $n++;
            $request = $this->queue[(int)$info['handle']];
            $result = $info['result'];

            curl_multi_remove_handle($this->mh, $request->getHandle());
            unset($this->running[$request->getUID()]);
            $this->detach($request);

            $event = new Event();
            $event->request = $request;
            $event->response = new Response($request, curl_multi_getcontent($request->getHandle()));
            if ($result !== CURLE_OK) {
                $event->response->setError(new CUrlException(curl_error($request->getHandle()), $result));
            }
            $event->queue = $this;
            $this->dispatch('complete', $event);
            $request->dispatch('complete', $event);
        }

        return $n;
    }

    /**
     * Returns count of handles in queue
     *
     * @return int    Handles count
     */
    public function count()
    {
        return count($this->queue);
    }

    /**
     * Executes requests in parallel
     *
     * @return void
     */
    public function send()
    {
        while ($this->socketPerform()) {
            $this->socketSelect();
        }
    }

    /**
     * Returns requests present in $queue but not in $running
     *
     * @return Request[]    Array of requests
     */
    protected function getRequestsNotRunning()
    {
        $map = $this->queue;
        foreach($this->running as $k => $v) unset($map[$k]);
        return $map;
    }
    /**
     * Download available data on socket.
     *
     * @throws CUrlException
     * @return bool    TRUE when there are any requests on queue, FALSE when finished
     */
    public function socketPerform()
    {
        if ($this->count() == 0) {
            throw new CUrlException('Cannot perform if there are no requests in queue.');
        }
        $notRunning = $this->getRequestsNotRunning();
        do {
            /**
             * Apply cURL options to new requests
             */
            foreach ($notRunning as $request)
            {
                if ($this->isRequestLimitReached()) {
                    break;
                }
                $this->applyDefaultOptionsTo($request);
                $request->applyOptionsTo($request);
                curl_multi_add_handle($this->mh, $request->getHandle());
                $this->running[$request->getUID()] = $request;
            }

            $runningHandles = null;
            do {
                // http://curl.haxx.se/libcurl/c/curl_multi_perform.html
                // If an added handle fails very quickly, it may never be counted as a running_handle.
                $mrc = curl_multi_exec($this->mh, $runningHandles);
            } while ($mrc === CURLM_CALL_MULTI_PERFORM);
            if ($runningHandles < count($this->running)) {
                $this->read();
            }
            $notRunning = $this->getRequestsNotRunning();
        } while (count($notRunning) > 0);
        // Why the loop? New requests might be added at runtime on 'complete' event.
        // So we need to attach them to curl_multi handle immediately.

        return $this->count() > 0;
    }

    private function applyDefaultOptionsTo($request)
    {
        if(!empty($this->getDefaultOptions())) {
            curl_setopt_array($request->getHandle(), $this->getDefaultOptions());
        }
    }

    /**
     * Waits until activity on socket
     * On success, returns TRUE. On failure, this function will
     * return FALSE on a select failure or timeout (from the underlying
     * select system call)
     *
     * @param float|int $timeout Maximum time to wait
     * @throws CUrlException
     * @return bool
     */
    public function socketSelect($timeout = 1)
    {
        if ($this->count() == 0) {
            throw new CUrlException('Cannot select if there are no requests in queue.');
        }
        return curl_multi_select($this->mh, $timeout) !== -1;
    }
}