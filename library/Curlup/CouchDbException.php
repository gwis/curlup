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

/**
 * Exception thrown when there is a CouchDB error
 *
 * CouchDB errors include, but are not limited to:
 *
 * <pre>
 * cannot find a document
 * unexpected or invalid values in headers
 * CouchDB crashed
 * </pre>
 */
class CouchDbException extends Exception
{
    /**
     * Error as reported by CouchDB
     *
     * These are not currently well-documented, but they are represented here
     * as a string, and in the CouchDB source code as an erlang atom.
     *
     * Example: "not_found"
     *
     * @var string
     */
    protected $error;

    /**
     * Reason for error as reported by CouchDB
     *
     * These are not currently well-documented, but they are strings.
     *
     * Example: "missing"
     *
     * @var string
     */
    protected $reason;

    /**
     * Constructor
     *
     * Decodes the message, which since it comes from CouchDB is assumed to
     * be valid JSON, and then sets up the exception members.
     */
    public function __construct($message = '', $code = 0, \Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);

        $decodedMessage = json_decode($message);

        $this->error = $decodedMessage->error;
        $this->reason = $decodedMessage->reason;
    }

    /**
     * Retrieve the CouchDB error
     *
     * @return string
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * Retrieve the CouchDB error reason
     *
     * @return string
     */
    public function getReason()
    {
        return $this->reason;
    }
}
