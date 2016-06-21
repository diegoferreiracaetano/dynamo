<?php

namespace Autodoc\DynamoDb;

use Aws\DynamoDb\Marshaler;
use Illuminate\Support\Facades\App;
use Illuminate\Support\ServiceProvider;

class DynamoDbServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     */
    public function register()
    {
        $marshalerOptions = [
            'nullify_invalid' => true,
        ];

        $this->bindForApp($marshalerOptions);
    }

    protected function bindForApp($marshalerOptions = [])
    {
        $this->app->singleton('Autodoc\DynamoDb\DynamoDbClientInterface', function ($app) use ($marshalerOptions) {
            $config = config('services.dynamodb');
            
            $client = new \Autodoc\DynamoDb\DynamoDbClientService($config, new Marshaler($marshalerOptions));

            return $client;
        });
    }
}
