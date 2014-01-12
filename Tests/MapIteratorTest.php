<?php
/**
 * @author Socolov Vladimir<socolov.vladimir@gmail.com>
 */
namespace wlfrm\Iterator\Tests;
use wlfrm\Iterator\MapIterator;

class MapIteratorTest extends \PHPUnit_Framework_TestCase
{
	protected $_callable_value;
	protected $_callable_key;
	protected $_callable_value_increment;
	protected $_callable_key_increment;
	protected $_callable_value_not_callable;
	protected $_callable_key_not_scalar;
	protected $_callable_value_with_argument;
	protected $_callable_value_with_vary_args;

	protected $_sample_array = array(1, 2, 3, NULL, FALSE, 0, 100500);
	protected $_sample_array_product = array(2, 3, 4, 1, 1, 1, 100501);
	protected $_sample_array_product_keys = array(1, 2, 3, 4, 5, 6, 7);
	protected $_sample_array_chars = array('a','b','c');
	protected $_sample_array_chars_keys = array('a'=>'a','b'=>'b','c'=>'c');

	public function setUp()
	{
		$this->_callable_value = function($value){return $value;};
		$this->_callable_key = function($key){return $key;};
		$this->_callable_value_increment = function($value){return $value + 1;};
		$this->_callable_key_increment = function($key){return $key + 1;};
		$this->_callable_value_not_callable = 1;
		$this->_callable_key_not_scalar = function($key, $value){return array($value, $key);};
		$this->_callable_value_with_argument = function($value, $param){return $value+$param;};
		$this->_callable_value_with_vary_args = function(){$args = func_get_args(); $result = ''; foreach ($args as $num_arg => $param) {$result .= $param;} return $result;};
	}

	/**
	 * Special callback-method for checking pass not a Closure, but regular callback
	 * @param type $value
	 * @param type $key
	 * @return type
	 */
	public function someValueCallbackIncrement($value)
	{
		return $value + 1;
	}

	/**
	 * Special callback-method for checking pass not a Closure, but regular callback
	 * @param type $value
	 * @param type $key
	 * @return type
	 */
	public function someKeyCallbackIncrement($key)
	{
		return $key + 1;
	}

	/**
	 * Special static callback-method for checking pass not a Closure, but regular callback
	 * @param type $value
	 * @param type $key
	 * @return type
	 */
	public static function someStaticValueCallbackIncrement($value)
	{
		return $value + 1;
	}

	public function someCallbackSwapValuesAndKeys($value, $key)
	{
		return $key;
	}

	/**
	 * @expectedException        InvalidArgumentException
	 * @expectedExceptionMessage Value-mapper should be valid callable, Closure, or NULL value
	 */
	public function testBadConfigurationValue()
	{
		$iter = MapIterator::create($this->_sample_array, $this->_callable_value_not_callable);
		iterator_to_array($iter);
	}


	/**
	 * @expectedException        InvalidArgumentException
	 * @expectedExceptionMessage Key-mapper should be valid callable, Closure, or NULL value
	 */
	public function testBadConfigurationKeyCallable()
	{
		$iter = MapIterator::create($this->_sample_array, $this->_callable_value)->setKeyCallable($this->_callable_value_not_callable);
		iterator_to_array($iter);
	}

	/**
	 * @expectedException        InvalidArgumentException
	 * @expectedExceptionMessage For what you are using me ? No callback passed !
	 */
	public function testBadConfigurationNoCallbacks()
	{
		$iter = MapIterator::create($this->_sample_array, MapIterator::VALUE_DEFAULT)
			->setKeyCallable(MapIterator::KEY_DEFAULT);

		iterator_to_array($iter);
	}

	/**
	 * @expectedException        RuntimeException
	 * @expectedExceptionMessage Key callable must return scalar type only
	 */
	public function testBadKeyReturnCallable()
	{
		$iter = MapIterator::create($this->_sample_array, $this->_callable_value)
			->setKeyCallable($this->_callable_key_not_scalar)
			->setKeyCallablePassValues();

		iterator_to_array($iter);
	}

	public function testCallableValue()
	{
		$iter = MapIterator::create($this->_sample_array, $this->_callable_value);
		$this->assertEquals($this->_sample_array, iterator_to_array($iter), 'closure callback value modified');
	}

	public function testCallableValueIncrement()
	{
		$iter = MapIterator::create($this->_sample_array, $this->_callable_value_increment);
		$this->assertEquals(iterator_to_array($iter), $this->_sample_array_product, 'closure callback increment values');
	}

	public function testCallableKey()
	{
		$iter = MapIterator::create($this->_sample_array, $this->_callable_value)
			->setKeyCallable($this->_callable_key);
		$this->assertEquals($this->_sample_array, iterator_to_array($iter), 'closure callback keys and values modified');
	}

	public function testCallableKeyIncrement()
	{
		$iter = MapIterator::create($this->_sample_array, MapIterator::VALUE_DEFAULT)
			->setKeyCallable($this->_callable_key_increment);

		$this->assertEquals($this->_sample_array_product_keys, array_keys(iterator_to_array($iter)), 'closure callback keys modified');
	}

	public function testCallableValueIncrementAndCallableKeysIncrement()
	{
		$iter = MapIterator::create($this->_sample_array, $this->_callable_value_increment)
			->setKeyCallable($this->_callable_key_increment);
		$result_array = iterator_to_array($iter);
		$this->assertEquals($this->_sample_array_product_keys, array_keys($result_array), 'closure  + keys modified');
		$this->assertEquals($this->_sample_array_product, array_values($result_array), 'closure callback values modified');
	}

	public function testCallbackValueIncrement()
	{
		$iter = MapIterator::create($this->_sample_array, array($this, 'someValueCallbackIncrement'));
		$this->assertEquals($this->_sample_array_product, iterator_to_array($iter), 'Objects callback values modified');
	}

	public function testCallbackValueAndKeyIncrement()
	{
		$iter = MapIterator::create($this->_sample_array, array($this, 'someValueCallbackIncrement'))
			->setKeyCallable(array($this, 'someKeyCallbackIncrement'));
		$result_array = iterator_to_array($iter);
		$this->assertEquals($this->_sample_array_product_keys, array_keys($result_array), 'object + keys modified');
		$this->assertEquals($this->_sample_array_product, array_values($result_array), 'Objects callback values and keys modified');
	}

	public function testStaticCallback()
	{
		$iter = MapIterator::create($this->_sample_array, array(__NAMESPACE__ .'\MapIteratorTest', 'someStaticValueCallbackIncrement'));
		$this->assertEquals($this->_sample_array_product, iterator_to_array($iter), 'class static callback');
	}

	public function testAdditionalArguments()
	{
		$param_for_iterator = 1;
		$iter = MapIterator::create($this->_sample_array, $this->_callable_value_with_argument)
			->setValueArgs($param_for_iterator);
		$this->assertEquals($this->_sample_array_product, iterator_to_array($iter), 'closure callback value modified with additional params');
	}

	public function testKeyAdditionalArguments()
	{
		$param_for_iterator = 1;
		$iter = MapIterator::create($this->_sample_array, MapIterator::VALUE_DEFAULT)
			->setKeyCallable($this->_callable_value_with_argument)
			->setKeyArgs($param_for_iterator);
		$this->assertEquals(array_keys(iterator_to_array($iter)), $this->_sample_array_product_keys, "Keys incremented on $param_for_iterator");
	}

	public function testUserFunc()
	{
		$iter = MapIterator::create($this->_sample_array_chars, 'strtoupper');
		$this->assertEquals(iterator_to_array($iter), array('A','B','C'), 'user-func callback value modified');
	}

	public function testUserFuncKey()
	{
		$iter = MapIterator::create($this->_sample_array_chars_keys, 'strtoupper')
			->setKeyCallable('strtoupper');
		$this->assertEquals(iterator_to_array($iter), array('A'=>'A','B'=>'B','C'=>'C'), 'user-func callback keys and values modified');
	}

	public function testOnlyKeys()
	{
		$iter = MapIterator::create($this->_sample_array_chars_keys, MapIterator::VALUE_DEFAULT)
			->setKeyCallable('strtoupper');
		$this->assertEquals(iterator_to_array($iter), array('A'=>'a','B'=>'b','C'=>'c'), 'user-func callback keys only modified, value as default');
	}

	/**
	 * @expectedException        InvalidArgumentException
	 *
	 */
	public function testBadCallbackArgumentsClosure()
	{
		$iter = MapIterator::create($this->_sample_array, $this->_callable_value)
			->setValueArgs(1);
		iterator_to_array($iter);
	}

	/**
	 * @expectedException        InvalidArgumentException
	 *
	 */
	public function testBadCallbackArgumentsObjectsCallback()
	{
		$iter = MapIterator::create($this->_sample_array, array($this, 'someValueCallbackIncrement'))
			->setValueArgs(1);

		iterator_to_array($iter);
	}

	/**
	 * @expectedException        InvalidArgumentException
	 *
	 */
	public function testBadCallbackArgumentsStaticCallback()
	{
		$iter = MapIterator::create($this->_sample_array, array(get_class($this), 'someStaticValueCallbackIncrement'))
			->setValueArgs(1);
		iterator_to_array($iter);
	}

	/**
	 * @expectedException        InvalidArgumentException
	 *
	 */
	public function testBadCallbackArgumentsSimpleInternal()
	{
		$iter = MapIterator::create($this->_sample_array, 'strtoupper')
			->setValueArgs(1);

		iterator_to_array($iter);
	}

	/**
	 * @expectedException        InvalidArgumentException
	 * @expectedExceptionMessage Maximum 2 arguments awaiting. Use setters for configure MapIterator
	 */
	public function testBadConstructorArguments()
	{
		MapIterator::create($this->_sample_array, 'strtoupper', 'some_extra_argument');
	}

	public function testSetOrChangeValueCallable()
	{
		$iter = MapIterator::create($this->_sample_array, $this->_callable_value);
		//some code before iteration ...
		//...
		//and we change our mind, and realy need ANOTHER ONE CALLABLE !!
		$iter->setValueCallable($this->_callable_value_with_argument)
			->setValueArgs(1);
		$this->assertEquals(iterator_to_array($iter), $this->_sample_array_product, 'Test set value callable, and pass additional arguments');
	}

	public function testPassKeysIntoValueCallable()
	{
		$iter = new MapIterator(new \ArrayIterator($this->_sample_array_product_keys));
		//will try to swap keys with values and vice versa
		$iter->setValueCallable(array($this, 'someCallbackSwapValuesAndKeys'))
			->setValueCallablePassKeys()
			->setKeyCallable(array($this, 'someCallbackSwapValuesAndKeys'))
			->setKeyCallablePassValues();
		$result = iterator_to_array($iter);

		$this->assertEquals(array_keys($result), array_values($this->_sample_array_product_keys), 'Keys was replaced with values');
		$this->assertEquals(array_values($result), array_keys($this->_sample_array_product_keys), 'Values was replaced with keys');
	}

	/**
	 * @expectedException        InvalidArgumentException
	 * @expectedExceptionMessage No iterator passed. Cannot begin iterations
	 */
	public function testPassIteratorDelayedWithoutIterator()
	{
		$iter = new MapIterator();
		iterator_to_array($iter);
	}

	public function testPassIteratorDelayed()
	{
		$iter = new MapIterator(new \ArrayIterator(array(100500,200,300)));
		//will try to swap keys with values and vice versa
		$iter->setValueCallable(array($this, 'someCallbackSwapValuesAndKeys'))
		->setValueCallablePassKeys()
		->setKeyCallable(array($this, 'someCallbackSwapValuesAndKeys'))
		->setKeyCallablePassValues();
		//some code before iteration ...
		//...
		//and we change our mind, and realy need ANOTHER ONE ITERATOR to be applied !!
		$iter->setIterator($this->_sample_array_product_keys);
		$result = iterator_to_array($iter);

		$this->assertEquals(array_keys($result), array_values($this->_sample_array_product_keys), 'Keys was replaced with values');
		$this->assertEquals(array_values($result), array_keys($this->_sample_array_product_keys), 'Values was replaced with keys');
	}

	/**
	 * @expectedException        InvalidArgumentException
	 */
	public function testVaryArgsCallable()
	{
		$iter = MapIterator::create()
			->setIterator($this->_sample_array_chars)
			->setValueCallable($this->_callable_value_with_vary_args);
		iterator_to_array($iter);
	}

	public function testVaryArgsCallableDisableCheck()
	{
		$iter = MapIterator::create()
				->setIterator($this->_sample_array_chars)
				->setValueCallable($this->_callable_value_with_vary_args)
				->disableArgumentsChecks();
		 $this->assertEquals($this->_sample_array_chars, iterator_to_array($iter), "No real changes to array. Useless but work");
	}

	public function testVaryArgsCallableDisableCheckWithKeys()
	{
		$iter = MapIterator::create()
				->setIterator($this->_sample_array_chars)
				->setValueCallable($this->_callable_value_with_vary_args)
				->setValueCallablePassKeys()
				->disableArgumentsChecks();
		$this->assertEquals(array('a0','b1','c2'), iterator_to_array($iter), "Join values with keys. Useless but work");
	}

	public function testVaryArgsCallableDisableCheckWithKeysAndParam()
	{
		$iter = MapIterator::create()
				->setIterator($this->_sample_array_chars)
				->setValueCallable($this->_callable_value_with_vary_args)
				->setValueCallablePassKeys()
				->setValueArgs('_x', '_y')
				->disableArgumentsChecks();
		$this->assertEquals(array('a0_x_y','b1_x_y','c2_x_y'), iterator_to_array($iter), "Join values with keys. Useless but work");
	}

	/**
	 * @expectedException        InvalidArgumentException
	 */
	public function testVaryArgsCallableEnableCheckWithKeysAndParam()
	{
		$iter = MapIterator::create()
				->setIterator($this->_sample_array_chars)
				->setValueCallable($this->_callable_value_with_vary_args)
				->setValueCallablePassKeys()
				->setValueArgs('_x', '_y')
				->disableArgumentsChecks();

		//some code before iteration ...
		//...
		//and we change our mind, and really need strict validation over arguments
		$iter->enableArgumentsChecks();
		iterator_to_array($iter);
	}

	public function testIteratorAggregate()
	{
		$iter = MapIterator::create()
			->setIterator(new IteratorAggregateStub(array(1,2,3)))
			->setValueCallable($this->_callable_value_increment);
		$this->assertEquals(array(2,3,4), iterator_to_array($iter), "Should work");
	}
}