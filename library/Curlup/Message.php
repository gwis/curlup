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

    public function addHeader($header, $value)
    {
        $this->headers[$header] = $value;

        return $this;
    }

    public function getBody()
    {
        return $this->body;
    }

    public function getHeaders()
    {
        return $this->headers;
    }

    public function getHttpVersion()
    {
        return $this->httpVersion;
    }

    public function getJsonDecodedBody()
    {
        $args = func_get_args();
        array_unshift($args, $this->getBody());

        return call_user_func_array('json_decode', $args);
    }

    public function getResponseCode()
    {
        return $this->responseCode;
    }

    public function getResponseStatus()
    {
        return $this->responseStatus;
    }

    public function setBody($body)
    {
        $this->body = $body;

        return $this;
    }

    public function setHeaders(array $headers)
    {
        $this->headers = array_change_key_case($headers);

        return $this;
    }

    public function setHttpVersion($version)
    {
        $this->httpVersion = $version;

        return $this;
    }

    public function setJsonDecodedBody($body)
    {
        $this->addHeader('Content-Type', 'application/json;charset=UTF-8');

        $args = func_get_args();

        $this->setBody(call_user_func_array('json_encode', $args));

        return $this;
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

    public function setResponseStatus($status)
    {
        $this->responseStatus = $status;

        return $this;
    }
}
