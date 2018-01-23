<?php

namespace LegalThings;

use Exception;

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
     * @var array
     */
    public $transformed;
    
    
    /**
     * Class constructor
     * 
     * @param object|array $filter
     */
    public function __construct($filter = [])
    {
        $this->filter = (array)$filter;
        $this->transformed = [];
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
        if (isset($this->filter['es_raw']) && $this->filter['es_raw']) {
            // use the raw query instead of converting it
            unset($this->filter['es_raw']);
            return $this->transform = $this->filter;
        }

        foreach ($this->filter as $key => $value) {
            list($field, $operator) = array_map('trim', explode('(', str_replace(')', '', $key))) + [1 => 'default'];

            $fn = $this->camelcase("add_${operator}_filter");
            
            if (!method_exists($this, $fn)) {
                throw new Exception("Invalid filter key '$key'. Unknown operator '$operator'.");
            }

            call_user_func_array([$this, $fn], [$field, $value]);
        }

        return $this->transformed;
    }
    
    
    /**
     * Transform 'default' filter (no operator), to Elasticsearch equivalent
     * Example ['foo' => 'bar']
     * 
     * @param type $field
     * @param type $value
     * 
     * @return $this
     */
    public function addDefaultFilter($field, $value)
    {
        if (is_null($value)) {
            $this->transformed['bool']['must'][]['exists']['field'] = $field;
        } else if (is_array($value)) {
            $this->transformed['bool']['must'][]['terms'][$field] = $value;
        } else {
            $this->transformed['bool']['must'][]['term'][$field] = $value;
        }
        
        return $this;
    }
    
    /**
     * Transform 'not' filter, to Elasticsearch equivalent
     * Example ['foo(not)' => 'bar']
     * 
     * @param type $field
     * @param type $value
     * 
     * @return $this
     */
    public function addNotFilter($field, $value)
    {
        if (is_null($value)) {
            $this->transformed['bool']['must_not'][]['exists']['field'] = $field;
        } else if (is_array($value)) {
            $this->transformed['bool']['must_not'][]['terms'][$field] = $value;
        } else {
            $this->transformed['bool']['must_not'][]['term'][$field] = $value;
        }
        
        return $this;
    }
    
    /**
     * Transform 'should' filter, to Elasticsearch equivalent
     * Example ['foo(should)' => 'bar']
     * 
     * @param type $field
     * @param type $value
     * 
     * @return $this
     */
    public function addShouldFilter($field, $value)
    {
        if (is_null($value)) {
            $this->transformed['bool']['should'][]['exists']['field'] = $field;
        } else if (is_array($value)) {
            $this->transformed['bool']['should'][]['terms'][$field] = $value;
        } else {
            $this->transformed['bool']['should'][]['term'][$field] = $value;
        }
        
        return $this;
    }
    
    /**
     * Transform 'should_not' filter, to Elasticsearch equivalent
     * Example ['foo(should_not)' => 'bar']
     * 
     * @param type $field
     * @param type $value
     * 
     * @return $this
     */
    public function addShouldNotFilter($field, $value)
    {
        if (is_null($value)) {
            $this->transformed['bool']['should_not'][]['exists']['field'] = $field;
        } else if (is_array($value)) {
            $this->transformed['bool']['should_not'][]['terms'][$field] = $value;
        } else {
            $this->transformed['bool']['should_not'][]['term'][$field] = $value;
        }
        
        return $this;
    }
    
    /**
     * Transform 'min' filter, to Elasticsearch equivalent
     * Example ['foo(min)' => 3]
     * 
     * @param type $field
     * @param type $value
     * 
     * @return $this
     */
    public function addMinFilter($field, $value)
    {
        $this->transformed['bool']['must'][]['range'][$field] = ['gte' => $value];
        
        return $this;
    }
    
    /**
     * Transform 'max' filter, to Elasticsearch equivalent
     * Example ['foo(max)' => 3]
     * 
     * @param type $field
     * @param type $value
     * 
     * @return $this
     */
    public function addMaxFilter($field, $value)
    {
        $this->transformed['bool']['must'][]['range'][$field] = ['lte' => $value];
        
        return $this;
    }
    
    /**
     * Transform 'any' filter, to Elasticsearch equivalent
     * Example ['foo(any)' => ['bar']]
     * 
     * @param type $field
     * @param type $value
     * 
     * @return $this
     */
    public function addAnyFilter($field, $value)
    {
        $this->transformed['bool']['must'][]['terms'][$field] = $value;
        
        return $this;
    }
    
    /**
     * Transform 'none' filter, to Elasticsearch equivalent
     * Example ['foo(none)' => ['bar']]
     * 
     * @param type $field
     * @param type $value
     * 
     * @return $this
     */
    public function addNoneFilter($field, $value)
    {
        $this->transformed['bool']['must_not'][]['term'][$field] = $value;
        
        return $this;
    }
    
    /**
     * Transform 'all' filter, to Elasticsearch equivalent
     * Example ['foo(all)' => ['bar']]
     * 
     * @param type $field
     * @param type $value

     * 
     * @return $this
     */
    public function addAllFilter($field, $value)
    {
        $this->transformed['bool']['must'][]['term'][$field] = $value;
        
        return $this;
    }
    
    
    /**
     * Turn a sentence, camelCase, snake_case or kabab-case into camelCase
     *
     * @param string $string
     * @return string
     */
    protected function camelcase($string)
    {
        $sentence = preg_replace('/[\W_]+/', ' ', $string);
        return lcfirst(str_replace(' ', '', ucwords($sentence)));
    }
}
