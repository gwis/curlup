<?php

namespace CurlupTest;

use Curlup;

class CouchDbTest extends \PHPUnit_Framework_TestCase
{
    public static function setUpBeforeClass()
    {
        $db = new Curlup\CouchDb(array(
            'uri' => TESTS_CURLUP_COUCHDB_URI
        ));

        $db->createDb('test1' . TESTS_CURLUP_COUCHDB_DATABASE_SUFFIX)->send();
        $db->createDb('test2' . TESTS_CURLUP_COUCHDB_DATABASE_SUFFIX)->send();
    }

    public static function tearDownAfterClass()
    {
        $db = new Curlup\CouchDb(array(
            'uri' => TESTS_CURLUP_COUCHDB_URI
        ));

        $db->deleteDb('test1' . TESTS_CURLUP_COUCHDB_DATABASE_SUFFIX)->send();
        $db->deleteDb('test2' . TESTS_CURLUP_COUCHDB_DATABASE_SUFFIX)->send();
    }

    public function setUp()
    {
        $this->db = new Curlup\CouchDb(array(
            'uri' => TESTS_CURLUP_COUCHDB_URI
        ));
    }

    public function testConstructWithOptions()
    {
        $db = new Curlup\CouchDb(array(
            'timeout' => 42,
            'uri' => 'http://example.com/couchdb'
        ));

        $this->assertEquals('http://example.com/couchdb', $db->getUri());
        $this->assertEquals(42, $db->getTimeout());
    }

    public function testCreateRequest()
    {
        $r = $this->db->createRequest('/test', Curlup\Message::HTTP_METHOD_GET);

        $this->assertInstanceOf(
            'Curlup\\Request',
            $r
        );

        $this->assertEquals(
            TESTS_CURLUP_COUCHDB_URI . '/test',
            $r->getUri()
        );
    }

    public function testCreateRequestPool()
    {
        $this->db->setTimeout(42);

        $rp = $this->db->createRequestPool();

        $this->assertInstanceOf(
            'Curlup\\RequestPool',
            $rp
        );

        $this->assertEquals(
            42,
            $rp->getTimeout(),
            'Timeout was not passed to RequestPool'
        );
    }

    public function testAllDbs()
    {
        $response = $this->db->allDbs()->sendAndDecode();

        $this->assertContains('test1' . TESTS_CURLUP_COUCHDB_DATABASE_SUFFIX, $response);
        $this->assertContains('test2' . TESTS_CURLUP_COUCHDB_DATABASE_SUFFIX, $response);
    }

    public function testRoot()
    {
        $response = $this->db->root()->sendAndDecode();

        $this->assertObjectHasAttribute('couchdb', $response);
        $this->assertEquals('Welcome', $response->couchdb);
    }

    public function testStats()
    {
        $response = $this->db->stats()->sendAndDecode();

        $this->assertObjectHasAttribute('couchdb', $response);
        $this->assertObjectHasAttribute('open_databases', $response->couchdb);
    }

    public function testSpecificStats()
    {
        $response = $this->db->stats(array('couchdb', 'open_databases'))->sendAndDecode();

        $this->assertObjectHasAttribute('couchdb', $response);
        $this->assertObjectHasAttribute('open_databases', $response->couchdb);

        $this->assertEquals(1, count(get_object_vars($response)));
        $this->assertEquals(1, count(get_object_vars($response->couchdb)));
    }

    public function testUuids()
    {
        $response = $this->db->uuids()->sendAndDecode();

        $this->assertObjectHasAttribute('uuids', $response);
        $this->assertEquals(1, count($response->uuids));

        $response = $this->db->uuids()->setQueryData(array('count' => 10))->sendAndDecode();

        $this->assertObjectHasAttribute('uuids', $response);
        $this->assertEquals(10, count($response->uuids));
    }

    /**
     * @expectedException Curlup\Exception
     */
    public function testSetInvalidUri()
    {
        $this->db->setUri('foo');
    }
}
