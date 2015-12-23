# Caching static models component for Yii 2

Component allows easy cache and get static models data like statuses, cities or categories.
  There are methods to manually clear cache in purpose to update those data. 

## Install

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```bash
$ php composer.phar require --prefer-dist t-kanstantsin/yii2-cache-model "*"
```

or add

```json
"t-kanstantsin/yii2-cache-model": "*"
```

to the require section of your `composer.json` file.


## Usage

To configure component place this code in config's component definition:

```php
    'cacheModel' => [
        'class' => tkanstantsin\cache\CacheModel::class, 
        'cache' => 'cache', // cache component
        'duration' => 86400, // caching time (it can't be greater than in 'cache' component)
    ],
```

### List of all cached models by class name ###

```php
\Yii::$app->cacheModel->get(foo\Foo::class);
```

### Particular model ###

```php
\Yii::$app->cacheModel->get(foo\Foo::class, $fooId);
```

### Array of models ###

```php
\Yii::$app->cacheModel->get(foo\Foo::class, [$fooId1, $fooId2]);
```

### Manually clear cache ###

```
\Yii::$app->cacheModel->flush(foo\Foo::class);
```

## Credits

- [Konstantin Timoshenko](https://github.com/t-kanstantsin)

## License

The BSD License (BSD). Please see [License File](LICENSE.md) for more information.
