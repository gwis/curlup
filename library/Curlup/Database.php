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
 * CouchDB database
 *
 * @category Curlup
 * @package Curlup
 */
class Database
{
    /**
     * CouchDB instance
     *
     * @var CouchDb
     */
    protected $couchDb;

    /**
     * Database name
     *
     * @var string
     */
    protected $databaseName;

    /**
     * Accepts a CouchDB instance and a database name
     *
     * @param $couchDb
     * @param string $databaseName
     */
    public function __construct(CouchDb $couchDb, $databaseName)
    {
        $this->couchDb = $couchDb;
        $this->databaseName = $databaseName;
    }

    /**
     * Corresponds to the /<db>/_all_docs API endpoint
     *
     * @return Request
     */
    public function allDocs()
    {
        $request = $this->getCouchDb()->createRequest(
            sprintf(
                '/%s/_all_docs',
                urlencode($this->getDatabaseName())
            ),
            Request::HTTP_METHOD_GET
        );

        return $request;
    }

    /**
     * Corresponds to the /<db>/_all_docs API endpoint
     *
     * Accepts multiple document keys.
     *
     * @param $keys
     * @return Request
     */
    public function allDocsMultiKey(array $keys)
    {
        $request = $this->getCouchDb()->createRequest(
            sprintf(
                '/%s/_all_docs',
                urlencode($this->getDatabaseName())
            ),
            Request::HTTP_METHOD_POST
        )
        ->setJsonDecodedBody(array('keys' => $keys));

        return $request;
    }

    /**
     * Correponds to the /<db>/_bulk_docs API endpoint
     *
     * Accepts an array of documents, and the concurrency method you wish to
     * use when attempting to persist them.
     *
     * @param $docs
     * @param bool $allOrNothing See CouchDB documentation for details
     * @return Request
     */
    public function bulkDocs(array $docs, $allOrNothing = false)
    {
        $request = $this->getCouchDb()->createRequest(
            sprintf(
                '/%s/_bulk_docs',
                urlencode($this->getDatabaseName())
            ),
            Request::HTTP_METHOD_POST
        )
        ->setJsonDecodedBody(
            array(
                'all_or_nothing' => $allOrNothing,
                'docs' => $docs
            )
        );

        return $request;
    }

    /**
     * Corresponds to the /<db>/_changes API endpoint
     *
     * @return Request
     */
    public function changes()
    {
        $request = $this->getCouchDb()->createRequest(
            sprintf(
                '/%s/_changes',
                urlencode($this->getDatabaseName())
            ),
            Request::HTTP_METHOD_GET
        );

        return $request;
    }

    /**
     * Corresponds to the /<db>/_compact API endpoint
     *
     * @return Request
     */
    public function compact()
    {
        $request = $this->getCouchDb()->createRequest(
            sprintf(
                '/%s/_compact',
                urlencode($this->getDatabaseName())
            ),
            Request::HTTP_METHOD_POST
        );

        return $request;
    }

    /**
     * Corresponds to the /<db>/_compact API endpoint
     *
     * Compacts all views in the given view group. See CouchDB documentation
     * for more details.
     *
     * @return Request
     */
    public function compactViews($viewGroup)
    {
        if (empty($viewGroup)) {
            throw new \InvalidArgumentException(
                'supplied view group must not be empty'
            );
        }

        $request = $this->getCouchDb()->createRequest(
            sprintf(
                '/%s/_compact/%s',
                urlencode($this->getDatabaseName()),
                urlencode($viewGroup)
            ),
            Request::HTTP_METHOD_POST
        );

        return $request;
    }

    /**
     * Delete the given document ID
     *
     * Corresponds to the DELETE /<db>/<docId> API endpoint
     *
     * @param string $id Document ID
     * @param string $rev Revision ID to delete
     * @return Request
     */
    public function deleteDocument($id, $rev)
    {
        if (empty($id)) {
            throw new \InvalidArgumentException(
                'supplied document ID must not be empty'
            );
        }

        if (empty($rev)) {
            throw new \InvalidArgumentException(
                'supplied revision must not be empty'
            );
        }

        $request = $this->getCouchDb()->createRequest(
            sprintf(
                '/%s/%s',
                urlencode($this->getDatabaseName()),
                urlencode($id)
            ),
            Request::HTTP_METHOD_DELETE
        )
        ->setQueryData(array('rev' => $rev));

        return $request;
    }

    /**
     * Fetch results of a list function in a given design document
     *
     * Corresponds to the GET /<db>/_design/<ddoc>/_list/<listId>/<viewId>/?<viewddoc> API endpoint
     *
     * @param string $list List function
     * @param string $listDesignDocument List design document
     * @param string $view View function
     * @param string $viewDesignDocument Optional view design document
     * @return Request
     */
    public function designList($list, $listDesignDocument, $view, $viewDesignDocument = '')
    {
        if (empty($list)) {
            throw new \InvalidArgumentException(
                'supplied list function must not be empty'
            );
        }

        if (empty($listDesignDocument)) {
            throw new \InvalidArgumentException(
                'supplied list design document must not be empty'
            );
        }

        if (empty($view)) {
            throw new \InvalidArgumentException(
                'supplied list view must not be empty'
            );
        }

        if (empty($viewDesignDocument)) {
            $viewFragment = urlencode($view);
        } else {
            $viewFragment = sprintf(
                '%s/%s',
                urlencode($viewDesignDocument),
                urlencode($view)
            );
        }

        $fragment = sprintf(
            '_design/%s/_list/%s/%s',
            urlencode($listDesignDocument),
            urlencode($list),
            $viewFragment
        );

        $request = $this->getCouchDb()->createRequest(
            sprintf(
                '/%s/%s',
                urlencode($this->getDatabaseName()),
                $fragment
            ),
            Request::HTTP_METHOD_GET
        );

        return $request;
    }

    /**
     * Fetch results of specific keys against a list function in a given design document
     *
     * Corresponds to the POST /<db>/_design/<ddoc>/_list/<listId>/<viewId>/?<viewddoc> API endpoint
     *
     * @param $keys Keys to query for
     * @param string $list List function
     * @param string $listDesignDocument List design document
     * @param string $view View function
     * @param string $viewDesignDocument Optional view design document
     * @return Request
     */
    public function designListMultiKey(array $keys, $list, $listDesignDocument, $view, $viewDesignDocument = '')
    {
        $request = $this->designList(
            $list,
            $listDesignDocument,
            $view,
            $viewDesignDocument
        )
        ->setMethod(Request::HTTP_METHOD_POST)
        ->setJsonDecodedBody(array('keys' => $keys));

        return $request;
    }

    /**
     * Fetch results of a show function in a given design document against a given document
     *
     * Corresponds to the GET /<db>/_design/<ddoc>/_show/<showId>/<docId> API endpoint
     *
     * @param string $show Show function
     * @param string $showDesignDocument Show design document
     * @param string $docId Document ID to show
     * @return Request
     */
    public function designShow($show, $showDesignDocument, $docId)
    {
        if (empty($show)) {
            throw new \InvalidArgumentException(
                'supplied show function must not be empty'
            );
        }

        if (empty($showDesignDocument)) {
            throw new \InvalidArgumentException(
                'supplied show design document must not be empty'
            );
        }

        if (empty($docId)) {
            throw new \InvalidArgumentException(
                'supplied document ID must not be empty'
            );
        }

        $fragment = sprintf(
            '_design/%s/_show/%s/%s',
            urlencode($showDesignDocument),
            urlencode($show),
            $docId
        );

        $request = $this->getCouchDb()->createRequest(
            sprintf(
                '/%s/%s',
                urlencode($this->getDatabaseName()),
                $fragment
            ),
            Request::HTTP_METHOD_GET
        );

        return $request;
    }

    /**
     * Fetch results of a view function in a given design document
     *
     * Corresponds to the GET /<db>/_design/<ddoc>/_view/<viewId> API endpoint
     *
     * @param string $designDocument View design document
     * @param string $view View function
     * @return Request
     */
    public function designView($designDocument, $view)
    {
        if (empty($designDocument)) {
            throw new \InvalidArgumentException(
                'supplied view design document must not be empty'
            );
        }

        if (empty($view)) {
            throw new \InvalidArgumentException(
                'supplied view function must not be empty'
            );
        }

        $request = $this->getCouchDb()->createRequest(
            sprintf(
                '/%s/_design/%s/_view/%s',
                urlencode($this->getDatabaseName()),
                urlencode($designDocument),
                urlencode($view)
            ),
            Request::HTTP_METHOD_GET
        );

        return $request;
    }

    /**
     * Fetch results of specific keys against a view function in a given design document
     *
     * Corresponds to the GET /<db>/_design/<ddoc>/_view/<viewId> API endpoint
     *
     * @param $keys Keys to query for
     * @param string $designDocument View design document
     * @param string $view View function
     * @return Request
     */
    public function designViewMultiKey(array $keys, $designDocument, $view)
    {
        $request = $this->getCouchDb()->createRequest(
            sprintf(
                '/%s/_design/%s/_view/%s',
                urlencode($this->getDatabaseName()),
                urlencode($designDocument),
                urlencode($view)
            ),
            Request::HTTP_METHOD_POST
        )
        ->setJsonDecodedBody(array('keys' => $keys));

        return $request;
    }

    /**
     * Corresponds to the POST /<db>/_ensure_full_commit API endpoint
     *
     * @return Request
     */
    public function ensureFullCommit()
    {
        $request = $this->getCouchDb()->createRequest(
            sprintf(
                '/%s/_ensure_full_commit',
                urlencode($this->getDatabaseName())
            ),
            Request::HTTP_METHOD_POST
        );

        return $request;
    }

    /**
     * Retrieve a single attachment from the database by attachment ID
     *
     * @param string $docId Document for the attachment
     * @param string $attachmentId Attachment id
     * @return Request
     */
    public function fetchAttachment($docId, $attachmentId)
    {
        if (empty($docId)) {
            throw new \InvalidArgumentException(
                'supplied document ID must not be empty'
            );
        }

        if (empty($attachmentId)) {
            throw new \InvalidArgumentException(
                'supplied attachment ID must not be empty'
            );
        }

        $request = $this->getCouchDb()->createRequest(
            sprintf(
                '/%s/%s/%s',
                urlencode($this->getDatabaseName()),
                urlencode($docId),
                urlencode($attachmentId)
            ),
            Request::HTTP_METHOD_GET
        );

        return $request;
    }

    /**
     * Retrieve a single document from the database by document ID
     *
     * @param string $id Document ID
     * @return Request
     */
    public function fetchDocument($id)
    {
        if (empty($id)) {
            throw new \InvalidArgumentException(
                'supplied document ID must not be empty'
            );
        }

        $request = $this->getCouchDb()->createRequest(
            sprintf(
                '/%s/%s',
                urlencode($this->getDatabaseName()),
                urlencode($id)
            ),
            Request::HTTP_METHOD_GET
        );

        return $request;
    }

    /**
     * Get the underlying CouchDB instance
     *
     * @return CouchDb
     */
    public function getCouchDb()
    {
        return $this->couchDb;
    }

    /**
     * Get the current database name
     *
     * @return string
     */
    public function getDatabaseName()
    {
        return $this->databaseName;
    }

    /**
     * POST a raw pre-encoded JSON document to the database
     *
     * @param string $doc JSON-encoded document
     * @return Request
     */
    public function postRawDocument($doc)
    {
        $request = $this->getCouchDb()->createRequest(
            sprintf(
                '/%s/',
                urlencode($this->getDatabaseName())
            ),
            Request::HTTP_METHOD_POST
        )
        ->addHeader('Content-Type', 'application/json;charset=UTF-8')
        ->setBody($doc);

        return $request;
    }

    /**
     * PUT a raw pre-encoded JSON document to the database
     *
     * @param string $doc JSON-encoded document
     * @param string $id Document ID
     * @return Request
     */
    public function putRawDocument($doc, $id)
    {
        $request = $this->getCouchDb()->createRequest(
            sprintf(
                '/%s/%s',
                urlencode($this->getDatabaseName()),
                urlencode($id)
            ),
            Request::HTTP_METHOD_PUT
        )
        ->addHeader('Content-Type', 'application/json;charset=UTF-8')
        ->setBody($doc);

        return $request;
    }

    /**
     * Attach something to a document
     *
     * @param string $body Attachment body
     * @param string $docId Document ID to attach to
     * @param string $attachmentId Attachment ID
     * @param string $rev Document revision to attach to
     * @return Request
     */
    public function saveAttachment($body, $docId, $attachmentId, $rev)
    {
        $request = $this->getCouchDb()->createRequest(
            sprintf(
                '/%s/%s/%s',
                urlencode($this->getDatabaseName()),
                urlencode($docId),
                urlencode($attachmentId)
            ),
            Request::HTTP_METHOD_PUT
        )
        ->setQueryData(array('rev' => $rev))
        ->setBody($body);

        return $request;
    }

    /**
     * Persist a document to the database
     *
     * @param $doc Array or object representation of a document
     * @return Request
     */
    public function saveDocument($doc)
    {
        $encodedId = '';
        $hasId = false;
        $method = Request::HTTP_METHOD_POST;

        if (is_object($doc)) {
            $hasId = !empty($doc->_id);
            if ($hasId) {
                $encodedId = urlencode($doc->_id);
            }
        } elseif (is_array($doc)) {
            $hasId = !empty($doc['_id']);
            if ($hasId) {
                $encodedId = urlencode($doc['_id']);
            }
        }

        if ($hasId) {
            $method = Request::HTTP_METHOD_PUT;
        }

        $request = $this->getCouchDb()->createRequest(
            sprintf(
                '/%s/%s',
                urlencode($this->getDatabaseName()),
                $encodedId
            ),
            $method
        )
        ->setJsonDecodedBody($doc);

        return $request;
    }

    /**
     * Corresponds to the /<db>/_temp_view API endpoint
     *
     * @param string $viewFunction View function to execute on the server
     * @return Request
     */
    public function tempView($viewFunction)
    {
        $request = $this->getCouchDb()->createRequest(
            sprintf(
                '/%s/_temp_view',
                urlencode($this->getDatabaseName())
            ),
            Request::HTTP_METHOD_POST
        )
        ->setJsonDecodedBody($viewFunction);

        return $request;
    }

    /**
     * Corresponds to the /<db>/_view_cleanup API endpoint
     *
     * @return Request
     */
    public function viewCleanup()
    {
        $request = $this->getCouchDb()->createRequest(
            sprintf(
                '/%s/_view_cleanup',
                urlencode($this->getDatabaseName())
            ),
            Request::HTTP_METHOD_POST
        );

        return $request;
    }
}
