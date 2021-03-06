<?php

namespace arthur\tests\cases\data\collection;

use arthur\data\Connections;
use arthur\data\source\MongoDb;
use arthur\data\source\http\adapter\CouchDb;
use arthur\data\entity\Document;
use arthur\data\collection\DocumentSet;
use arthur\tests\mocks\data\model\MockDocumentPost;
use arthur\tests\mocks\data\source\mongo_db\MockResult;
use arthur\tests\mocks\data\model\MockDocumentMultipleKey;

class DocumentSetTest extends \arthur\test\Unit 
{
	protected $_model = 'arthur\tests\mocks\data\model\MockDocumentPost';
	protected $_backup = array();

	public function skip() 
	{
		$this->skipIf(!MongoDb::enabled(), 'MongoDb is not enabled');
		$this->skipIf(!CouchDb::enabled(), 'CouchDb is not enabled');
	}

	public function setUp() 
	{
		if(empty($this->_backup)) 
		{
			foreach(Connections::get() as $conn) {
				$this->_backup[$conn] = Connections::get($conn, array('config' => true));
			}
		}
		Connections::reset();

		Connections::add('mongo', array('type' => 'MongoDb', 'autoConnect' => false));
		Connections::add('couch', array('type' => 'http', 'adapter' => 'CouchDb'));

		MockDocumentPost::config(array('connection' => 'mongo'));
		MockDocumentMultipleKey::config(array('connection' => 'couch'));
	}

	public function tearDown() 
	{
		foreach($this->_backup as $name => $config) {
			Connections::add($name, $config);
		}
	}

	public function testPopulateResourceClose() 
	{
		$resource = new MockResult();
		$doc      = new DocumentSet(array('model' => $this->_model, 'result' => $resource));
		$model    = $this->_model;

		$result = $doc->rewind();
		$this->assertTrue($result instanceof Document);
		$this->assertTrue(is_object($result['_id']));

		$expected = array('_id' => '4c8f86167675abfabdbf0300', 'title' => 'bar');
		$this->assertEqual($expected, $result->data());

		$expected = array('_id' => '5c8f86167675abfabdbf0301', 'title' => 'foo');
		$this->assertEqual($expected, $doc->next()->data());

		$expected = array('_id' => '6c8f86167675abfabdbf0302', 'title' => 'dib');
		$result   = $doc->next()->data();
		$this->assertEqual($expected, $result);

		$this->assertNull($doc->next());
	}

	public function testMappingToNewDocumentSet() 
	{
		$result = new MockResult();
		$model  = $this->_model;
		$doc    = new DocumentSet(compact('model', 'result'));

		$mapped = $doc->map(function($data) { return $data; });
		$this->assertEqual($doc->data(), $mapped->data());
		$this->assertEqual($model, $doc->model());
		$this->assertEqual($model, $mapped->model());
	}
}