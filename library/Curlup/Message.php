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
 * CouchDB HTTP message
 *
 * @category Curlup
 * @package Curlup
 */
class Message
{
    /**
     * HTTP DELETE
     *
     * @var string
     */
    const HTTP_METHOD_DELETE = 'DELETE';

    /**
     * HTTP COPY
     *
     * @var string
     */
    const HTTP_METHOD_COPY = 'COPY';

    /**
     * HTTP GET
     *
     * @var string
     */
    const HTTP_METHOD_GET = 'GET';

    /**
     * HTTP HEAD
     *
     * @var string
     */
    const HTTP_METHOD_HEAD = 'HEAD';

    /**
     * HTTP POST
     *
     * @var string
     */
    const HTTP_METHOD_POST = 'POST';

    /**
     * HTTP PUT
     *
     * @var string
     */
    const HTTP_METHOD_PUT = 'PUT';

    /**
     * HTTP message body
     *
     * @var string
     */
    protected $body;

    /**
     * HTTP headers
     *
     * @var array
     */
    protected $headers;

    /**
     * HTTP message version
     *
     * @var string
     */
    protected $httpVersion;

    /**
     * HTTP response code
     *
     * @var int
     */
    protected $responseCode;

    /**
     * HTTP response status line
     *
     * @var string
     */
    protected $responseStatus;

    public function __construct()
    {
        $this->headers = array();
    }

    /**
     * Add a header key/value pair
     *
     * Will overwrite existing headers.
     *
     * @param string $header Header key
     * @param string $value Header value
     */
    public function addHeader($header, $value)
    {
        $this->headers[$header] = $value;

        return $this;
    }

    /**
     * Fetch the body of the message
     *
     * @return string
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * Fetch the current header key/value pairs
     *
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * Return the current HTTP version
     *
     * @return string
     */
    public function getHttpVersion()
    {
        return $this->httpVersion;
    }

    /**
     * Decodes and returns the body of the message, assuming it is JSON-encoded
     *
     * Accepts optional parameters which are passed directly to json_decode
     *
     * @return mixed
     */
    public function getJsonDecodedBody()
    {
        $args = func_get_args();
        array_unshift($args, $this->getBody());

        return call_user_func_array('json_decode', $args);
    }

    /**
     * Return the current HTTP response code
     *
     * @return int
     */
    public function getResponseCode()
    {
        return $this->responseCode;
    }

    /**
     * Return the current HTTP response status
     *
     * @return string
     */
    public function getResponseStatus()
    {
        return $this->responseStatus;
    }

    /**
     * Set the raw body of the messag
     *
     * @return Message
     */
    public function setBody($body)
    {
        $this->body = $body;

        return $this;
    }

    /**
     * Set the raw body of the messag
     *
     * @return Message
     */
    public function setHeaders(array $headers)
    {
        $this->headers = array_change_key_case($headers);

        return $this;
    }

    /**
     * Set the HTTP version for the message
     *
     * @return Message
     */
    public function setHttpVersion($version)
    {
        $this->httpVersion = $version;

        return $this;
    }

    /**
     * Encodes the body of the message as JSON and sets as the message body
     *
     * Accepts optional arguments which are passed directly to json_encode.
     * Sets/overwrites the Content-Type header appropriately.
     *
     * @return Message
     */
    public function setJsonDecodedBody($body)
    {
        $this->addHeader('Content-Type', 'application/json;charset=UTF-8');

        $args = func_get_args();

        $this->setBody(call_user_func_array('json_encode', $args));

        return $this;
    }

    /**
     * Set multiple values for the Message object
     *
     * You may use this as a shortcut for setting options, but the method
     * names must conform to <pre>'set' . ucfirst($key)</pre> and the
     * methods can only accept a single value.
     *
     * @param $options
     * @return Message
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
     * Set HTTP code
     *
     * @param int $code
     * @return Message
     */
    public function setResponseCode($code)
    {
        $this->responseCode = filter_var(
            $code,
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
     * Set HTTP response status
     *
     * @param string $status
     * @return Message
     */
    public function setResponseStatus($status)
    {
        $this->responseStatus = $status;

        return $this;
    }
}
