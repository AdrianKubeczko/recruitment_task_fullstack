<?php

declare(strict_types=1);

namespace Unit\Services;

use App\Service\ExchangeRatesService;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;

class ExchangeRatesServiceTest extends TestCase
{
    private $httpClient;
    private $exchangeRatesService;

    protected function setUp(): void
    {
        $this->httpClient = $this->createMock(HttpClientInterface::class);
        $this->exchangeRatesService = new ExchangeRatesService($this->httpClient, 'https://api.nbp.pl/api/exchangerates/tables/A');
    }

    public function testGetExchangeRatesValidData()
    {
        $date = '2024-07-19';
        $currencies = ['EUR', 'USD', 'CZK', 'BRL', 'IDR'];

        $mockResponseToday = [
            [
                "table" => "A",
                "no" => "140/A/NBP/2024",
                "effectiveDate" => "2024-07-19",
                "rates" => [
                    ["currency" => "dolar amerykański", "code" => "USD", "mid" => 3.9461],
                    ["currency" => "euro", "code" => "EUR", "mid" => 4.2930],
                    ["currency" => "korona czeska", "code" => "CZK", "mid" => 0.1703],
                    ["currency" => "real (Brazylia)", "code" => "BRL", "mid" => 0.7117],
                    ["currency" => "rupia indonezyjska", "code" => "IDR", "mid" => 0.00024374],
                ]
            ]
        ];

        $mockResponseDate = $mockResponseToday; // Same data for simplicity in this example

        $todayResponse = $this->createMock(ResponseInterface::class);
        $todayResponse->method('toArray')->willReturn($mockResponseToday);

        $dateResponse = $this->createMock(ResponseInterface::class);
        $dateResponse->method('toArray')->willReturn($mockResponseDate);

        $this->httpClient->method('request')
            ->willReturnOnConsecutiveCalls($todayResponse, $dateResponse);

        $result = $this->exchangeRatesService->getExchangeRates($date, $currencies);

        $expected = [
            [
                "currency" => "dolar amerykański",
                "code" => "USD",
                "todayMid" => 3.9461,
                "dateMid" => 3.9461
            ],
            [
                "currency" => "euro",
                "code" => "EUR",
                "todayMid" => 4.2930,
                "dateMid" => 4.2930
            ],
            [
                "currency" => "korona czeska",
                "code" => "CZK",
                "todayMid" => 0.1703,
                "dateMid" => 0.1703
            ],
            [
                "currency" => "real (Brazylia)",
                "code" => "BRL",
                "todayMid" => 0.7117,
                "dateMid" => 0.7117
            ],
            [
                "currency" => "rupia indonezyjska",
                "code" => "IDR",
                "todayMid" => 0.00024374,
                "dateMid" => 0.00024374
            ]
        ];

        $this->assertEquals($expected, $result);
    }

    public function testThrowsExceptionWhenDateIsInvalid()
    {
        $date = '2022-07-13';
        $currencies = ['EUR', 'USD', 'CZK', 'BRL', 'IDR'];

        try {
            $this->exchangeRatesService->getExchangeRates($date, $currencies);
            $this->fail('Expected HttpException was not thrown.');
        } catch (HttpException $e) {
            $this->assertEquals(Response::HTTP_BAD_REQUEST, $e->getStatusCode());
            $this->assertEquals('Invalid date format or date is older than 2023.', $e->getMessage());
        }
    }

    public function testThrowsExceptionWhenRatesNotFound()
    {
        $date = '2024-07-19';
        $currencies = ['EUR', 'USD', 'CZK', 'BRL', 'IDR'];

        $this->httpClient->method('request')
            ->willThrowException($this->createMock(ClientExceptionInterface::class));


        try {
            $this->exchangeRatesService->getExchangeRates($date, $currencies);
        } catch (HttpException $e) {
            $this->assertEquals(Response::HTTP_NOT_FOUND, $e->getStatusCode());
            $this->assertEquals('Exchange rates not found for the given date.', $e->getMessage());
        }
    }

    public function testThrowsExceptionWhenGeneralErrorOccurs()
    {
        $date = '2024-07-19';
        $currencies = ['EUR', 'USD', 'CZK', 'BRL', 'IDR'];

        $this->httpClient->method('request')
            ->willThrowException(new \Exception('Some error'));

        try {
            $this->exchangeRatesService->getExchangeRates($date, $currencies);
        } catch (HttpException $e) {
            $this->assertEquals(Response::HTTP_INTERNAL_SERVER_ERROR, $e->getStatusCode());
            $this->assertEquals('Error fetching rates: Some error', $e->getMessage());
        }
    }
}
