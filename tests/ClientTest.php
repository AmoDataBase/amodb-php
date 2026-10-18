<?php

declare(strict_types=1);

namespace Amodb\Tests;

use Amodb\Client;
use Amodb\Options;

final class ClientTest extends ClientTestCase
{
    public function testBearerAuthIsTheDefault(): void
    {
        $transport = (new FakeTransport())->queueJson(['id' => 550]);
        $client = $this->makeClient($transport);

        $result = $client->movies->details(550);

        $this->assertSame(['id' => 550], $result);
        $request = $transport->last();
        $this->assertSame('GET', $request['method']);
        $this->assertSame('https://api.amodb.dev/3/movie/550', $request['url']);
        $this->assertSame('Bearer ' . self::KEY, $request['headers']['Authorization']);
        $this->assertStringNotContainsString(self::KEY, $request['url']);
    }

    public function testQueryAuthSendsApiKeyInTheUrl(): void
    {
        $transport = (new FakeTransport())->queueJson(['id' => 550]);
        $client = $this->makeClient($transport, ['auth' => 'query']);

        $client->movies->details(550);

        $request = $transport->last();
        $this->assertSame('https://api.amodb.dev/3/movie/550?api_key=' . self::KEY, $request['url']);
        $this->assertArrayNotHasKey('Authorization', $request['headers']);
    }

    public function testRequestsCarryUserAgentAndAcceptHeaders(): void
    {
        $transport = (new FakeTransport())->queueJson([]);
        $client = $this->makeClient($transport);

        $client->configuration();

        $headers = $transport->last()['headers'];
        $this->assertSame('amodb-php/0.1.0', $headers['User-Agent']);
        $this->assertSame('application/json', $headers['Accept']);
        $this->assertSame('/3/configuration', self::pathOf($transport->last()['url']));
    }

    public function testNoLanguageIsSentByDefault(): void
    {
        $transport = (new FakeTransport())->queueJson([]);
        $client = $this->makeClient($transport);

        $client->movies->details(550);

        $this->assertSame([], self::queryOf($transport->last()['url']));
    }

    public function testDefaultLanguageIsSent(): void
    {
        $transport = (new FakeTransport())->queueJson([]);
        $client = $this->makeClient($transport, ['language' => 'ru-RU']);

        $client->movies->details(550);

        $this->assertSame('https://api.amodb.dev/3/movie/550?language=ru-RU', $transport->last()['url']);
    }

    public function testPerCallLanguageOverridesTheDefault(): void
    {
        $transport = (new FakeTransport())->queueJson([]);
        $client = $this->makeClient($transport, ['language' => 'ru-RU']);

        $client->movies->details(550, language: 'fr-FR');

        $this->assertSame(['language' => 'fr-FR'], self::queryOf($transport->last()['url']));
    }

    public function testLanguageInParamsOverridesTheDefault(): void
    {
        $transport = (new FakeTransport())->queueJson([]);
        $client = $this->makeClient($transport, ['language' => 'ru-RU']);

        $client->movies->details(550, params: ['language' => 'de-DE']);

        $this->assertSame(['language' => 'de-DE'], self::queryOf($transport->last()['url']));
    }

    public function testAppendToResponseIsJoinedWithCommas(): void
    {
        $transport = (new FakeTransport())->queueJson([])->queueJson([]);
        $client = $this->makeClient($transport);

        $client->movies->details(550, ['credits', 'images', 'ratings']);
        $first = $transport->last()['url'];
        $client->tv->details(1396, 'credits');
        $second = $transport->last()['url'];

        $this->assertStringContainsString('append_to_response=credits%2Cimages%2Cratings', $first);
        $this->assertSame(['append_to_response' => 'credits,images,ratings'], self::queryOf($first));
        $this->assertSame(['append_to_response' => 'credits'], self::queryOf($second));
    }

    public function testPathSegmentsArePercentEncoded(): void
    {
        $transport = (new FakeTransport())->queueJson([])->queueJson([]);
        $client = $this->makeClient($transport);

        $client->find('tt 0/1', 'imdb_id');
        $find = $transport->last()['url'];
        $client->movies->details('a b');
        $details = $transport->last()['url'];

        $this->assertStringContainsString('/3/find/tt%200%2F1?', $find);
        $this->assertSame(['external_source' => 'imdb_id'], self::queryOf($find));
        $this->assertSame('https://api.amodb.dev/3/movie/a%20b', $details);
    }

    public function testRequestEscapeHatchEncodesParameters(): void
    {
        $transport = (new FakeTransport())->queueJson(['ok' => true]);
        $client = $this->makeClient($transport);

        $result = $client->request('movie/550/credits', [
            'adult' => false,
            'with_genres' => [28, 12],
            'skip' => null,
            'q' => 'a b&c',
        ]);

        $this->assertSame(['ok' => true], $result);
        $url = $transport->last()['url'];
        $this->assertSame('/3/movie/550/credits', self::pathOf($url));
        $this->assertSame(['adult' => 'false', 'with_genres' => '28,12', 'q' => 'a b&c'], self::queryOf($url));
        $this->assertStringContainsString('q=a%20b%26c', $url);
        $this->assertStringNotContainsString('skip', $url);
    }

    public function testATrailingSlashOnTheBaseUrlIsIgnored(): void
    {
        $transport = (new FakeTransport())->queueJson([]);
        $client = $this->makeClient($transport, ['baseUrl' => 'https://api.amodb.dev/3/']);

        $client->request('/movie/1');

        $this->assertSame('https://api.amodb.dev/3/movie/1', $transport->last()['url']);
    }

    public function testImageUrl(): void
    {
        $client = $this->makeClient(new FakeTransport());

        $this->assertSame('https://img.amodb.dev/t/p/w500/abc.jpg', $client->imageUrl('/abc.jpg'));
        $this->assertSame('https://img.amodb.dev/t/p/original/abc.jpg', $client->imageUrl('abc.jpg', 'original'));
        $this->assertSame('https://img.amodb.dev/t/p/w92/a/b.png', $client->imageUrl('/a/b.png', 'w92'));
        $this->assertNull($client->imageUrl(null));
        $this->assertNull($client->imageUrl(''));
    }

    public function testImageUrlUsesTheImageBaseUrlOption(): void
    {
        $client = $this->makeClient(new FakeTransport(), ['imageBaseUrl' => 'https://img.amodb.dev/custom']);

        $this->assertSame('https://img.amodb.dev/custom/w185/x.jpg', $client->imageUrl('/x.jpg', 'w185'));
    }

    public function testPlansIsFetchedFromTheOriginWithoutAKey(): void
    {
        $transport = (new FakeTransport())->queueJson(['plans' => []])->queueJson(['plans' => []]);

        $this->makeClient($transport)->plans();
        $bearer = $transport->last();
        $this->makeClient($transport, ['auth' => 'query'])->plans();
        $query = $transport->last();

        $this->assertSame('https://api.amodb.dev/plans', $bearer['url']);
        $this->assertArrayNotHasKey('Authorization', $bearer['headers']);
        $this->assertSame('https://api.amodb.dev/plans', $query['url']);
        $this->assertArrayNotHasKey('Authorization', $query['headers']);
    }

    public function testTimeoutIsPassedToTheTransport(): void
    {
        $transport = (new FakeTransport())->queueJson([])->queueJson([]);

        $this->makeClient($transport)->configuration();
        $defaultTimeout = $transport->last()['timeout'];
        $this->makeClient($transport, ['timeout' => 5])->configuration();
        $customTimeout = $transport->last()['timeout'];

        $this->assertSame(15.0, $defaultTimeout);
        $this->assertSame(5.0, $customTimeout);
    }

    public function testOptionsObjectIsAccepted(): void
    {
        $transport = (new FakeTransport())->queueJson([]);
        $client = new Client(self::KEY, new Options(language: 'it-IT', transport: $transport));

        $client->movies->popular();

        $this->assertSame(['language' => 'it-IT'], self::queryOf($transport->last()['url']));
    }

    public function testAnEmptyKeyFailsFast(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new Client('');
    }

    public function testAKeyOfOnlySpacesFailsFast(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new Client('   ');
    }

    public function testUnknownOptionsAreRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new Client(self::KEY, ['languge' => 'ru-RU']);
    }

    public function testInvalidAuthModeIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new Client(self::KEY, ['auth' => 'basic']);
    }

    public function testInvalidBaseUrlIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new Client(self::KEY, ['baseUrl' => 'not a url']);
    }

    public function testVarDumpStyleOutputHidesTheKey(): void
    {
        $client = $this->makeClient(new FakeTransport());

        $this->assertStringNotContainsString(self::KEY, print_r($client, true));
    }
}
