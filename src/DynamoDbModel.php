<?php

namespace Autodoc\DynamoDb;

use Aws\DynamoDb\Exception as DynamoDbException;
use Aws\DynamoDb\Marshaler;

abstract  class DynamoDbModel {

	protected static $dynamoDb;
	protected $table;
	protected $client; 
	protected $marshaler;
	private $filter;
	private $expression;
	private $where;
	private $result;
	
	 public function __construct(array $attributes = [], DynamoDbClientService $dynamoDb = null)
    {
        
        if (is_null(static::$dynamoDb)) {
            static::$dynamoDb = $dynamoDb;
        }

        $this->setupDynamoDb();
    }

    protected function setupDynamoDb()
    {
        if (is_null(static::$dynamoDb)) {
            static::$dynamoDb = app(\Autodoc\DynamoDb\DynamoDbClientInterface::class);
        }

        $this->client = static::$dynamoDb->getClient();
        $this->marshaler = static::$dynamoDb->getMarshaler();
    }
	
	protected function setclient()
	{
		
		$this->client = $this->getInstance();
	}
		
	public function save(array $data)
	{		
	
		$data = $this->marshaler->marshalItem($data);
		
		$params = ['TableName' => $this->table,'Item' => $data];
	
		try {
				return $this->client->putItem($params);

		} catch (DynamoDbException $e) {
				return $e->getMessage() ;
		}
	}
	
	
	
	public function get()
	{
		
		$params = ['TableName' => $this->table];
		
		
		if($this->where)		
    		$params =array_merge($params,$this->where);

			
		$retorno = [];
		try {
			$response = $this->client->scan($params);
			$retorno = array_map(function ($item) {
				return $this->marshaler->unmarshalItem($item);
		
			}, $response['Items']);
			
				$this->result = $retorno;
			
			return $this;
					
		} catch (DynamoDbException $e) {
			return $e->getMessage() ;
		}
	}
	
	public function all()
	{

		$params = ['TableName' => $this->table];
		$retorno = [];
		try {		
			$response = $this->client->scan($params);
			$retorno = array_map(function ($item) {
				return $this->marshaler->unmarshalItem($item);
				
			}, $response['Items']);
			
			$this->result = $retorno;
			
			return $this;
			
		} catch (DynamoDbException $e) {
				return $e->getMessage() ;
		}
	}
	
	
	public function delete(array $data)
	{
		
		$data =$this->marshaler->unmarshalItem($data);
		
		$params = ['TableName' => $this->table, 'key' => $data];
		
		try {
				return $this->client->deleteItem($params);
		} catch (DynamoDbException $e) {
				return $e->getMessage() ;
		}
		
	}
	
	
	
	
	public function where($column, $operator = null, $value = null, $boolean = 'and')
    {
        if ($boolean != 'and') {
            throw new NotSupportedException('Only support "and" in where clause');
        }

     
        // If the column is an array, we will assume it is an array of key-value pairs
        // and can add them each as a where clause. We will maintain the boolean we
        // received when the method was called and pass it into the nested where.
        if (is_array($column)) {
            foreach ($column as $key => $value) {
                return $this->where($key, '=', $value);
            }
        }

        // Here we will make some assumptions about the operator. If only 2 values are
        // passed to the method, we will assume that the operator is an equals sign
        // and keep going. Otherwise, we'll require the operator to be passed in.
        if (func_num_args() == 2) {
            list($value, $operator) = [$operator, '='];
        }

        // If the columns is actually a Closure instance, we will assume the developer
        // wants to begin a nested where statement which is wrapped in parenthesis.
        // We'll add that Closure to the query then return back out immediately.
        if ($column instanceof Closure) {
            throw new NotSupportedException('Closure in where clause is not supported');
        }

        // If the given operator is not found in the list of valid operators we will
        // assume that the developer is just short-cutting the '=' operators and
        // we will set the operators to '=' and set the values appropriately.
        if (!ComparisonOperator::isValidOperator($operator)) {
            list($value, $operator) = [$operator, '='];
        }

        // If the value is a Closure, it means the developer is performing an entire
        // sub-select within the query and we will need to compile the sub-select
        // within the where clause to get the appropriate query record results.
        if ($value instanceof Closure) {
            throw new NotSupportedException('Closure in where clause is not supported');
        }

        $this->generateExpression($column, $operator, $value).
        
        $this->where = [
         	'FilterExpression' =>$this->filter,
    		'ExpressionAttributeValues' => $this->expression
        ];

        return $this;
    }
    
    
    private function  generateExpression($column, $operator, $value)
    {
    	
    	$this->cont++;
    	$alias = 'v_'.$column.'_'.$this->cont;
    	$valor = $this->marshaler->marshalItem([$value]);
    	
    	$filter = $column.' '.$operator.' :'.$alias;
    	$expression =  [':'.$alias.'' => end($valor)];
    	

    	if($this->filter && $this->expression)
    	{
    		$this->filter .=  ' and '.$filter;
    		$this->expression = array_merge($this->expression, $expression);
    	}
    	else
   		{
    		$this->filter =  $filter;
    		$this->expression = $expression;
    	}
    }
    
    
    public function toColletion()
    {
    	$retorno = null;
    
    	if($this->result)
    	{  	
    		$retorno = array_map(function ($item) {
    			return (object) $item;
    		
    		}, $this->result);
    		
     	    $retorno = collect($retorno);
    	}
    	
    	return $retorno;
    }
    
    
    public function toArray()
    {
    	$retorno = [];
    
    	if($this->result)
    	{
    		$retorno = $this->result;
    	}
    	 
    	return $retorno;
    }
}
