<?php

namespace Autodoc\DynamoDb;

interface DynamoDbClientInterface
{
    public function getClient();

    public function getMarshaler();
}
