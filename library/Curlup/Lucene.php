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
 * couchdb-lucene instance
 *
 * @category Curlup
 * @package Curlup
 */
class Lucene
{
    /**
     * Database instance
     *
     * @var Database
     */
    protected $database;

    /**
     * Key for the local CouchDB instance (known to couchdb-lucene)
     *
     * @var string
     */
    protected $key;

    /**
     * Constructor
     *
     * @param $database CouchDB database instance
     * @return void
     */
    public function __construct(Database $database, $key = 'local')
    {
        $this->database = $database;
        $this->key = $key;
    }

    /**
     * Query a couchdb-lucene index
     *
     * @param string $view View function
     * @param string $viewDesignDocument View design document
     * @return Request
     */
    public function designView($view, $viewDesignDocument)
    {
        if (empty($viewDesignDocument)) {
            throw new \InvalidArgumentException(
                'supplied view design document must not be empty'
            );
        }

        if (empty($view)) {
            throw new \InvalidArgumentException(
                'supplied view function must not be empty'
            );
        }

        $request = $this->database->getCouchDb()->createRequest(
            sprintf(
                '/_fti/%s/%s/_design/%s/%s',
                urlencode($this->key),
                urlencode($this->database->getDatabaseName()),
                urlencode($viewDesignDocument),
                urlencode($view)
            ),
            Request::HTTP_METHOD_GET
        );

        return $request;
    }

    /**
     * Convenience function for issuing a query to a couchdb-lucene view index
     *
     * @param string $query Lucene query to issue
     * @param string $view View function
     * @param string $viewDesignDocument View design document
     * @return Request
     */
    public function query($query, $view, $viewDesignDocument)
    {
        if (empty($query)) {
            throw new \InvalidArgumentException(
                'supplied query must not be empty'
            );
        }

        return $this->designView($view, $viewDesignDocument)
        ->setQueryData(array('q' => $query));
    }

    /**
     * Escape special query characters
     *
     * Adapted from Apache Solr
     *
     * @param string $query Query string to escape
     * @return string
     */
    public static function escape($query)
    {
        return preg_replace('/([\\\+\-\(\)\^\[\]\{\}\*\?\|~":!&;\s])/', '\\\\\1', $query);
    }
}
