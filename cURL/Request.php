<?php

namespace App\Util\cURL;

use Symfony\Component\EventDispatcher\EventDispatcher;

class Request extends EventDispatcher
{
    /**
     * @var resource cURL handler
     */
    protected $ch;

    /**
     * @var \App\Util\cURL\RequestQueue Queue instance when requesting async
     */
    protected $queue;

    /**
     * @var array Object containing options for current request
     */
    protected $options = null;

    /**
     * Create new cURL handle
     *
     * @param string $url The URL to fetch.
     */
    public function __construct($url = null)
    {
        if ($url !== null) {
            $this->options[CURLOPT_URL] = $url;
        }
        $this->ch = curl_init();
    }

    /**
     * Closes cURL resource and frees the memory.
     * It is neccessary when you make a lot of requests
     * and you want to avoid fill up the memory.
     */
    public function __destruct()
    {
        if (isset($this->ch)) {
            curl_close($this->ch);
        }
    }

    public function applyOptionsTo(Request $request)
    {
        if (!empty($this->options)) {
            curl_setopt_array($request->getHandle(), $this->options);
        }
    }

    /**
     * Get the cURL\Options instance
     * Creates empty one if does not exist
     *
     * @return array
     */
    public function getOptions()
    {
        if (!isset($this->options)) {
            $this->options = array();
        }
        return $this->options;
    }

    /**
     * Sets Options
     *
     * @param array $options Options
     * @return void
     */
    public function setOptions(array $options)
    {
        $this->options = $options;
    }

    /**
     * Returns cURL raw resource
     *
     * @return resource    cURL handle
     */
    public function getHandle()
    {
        return $this->ch;
    }

    /**
     * Get unique id of cURL handle
     * Useful for debugging or logging.
     *
     * @return int
     */
    public function getUID()
    {
        return (int)$this->ch;
    }

    /**
     * Perform a cURL session.
     * Equivalent to curl_exec().
     * This function should be called after initializing a cURL
     * session and all the options for the session are set.
     *
     * Warning: it doesn't fire 'complete' event.
     *
     * @return Response
     */
    public function send()
    {
        if (!empty($this->options)) {
            $this->applyOptionsTo($this);
        }
        $content = curl_exec($this->ch);

        $response = new Response($this, $content);
        $errorCode = curl_errno($this->ch);
        if ($errorCode !== CURLE_OK) {
            $response->setError(new CUrlException(curl_error($this->ch), $errorCode));
        }
        return $response;
    }
    /**
     * Creates new RequestsQueue with single Request attached to it
     * and calls RequestsQueue::socketPerform() method.
     *
     * @see RequestsQueue::socketPerform()
     */
    public function socketPerform()
    {
        if (!isset($this->queue)) {
            $this->queue = new RequestQueue();
            $this->queue->attach($this);
        }
        return $this->queue->socketPerform();
    }
    /**
     * Calls socketSelect() on previously created RequestsQueue
     *
     * @see RequestsQueue::socketSelect()
     */
    public function socketSelect($timeout = 1)
    {
        if (!isset($this->queue)) {
            throw new CUrlException('You need to call socketPerform() before.');
        }
        return $this->queue->socketSelect($timeout);
    }
}