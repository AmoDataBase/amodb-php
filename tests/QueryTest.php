<?php

declare(strict_types=1);

namespace Amodb\Tests;

use Amodb\Http\Query;
use PHPUnit\Framework\TestCase;

final class QueryTest extends TestCase
{
    public function testBooleansBecomeTrueAndFalse(): void
    {
        $this->assertSame('a=true&b=false', Query::build(['a' => true, 'b' => false]));
    }

    public function testNullValuesAreDropped(): void
    {
        $this->assertSame('b=1', Query::build(['a' => null, 'b' => 1]));
    }

    public function testListsAreJoinedWithCommas(): void
    {
        $this->assertSame(
            'append_to_response=credits%2Cimages',
            Query::build(['append_to_response' => ['credits', 'images']]),
        );
    }

    public function testListsDropNullsAndConvertBooleansAndNumbers(): void
    {
        $this->assertSame('x=true%2C3%2Ca', Query::build(['x' => [true, null, 3, 'a']]));
    }

    public function testEmptyListsAreDropped(): void
    {
        $this->assertSame('', Query::build(['x' => [], 'y' => [null]]));
    }

    public function testNumbersAreSentAsText(): void
    {
        $this->assertSame('page=2&vote_average.gte=7.5', Query::build(['page' => 2, 'vote_average.gte' => 7.5]));
    }

    public function testKeysAreSentAsGiven(): void
    {
        $this->assertSame('with_genres=28&include_adult=false', Query::build(['with_genres' => '28', 'include_adult' => false]));
    }

    public function testValuesArePercentEncoded(): void
    {
        $this->assertSame(
            'query=fight%20club%20%26%20more%2F%C3%A9',
            Query::build(['query' => "fight club & more/\u{e9}"]),
        );
    }

    public function testEmptyStringsAreKept(): void
    {
        $this->assertSame('query=', Query::build(['query' => '']));
    }

    public function testNoParametersGiveAnEmptyString(): void
    {
        $this->assertSame('', Query::build([]));
    }

    public function testNestedListsAreRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Query::build(['x' => [['a']]]);
    }

    public function testUnsupportedValuesAreRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Query::build(['x' => new \stdClass()]);
    }
}
