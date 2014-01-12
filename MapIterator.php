<?php
/**
 * @author Socolov Vladimir<socolov.vladimir@gmail.com>
 */
namespace wlfrm\Iterator;

class MapIterator implements \Iterator
{
	protected $_iterator;
	protected $_callable;
	protected $_callable_pass_keys;
	protected $_key_callable;
	protected $_key_callable_pass_values;
	protected $_args;
	protected $_key_args;
	protected $_arguments_checks = TRUE;
	const KEY_DEFAULT = NULL;
	const VALUE_DEFAULT = NULL;

	/**
	 *
	 * @param Traversable $iterator
	 * @param Closure $callable
	 */
	public function __construct(\Traversable $iterator = NULL, $callable = self::VALUE_DEFAULT)
	{
		$this->_callable = $callable;
		$this->_key_callable = self::KEY_DEFAULT;
		$this->_callable_pass_keys = FALSE;
		$this->_key_callable_pass_values = FALSE;
		$this->_args = array();//is_array($additional_args) ? $additional_args : (array) $additional_args;
		$this->_key_args = array();
		$this->setIterator($iterator);
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

	/**
	 * validation callbacks for functions/methods
	 *
	 * @throws \InvalidArgumentException
	 */
	private function _validateCallbacks()
	{
		if (self::VALUE_DEFAULT !== $this->_callable && !($this->_callable instanceof \Closure) && !is_callable($this->_callable))
		{
			throw new \InvalidArgumentException("Value-mapper should be valid callable, Closure, or NULL value");
		}
		if (self::KEY_DEFAULT !== $this->_key_callable && !($this->_key_callable instanceof \Closure) && !is_callable($this->_key_callable))
		{
			throw new \InvalidArgumentException("Key-mapper should be valid callable, Closure, or NULL value");
		}
		if (self::VALUE_DEFAULT === $this->_callable && self::KEY_DEFAULT === $this->_key_callable)
		{
			throw new \InvalidArgumentException('For what you are using me ? No callback passed !');
		}
	}

	/**
	 * Checking count of arguments count for registered callbacks
	 *
	 * @throws \InvalidArgumentException
	 */
	private function _validateCallbacksArguments()
	{
		if (!$this->_arguments_checks)
			return;

		foreach (array('value'=>$this->_callable, 'key'=>$this->_key_callable) as $type=>$callable)
		{
			if ('value' == $type && $callable == self::VALUE_DEFAULT) continue;
			if ('key' == $type && $callable == self::KEY_DEFAULT) continue;

			if (is_array($callable))
			{
				$reflection_callback_value = new \ReflectionMethod($callable[0], $callable[1]);
			}
			else
			{
				$reflection_callback_value = new \ReflectionFunction($callable);
			}

			$passed_args_number = 0;
			switch ($type)
			{
				case 'value':
					$passed_args_number = count($this->_args)+1;
					if ($this->_callable_pass_keys)
						$passed_args_number++;
					break;
				case 'key':
					$passed_args_number = count($this->_key_args)+1;
					if ($this->_key_callable_pass_values)
						$passed_args_number++;
					break;
			}
			$agr_num = $reflection_callback_value->getNumberOfRequiredParameters();
			if ($agr_num != $passed_args_number)
			{
				throw new \InvalidArgumentException(
					"Bad argument count pased for $type-callable. Awaiting `".
					$agr_num.'` arguments. But passed `'.$passed_args_number."`".
					"\nMore information about $type-callable: \n".
					$reflection_callback_value
				);
			}
		}
	}

	/**
	 * factory-method for simplified creation of MapIterator
	 *
	 * @param Traversable|array $iterator
	 * @param Closure|callback $callable
	 * @throws \InvalidArgumentException
	 * @return MapIterator
	 */
	public static function create()
	{
		$args = func_get_args();
		$iterator = array_shift($args);
		$callable = array_shift($args);

		$wrong_arguments = array_shift($args);
		if ($wrong_arguments)
		{
			throw new \InvalidArgumentException('Maximum 2 arguments awaiting. Use setters for configure MapIterator');
		}

		if (is_array($iterator))
		{
			$iterator = new \ArrayIterator($iterator);
		}

		return new static($iterator, $callable);
	}

	/**
	 * Method for define and redefine keys into iterations
	 *
	 * @return int|string
	 * @throws \RuntimeException
	 */
	public function key()
	{
		if ($this->_key_callable === self::KEY_DEFAULT)
			return $this->_iterator->key();

		$params = array('key' => $this->_iterator->key());

		if ($this->_key_callable_pass_values)
        {
			$params += array('value' => $this->_iterator->current());
		}

		if ($this->_key_args)
		{
			$params += $this->_key_args;
		}

		$key_to_return = call_user_func_array(
			$this->_key_callable,
			$params
		);

		if (is_array($key_to_return) || is_object($key_to_return))
		{
			throw new \RuntimeException('Key callable must return scalar type only');
		}
		return $key_to_return;
	}

	/**
	 * Setting original iterator to be mapped later, into iterations
	 *
	 * @param $iterator array|Traversable|mixed
	 * @return MapIterator
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

	/**
	 * Setting callable-modifier for keys
	 *
	 * @param callable $key_callable
	 * @return MapIterator
	 */

	public function setKeyCallable($key_callable)
	{
		$this->_key_callable = $key_callable;
		return $this;
	}

	/**
	 * @param bool $pass_values
	 * @return MapIterator
	 */
	public function setKeyCallablePassValues($pass_values = TRUE)
	{
		$this->_key_callable_pass_values = (bool) $pass_values;
		return $this;
	}

	/**
	 * Setter for additional arguments for key-callback
	 * allow to pass ANY number of additional params "regulary"
	 * without wrapping into array
	 *
	 * @param mixed $key_arg1
	 * @param mixed $key_arg2
	 * ...
	 * @param mixed $key_argN
	 * @return MapIterator
	 */
	public function setKeyArgs()
	{
		$this->_key_args = func_get_args();
		return $this;
	}

	/**
	 * Setting callable-modifier for values
	 *
	 * @param callable $callable
	 * @return MapIterator
	 */
	public function setValueCallable($callable)
	{
		$this->_callable = $callable;
		return $this;
	}

	/**
	 * Switcher for allowing current key as param into value-callback
	 *
	 * @param bool $pass_keys
	 * @return MapIterator
	 */
	public function setValueCallablePassKeys($pass_keys = TRUE)
	{
		$this->_callable_pass_keys = (bool) $pass_keys;
		return $this;
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
	 * @return MapIterator
	 */
	public function setValueArgs()
	{
		$this->_args = func_get_args();
		return $this;
	}

	/**
	 * Disabling all checks for argument count onto callbacks.
	 * This for avoid some 'slow' Reflections, or for callbacks with length-vary params
	 *
	 * @return MapIterator
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
	 * @inheritdoc
	 */
	public function current()
	{
		if ($this->_callable === self::KEY_DEFAULT)
			return $this->_iterator->current();

        $params = array('value' => $this->_iterator->current());
		if ($this->_callable_pass_keys)
        {
			$params += array('key' => $this->_iterator->key());
		}
		if ($this->_args)
		{
			$params += $this->_args;
		}

		return call_user_func_array(
			$this->_callable,
			$params
		);
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
	public function rewind()
	{
		$this->_validateIterator();
		$this->_validateCallbacks();
		$this->_validateCallbacksArguments();
		while ($this->_iterator instanceof \IteratorAggregate)
		{
			$this->_iterator = $this->_iterator->getIterator();
		}
		return $this->_iterator->rewind();
	}

	/**
	 * @inheritdoc
	 */
	public function valid()
	{
		return $this->_iterator->valid();
	}
}