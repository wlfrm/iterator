<?php
/**
 * @author Socolov Vladimir<socolov.vladimir@gmail.com>
 */
namespace wlfrm\Iterator\Tests;

use Traversable;

class IteratorAggregateStub implements \IteratorAggregate
{
	protected $_iterator;
	protected $_values;

	public function __construct($values)
	{
		$this->_values = $values;
	}

	/**
	 * (PHP 5 &gt;= 5.0.0)<br/>
	 * Retrieve an external iterator
	 * @link http://php.net/manual/en/iteratoraggregate.getiterator.php
	 * @return Traversable An instance of an object implementing <b>Iterator</b> or
	 * <b>Traversable</b>
	 */
	public function getIterator()
	{
		if (!$this->_iterator)
		{
			$this->_iterator = new \ArrayIterator($this->_values);
		}
		return $this->_iterator;
	}
}