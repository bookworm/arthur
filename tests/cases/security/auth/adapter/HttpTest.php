<?php

namespace arthur\tests\cases\security\auth\adapter;

use arthur\tests\mocks\security\auth\adapter\MockHttp;
use arthur\action\Request;

class HttpTest extends \arthur\test\Unit 
{
	public function setUp() 
	{
		$this->request = new Request();
	}

	public function tearDown() { }

	public function testCheckBasicIsFalse() 
	{
		$http = new MockHttp(array('method' => 'basic', 'users' => array('gwoo' => 'li3')));
		$result = $http->check($this->request);
		$this->assertFalse($result);

		$expected = array('WWW-Authenticate: Basic realm="' . basename(ARTHUR_APP_PATH) . '"');
		$result = $http->headers;
		$this->assertEqual($expected, $result);
	}

	public function testCheckBasicIsTrue() 
	{
		$request = new Request(array(
			'env' => array('PHP_AUTH_USER' => 'gwoo', 'PHP_AUTH_PW' => 'li3')
		));
		$http = new MockHttp(array('method' => 'basic', 'users' => array('gwoo' => 'li3')));
		$result = $http->check($request);
		$this->assertTrue($result);

		$expected = array();
		$result   = $http->headers;
		$this->assertEqual($expected, $result);
	}

	public function testCheckDigestIsFalse() 
	{
		$http   = new MockHttp(array('realm' => 'app', 'users' => array('gwoo' => 'li3')));
		$result = $http->check($this->request);
		$this->assertFalse($result);
		$this->assertPattern('/Digest/', $http->headers[0]);
		$this->assertPattern('/realm="app",/', $http->headers[0]);
		$this->assertPattern('/qop="auth",/', $http->headers[0]);
		$this->assertPattern('/nonce=/', $http->headers[0]);
	}

	public function testCheckDigestIsTrue() 
	{
		$request = new Request(array(
			'env' => array('PHP_AUTH_DIGEST' =>
				'qop="auth",nonce="4bca0fbca7bd0",'
				. 'nc="00000001",cnonce="95b2cd1e179bf5414e52ed62811481cf",'
				. 'uri="/http_auth",realm="app",'
				. 'opaque="d3fb67a7aa4d887ec4bf83040a820a46",username="gwoo",'
				. 'response="04d7d878c67f289f37e553d2025e3a52"')
		));
		$http   = new MockHttp(array('realm' => 'app', 'users' => array('gwoo' => 'li3')));
		$result = $http->check($request);
		$this->assertTrue($result);

		$expected = array();
		$result   = $http->headers;
		$this->assertEqual($expected, $result);
	}
}