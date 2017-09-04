<?php

namespace LegalThings;

use Elasticsearch\ClientBuilder;
use Elasticsearch\Client;

class Elasticsearch
{
    /**
     * @var object
     */
    public $config;
    
    /**
     * @var Client
     */
    public $client;
    
    
    /**
     * Class constructor
     * 
     * @param object|array $config
     * @param Client       $client
     */
    public function __construct($config = [], $client = null)
    {
        $this->config = (object)$config;
        
        $this->client = $client ?: $this->create($this->config);
    }
    
    /**
     * Create a client
     * 
     * @param object $config
     * 
     * @return Logger $logger
     */
    protected function create($config)
    {
        $client = ClientBuilder::fromConfig((array)$config);

        return $client;
    }
    
    
    /**
     * Search through Elasticsearch only giving basic parameters
     * This function will take care of transforming filters and queries to data that Elasticsearch expects
     * 
     * @param string       $index   index name
     * @param string       $type    index type
     * @param string       $text    text to search for
     *                              example 'john doe'
     * @param array        $fields  search for text only in given fields
     *                              example ['name']
     * @param array|object $filter  filter the results, example
     *                              example ['year(min)' => 2016, 'type' => 'foo']
     * @param array        $sort    sort the results
     *                              example ['^last_modified'] or ['last_modified:desc'] or ['last_modified']
     * @param int          $limit   limit the results
     * @param int          $offset  return results starting from given offset
     * 
     * @return array
     */
    public function search($index, $type, $text = null, $fields = [], $filter = [], $sort = [], $limit = null, $offset = null)
    {
        if (isset($text)) {
            $text = ['query_string' => ['query' => $text, 'fields' => $fields]];
        }
        
        if (isset($filter)) {
            $filter = (new ElasticFilter($filter))->transform();
        }
        
        if (isset($sort)) {
            $sort = (new ElasticSort($sort))->transform();
        }
        
        $params = [
            'index' => $index,
            'type' => $type,
            'sort' => $sort,
            'body' => [
                'from' => $offset,
                'size' => $limit,
                'query' => [
                    'bool' => [
                        'must' => $text,
                        'filter' => $filter
                    ]
                ]
            ]
        ];

        return $this->client->search($params);
    }
    
    /**
     * Index data in Elasticsearch only giving basic parameters
     * 
     * @param string       $index   index name
     * @param string       $type    index type
     * @param string       $id      identifier for the data
     * @param array|object $data    data to index
     * 
     * @return array
     */
    public function index($index, $type, $id, $data)
    {
        $params = [
            'index' => $index,
            'type' => $type,
            'id' => $id,
            'body' => $data
        ];

        return $this->client->index($params);
    }
}
