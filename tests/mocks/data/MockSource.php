<?php

namespace arthur\tests\mocks\data;

use arthur\util\Inflector;

class MockSource extends \arthur\data\Source 
{
	protected $_classes = array(
		'entity'       => 'arthur\data\entity\Record',
		'set'          => 'arthur\data\collection\RecordSet',
		'relationship' => 'arthur\data\model\Relationship'
	);

	private $_mockPosts = array(
		'id' => array('type' => 'int', 'length' => '10', 'null' => false, 'default' => null),
		'user_id' => array(
			'type' => 'int', 'length' => '10', 'null' => true, 'default' => null
		),
		'title' => array(
			'type' => 'varchar', 'length' => '255', 'null' => true, 'default' => null
		),
		'body' => array(
			'type' => 'text', 'length' => null, 'null' => true, 'default' => null
		),
		'created' => array(
			'type' => 'datetime', 'length' => null, 'null' => true, 'default' => null
		),
		'modified' => array(
			'type' => 'datetime', 'length' => null, 'null' => true, 'default' => null
		),
		'status' => array(
			'type' => 'tinyint', 'length' => '1', 'null' => false, 'default' => '0'
		)
	);

	private $_mockComments = array(
		'id' => array(
			'type' => 'int', 'length' => '10', 'null' => false, 'default' => null
		),
		'comment_type_id' => array(
			'type' => 'int', 'length' => '10', 'null' => false, 'default' => null
		),
		'article_id' => array(
			'type' => 'int', 'length' => '10', 'null' => false, 'default' => null
		),
		'comment_id' => array(
			'type' => 'int', 'length' => '10', 'null' => false, 'default' => null
		),
		'user_id' => array(
			'type' => 'int', 'length' => '10', 'null' => false, 'default' => null
		),
		'created' => array(
			'type' => 'datetime', 'length' => null, 'null' => false, 'default' => null
		),
		'body' => array(
			'type' => 'text', 'length' => null, 'null' => false, 'default' => null
		),
		'subscribed' => array(
			'type' => 'tinyint', 'length' => '1', 'null' => false, 'default' => null
		),
		'published' => array(
			'type' => 'tinyint', 'length' => '1', 'null' => false, 'default' => null
		)
	);

	private $_mockTags = array(
		'id' => array(
			'type' => 'int', 'length' => '10', 'null' => false, 'default' => null
		),
		'linked' => array(
			'type' => 'int', 'length' => '10', 'null' => true, 'default' => null
		),
		'name' => array(
			'type' => 'varchar', 'length' => '20', 'null' => true, 'default' => null
		),
		'keyname' => array(
			'type' => 'varchar', 'length' => '20', 'null' => true, 'default' => null
		)
	);

	public function connect() 
	{
		return ($this->_isConnected = true);
	}

	public function disconnect() 
	{
		return !($this->_isConnected = false);
	}

	public function sources($class = null) 
	{
		return array('mock_posts', 'mock_comments', 'mock_tags', 'posts_tags');
	}

	public function describe($entity, array $meta = array()) 
	{
		$var = "_" . Inflector::camelize($entity, false);
		if($this->{$var})
			return $this->{$var};

		return array();
	}

	public function create($query, array $options = array()) 
	{
		return compact('query', 'options');
	}

	public function read($query, array $options = array()) 
	{
		return compact('query', 'options');
	}

	public function update($query, array $options = array()) 
	{
		return compact('query', 'options');
	}

	public function delete($query, array $options = array()) 
	{
		return compact('query', 'options');
	}

	public function schema($query, $resource = null, $context = null) 
	{
	}

	public function result($type, $resource, $context) 
	{
	}

	public function cast($entity, array $data = array(), array $options = array()) 
	{
		$defaults = array('first' => false);
		$options += $defaults;  
		
		return $options['first'] ? reset($data) : $data;
	}

	public function relationship($class, $type, $name, array $config = array()) 
	{
		$field   = Inflector::underscore(Inflector::singularize($name));//($type == 'hasMany') ?  : ;
		$key     = "{$field}_id";
		$primary = $class::meta('key');

		if(is_array($primary))
			$key = array_combine($primary, $primary);
		elseif($type == 'hasMany' || $type == 'hasOne') 
		{
			if($type == 'hasMany')
				$field = Inflector::pluralize($field);

			$secondary = Inflector::underscore(Inflector::singularize($class::meta('name')));
			$key = array($primary => "{$secondary}_id");
		}

		$from      = $class;
		$fieldName = $field;
		$config   += compact('type', 'name', 'key', 'from', 'fieldName'); 
		
		return $this->_instance('relationship', $config);
	}

	public function calculation($type, $query, array $options = array()) 
	{
		$query->calculate($type);   
		
		return compact('query', 'options');
	}
}