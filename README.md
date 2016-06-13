laravel-dynamodb
================
Supports all key types - primary hash key and composite keys.

* [Install](#install)
* [Usage](#usage)
* [Composite Keys](#composite-keys)
* [Requirements](#requirements)
* [License](#license)
* [Author and Contributors](#author-and-contributors)

Install
------

* Composer install
    ```bash
    composer require autodoc/dynamodb:0.1.x
    ```

* Install service provider:

    ```php
    // config/app.php
    
    'providers' => [
        ...
        Autodoc\DynamoDb\DynamoDbServiceProvider::class,
        ...
    ];
    ```

* Put DynamoDb config in `config/services.php`:

    ```php
    // config/services.php
        ...
        'dynamodb' =>	[
				'region'  => env('DYNAMODB_REGION'),
				'version' => env('DYNAMODB_VERSION'),
				'scheme' => env('DYNAMODB_SCHEME'),
				'credentials' => [
					'key'    =>  env('DYNAMODB_KEY'),
					'secret' =>  env('DYNAMODB_SECRET'),
				]
			],		
        ...
    ```

Usage
-----
* Extends your model with `Autodoc\DynamoDb\DynamoDbModel`.  


Requirements
-------------
Laravel ^5.1


License
--------
MIT


Author and Contributors
-------
* Diego Ferreira Caetano
