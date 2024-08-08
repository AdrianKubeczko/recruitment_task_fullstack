import React from 'react';
import { render, screen, fireEvent, waitFor, act, within } from '@testing-library/react';
import axios from 'axios';
import { MemoryRouter, Route } from 'react-router-dom';
import ExchangeRates from './../../assets/js/components/ExchangeRates';
import exchangeRates from './../../assets/js/const/exchangeRates';
import errorMessages from './../../assets/js/const/errorMessages';

jest.mock('axios');

const mockRates = [
    {
        code: 'USD',
        currency: 'dolar amerykański',
        todayMid: 3.8,
        dateMid: 3.75
    },
    {
        code: 'EUR',
        currency: 'euro',
        todayMid: 4.5,
        dateMid: 4.4
    }
];

const renderComponent = (initialDate) => {
    return render(
        <MemoryRouter initialEntries={[`/exchange-rates/${initialDate}`]}>
            <Route path="/exchange-rates/:date">
                <ExchangeRates />
            </Route>
        </MemoryRouter>
    );
};

describe('ExchangeRates Component', () => {
    beforeEach(() => {
        jest.clearAllMocks();
    });

    test('renders correctly and fetches exchange rates', async () => {
        axios.get.mockResolvedValueOnce({ data: mockRates });

        await act(async () => {
            renderComponent('2023-07-01');
        });

        expect(screen.getByText('Kursy walut')).toBeInTheDocument();
        expect(screen.getByDisplayValue('2023-07-01')).toBeInTheDocument();

        await waitFor(() => {
            expect(screen.getByText('USD - dolar amerykański')).toBeInTheDocument();
            expect(screen.getByText('EUR - euro')).toBeInTheDocument();
        });
    });

    test('handles date change and fetches new exchange rates', async () => {
        axios.get.mockResolvedValueOnce({ data: mockRates });

        await act(async () => {
            renderComponent('2023-07-01');
        });

        fireEvent.change(screen.getByDisplayValue('2023-07-01'), {
            target: { value: '2023-06-01' }
        });

        fireEvent.click(screen.getByText('Zatwierdź datę'));

        await waitFor(() => {
            expect(axios.get).toHaveBeenCalledWith('/api/exchange-rates', {
                params: {
                    date: '2023-06-01',
                    currencies: ['USD', 'EUR', 'CZK', 'IDR', 'BRL']
                }
            });
        });
    });

    test('displays error message on fetch failure', async () => {
        axios.get.mockRejectedValueOnce({
            response: {
                data: {
                    message: 'Error message',
                    code: 404
                }
            }
        });

        await act(async () => {
            renderComponent('2023-07-01');
        });

        await waitFor(() => {
            expect(screen.getByText(errorMessages[404])).toBeInTheDocument();
        });
    });

    test('shows loading state during fetch', async () => {
        axios.get.mockImplementation(
            () => new Promise((resolve) => setTimeout(() => resolve({ data: mockRates }), 100))
        );

        await act(async () => {
            renderComponent('2023-07-01');
        });

        expect(screen.getByText('Ładowanie...')).toBeInTheDocument();

        await waitFor(() => {
            expect(screen.queryByText('Ładowanie...')).not.toBeInTheDocument();
        });
    });

    test('ensures date is within valid bounds', async () => {
        axios.get.mockResolvedValueOnce({ data: mockRates });

        await act(async () => {
            renderComponent('2023-07-01');
        });

        const minDate = new Date('2023-01-01').toISOString().split('T')[0];
        const maxDate = new Date().toISOString().split('T')[0];

        fireEvent.change(screen.getByDisplayValue('2023-07-01'), {
            target: { value: '2022-12-31' }
        });
        fireEvent.blur(screen.getByDisplayValue('2022-12-31'));
        expect(screen.getByDisplayValue(minDate)).toBeInTheDocument();

        fireEvent.change(screen.getByDisplayValue(minDate), { target: { value: '2025-01-01' } });
        fireEvent.blur(screen.getByDisplayValue('2025-01-01'));
        expect(screen.getByDisplayValue(maxDate)).toBeInTheDocument();
    });
});
