<?php

namespace LegalThings;

use Exception;
use function Jasny\camelcase;

/**
 * Filters in Elasticsearch are constructed of rather complicated, nested, data structures
 * This class simplifies creating filters
 */
class ElasticFilter
{
    /**
     * @var array
     */
    public $filter;
    
    
    /**
     * Class constructor
     * 
     * @param object|array $filter
     */
    public function __construct($filter)
    {
        $this->filter = (array)$filter;
    }

    
    /**
     * Transform a Jasny DB styled filter to an Elasticsearch query.
     * See https://github.com/jasny/db#filter for more information on the syntax
     * 
     * This simplifies the complicated data structures required by Elasticsearch to search
     * Normally when you want to search for dates starting from a minumum date, you will have to nest many arrays in order to get the desired result
     * Now all you have to do is pass something like: ['start_date(min)' => '2017-01-01T00:00:00']
     * 
     * This is also useful when you want to search using query parameters, since you can't easily specify complicated structures there
     * 
     * @return array
     */
    public function transform()
    {
        $query = [];

        foreach ($this->filter as $key => $value) {
            list($field, $operator) = array_map('trim', explode('(', str_replace(')', '', $key))) + [1 => 'default'];

            $fn = camelcase("filter_${operator}");
            
            if (!method_exists($this, $fn)) {
                throw new Exception("Invalid filter key '$key'. Unknown operator '$operator'.");
            }

            call_user_func_array([$this, $fn], [&$field, &$value, &$query]);
        }

        return $query;
    }
    
    
    /**
     * Transform 'default' filter (no operator), to Elasticsearch equivalent
     * Example ['foo' => 'bar']
     * 
     * @param type $field
     * @param type $value
     * @param type $query
     * 
     * @return $this
     */
    public function filterDefault(&$field, &$value, &$query)
    {
        if (is_null($value)) {
            $query['bool']['must'][]['missing']['field'] = $field;
        } else if (is_array($value)) {
            $query['bool']['must'][]['terms'][$field] = $value;
        } else {
            $query['bool']['must'][]['term'][$field] = $value;
        }
        
        return $this;
    }
    
    /**
     * Transform 'not' filter, to Elasticsearch equivalent
     * Example ['foo(not)' => 'bar']
     * 
     * @param type $field
     * @param type $value
     * @param type $query
     * 
     * @return $this
     */
    public function filterNot(&$field, &$value, &$query)
    {
        if (is_null($value)) {
            $query['bool']['must_not'][]['missing']['field'] = $field;
        } else if (is_array($value)) {
            $query['bool']['must_not'][]['terms'][$field] = $value;
        } else {
            $query['bool']['must_not'][]['term'][$field] = $value;
        }
        
        return $this;
    }
    
    /**
     * Transform 'min' filter, to Elasticsearch equivalent
     * Example ['foo(min)' => 3]
     * 
     * @param type $field
     * @param type $value
     * @param type $query
     * 
     * @return $this
     */
    public function filterMin(&$field, &$value, &$query)
    {
        $query['bool']['must'][]['range'][$field] = ['gte' => $value];
        
        return $this;
    }
    
    /**
     * Transform 'max' filter, to Elasticsearch equivalent
     * Example ['foo(max)' => 3]
     * 
     * @param type $field
     * @param type $value
     * @param type $query
     * 
     * @return $this
     */
    public function filterMax(&$field, &$value, &$query)
    {
        $query['bool']['must'][]['range'][$field] = ['lte' => $value];
        
        return $this;
    }
    
    /**
     * Transform 'any' filter, to Elasticsearch equivalent
     * Example ['foo(any)' => ['bar']]
     * 
     * @param type $field
     * @param type $value
     * @param type $query
     * 
     * @return $this
     */
    public function filterAny(&$field, &$value, &$query)
    {
        $query['bool']['must'][]['terms'][$field] = $value;
        
        return $this;
    }
    
    /**
     * Transform 'none' filter, to Elasticsearch equivalent
     * Example ['foo(none)' => ['bar']]
     * 
     * @param type $field
     * @param type $value
     * @param type $query
     * 
     * @return $this
     */
    public function filterNone(&$field, &$value, &$query)
    {
        $query['bool']['must_not'][]['term'][$field] = $value;
        
        return $this;
    }
    
    /**
     * Transform 'all' filter, to Elasticsearch equivalent
     * Example ['foo(all)' => ['bar']]
     * 
     * @param type $field
     * @param type $value
     * @param type $query
     * 
     * @return $this
     */
    public function filterAll(&$field, &$value, &$query)
    {
        $query['bool']['must'][]['term'][$field] = $value;
        
        return $this;
    }
}
