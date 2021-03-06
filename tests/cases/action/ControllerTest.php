<?php

namespace arthur\tests\cases\action;

use arthur\action\Request;
use arthur\action\Controller;
use arthur\tests\mocks\action\MockPostsController;
use arthur\tests\mocks\action\MockControllerRequest;

class ControllerTest extends \arthur\test\Unit 
{
	public function testConstructionWithCustomRequest() 
	{
		$request         = new MockControllerRequest();
		$postsController = new MockPostsController(compact('request'));
		$result          = get_class($postsController->request);
		$this->assertEqual($result, 'arthur\tests\mocks\action\MockControllerRequest');
	}

	public function testMethodInvocation() 
	{
		$postsController = new MockPostsController();
		$result          = $postsController->__invoke(null, array('action' => 'index', 'args' => array()));

		$this->assertTrue(is_a($result, 'arthur\action\Response'));
		$this->assertEqual('List of posts', $result->body());
		$this->assertEqual(array('Content-type' => 'text/plain; charset=UTF-8'), $result->headers);

		$result2 = $postsController(null, array('action' => 'index', 'args' => array()));
		$this->assertEqual($result2, $result);

		$postsController = new MockPostsController();
		$this->expectException('/Unhandled media type/');
		$result = $postsController(null, array('action' => 'index', 'args' => array(true)));

		$this->assertTrue(is_a($result, 'arthur\action\Response'));
		$this->assertEqual($result->body, '');

		$headers = array('Content-type' => 'text/html; charset=UTF-8');
		$this->assertEqual($result->headers, $headers);

		$result = $postsController->access('_render');
		$this->assertEqual($result['data'], array('foo' => 'bar'));

		$postsController = new MockPostsController();
		$result = $postsController(null, array('action' => 'view', 'args' => array('2')));

		$this->assertTrue(is_a($result, 'arthur\action\Response'));
		$this->assertEqual($result->body, "Array\n(\n    [0] => This is a post\n)\n");

		$headers = array('status' => 200, 'Content-type' => 'text/plain; charset=UTF-8');
		$this->assertEqual($result->headers(), $headers);

		$result = $postsController->access('_render');
		$this->assertEqual($result['data'], array('This is a post'));
	}

	public function testRedirectResponse() 
	{
		$postsController = new MockPostsController();

		$result = $postsController(null, array('action' => 'delete'));
		$this->assertEqual($result->body(), '');

		$headers = array('Location' => '/posts');
		$this->assertEqual($result->headers, $headers);

		$postsController = new MockPostsController();
		$result = $postsController(null, array('action' => 'delete', 'args' => array('5')));

		$this->assertEqual($result->body(), 'Deleted 5');
		$this->assertFalse($postsController->stopped);

		$postsController = new MockPostsController(array('classes' => array(
			'response' => 'arthur\tests\mocks\action\MockControllerResponse'
		)));
		$this->assertFalse($postsController->stopped);

		$postsController->__invoke(null, array('action' => 'send'));
		$this->assertTrue($postsController->stopped);

		$result = $postsController->access('_render');
		$this->assertTrue($result['hasRendered']);

		$this->assertEqual($postsController->response->body(), null);
		$this->assertEqual(
			$postsController->response->headers,
			array('Location' => '/posts')
		);
	}

	public function testRenderWithAlternateTemplate() 
	{
		$postsController = new MockPostsController(array('classes' => array(
			'media' => 'arthur\tests\mocks\action\MockMediaClass'
		)));

		$result = $postsController(null, array('action' => 'view2'));
		$this->assertEqual('view', $result->options['template']);
		$this->assertEqual('default', $result->options['layout']);

		$result = $postsController(null, array('action' => 'view3'));
		$this->assertEqual('view', $result->options['template']);
		$this->assertFalse($result->options['layout']);
	}

	public function testRenderWithNamespacedController() 
	{
		$request = new Request();
		$request->params['controller'] = 'arthur\tests\mocks\action\MockPostsController';

		$controller = new MockPostsController(compact('request') + array('classes' => array(
			'media' => 'arthur\tests\mocks\action\MockMediaClass'
		)));

		$controller->render();
		$this->assertEqual('mock_posts', $controller->response->options['controller']);
	}

	public function testRenderWithDataArray() 
	{
		$request = new Request();
		$request->params['controller'] = 'arthur\tests\mocks\action\MockPostsController';

		$controller = new MockPostsController(compact('request') + array('classes' => array(
			'media' => 'arthur\tests\mocks\action\MockMediaClass'
		)));

		$controller->set(array('set' => 'data'));
		$controller->render(array('data' => array('render' => 'data')));

		$expected = array(
			'set'    => 'data',
			'render' => 'data'
		);        
		
		$this->assertEqual($expected, $controller->response->data);
	}

	public function testRenderWithDataSingleIndexedArray() 
	{
		$request = new Request();
		$request->params['controller'] = 'arthur\tests\mocks\action\MockPostsController';

		$controller = new MockPostsController(compact('request') + array('classes' => array(
			'media' => 'arthur\tests\mocks\action\MockMediaClass'
		)));

		$expected = array(array('id' => 1));
		$controller->render(array('data' => $expected));

		$this->assertEqual($expected, $controller->response->data);
	}

	public function testProtectedMethodAccessAttempt() 
	{
		$postsController = new MockPostsController();
		$this->expectException('/^Attempted to invoke a private method/');
		$result = $postsController->__invoke(null, array('action' => 'redirect'));

		$this->assertEqual($result->body, null);
		$this->assertEqual($result->headers(), array());

		$postsController = new MockPostsController();
		$this->expectException('/^Private/');
		$result = $postsController->invoke('_safe');

		$this->assertEqual($result->body, null);
		$this->assertEqual($result->headers(), array());
	}

	public function testResponseStatus() 
	{
		$postsController = new MockPostsController(array('classes' => array(
			'response' => 'arthur\tests\mocks\action\MockControllerResponse'
		)));
		$this->assertFalse($postsController->stopped);

		$postsController(null, array('action' => 'not_found'));

		$result = $postsController->access('_render');
		$this->assertTrue($result['hasRendered']);

		$expected = array('code' => 404, 'message' => 'Not Found');
		$result   = $postsController->response->status;
		$this->assertEqual($expected, $result);
		$result = json_decode($postsController->response->body(), true);
		$this->assertEqual($expected, $result);
	}

	public function testResponseTypeBasedOnRequestType() 
	{
		$request = new MockControllerRequest();
		$request->params['type'] = 'json';

		$postsController = new MockPostsController(array(
			'request' => $request,
			'classes' => array(
				'response' => 'arthur\tests\mocks\action\MockControllerResponse'
			)
		));
		$this->assertFalse($postsController->stopped);

		$postsController($request, array('action' => 'type'));

		$expected = array(
			'type' => 'json', 'data' => array('data' => 'test'), 'auto' => true,
			'layout' => 'default', 'template' => 'type', 'hasRendered' => true, 'negotiate' => false
		);
		$result = $postsController->access('_render');
		$this->assertEqual($expected, $result);

		$result = $postsController->response->headers('Content-type');
		$this->assertEqual('application/json; charset=UTF-8', $result);

		$result = json_decode($postsController->response->body(), true);
		$this->assertEqual(array('data' => 'test'), $result);
	}

	public function testResponseTypeBasedOnRequestParamsType() 
	{
		$request = new MockControllerRequest();
		$request->params['type'] = 'json';

		$postsController = new MockPostsController(array(
			'request' => $request,
			'classes' => array(
				'response' => 'arthur\tests\mocks\action\MockControllerResponse'
			)
		));
		$this->assertFalse($postsController->stopped);

		$postsController->__invoke($request, array('action' => 'type'));

		$expected = array(
			'type' => 'json', 'data' => array('data' => 'test'), 'auto' => true,
			'layout' => 'default', 'template' => 'type', 'hasRendered' => true, 'negotiate' => false
		);
		$result = $postsController->access('_render');
		$this->assertEqual($expected, $result);

		$result = $postsController->response->headers('Content-type');
		$this->assertEqual('application/json; charset=UTF-8', $result);

		$expected = array('data' => 'test');
		$result   = json_decode($postsController->response->body(), true);
		$this->assertEqual($expected, $result);
	}

	public function testManuallySettingTemplate() 
	{
		$postsController = new MockPostsController(array('classes' => array(
			'media' => 'arthur\tests\mocks\action\MockMediaClass'
		)));             
		
		$postsController(new Request(), array('action' => 'changeTemplate'));
		$result = $postsController->access('_render');     
		
		$this->assertEqual('foo', $result['template']);
	}

	public function testSetData() 
	{
		$postController = new MockPostsController();

		$setData = array('foo' => 'bar');
		$postController->set($setData);       
		
		$_render  = $postController->access('_render');
		$data     = $_render['data'];
		$expected = $setData;             
		
		$this->assertEqual($expected, $data);

		$setData = array('foo' => 'baz');
		$postController->set($setData);    
		
		$_render  = $postController->access('_render');
		$data     = $_render['data'];
		$expected = $setData;        
		
		$this->assertEqual($expected, $data);
	}

	public function testResponseTypeBasedOnRequestHeaderType() 
	{
		$request = new MockControllerRequest(array(
			'env' => array('HTTP_ACCEPT' => 'application/json,*/*')
		));

		$postsController = new MockPostsController(array(
			'request' => $request,
			'classes' => array('response' => 'arthur\tests\mocks\action\MockControllerResponse'),
			'render' => array('negotiate' => true)
		));
		$this->assertFalse($postsController->stopped);

		$postsController($request, array('action' => 'type'));

		$expected = array(
			'type'   => 'json', 'data' => array('data' => 'test'), 'auto' => true,
			'layout' => 'default', 'template' => 'type', 'hasRendered' => true, 'negotiate' => true
		);
		$result = $postsController->access('_render');
		$this->assertEqual($expected, $result);

		$result = $postsController->response->headers('Content-type');
		$this->assertEqual('application/json; charset=UTF-8', $result);

		$result = json_decode($postsController->response->body(), true);
		$this->assertEqual(array('data' => 'test'), $result);
	}

	public function testDispatchingWithExplicitControllerName() 
	{
		$request = new Request(array('url' => '/'));
		$request->params = array(
			'controller' => 'arthur\tests\mocks\action\MockPostsController',
			'action'     => 'index'
		);

		$postsController = new MockPostsController(compact('request'));
		$postsController->__invoke($request, $request->params);
	}

	public function testNonExistentFunction() 
	{
		$postsController = new MockPostsController();
		$this->expectException("Action `foo` not found.");
		$postsController(new Request(), array('action' => 'foo'));
	}
}