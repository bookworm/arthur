<?php

namespace arthur\tests\cases\storage\cache\adapter;

use SplFileInfo;
use arthur\core\Libraries;
use arthur\storage\cache\adapter\File;

class FileTest extends \arthur\test\Unit 
{
	protected $_hasEmpty = true;

	public function skip() 
	{
		$directory  = new SplFileInfo(Libraries::get(true, 'resources') . "/tmp/cache/");
		$accessible = ($directory->isDir() && $directory->isReadable() && $directory->isWritable());
		$message    = 'The File cache adapter path does not have the proper permissions.';
		$this->skipIf(!$accessible, $message);
	}

	public function setUp()
	 {
		$this->_hasEmpty = file_exists(Libraries::get(true, 'resources') . "/tmp/cache/empty");
		$this->File      = new File();
	}

	public function tearDown() 
	{
		if($this->_hasEmpty) {
			touch(Libraries::get(true, 'resources') . "/tmp/cache/empty");
			touch(Libraries::get(true, 'resources') . "/tmp/cache/templates/empty");
		}
		unset($this->File);
	}

	public function testEnabled() 
	{
		$file = $this->File;
		$this->assertTrue($file::enabled());
	}

	public function testWrite() 
	{
		$key    = 'key';
		$data   = 'data';
		$expiry = '+1 minute';
		$time   = time() + 60;

		$closure = $this->File->write($key, $data, $expiry);
		$this->assertTrue(is_callable($closure));

		$params   = compact('key', 'data', 'expiry');
		$result   = $closure($this->File, $params, null);
		$expected = 25;
		$this->assertEqual($expected, $result);

		$this->assertTrue(file_exists(Libraries::get(true, 'resources') . "/tmp/cache/{$key}"));
		$this->assertEqual(
			file_get_contents(Libraries::get(true, 'resources') . "/tmp/cache/{$key}"),
			"{:expiry:$time}\ndata"
		);

		$this->assertTrue(unlink(Libraries::get(true, 'resources') . "/tmp/cache/{$key}"));
		$this->assertFalse(file_exists(Libraries::get(true, 'resources') . "/tmp/cache/{$key}"));
	}

	public function testWriteDefaultCacheExpiry() 
	{
		$File = new File(array('expiry' => '+1 minute'));
		$key  = 'default_keykey';
		$data = 'data';
		$time = time() + 60;

		$closure = $File->write($key, $data);
		$this->assertTrue(is_callable($closure));

		$params   = compact('key', 'data');
		$result   = $closure($File, $params, null);
		$expected = 25;
		$this->assertEqual($expected, $result);

		$this->assertTrue(file_exists(Libraries::get(true, 'resources') . "/tmp/cache/{$key}"));
		$this->assertEqual(
			file_get_contents(Libraries::get(true, 'resources') . "/tmp/cache/{$key}"),
			"{:expiry:{$time}}\ndata"
		);

		$this->assertTrue(unlink(Libraries::get(true, 'resources') . "/tmp/cache/{$key}"));
		$this->assertFalse(file_exists(Libraries::get(true, 'resources') . "/tmp/cache/{$key}"));
	}

	public function testRead() 
	{
		$key  = 'key';
		$time = time() + 60;

		$closure = $this->File->read($key);
		$this->assertTrue(is_callable($closure));

		$path = Libraries::get(true, 'resources') . "/tmp/cache/{$key}";
		file_put_contents($path, "{:expiry:$time}\ndata");
		$this->assertTrue(file_exists($path));

		$params = compact('key');
		$result = $closure($this->File, $params, null);
		$this->assertEqual('data', $result);

		unlink($path);

		$key     = 'non_existent';
		$params  = compact('key');
		$closure = $this->File->read($key);
		$this->assertTrue(is_callable($closure));

		$result = $closure($this->File, $params, null);
		$this->assertFalse($result);
	}

	public function testExpiredRead() 
	{
		$key  = 'expired_key';
		$time = time() + 1;

		$closure = $this->File->read($key);
		$this->assertTrue(is_callable($closure));
		$path = Libraries::get(true, 'resources') . "/tmp/cache/{$key}";

		file_put_contents($path, "{:expiry:$time}\ndata");
		$this->assertTrue(file_exists($path));

		sleep(2);
		$params = compact('key');
		$this->assertFalse($closure($this->File, $params, null));

	}

	public function testDelete() 
	{
		$key  = 'key_to_delete';
		$time = time() + 1;
		$path = Libraries::get(true, 'resources') . "/tmp/cache/{$key}";

		file_put_contents($path, "{:expiry:$time}\ndata");
		$this->assertTrue(file_exists($path));

		$closure = $this->File->delete($key);
		$this->assertTrue(is_callable($closure));

		$params = compact('key');
		$this->assertTrue($closure($this->File, $params, null));

		$key    = 'non_existent';
		$params = compact('key');
		$this->assertFalse($closure($this->File, $params, null));
	}

	public function testClear() 
	{
		$key  = 'key_to_clear';
		$time = time() + 1;
		$path = Libraries::get(true, 'resources') . "/tmp/cache/{$key}";
		file_put_contents($path, "{:expiry:$time}\ndata");

		$result = $this->File->clear();
		$this->assertTrue($result);
		$this->assertFalse(file_exists($path));

		$result = touch(Libraries::get(true, 'resources') . "/tmp/cache/empty");
		$this->assertTrue($result);
	}

	public function testIncrement() 
	{
		$key    = 'key_to_increment';
		$result = $this->File->increment($key);
		$this->assertEqual(false, $result);
	}

	public function testDecrement() 
	{
		$key    = 'key_to_decrement';
		$result = $this->File->decrement($key);
		$this->assertEqual(false, $result);
	}
}