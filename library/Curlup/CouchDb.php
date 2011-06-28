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

/**
 * CouchDB instance
 *
 * @category Curlup
 * @package Curlup
 */
class CouchDb
{
    /**
     * URI for the local CouchDB instance
     *
     * @var string
     */
    protected $uri;

    /**
     * Timeout for requests created from this object
     *
     * @var int
     */
    protected $timeout;

    /**
     * Constructor
     *
     * Options passed to the constructor will be passed to {@see setOptions()}
     *
     * @param $options
     */
    public function __construct(array $options = array())
    {
        $this->setOptions($options);
    }

    /**
     * Generate a new request
     *
     * Requests should be generated using this method, because then some of
     * the options that were set up when the instance of the CouchDb object
     * were created can be passed along.
     *
     * @param string $path URI path (requires leading slash if you want one)
     * @param string $method Request method
     * @return Request
     */
    public function createRequest($path, $method)
    {
        $request = new Request(
            array(
                'method' => $method,
                'timeout' => $this->getTimeout(),
                'uri' => $this->getUri() . $path
            )
        );

        return $request;
    }

    /**
     * Generate a new request pool
     *
     * Request pools should be generated using this method, because then some of
     * the options that were set up when the instance of the CouchDb object
     * were created can be passed along.
     *
     * @return RequestPool
     */
    public function createRequestPool()
    {
        $requestPool = new RequestPool();

        return $requestPool;
    }

    /**
     * Get the base CouchDB URI
     *
     * @return string
     */
    public function getUri()
    {
        return $this->uri;
    }

    /**
     * Set the timeout for requests created from this object
     *
     * @return int
     */
    public function getTimeout()
    {
        return $this->timeout;
    }

    /**
     * Set options for this object
     *
     * All array keys passed will be filtered through a method call that will be
     * generated by capitalizing the first letter of the key and prepending
     * 'set'.
     *
     * Implements a fluent interface.
     *
     * @param $options
     * @return CouchDb
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
     * Set the timeout for requests created from this object
     *
     * @param $timeout
     * @return self
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

    /**
     * Set the base CouchDB URI
     *
     * @param $uri URI
     * @return self
     */
    public function setUri($uri)
    {
        $this->uri = filter_var(
            $uri,
            FILTER_VALIDATE_URL
        );

        return $this;
    }

    /**
     * Corresponds to the _all_dbs API endpoint
     *
     * http://wiki.apache.org/couchdb/HttpGetAllDbs
     *
     * @return Request
     */
    public function allDbs()
    {
        $request = $this->createRequest(
            '/_all_dbs',
            Request::HTTP_METHOD_GET
        );

        return $request;
    }

    /**
     * Corresponds to the _active_tasks API endpoint
     *
     * http://wiki.apache.org/couchdb/HttpGetActiveTasks
     *
     * @return Request
     */
    public function activeTasks()
    {
        $request = $this->createRequest(
            '/_active_tasks',
            Request::HTTP_METHOD_GET
        );

        return $request;
    }

    /**
     * Corresponds to the _log API endpoint
     *
     * http://wiki.apache.org/couchdb/HttpGetLog
     *
     * @return Request
     */
    public function log()
    {
        $request = $this->createRequest(
            '/_log',
            Request::HTTP_METHOD_GET
        );

        return $request;
    }

    /**
     * Corresponds to the _replicate API endpoint
     *
     * http://wiki.apache.org/couchdb/Replication
     *
     * @param $body Replication POST body as a PHP data structure
     * @return Request
     */
    public function replicate($body)
    {
        $request = $this->createRequest(
            '/_replicate',
            Request::HTTP_METHOD_POST
        )
        ->setJsonDecodedBody($body);

        return $request;
    }

    /**
     * Corresponds to the _restart API endpoint
     *
     * http://wiki.apache.org/couchdb/Complete_HTTP_API_Reference
     *
     * @return Request
     */
    public function restart()
    {
        $request = $this->createRequest(
            '/_restart',
            Request::HTTP_METHOD_POST
        )
        ->addHeader('Content-Type', 'application/json;charset=UTF-8');

        return $request;
    }

    /**
     * Corresponds to the / (root) API endpoint
     *
     * http://wiki.apache.org/couchdb/HttpGetRoot
     *
     * @return Request
     */
    public function root()
    {
        $request = $this->createRequest(
            '/',
            Request::HTTP_METHOD_GET
        );

        return $request;
    }

    /**
     * Corresponds to the _stats API endpoint
     *
     * http://wiki.apache.org/couchdb/Runtime_Statistics
     *
     * @param $name A group/key pair for specific stat retrieval
     * @return Request
     * @throws \InvalidArgumentException when $name has an invalid member count
     */
    public function stats(array $name = array())
    {
        $uri = '/_stats';

        if (count($name) === 2) {
            $uri = sprintf(
                '%s/%s/%s',
                $uri,
                urlencode($name[0]),
                urlencode($name[1])
            );
        } elseif (count($name) !== 0) {
            throw new \InvalidArgumentException(
                'The $name array must contain exactly 2 values for specific'
              . ' statistic retrieval.'
            );
        }

        $request = $this->createRequest(
            $uri,
            Request::HTTP_METHOD_GET
        );

        return $request;
    }

    /**
     * Corresponds to the _uuids API endpoint
     *
     * http://wiki.apache.org/couchdb/HttpGetUuids
     *
     * @return Request
     */
    public function uuids()
    {
        $request = $this->createRequest(
            '/_uuids',
            Request::HTTP_METHOD_GET
        );

        return $request;
    }
}
