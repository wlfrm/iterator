<?php
/**
 * @author Socolov Vladimir<socolov.vladimir@gmail.com>
 */
namespace wlfrm\Iterator\Tests;
use wlfrm\Iterator\CallbackFilterIterator;

class CallbackFilterIteratorTest extends \PHPUnit_Framework_TestCase
{
	protected $_callable_value;
	protected $_callable_key;
	protected $_callable_key_even;
	protected $_callable_key_n;

	protected $_sample_array = array(1, 2, 3, NULL, FALSE, 0, 100500);
	protected $_integers = array(0=>1,1=>2,2=>3,3=>4,4=>5);

	public function filterNullValues($value)
	{
		return !is_null($value);
	}

	public static function filterWithoutParams()
	{
		$params = func_get_args();
		$value = array_shift($params);
		$key = array_shift($params);
		$in = array();
		while($next = array_shift($params))
		{
			$in[] = $next;
		}

		return in_array($value, $in);
	}

	public function setUp()
	{
		$this->_callable_value = function($value){return $value;};
		$this->_callable_key_even = function($value, $key){ return $key%2 == 0; };
		$this->_callable_key_n = function($value, $key, $n){ return $key%$n == 0; };
	}


	public function testInstance()
	{
		$iter = CallbackFilterIterator::create();
		$this->assertInstanceOf('Traversable', $iter);
	}

	public function testSimpleCallback()
	{
		$iter = CallbackFilterIterator::create($this->_sample_array, 'is_string');
		$this->assertEquals(array(),iterator_to_array($iter),'No one string into sample array');
	}

	/**
	 * @expectedException        InvalidArgumentException
	 */
	public function testSimpleCallbackWithExtraParam()
	{
		$iter = CallbackFilterIterator::create($this->_sample_array, 'is_string')
		->setFilterCallablePassKeys();
		$this->assertEquals(array(),iterator_to_array($iter),'No one string into sample array');
	}

	/**
	 * @expectedException        InvalidArgumentException
	 * @expectedExceptionMessage No iterator passed. Cannot begin iterations
	 */
	public function testPassIteratorDelayedWithoutIterator()
	{
		$iter = CallbackFilterIterator::create();
		iterator_to_array($iter);
	}

	/**
	 * @expectedException        InvalidArgumentException
	 * @expectedExceptionMessage Maximum 2 arguments awaiting. Use setters for configure CallbackFilterIterator
	 */
	public function testBadConstructorArguments()
	{
		CallbackFilterIterator::create($this->_sample_array, 'is_string', 'some_extra_argument');
	}

	/**
	 * @expectedException        InvalidArgumentException
	 * @expectedExceptionMessage Filter-callable should be valid callable or Closure
	 */
	public function testBadCallable()
	{
		$iter = CallbackFilterIterator::create($this->_sample_array);
		iterator_to_array($iter);
	}

	public function testIteratorAggregateAsParam()
	{
		$iter = CallbackFilterIterator::create(new IteratorAggregateStub(array(1,2,3)))
			->setFilterCallable($this->_callable_value);

		$this->assertEquals(array(1,2,3), iterator_to_array($iter), 'Shold work with IteratorAggregate');
	}

	public function testSimpleArray()
	{
		$iter = CallbackFilterIterator::create(array(1,2,3))->setFilterCallable('is_numeric');
		$this->assertEquals(array(1,2,3), iterator_to_array($iter), "Sould work with simple arrays");
	}

	public function testSimpleArrayIntoSetIterator()
	{
		$iter = CallbackFilterIterator::create()->setIterator(array(1,2,3))->setFilterCallable('is_numeric');
		$this->assertEquals(array(1,2,3), iterator_to_array($iter), "Sould work with simple arrays");
	}

	public function testPassKeys()
	{
		$iter = CallbackFilterIterator::create($this->_integers)
				->setFilterCallable($this->_callable_key_even)
				->setFilterCallablePassKeys();
		$this->assertEquals(array(0=>1,2=>3,4=>5), iterator_to_array($iter), "Filter odd keys");
	}

	public function testPassAdditionalArgs()
	{
		$iter = CallbackFilterIterator::create($this->_integers)
				->setFilterCallable($this->_callable_key_n)
				->setFilterCallablePassKeys()
				->setArgs(3);
		$this->assertEquals(array(0=>1,3=>4), iterator_to_array($iter), "Filter not each 3-rd key");
	}

	public function testReflectionMethod()
	{
		$iter = CallbackFilterIterator::create($this->_sample_array, array($this, 'filterNullValues'));
		$this->assertEquals(array_values(array(1, 2, 3, FALSE, 0, 100500)), array_values(iterator_to_array($iter)), "Filter null values");
	}

	/**
	 * @expectedException        InvalidArgumentException
	 */
	public function testWithoutParamsError()
	{
		$iter = CallbackFilterIterator::create()
			->setFilterCallable(array(__NAMESPACE__ .'\CallbackFilterIteratorTest', 'filterWithoutParams'))
			->setFilterCallablePassKeys()
			->setIterator($this->_sample_array)
			->setArgs(1,2,3);
		iterator_to_array($iter);
	}

	public function testWithoutParamsOk()
	{
		$iter = CallbackFilterIterator::create()
			->setFilterCallable(array(__NAMESPACE__ .'\CallbackFilterIteratorTest', 'filterWithoutParams'))
			->setFilterCallablePassKeys()
			->setIterator($this->_sample_array)
			->setArgs(1,2,3)
			->disableArgumentsChecks();
		$this->assertEquals(array_values(array(1,2,3)), array_values(iterator_to_array($iter)), "Filterad all except args");
	}

	/**
	 * @expectedException        InvalidArgumentException
	 */
	public function testDisableAndEnableargumentsChecking()
	{
		$iter = CallbackFilterIterator::create()
			->setFilterCallable(array(__NAMESPACE__ .'\CallbackFilterIteratorTest', 'filterWithoutParams'))
			->setFilterCallablePassKeys()
			->setIterator($this->_sample_array)
			->setArgs(1,2,3)
			->disableArgumentsChecks();
		//some code before iteration ...
		//...
		//and we change our mind, and realy need to set arguments checking back !!
		$iter->enableArgumentsChecks();
		iterator_to_array($iter);
	}
}