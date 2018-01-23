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
            'category(all)' => ['A', 'B', 'C'],
            'permissions(should)' => ['read', 'all'],
            'type(should)' => 'book',
            'random(should)' => null,
            'stuff(should_not)' => ['this', 'that'],
            'sub_type(should_not)' => 'modern',
            'property(should_not)' => null
        ];

        $ef = new ElasticFilter($filter);
        $query = $ef->transform();
        
        $this->assertEquals([
            'bool' => [
                'must' => [
                    [ 'term' => [ 'id' => '0001' ] ],
                    [ 'terms' => [ 'authors' => [ 'John', 'Jane' ] ] ],
                    [ 'exists' => [ 'field' => 'deleted' ] ],
                    [ 'range' => [ 'start_date' => [ 'gte' => '2017-01-01T00:00:00' ] ] ],
                    [ 'range' => [ 'end_date' => [ 'lte' => '2018-01-01T00:00:00' ] ] ],
                    [ 'range' => [ 'age' => [ 'gte' => 25 ] ] ],
                    [ 'terms' => [ 'colors' => [ 'blue', 'green' ] ] ],
                    [ 'term' => [ 'category' => [ 'A', 'B', 'C' ] ] ]
                ],
                'must_not' => [
                    [ 'terms' => [ 'tags' => [ 'foo', 'bar' ] ] ],
                    [ 'exists' => [ 'field' => 'published' ] ],
                    [ 'term' => [ 'colors' => [ 'red' ] ] ]
                ],
                'should' => [
                    [ 'terms' => [ 'permissions' => [ 'read', 'all' ] ] ],
                    [ 'term' => [ 'type' => 'book' ] ],
                    [ 'exists' => [ 'field' => 'random' ] ]
                ],
                'should_not' => [
                    [ 'terms' => [ 'stuff' => [ 'this', 'that' ] ] ],
                    [ 'term' => [ 'sub_type' => 'modern' ] ],
                    [ 'exists' => [ 'field' => 'property' ] ]
                ]
            ]
        ], $query);
    }
    
    public function testTransformRaw()
    {
        $filter = [
            'foo' => 'bar',
            'not' => 'modified',
            'es_raw' => 1
        ];

        $ef = new ElasticFilter($filter);
        $query = $ef->transform();
        
        $this->assertEquals([
            'foo' => 'bar',
            'not' => 'modified'
        ], $query);
    }
    
    public function testFilterChaining()
    {
        $ef = new ElasticFilter();

        $query = $ef->addDefaultFilter('id', '0001')
            ->addDefaultFilter('authors', ['John', 'Jane'])
            ->addDefaultFilter('deleted', null)
            ->addMinFilter('start_date', '2017-01-01T00:00:00')
            ->addMaxFilter('end_date', '2018-01-01T00:00:00')
            ->addMinFilter('age', 25)
            ->addNotFilter('tags', ['foo', 'bar'])
            ->addNotFilter('published', null)
            ->addAnyFilter('colors', ['blue', 'green'])
            ->addNoneFilter('colors', ['red'])
            ->addAllFilter('category', ['A', 'B', 'C'])
            ->addShouldFilter('permissions', ['read', 'all'])
            ->addShouldFilter('type', 'book')
            ->addShouldFilter('random', null)
            ->addShouldNotFilter('stuff', ['this', 'that'])
            ->addShouldNotFilter('sub_type', 'modern')
            ->addShouldNotFilter('property', null)
            ->transform();
        
        $this->assertEquals([
            'bool' => [
                'must' => [
                    [ 'term' => [ 'id' => '0001' ] ],
                    [ 'terms' => [ 'authors' => ['John', 'Jane'] ] ],
                        [ 'exists' => [ 'field' => 'deleted' ] ],
                    [ 'range' => [ 'start_date' => [ 'gte' => '2017-01-01T00:00:00' ] ] ],
                    [ 'range' => [ 'end_date' => [ 'lte' => '2018-01-01T00:00:00' ] ] ],
                    [ 'range' => [ 'age' => [ 'gte' => 25 ] ] ],
                    [ 'terms' => [ 'colors' => [ 'blue', 'green' ] ] ],
                    [ 'term' => [ 'category' => [ 'A', 'B', 'C' ] ] ]
                ],
                'must_not' => [
                    [ 'terms' => [ 'tags' => [ 'foo', 'bar' ] ] ],
                    [ 'exists' => [ 'field' => 'published' ] ],
                    [ 'term' => [ 'colors' => [ 'red' ] ] ]
                ],
                'should' => [
                    [ 'terms' => [ 'permissions' => [ 'read', 'all' ] ] ],
                    [ 'term' => [ 'type' => 'book' ] ],
                    [ 'exists' => [ 'field' => 'random' ] ]
                ],
                'should_not' => [
                    [ 'terms' => [ 'stuff' => [ 'this', 'that' ] ] ],
                    [ 'term' => [ 'sub_type' => 'modern' ] ],
                    [ 'exists' => [ 'field' => 'property' ] ]
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
