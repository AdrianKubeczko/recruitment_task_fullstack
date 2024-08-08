import React, { useState, useEffect } from 'react';
import axios from 'axios';
import exchangeRates from './../const/exchangeRates';
import errorMessages from './../const/errorMessages';
import { useParams, useHistory } from 'react-router-dom';

const ExchangeRates = () => {
    const { date: urlDate } = useParams();
    const history = useHistory();

    const [rates, setRates] = useState();
    const [date, setDate] = useState(urlDate);
    const [error, setError] = useState();
    const [loading, setLoading] = useState(false);

    const minDate = new Date('2023-01-01');
    const maxDate = new Date();

    // 1.
    const displayedCurrencies = [
        exchangeRates.USD.code,
        exchangeRates.EUR.code,
        exchangeRates.CZK.code,
        exchangeRates.IDR.code,
        exchangeRates.BRL.code
    ];

    const tableHeaders = ['Waluta', 'Kursy dzisiejsze', 'Kursy dla wybranej daty'];

    const fetchExchangeRates = async (selectedDate) => {
        setLoading(true);
        try {
            const response = await axios.get('/api/exchange-rates', {
                params: {
                    date: selectedDate,
                    currencies: displayedCurrencies
                }
            });
            setRates(response.data);
        } catch (error) {
            const errorMessage = error?.response?.data?.message;
            const errorCode = error?.response?.data?.code;
            // 2.
            console.log(errorMessage)
            setError(errorMessages[errorCode]);
            setRates(undefined);
        } finally {
            setLoading(false);
        }
    };

    // 3.
    const handleDateBlur = () => {
        let selectedDate = new Date(date);
        if (selectedDate < minDate) selectedDate = minDate;
        if (selectedDate > maxDate) selectedDate = maxDate;
        const formattedDate = selectedDate.toISOString().split('T')[0];
        setDate(formattedDate);
        history.push(`/exchange-rates/${formattedDate}`);
    };

    useEffect(() => {
        fetchExchangeRates(date);
    }, []);

    const renderTableData = (rate) => {
        const exchangeParameters = exchangeRates[rate.code];
        if (!exchangeParameters) return null;

        const { sell: sellMargin, buy: buyMargin } = exchangeParameters;

        // 4.
        const getBuyString = (mid) =>
            buyMargin ? `kurs kupna: ${(mid - buyMargin).toFixed(4)} PLN` : '---';
        const getSellString = (mid) => `kurs sprzedaży: ${(mid + sellMargin).toFixed(4)} PLN`;

        const columns = [
            { id: 'currencyCode', content: `${rate.code} - ${rate.currency}` },
            {
                id: 'todaysRate',
                content: (
                    <div>
                        <p style={{ margin: 0 }}>{getBuyString(rate.todayMid)}</p>
                        <p style={{ margin: 0 }}>{getSellString(rate.todayMid)}</p>
                    </div>
                )
            },
            {
                id: 'datesRate',
                content: (
                    <div>
                        <p style={{ margin: 0 }}>{getBuyString(rate.dateMid)}</p>
                        <p style={{ margin: 0 }}>{getSellString(rate.dateMid)}</p>
                    </div>
                )
            }
        ];

        return (
            <tr key={rate.code} className="table-row">
                {columns.map((column) => (
                    <td key={column.id} className="table-data">
                        {column.content}
                    </td>
                ))}
            </tr>
        );
    };

    return (
        <div>
            <h1>Kursy walut</h1>
            <div className="management-container">
                <input
                    type="date"
                    onChange={(e) => setDate(e.target.value)}
                    onBlur={handleDateBlur}
                    value={date}
                    min={minDate.toISOString().split('T')[0]}
                    max={maxDate.toISOString().split('T')[0]}
                />
                <button
                    onClick={() => {
                        setError(undefined);
                        fetchExchangeRates(date);
                    }}
                >
                    Zatwierdź datę
                </button>
                {error && (
                    <div className="error">
                        <strong>{error}</strong>
                    </div>
                )}
                {loading && (
                    <div className="management-container">
                        Ładowanie...
                        <span className="fa fa-spin fa-spinner fa-2x"></span>
                    </div>
                )}
            </div>
            {rates && !error && !loading && (
                <table className="table">
                    <thead>
                        <tr className="table-thread-row">
                            {tableHeaders.map((header) => (
                                <th key={header} className="table-header">
                                    {header}
                                </th>
                            ))}
                        </tr>
                    </thead>
                    <tbody>{rates.map(renderTableData)}</tbody>
                </table>
            )}
        </div>
    );
};

export default ExchangeRates;
