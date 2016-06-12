<?php

namespace Autodoc\DynamoDb;

use Illuminate\Support\Facades\App;

trait ModelTrait
{
    public static function boot()
    {
        parent::boot();

        $observer = static::getObserverClassName();

        static::observe(new $observer(
            App::make('Autodoc\DynamoDb\DynamoDbClientInterface')
        ));
    }

    public static function getObserverClassName()
    {
        return 'Autodoc\DynamoDb\ModelObserver';
    }

    public function getDynamoDbTableName()
    {
        return $this->getTableName();
    }
}
