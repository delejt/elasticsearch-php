<?php

namespace LegalThings;

use Codeception\TestCase\Test;
use Elasticsearch\Client;
use GuzzleHttp\Ring\Client\MockHandler;

/**
 * Tests for Elasticsearch class
 * 
 * @covers \LegalThings\Elasticsearch
 */
class ElasticsearchTest extends Test
{
    protected function getConfig()
    {
        return [
            'hosts' => ['localhost:9200'],
            'retries' => 2
        ];
    }
    
    protected function getMockHandler($response, $status = 200)
    {
        return new MockHandler([
          'status' => $status,
          'body' => $response
        ]);
    }
    
    
    public function testConstruct()
    {
        $config = $this->getConfig();
        
        $es = new Elasticsearch($config);
        
        $this->assertEquals((object)$config, $es->config);
        $this->assertInstanceOf(Client::class, $es->client);
    }
    
    
    public function testClientInfo()
    {
        $config = $this->getConfig();
        $config['handler'] = new MockHandler([
            'status' => 200,
            'transfer_stats' => ['total_time' => 100],
            'body' => fopen('tests/_data/info-response.json', 'r')
        ]);
        
        $es = new Elasticsearch($config);
        
        $result = $es->client->info();
        
        $this->assertEquals('5.3.0', $result['version']['number']);
    }
    
    
    public function testSearch()
    {
        $config = $this->getConfig();
        $config['handler'] = new MockHandler([
            'status' => 200,
            'transfer_stats' => ['total_time' => 100],
            'body' => fopen('tests/_data/search-response.json', 'r')
        ]);
        
        $es = new Elasticsearch($config);
        
        $index = 'books';
        $type = 'ancient';
        $text = 'My book';
        $fields = ['name'];
        $filter = [
            'id' => '0001',
            'updated(max)' => '2017-01-01T00:00:00',
            'year(min)' => 1973,
            'published' => false
        ];
        $sort = ['^year'];
        $limit = 15;
        $offset = 0;
        
        $result = $es->search($index, $type, $text, $fields, $filter, $sort, $limit, $offset);
        
        $this->assertEquals('My book two', $result['hits']['hits'][0]['_source']['name']);
    }
}
