<?php

namespace LegalThings;

class ElasticSort
{
    /**
     * @var array
     */
    public $sort;
    
    
    /**
     * Class constructor
     * 
     * @param array $sort
     */
    public function __construct($sort)
    {
        $this->sort = (array)$sort;
    }

    
    /**
     * Convert a Jasny DB styled sort param to an Elasticsearch sort
     * 
     * To search descending place '^' in front of the field
     * Example '^last_modified' becomes 'last_modified:desc'
     * 
     * @return array
     */
    public function transform()
    {
        return array_map(function($param) {
            $result = $param;
            $firstChar = $result[0];
            
            if ($firstChar === '^') {
                $result = substr($result, 1) . ':desc';
            }
            
            return $result;
        }, $this->sort);
    }
}
