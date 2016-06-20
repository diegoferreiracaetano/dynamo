<?php

namespace Autodoc\DynamoDb;

use Aws\DynamoDb\Exception as DynamoDbException;
use Aws\DynamoDb\Marshaler;

abstract  class DynamoDbModel {

	protected static $dynamoDb;
	protected $table;
	protected $client; 
	protected $marshaler;
	protected  $filter;
	protected  $expression;
	protected  $where = [];
	protected  $result;
	protected  $index;
	protected  $indexName;
	protected  $lastEvaluatedKey;
	
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
		
	public function save(array $data)
	{		
	
		$data = $this->marshaler->marshalItem($data);
		
		$params = ['TableName' => $this->table,'Item' => $data];
	
		try 
		{
			return $this->client->putItem($params);
				
		} catch (DynamoDbException $e) {
				return $e->getMessage() ;
		}
	}
	
	
	
	public function get($columns = [])
	{
		
		$params = ['TableName' => $this->table];
		
		if($this->where)		
    		$params =array_merge($params,$this->where);
		
    	if($this->lastEvaluatedKey)
    		$params['ExclusiveStartKey'] = $this->lastEvaluatedKey;
    	
    	if($columns)
    		$params['ProjectionExpression'] = head($columns);
    	
    		
		$retorno = [];
	
		try 
		{			
			if($this->index)
				$response = $this->client->query($params);
			else 
				$response = $this->client->scan($params);
							
			if(isset($response) && isset($response['LastEvaluatedKey']))
			{
				 $this->lastEvaluatedKey = $response['LastEvaluatedKey'];
			}
			
			foreach ($response['Items'] as $item) 
			{
				$retorno[] = $this->marshaler->unmarshalItem($item);		
			}	
		
			$this->result = $retorno;
			
			return $this;	
		} 
		catch (DynamoDbException $e) 
		{
			return $e->getMessage() ;
		}
	}
	
	public function all()
	{
		$this->get();
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
        if (is_array($column)) {
            foreach ($column as $key => $value) {
                return $this->where($key, '=', $value);
            }
        }

        if (func_num_args() == 2) {
            list($value, $operator) = [$operator, '='];
        }

        if ($column instanceof Closure) {
            throw new NotSupportedException('Closure in where clause is not supported');
        }

        if (!ComparisonOperator::isValidOperator($operator)) {
            list($value, $operator) = [$operator, '='];
        }

        if ($value instanceof Closure) {
            throw new NotSupportedException('Closure in where clause is not supported');
        }

        $this->generateExpression($column, $operator, $value);
    	$this->preencheWhere();
     
        return $this;
    }
    
    protected  function preencheWhere()
    {
    	
    	if($this->filter)
    		$this->where['FilterExpression'] =$this->filter;
    	
    	if($this->indexName)
    		$this->where['IndexName'] = $this->indexName;
    	
    	if($this->index)
    		$this->where['KeyConditionExpression'] = $this->index;
    	
    	if($this->expression)
    		$this->where['ExpressionAttributeValues'] =$this->expression;
    		    	
    }
    
    
    public function whereIndex($index,$column, $operator = null, $value = null, $boolean = 'and')
    {
    	if ($boolean != 'and') {
    		throw new NotSupportedException('Only support "and" in where clause');
    	}
    	if (is_array($column)) {
    		foreach ($column as $key => $value) {
    			return $this->where($key, '=', $value);
    		}
    	}
    
    	if (func_num_args() == 2) {
    		list($value, $operator) = [$operator, '='];
    	}
    
    	if ($column instanceof Closure) {
    		throw new NotSupportedException('Closure in where clause is not supported');
    	}
    
    	if (!ComparisonOperator::isValidOperator($operator)) {
    		list($value, $operator) = [$operator, '='];
    	}
    
    	if ($value instanceof Closure) {
    		throw new NotSupportedException('Closure in where clause is not supported');
    	}
    	$this->indexName = $index;
    	$this->generateExpressionIndex($column, $operator, $value).
    	$this->preencheWhere();
    
    	return $this;
    }
    
       
    private function  generateExpression($column, $operator, $value)
    {
    	
    	$this->cont++;
    	$alias = 'v_'.$column.'_'.$this->cont;
    	$valor = $this->marshaler->marshalItem([$value]);
    	
    	$filter = $column.' '.$operator.' :'.$alias;
    	$expression =  [':'.$alias.'' => end($valor)];
    	
 
    	if($this->filter)
    	{
    		$this->filter.=  ' and '.$filter;
    	}
    	else 
    		{
    		$this->filter  =  $filter;
    	}
    	
    	if ($this->expression){
    		
    		$this->expression = array_merge($this->expression, $expression);
    	}
    	else
   		{
    		$this->expression = $expression;
    	}
    }
    
    private function  generateExpressionIndex($column, $operator, $value)
    {
    	 
    	$this->cont++;
    	$alias = 'v_'.$column.'_'.$this->cont;
    	$valor = $this->marshaler->marshalItem([$value]);
    	 
    	$filter = $column.' '.$operator.' :'.$alias;
    	$expression =  [':'.$alias.'' => end($valor)];
    	 
    
    	if($this->index)
    	{
    		$this->index.=  ' and '.$filter;
    	}
    	else
    		{
    		$this->index  =  $filter;
    	}
    	if ($this->expression)
    	{
    		$this->expression = array_merge($this->expression, $expression);
    	}
    	else
    		{	
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
