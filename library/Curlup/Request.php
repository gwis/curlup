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

class Request extends Message
{
    protected $curlHandle;
    protected $method;
    protected $queryData;
    protected $timeout;
    protected $uri;

    public function __construct(array $options = array())
    {
        $this->curlHandle = curl_init();
        $this->headers = array(
            'Expect' => ''
        );
        $this->queryData = array();
        $this->timeout = 10;

        $this->setOptions($options);
    }

    public function __destruct()
    {
        curl_close($this->curlHandle);
    }

    public function addQueryData($key, $value)
    {
        $this->queryData[$key] = $value;

        return $this;
    }

    public function getCurlHandle()
    {
        return $this->curlHandle;
    }

    public function getCurlOptions()
    {
        $curlOptions = array(
            CURLOPT_BINARYTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => $this->timeout,
            CURLOPT_CUSTOMREQUEST => $this->method,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HEADER => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_MAXREDIRS => 3,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_URL => $this->uri,
            CURLOPT_USERAGENT => 'curlup/0.1dev'
        );

        $headerCount = count($this->headers);
        if ($headerCount > 0) {
            $curlHeaders = array_map(
                'sprintf',
                array_fill(0, $headerCount, '%s: %s'),
                array_keys($this->headers),
                array_values($this->headers)
            );

            $curlOptions[CURLOPT_HTTPHEADER] = $curlHeaders;
        }

        $queryData = $this->getQueryData();
        if (count($queryData) > 0) {
            $curlOptions[CURLOPT_URL] .= '?' . http_build_query(
                $queryData,
                null,
                '&'
            );
        }

        $body = $this->getBody();
        if (strlen($body) > 0) {
            $curlOptions[CURLOPT_POSTFIELDS] = $body;
        }

        return $curlOptions;
    }


    public function getMethod()
    {
        return $this->method;
    }

    public function getQueryData()
    {
        return $this->queryData;
    }

    public function getTimeout()
    {
        return $this->timeout;
    }

    public function getUri()
    {
        return $this->uri;
    }

    public function send()
    {
        curl_setopt_array(
            $this->curlHandle,
            $this->getCurlOptions()
        );

        $ret = curl_exec($this->curlHandle);

        if ($ret === false) {
            throw new CurlException(
                curl_error($this->curlHandle),
                curl_errno($this->curlHandle)
            );
        }

        return Response::factory($ret);
    }

    /**
     * Convenience method for sending a request and decoding the response
     *
     * This calls the {@see send()} method and then the
     * {@see Message::getJsonDecodedBody()} method, and returns the result.
     *
     * This function accepts parameters which will be passed directly to
     * {@see Message::getJsonDecodedBody()}. This can be generally used to
     * pass extra arguments to json_decode.
     *
     * @return mixed
     */
    public function sendAndDecode()
    {
        return call_user_func_array(
            array($this->send(), 'getJsonDecodedBody'),
            func_get_args()
        );
    }

    public function setMethod($method)
    {
        $this->method = $method;
    }

    public function setQueryData(array $queryData)
    {
        $this->queryData = $queryData;

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

    public function setUri($uri)
    {
        $this->uri = $uri;
    }
}
