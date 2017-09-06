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
    
    public function testIndex()
    {
        $config = $this->getConfig();
        $config['handler'] = new MockHandler([
            'status' => 200,
            'transfer_stats' => ['total_time' => 100],
            'body' => fopen('tests/_data/index-response.json', 'r')
        ]);
        
        $es = new Elasticsearch($config);
        
        $index = 'books';
        $type = 'ancient';
        $id = '0001';
        $data = [
            'id' => '0001',
            'updated' => '2017-01-01T00:00:00',
            'year' => 1980,
            'published' => false,
            'name' => 'My book two'
        ];
        
        $result = $es->index($index, $type, $id, $data);
        
        $this->assertTrue($result['created']);
        $this->assertEquals('0001', $result['_id']);
    }
    
    public function testUpdate()
    {
        $config = $this->getConfig();
        $config['handler'] = new MockHandler([
            'status' => 200,
            'transfer_stats' => ['total_time' => 100],
            'body' => fopen('tests/_data/update-response.json', 'r')
        ]);
        
        $es = new Elasticsearch($config);
        
        $index = 'books';
        $type = 'ancient';
        $id = '0001';
        $data = [
            'name' => 'My book three'
        ];
        
        $result = $es->update($index, $type, $id, $data);
        
        $this->assertEquals('updated', $result['result']);
        $this->assertEquals('0001', $result['_id']);
    }
    
    public function testGet()
    {
        $config = $this->getConfig();
        $config['handler'] = new MockHandler([
            'status' => 200,
            'transfer_stats' => ['total_time' => 100],
            'body' => fopen('tests/_data/get-response.json', 'r')
        ]);
        
        $es = new Elasticsearch($config);
        
        $index = 'books';
        $type = 'ancient';
        $id = '0001';
        
        $result = $es->get($index, $type, $id);
        
        $this->assertEquals('0001', $result['_source']['id']);
    }
    
    public function testDelete()
    {
        $config = $this->getConfig();
        $config['handler'] = new MockHandler([
            'status' => 200,
            'transfer_stats' => ['total_time' => 100],
            'body' => fopen('tests/_data/delete-response.json', 'r')
        ]);
        
        $es = new Elasticsearch($config);
        
        $index = 'books';
        $type = 'ancient';
        $id = '0001';
        
        $result = $es->delete($index, $type, $id);
        
        $this->assertEquals('deleted', $result['result']);
        $this->assertEquals('0001', $result['_id']);
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
        
        $this->assertCount(1, $result['hits']['hits']);
        $this->assertEquals('0001', $result['hits']['hits'][0]['_source']['id']);
    }
    
    
    public function testIndexCreate()
    {
        $config = $this->getConfig();
        $config['handler'] = new MockHandler([
            'status' => 200,
            'transfer_stats' => ['total_time' => 100],
            'body' => fopen('tests/_data/index-create-response.json', 'r')
        ]);
        
        $es = new Elasticsearch($config);
        
        $index = 'books';
        $data = [
            'settings' => [
                'number_of_shards' => 1
            ]
        ];
        
        $result = $es->indexCreate($index, $data);
        
        $this->assertTrue($result['acknowledged']);
        $this->assertTrue($result['shards_acknowledged']);
    }
    
    public function testIndexExists()
    {
        $config = $this->getConfig();
        $config['handler'] = new MockHandler([
            'status' => 200,
            'transfer_stats' => ['total_time' => 100]
        ]);
        
        $es = new Elasticsearch($config);
        
        $index = 'books';
        
        $result = $es->indexExists($index);
        
        $this->assertTrue($result);
    }
    
    public function testIndexDelete()
    {
        $config = $this->getConfig();
        $config['handler'] = new MockHandler([
            'status' => 200,
            'transfer_stats' => ['total_time' => 100],
            'body' => fopen('tests/_data/index-delete-response.json', 'r')
        ]);
        
        $es = new Elasticsearch($config);
        
        $index = 'books';
        
        $result = $es->indexDelete($index);
        
        $this->assertTrue($result['acknowledged']);
    }
}
