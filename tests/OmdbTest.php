<?php

declare(strict_types=1);

namespace Amodb\Tests;

use Amodb\Exception\AmodbException;

final class OmdbTest extends ClientTestCase
{
    public function testByIdSendsTheKeyAsApikey(): void
    {
        $payload = [
            'Title' => 'Fight Club',
            'Year' => '1999',
            'imdbID' => 'tt0137523',
            'Response' => 'True',
        ];
        $transport = (new FakeTransport())->queueJson($payload);
        $client = $this->makeClient($transport);

        $result = $client->omdb->byId('tt0137523', plot: 'full');

        $this->assertSame($payload, $result);
        $request = $transport->last();
        $this->assertStringStartsWith('https://api.amodb.dev/omdb/?', $request['url']);
        $this->assertEquals(
            ['apikey' => self::KEY, 'i' => 'tt0137523', 'plot' => 'full', 'r' => 'json'],
            self::queryOf($request['url']),
        );
    }

    public function testOmdbCallsNeverSendBearerOrLanguage(): void
    {
        $transport = (new FakeTransport())->queueJson(['Response' => 'True']);
        $client = $this->makeClient($transport, ['language' => 'ru-RU']);

        $client->omdb->byTitle('Fight Club');

        $request = $transport->last();
        $query = self::queryOf($request['url']);
        $this->assertArrayNotHasKey('Authorization', $request['headers']);
        $this->assertArrayNotHasKey('language', $query);
        $this->assertArrayNotHasKey('api_key', $query);
        $this->assertSame(self::KEY, $query['apikey']);
    }

    public function testOmdbUsesApikeyEvenInQueryAuthMode(): void
    {
        $transport = (new FakeTransport())->queueJson(['Response' => 'True']);
        $client = $this->makeClient($transport, ['auth' => 'query']);

        $client->omdb->byId('tt0137523');

        $query = self::queryOf($transport->last()['url']);
        $this->assertSame(self::KEY, $query['apikey']);
        $this->assertArrayNotHasKey('api_key', $query);
    }

    public function testByTitleParameters(): void
    {
        $transport = (new FakeTransport())->queueJson(['Response' => 'True']);
        $client = $this->makeClient($transport);

        $client->omdb->byTitle('Fight Club', type: 'movie', year: 1999, plot: 'short');

        $this->assertEquals(
            ['apikey' => self::KEY, 't' => 'Fight Club', 'type' => 'movie', 'y' => '1999', 'plot' => 'short', 'r' => 'json'],
            self::queryOf($transport->last()['url']),
        );
    }

    public function testSearchParameters(): void
    {
        $transport = (new FakeTransport())->queueJson(['Response' => 'True', 'Search' => []]);
        $client = $this->makeClient($transport);

        $client->omdb->search('fight', type: 'movie', year: 1999, page: 2);

        $this->assertEquals(
            ['apikey' => self::KEY, 's' => 'fight', 'type' => 'movie', 'y' => '1999', 'page' => '2', 'r' => 'json'],
            self::queryOf($transport->last()['url']),
        );
    }

    public function testResponseFalseRaisesAnAmodbException(): void
    {
        $body = ['Response' => 'False', 'Error' => 'Movie not found!'];
        $transport = (new FakeTransport())->queueJson($body, 200);
        $client = $this->makeClient($transport);

        try {
            $client->omdb->byId('tt0000000');
            $this->fail('Expected an AmodbException.');
        } catch (AmodbException $e) {
            $this->assertSame(200, $e->getStatus());
            $this->assertNull($e->getStatusCode());
            $this->assertSame('Movie not found!', $e->getStatusMessage());
            $this->assertSame('Movie not found!', $e->getMessage());
            $this->assertSame($body, $e->getBody());
        }
    }

    public function testOmdbHttpErrorUsesTheErrorText(): void
    {
        $transport = (new FakeTransport())->queueJson(['Response' => 'False', 'Error' => 'Invalid API key!'], 401);
        $client = $this->makeClient($transport);

        try {
            $client->omdb->byId('tt0137523');
            $this->fail('Expected an AmodbException.');
        } catch (AmodbException $e) {
            $this->assertSame(401, $e->getStatus());
            $this->assertNull($e->getStatusCode());
            $this->assertSame('Invalid API key!', $e->getStatusMessage());
        }
    }

    public function testCustomOmdbUrlWithoutTrailingSlash(): void
    {
        $transport = (new FakeTransport())->queueJson(['Response' => 'True']);
        $client = $this->makeClient($transport, ['omdbUrl' => 'https://api.amodb.dev/omdb']);

        $client->omdb->byId('tt0137523');

        $this->assertStringStartsWith('https://api.amodb.dev/omdb?apikey=', $transport->last()['url']);
    }
}
