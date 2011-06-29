<?php

/**
 * curlup
 *
 * @category Curlup
 * @package Curlup
 */

/**
 * Copyright © 2011, Gordon Stratton <gordon.stratton@gmail.com>
 *
 * Permission to use, copy, modify, and/or distribute this software for any
 * purpose with or without fee is hereby granted, provided that the above
 * copyright notice and this permission notice appear in all copies.
 *
 * THE SOFTWARE IS PROVIDED "AS IS" AND THE AUTHOR DISCLAIMS ALL WARRANTIES
 * WITH REGARD TO THIS SOFTWARE INCLUDING ALL IMPLIED WARRANTIES OF
 * MERCHANTABILITY AND FITNESS. IN NO EVENT SHALL THE AUTHOR BE LIABLE FOR
 * ANY SPECIAL, DIRECT, INDIRECT, OR CONSEQUENTIAL DAMAGES OR ANY DAMAGES
 * WHATSOEVER RESULTING FROM LOSS OF USE, DATA OR PROFITS, WHETHER IN AN
 * ACTION OF CONTRACT, NEGLIGENCE OR OTHER TORTIOUS ACTION, ARISING OUT OF
 * OR IN CONNECTION WITH THE USE OR PERFORMANCE OF THIS SOFTWARE.
 */

namespace Curlup;

use SplObjectStorage;

/**
 * CouchDB HTTP request pool
 *
 * @category Curlup
 * @package Curlup
 */
class RequestPool
{
    /**
     * Current cURL multi handle
     *
     * @var resource
     */
    protected $curlMultiHandle;

    /**
     * Requests added to the pool
     *
     * @var SplObjectStorage
     */
    protected $requests;

    /**
     * Responses added to the pool
     *
     * @var SplObjectStorage
     */
    protected $responses;

    /**
     * Timeout for the entire request pool
     *
     * @var int
     */
    protected $timeout;

    /**
     * Constructor
     *
     * @param $options Initial options for the object
     * @return void
     */
    public function __construct(array $options = array())
    {
        $this->curlMultiHandle = curl_multi_init();

        $this->requests = new SplObjectStorage();
        $this->responses = new SplObjectStorage();
        $this->timeout = 10;

        $this->setOptions($options);
    }

    /**
     * Destructor
     *
     * @return void
     */
    public function __destruct()
    {
        curl_multi_close($this->curlMultiHandle);
    }

    /**
     * Add a request to the pool
     *
     * @param $request Request to attach
     * @return RequestPool
     */
    public function attach(Request $request)
    {
        $this->requests->attach($request);

        return $this;
    }

    /**
     * Clear all requests in the pool
     *
     * @return RequestPool
     */
    public function clear()
    {
        $this->requests->removeAll($this->requests);

        return $this;
    }

    /**
     * Remove a specific request from the pool
     *
     * @param $request Request to remove
     * @return RequestPool
     */
    public function detach(Request $request)
    {
        $this->requests->detach($request);

        return $this;
    }

    /**
     * Get the current requests
     *
     * @return SplObjectStorage
     */
    public function getRequests()
    {
        return $this->requests;
    }

    /**
     * Get the current responses
     *
     * @return SplObjectStorage
     */
    public function getResponses()
    {
        return $this->responses;
    }

    /**
     * Get the timeout
     *
     * @return int
     */
    public function getTimeout()
    {
        return $this->timeout;
    }

    /**
     * Send (execute) all requests in the pool
     *
     * Returns SplObjectStorage container of responses.
     *
     * @return SplObjectStorage
     */
    public function send()
    {
        // Reusable fun to run all runnable cURL handles
        $performRequests = function($mh, &$active) {
            do {
                $mhRet = curl_multi_exec($mh, $active);
            } while ($mhRet == CURLM_CALL_MULTI_PERFORM);

            return $mhRet;
        };

        // Iterate over attached requests and add them to the cURL multi handle
        foreach ($this->getRequests() as $request) {
            $curlHandle = $request->getCurlHandle();
            $curlOptions = $request->getCurlOptions();

            curl_setopt_array($curlHandle, $curlOptions);
            curl_multi_add_handle(
                $this->curlMultiHandle,
                $curlHandle
            );
        }

        // Execute all of our requests
        $mhRet = $performRequests($this->curlMultiHandle, $active);

        // Intelligently wait for the requests to finish
        while ($active && $mhRet == CURLM_OK) {
            $selectRet = curl_multi_select(
                $this->curlMultiHandle,
                $this->getTimeout()
            );

            switch ($selectRet) {
                case -1:
                    throw new CurlException(
                        'curl_multi_select returned -1',
                        -1
                    );
                    break;
                case 0:
                    throw new CurlException(
                        'curl_multi_select timed out',
                        0
                    );
                    break;
                default:
                    $performRequests($this->curlMultiHandle, $active);
                    break;
            }
        }

        // cURL multi error -- not sure how best to handle these…
        if ($mhRet != CURLM_OK) {
            throw new CurlException(
                'CURLM error string not supported by PHP, see exception code',
                $mhRet
            );
        }

        // Finish out the requests
        foreach ($this->getRequests() as $request) {
            if (($errno = curl_errno($request->getCurlHandle())) !== 0) {
                $errstr = curl_error($request->getCurlHandle());

                throw new CurlException(
                    $errno,
                    $errstr
                );
            }

            $response = Response::factory(
                curl_multi_getcontent(
                    $request->getCurlHandle()
                )
            );

            $this->responses->attach($response);
        }

        return $this->responses;
    }

    /**
     * Set multiple values for the RequestPool object
     *
     * You may use this as a shortcut for setting options, but the method
     * names must conform to <pre>'set' . ucfirst($key)</pre> and the
     * methods can only accept a single value.
     *
     * @param $options
     * @return RequestPool
     */
    public function setOptions(array $options)
    {
        foreach ($options as $key => $value) {
            $methodName = 'set' . ucfirst($key);

            if (is_callable(array($this, $methodName))) {
                $this->$methodName($value);
            }
        }

        return $this;
    }

    /**
     * Set the timeout
     *
     * @param int $timeout
     * @return RequestPool
     */
    public function setTimeout($timeout)
    {
        $this->timeout = filter_var(
            $timeout,
            FILTER_VALIDATE_INT,
            array(
                'options' => array(
                    'default' => 0,
                    'min_range' => 0
                )
            )
        );

        return $this;
    }
}
