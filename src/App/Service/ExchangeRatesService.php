<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use App\Helper\FilterHelper;
use App\Helper\DateHelper;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ExchangeRatesService
{
    private $httpClient;
    private $nbpApiUrl;

    public function __construct(HttpClientInterface $httpClient, string $nbpApiUrl)
    {
        $this->httpClient = $httpClient;
        $this->nbpApiUrl = $nbpApiUrl;
    }

    /**
     * Fetches exchange rates for the specified date and currencies.
     *
     * @param string $date
     * @param array $currencies
     * @return array
     * @throws HttpException
     */
    public function getExchangeRates(string $date, array $currencies): array
    {
        if (!is_array($currencies)) {
            throw new HttpException(Response::HTTP_BAD_REQUEST, 'Currencies parameter must be an array.');
        }

        if (!DateHelper::isValidDate($date)) {
            throw new HttpException(Response::HTTP_BAD_REQUEST, 'Invalid date format or date is older than 2023.');
        }

        // 6.
        $todayRates = $this->fetchRates("{$this->nbpApiUrl}?format=json");
        $specifiedDateRates = $this->fetchRates("{$this->nbpApiUrl}/$date?format=json");

        return FilterHelper::combineCurrencyRates($todayRates, $specifiedDateRates, $currencies);
    }

    /**
     * Fetches rates from the given URL.
     *
     * @param string $url
     * @return array
     * @throws HttpException
     */
    private function fetchRates(string $url): array
    {
        try {
            $response = $this->httpClient->request('GET', $url);
            return $response->toArray();
        } catch (ClientExceptionInterface $e) {
            throw new HttpException(Response::HTTP_NOT_FOUND, 'Exchange rates not found for the given date.');
        } catch (\Exception $e) {
            throw new HttpException(Response::HTTP_INTERNAL_SERVER_ERROR, 'Error fetching rates: ' . $e->getMessage());
        }
    }
}
