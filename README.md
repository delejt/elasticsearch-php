Legal Things - Elasticsearch PHP
==================

This library provides you with a simplified interface to use Elasticsearch.

## Requirements

- [PHP](http://www.php.net) >= 5.6.0

_Required PHP extensions are marked by composer_


## Installation

The library can be installed using composer.

    composer require legalthings/elasticsearch-php


## Usage

```php
use LegalThings/Elasticsearch;

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

/*
{
  "took": 1,
  "timed_out": false,
  "_shards": {
    "total": 5,
    "successful": 5,
    "failed": 0
  },
  "hits": {
    "total": 1,
    "max_score": null,
    "hits": [{
      "_index": "books",
      "_type": "ancient",
      "_id": "0001",
      "_score": null,
      "_source": {
        "id": "0001",
        "updated": "2017-01-01T00:00:00",
        "year": 1980,
        "published": false,
        "name": "My book two"
      },
      "sort": [1980]
    }]
  }
}
*/
```


## Filters

This library makes it easy to filter for data in Elasicsearch, because you don't have to transform the filter in a specific structure.
See the [tests](https://github.com/legalthings/elasticsearch-php/blob/414a05f8c9127b69773f853953351a7df47a335c/tests/unit/ElasticFilterTest.php#L24-L62) for more examples.
See [jasny filter](https://github.com/jasny/db#filter) for more information about the syntax.

```php
use LegalThings/ElasticFilter;

$filter = [
    'id' => '0001',
    'authors' => ['John', 'Jane'],
    'deleted' => null,
    'start_date(min)' => '2017-01-01T00:00:00',
    'end_date(max)' => '2018-01-01T00:00:00',
    'age(min)' => 25,
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
```


## Configuration
You can use any configuration that Elasticsearch allows you to.
See [this](https://www.elastic.co/guide/en/elasticsearch/client/php-api/current/_configuration.html#_building_the_client_from_a_configuration_hash) link for more information.

```php
[
    'hosts' => [
      'localhost:9200'
    ],
    'retries' => 2
]
```
