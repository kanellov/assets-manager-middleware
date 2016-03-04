# assets-manager-middleware

|master|develop|
|------|-------|
|[![Build Status](https://travis-ci.org/kanellov/assets-manager-middleware.svg?branch=master)](https://travis-ci.org/kanellov/assets-manager-middleware)|[![Build Status](https://travis-ci.org/kanellov/assets-manager-middleware.svg?branch=develop)](https://travis-ci.org/kanellov/assets-manager-middleware)|

A middleware to serve assets from non public directories

## Requirements

- php >= 5.5
- psr/http-message

## Installation

``` terminal
$ composer require kanellov/assets-manager-middleware
```

## Exapmple

Using middleware with Slim Framework.

``` php
<?php 

chdir(dirname(__FILE__));

require 'vendor/autoload';

$app = new \Slim\App([
    'assets' => [
        'paths' => [
            'some/path',
        ],
        'web_dir' => __DIR__ . '/assets',
    ],
]);

$container = $app->getContainer();

// Register assets-manager-middleware in dependecy container
$container['assets'] = function ($c) {
    $settings = $c->get('settings');
    $config   = $settings['assets'];
    return new \Knlv\Middleware\AssetsManager($config);
};

// add middleware
$app->add('assets');

$app->run();

```

## License

The assets-manager-middleware is licensed under the GNU GENERAL PUBLIC LICENSE Version 3. See [License File](LICENSE) for more information.