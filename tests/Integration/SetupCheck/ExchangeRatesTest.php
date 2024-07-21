<?php

namespace Integration\ExchangeRates;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class ExchangeRatesTest extends WebTestCase
{
    public function testGetRatesSuccess(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/exchange-rates', [
            'date' => '2024-07-19',
            'currencies' => ['USD', 'EUR']
        ]);
        $response = $client->getResponse();

        $this->assertResponseIsSuccessful();
        $this->assertJson($response->getContent());
    }

    public function testNoDataForChosenDate(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/exchange-rates', [
            'date' => '2024-07-13',
            'currencies' => ['USD', 'EUR']
        ]);
        $response = $client->getResponse();

        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }
}
