<?php

namespace arthur\tests\mocks\core;

class MockStaticMethodFiltering extends \arthur\core\StaticObject 
{
	public static function method($data) 
	{
		$data[] = 'Starting outer method call'; 
		
		$result = static::_filter(__FUNCTION__, compact('data'), function($self, $params, $chain) 
		{
			$params['data'][] = 'Inside method implementation of ' . $self;
			return $params['data'];
		});
		$result[] = 'Ending outer method call';        
		
		return $result;
	}

	public static function method2() 
	{
		$filters =& static::$_methodFilters;     
		
		$method = function($self, $params, $chain) use (&$filters) 
		{
			return $filters;
		};       
		
		return static::_filter(__FUNCTION__, array(), $method);
	}

	public static function manual($filters) 
	{
		$method = function($self, $params, $chain) 
		{
			return "Working";
		};              
		
		return static::_filter(__FUNCTION__, array(), $method, $filters);
	}

	public static function callSubclassMethod() 
	{
		return static::_filter(__FUNCTION__, array(), function($self, $params, $chain) 
		{
			return $self::childMethod();
		});
	}

	public static function foo() 
	{
		$args = func_get_args();
		return $args;
	}

	public static function parents($get = false) 
	{
		if($get === null)
			static::$_parents = array();
		if($get)
			return static::$_parents;
			
		return static::_parents();
	}
}