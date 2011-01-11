<?php

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

class RequestPool implements Countable, Iterator
{
    protected $curlMultiHandle;
    protected $requests;
    protected $responses;
    protected $timeout;

    public function __construct(array $options = array())
    {
        $this->curlMultiHandle = curl_multi_init();

        $this->requests = new \SplObjectStorage();
        $this->responses = new \SplObjectStorage();
        $this->timeout = 10;

        $this->setOptions($options);
    }

    public function __destruct()
    {
        curl_multi_close($this->curlMultiHandle);
    }

    public function attach(Request $request)
    {
        $this->requests->attach($request);

        return $this;
    }

    public function clear()
    {
        $this->requests->removeAll($this->requests);

        return $this;
    }

    public function count()
    {
        return count($this->requests);
    }

    public function current()
    {
        return current($this->requests);
    }

    public function detach(Request $request)
    {
        $this->requests->detach($request);

        return $this;
    }

    public function getRequests()
    {
        return $this->requests;
    }

    public function getResponses()
    {
        return $this->responses;
    }

    public function getTimeout()
    {
        return $this->timeout;
    }

    public function key()
    {
        return key($this->requests);
    }

    public function next()
    {
        return next($this->requests);
    }

    public function rewind()
    {
        return rewind($this->requests);
    }

    public function valid()
    {
        return $this->requests->valid();
    }

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
    }
}
