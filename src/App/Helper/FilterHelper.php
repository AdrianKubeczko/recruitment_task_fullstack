<?php

namespace App\Helper;

class FilterHelper
{
    /**
     * Combines today's currency rates with specified date rates for a given list of currencies.
     *
     * @param array $todaysRates
     * @param array $specifiedDateRates
     * @param array $currencies
     * @return array
     */
    public static function combineCurrencyRates(array $todaysRates, array $specifiedDateRates, array $currencies): array
    {
        $result = [];

        // Create a map of currency rates for the specified date
        $dateRatesMap = [];
        foreach ($specifiedDateRates[0]['rates'] as $rate) {
            $dateRatesMap[$rate['code']] = $rate['mid'];
        }

        // Iterate over today's rates and combine them with the specified date rates if the currency is in the list
        foreach ($todaysRates[0]['rates'] as $rate) {
            if (in_array($rate['code'], $currencies)) {
                $currencyCode = $rate['code'];
                $result[] = [
                    "currency" => $rate['currency'],
                    "code" => $currencyCode,
                    "todayMid" => $rate['mid'],
                    "dateMid" => isset($dateRatesMap[$currencyCode]) ? $dateRatesMap[$currencyCode] : null
                ];
            }
        }

        return $result;
    }
}
