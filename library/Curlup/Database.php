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

    public function compactViews($designDocument)
    {
        $request = $this->getCouchDb()->createRequest(
            sprintf(
                '/%s/_compact/%s',
                urlencode($this->getDatabaseName()),
                urlencode($designDocument)
            ),
            Request::HTTP_METHOD_POST
        );

        return $request;
    }

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

    public function designView($designDocument, $view)
    {
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
        $docId = (string)$docId;
        $attachmentId = (string)$attachmentId;

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

    public function fetchDocument($id)
    {
        $id = (string)$id;

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

    public function getCouchDb()
    {
        return $this->couchDb;
    }

    public function getDatabaseName()
    {
        return $this->databaseName;
    }

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
