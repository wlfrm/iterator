<?php
/**
 * @author Socolov Vladimir<socolov.vladimir@gmail.com>
 */
namespace wlfrm\Iterator;

class CallbackFilterIterator implements \Iterator
{
	protected $_iterator;
	protected $_callable;
	protected $_callable_pass_keys;
	protected $_args;
	protected $_arguments_checks = TRUE;

	public function __construct(\Traversable $iterator = NULL, $callable = NULL)
	{
		$this->_callable = $callable;
		$this->setIterator($iterator);
	}


	/**
	 * Setter for additional arguments for values-callback
	 * allow to pass ANY number of additional params "regulary"
	 * without wrapping into array
	 *
	 * @param mixed $key_arg1
	 * @param mixed $key_arg2
	 * ...
	 * @param mixed $key_argN
	 */
	public function setArgs()
	{
		$this->_args = func_get_args();
		return $this;
	}

	public function setFilterCallable($callable)
	{
		$this->_callable = $callable;
		return $this;
	}

	/**
	 * Setting original iterator to be mapped later, into iterations
	 *
	 * @param $iterator array|Traversable|mixed
	 * @return $this
	 */
	public function setIterator($iterator)
	{
		if (is_array($iterator))
		{
			$this->_iterator = new \ArrayIterator($iterator);
		}
		elseif ($iterator instanceof \Traversable)
		{
			$this->_iterator = $iterator;
		}
		return $this;
	}

	public static function create()
	{
		$args = func_get_args();
		$iterator = array_shift($args);
		$callable = array_shift($args);

		$wrong_arguments = array_shift($args);
		if ($wrong_arguments)
		{
			throw new \InvalidArgumentException('Maximum 2 arguments awaiting. Use setters for configure CallbackFilterIterator');
		}

		if (is_array($iterator))
		{
			$iterator = new \ArrayIterator($iterator);
		}

		return new static($iterator, $callable);
	}

	/**
	 * @inheritdoc
	 */
	public function rewind()
	{
		$this->_validateIterator();
		$this->_validateCallback();
		$this->_validateCallbackArguments();

		while ($this->_iterator instanceof \IteratorAggregate)
		{
			$this->_iterator = $this->_iterator->getIterator();
		}
		return $this->_iterator->rewind();
	}

	/**
	 * @inheritdoc
	 */
	public function next()
	{
		return $this->_iterator->next();
	}

	/**
	 * @inheritdoc
	 */
	public function valid()
	{
		if (!$this->_iterator->valid())
		{
			return false;
		}
		elseif ($this->accept())
		{
			return true;
		}
		else
		{
			$this->next();
			return $this->valid();
		}
	}

	public function current()
	{
		return $this->_iterator->current();
	}

	public function key()
	{
		return $this->_iterator->key();
	}

	private function _validateCallbackArguments()
	{
		if (!$this->_arguments_checks)
			return;

		$callable = $this->_callable;
		if (is_array($callable))
		{
			$reflection_callback_value = new \ReflectionMethod($callable[0], $callable[1]);
		}
		else
		{
			$reflection_callback_value = new \ReflectionFunction($callable);
		}

		$passed_args_number = count($this->_args)+1;
		if ($this->_callable_pass_keys)
			$passed_args_number+=1;

		$agr_num = $reflection_callback_value->getNumberOfRequiredParameters();
		if ($agr_num != $passed_args_number)
		{
			throw new \InvalidArgumentException(
				"Bad argument count pased for Filter-callable. Awaiting `".
				$agr_num.'` arguments. But passed `'.$passed_args_number."`".
				"\nMore information about Filter-callable: \n".
				$reflection_callback_value
			);
		}
	}

	/**
	 * Disabling all checks for argument count onto callbacks.
	 * This for avoid some 'slow' Reflections, or for callbacks with length-vary params
	 *
	 * @return CallbackFilterIterator
	 */
	public function disableArgumentsChecks()
	{
		$this->_arguments_checks = FALSE;
		return $this;
	}

	/**
	 * @return MapIterator
	 */
	public function enableArgumentsChecks()
	{
		$this->_arguments_checks = TRUE;
		return $this;
	}

	/**
	 * Switcher for allowing current key as param into filter-callback
	 *
	 * @param bool $pass_keys
	 * @return CallbackFilterIterator
	 */
	public function setFilterCallablePassKeys($pass_keys = TRUE)
	{
		$this->_callable_pass_keys = (bool) $pass_keys;
		return $this;
	}

	/**
	 * @throws \InvalidArgumentException
	 */
	private function _validateCallback()
	{
		if (!($this->_callable instanceof \Closure) && !is_callable($this->_callable))
		{
			throw new \InvalidArgumentException("Filter-callable should be valid callable or Closure");
		}
	}

	/**
	 * @throws \InvalidArgumentException
	 */
	private function _validateIterator()
	{
		if (!$this->_iterator instanceof \Traversable)
		{
			throw new \InvalidArgumentException("No iterator passed. Cannot begin iterations");
		}
	}

	private function accept()
	{
		$params = array('value' => $this->_iterator->current());
		if ($this->_callable_pass_keys)
		{
			$params += array('key' => $this->_iterator->key());
		}
		if ($this->_args)
		{
			$params += $this->_args;
		}

		return (bool) call_user_func_array($this->_callable, $params);
	}
}