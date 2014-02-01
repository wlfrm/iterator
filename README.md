Wlfrm Iterator
===============

Provides useful Iterator decorators

- FilterIterator: Little bit better than CallbackFilterIterator: allow to map any Traversable
- MapIterator: Maps keys/values before yielding for any Traversable object

### Installing via Composer

```bash
# Install Composer
curl -sS https://getcomposer.org/installer | php

# Add Guzzle as a dependency
php composer.phar require wlfrm/iterator:~1.0
```

After installing, you need to require Composer's autoloader:

```php
require 'vendor/autoload.php';
```