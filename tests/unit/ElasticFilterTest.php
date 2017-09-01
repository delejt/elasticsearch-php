<?php

namespace LegalThings;

use Codeception\TestCase\Test;

/**
 * Tests for ElasticFilter class
 * 
 * @covers \LegalThings\ElasticFilter
 */
class ElasticFilterTest extends Test
{
    public function testConstruct()
    {
        $filter = ['foo' => 'bar'];

        $ef = new ElasticFilter($filter);
        
        $this->assertEquals($filter, $ef->filter);
    }
    
    
    public function testTransform()
    {
        $filter = [
            'id' => '0001',
            'authors' => ['John', 'Jane'],
            'deleted' => null,
            'start_date(min)' => '2017-01-01T00:00:00',
            'end_date(max)' => '2018-01-01T00:00:00',
            'age(min)' => 25, // can also use string, depending on Elasticsearch mapping
            'tags(not)' => ['foo', 'bar'],
            'published(not)' => null,
            'colors(any)' => ['blue', 'green'],
            'colors(none)' => ['red'],
            'category(all)' => ['A', 'B', 'C']
        ];

        $ef = new ElasticFilter($filter);
        $query = $ef->transform();
        
        $this->assertEquals([
            'bool' => [
                'must' => [
                    [ 'term' => [ 'id' => '0001' ] ],
                    [ 'terms' => [ 'authors' => ['John', 'Jane'] ] ],
                    [ 'missing' => [ 'field' => 'deleted' ] ],
                    [ 'range' => [ 'start_date' => [ 'gte' => '2017-01-01T00:00:00' ] ] ],
                    [ 'range' => [ 'end_date' => [ 'lte' => '2018-01-01T00:00:00' ] ] ],
                    [ 'range' => [ 'age' => [ 'gte' => 25 ] ] ],
                    [ 'terms' => [ 'colors' => [ 'blue', 'green' ] ] ],
                    [ 'term' => [ 'category' => [ 'A', 'B', 'C' ] ] ]
                ],
                'must_not' => [
                    [ 'terms' => [ 'tags' => [ 'foo', 'bar' ] ] ],
                    [ 'missing' => [ 'field' => 'published' ] ],
                    [ 'term' => [ 'colors' => [ 'red' ] ] ]
                ]
            ]
        ], $query);
    }
    
    
    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Invalid filter key 'id(foo)'. Unknown operator 'foo'.
     */
    public function testToQueryUnknownFilterException()
    {
        $filter = [
            'id(foo)' => '0001'
        ];

        $ef = new ElasticFilter($filter);
        $ef->transform();
    }
}