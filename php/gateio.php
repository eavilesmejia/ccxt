<?php

namespace ccxt;

// PLEASE DO NOT EDIT THIS FILE, IT IS GENERATED AND WILL BE OVERWRITTEN:
// https://github.com/ccxt/ccxt/blob/master/CONTRIBUTING.md#how-to-contribute-code

use Exception as Exception; // a common import

class gateio extends Exchange {

    public function describe () {
        return array_replace_recursive (parent::describe (), array (
            'id' => 'gateio',
            'name' => 'Gate.io',
            'countries' => 'CN',
            'version' => '2',
            'rateLimit' => 1000,
            'has' => array (
                'CORS' => false,
                'createMarketOrder' => false,
                'fetchTickers' => true,
                'withdraw' => true,
                'createDepositAddress' => true,
                'fetchDepositAddress' => true,
            ),
            'urls' => array (
                'logo' => 'https://user-images.githubusercontent.com/1294454/31784029-0313c702-b509-11e7-9ccc-bc0da6a0e435.jpg',
                'api' => array (
                    'public' => 'https://data.gate.io/api',
                    'private' => 'https://data.gate.io/api',
                ),
                'www' => 'https://gate.io/',
                'doc' => 'https://gate.io/api2',
                'fees' => array (
                    'https://gate.io/fee',
                    'https://support.gate.io/hc/en-us/articles/115003577673',
                ),
            ),
            'api' => array (
                'public' => array (
                    'get' => array (
                        'pairs',
                        'marketinfo',
                        'marketlist',
                        'tickers',
                        'ticker/{id}',
                        'orderBook/{id}',
                        'trade/{id}',
                        'tradeHistory/{id}',
                        'tradeHistory/{id}/{tid}',
                    ),
                ),
                'private' => array (
                    'post' => array (
                        'balances',
                        'depositAddress',
                        'newAddress',
                        'depositsWithdrawals',
                        'buy',
                        'sell',
                        'cancelOrder',
                        'cancelAllOrders',
                        'getOrder',
                        'openOrders',
                        'tradeHistory',
                        'withdraw',
                    ),
                ),
            ),
            'fees' => array (
                'trading' => array (
                    'tierBased' => true,
                    'percentage' => true,
                    'maker' => 0.002,
                    'taker' => 0.002,
                ),
            ),
        ));
    }

    public function fetch_markets () {
        $response = $this->publicGetMarketinfo ();
        $markets = $this->safe_value($response, 'pairs');
        if (!$markets)
            throw new ExchangeError ($this->id . ' fetchMarkets got an unrecognized response');
        $result = array ();
        for ($i = 0; $i < count ($markets); $i++) {
            $market = $markets[$i];
            $keys = is_array ($market) ? array_keys ($market) : array ();
            $id = $keys[0];
            $details = $market[$id];
            list ($base, $quote) = explode ('_', $id);
            $base = strtoupper ($base);
            $quote = strtoupper ($quote);
            $base = $this->common_currency_code($base);
            $quote = $this->common_currency_code($quote);
            $symbol = $base . '/' . $quote;
            $precision = array (
                'amount' => $details['decimal_places'],
                'price' => $details['decimal_places'],
            );
            $amountLimits = array (
                'min' => $details['min_amount'],
                'max' => null,
            );
            $priceLimits = array (
                'min' => null,
                'max' => null,
            );
            $limits = array (
                'amount' => $amountLimits,
                'price' => $priceLimits,
            );
            $result[] = array (
                'id' => $id,
                'symbol' => $symbol,
                'base' => $base,
                'quote' => $quote,
                'info' => $market,
                'maker' => $details['fee'] / 100,
                'taker' => $details['fee'] / 100,
                'precision' => $precision,
                'limits' => $limits,
            );
        }
        return $result;
    }

    public function fetch_balance ($params = array ()) {
        $this->load_markets();
        $balance = $this->privatePostBalances ();
        $result = array ( 'info' => $balance );
        $currencies = is_array ($this->currencies) ? array_keys ($this->currencies) : array ();
        for ($i = 0; $i < count ($currencies); $i++) {
            $currency = $currencies[$i];
            $code = $this->common_currency_code($currency);
            $account = $this->account ();
            if (is_array ($balance) && array_key_exists ('available', $balance)) {
                if (is_array ($balance['available']) && array_key_exists ($currency, $balance['available'])) {
                    $account['free'] = floatval ($balance['available'][$currency]);
                }
            }
            if (is_array ($balance) && array_key_exists ('locked', $balance)) {
                if (is_array ($balance['locked']) && array_key_exists ($currency, $balance['locked'])) {
                    $account['used'] = floatval ($balance['locked'][$currency]);
                }
            }
            $account['total'] = $this->sum ($account['free'], $account['used']);
            $result[$code] = $account;
        }
        return $this->parse_balance($result);
    }

    public function fetch_order_book ($symbol, $limit = null, $params = array ()) {
        $this->load_markets();
        $orderbook = $this->publicGetOrderBookId (array_merge (array (
            'id' => $this->market_id($symbol),
        ), $params));
        return $this->parse_order_book($orderbook);
    }

    public function parse_ticker ($ticker, $market = null) {
        $timestamp = $this->milliseconds ();
        $symbol = null;
        if ($market)
            $symbol = $market['symbol'];
        $last = floatval ($ticker['last']);
        return array (
            'symbol' => $symbol,
            'timestamp' => $timestamp,
            'datetime' => $this->iso8601 ($timestamp),
            'high' => floatval ($ticker['high24hr']),
            'low' => floatval ($ticker['low24hr']),
            'bid' => floatval ($ticker['highestBid']),
            'bidVolume' => null,
            'ask' => floatval ($ticker['lowestAsk']),
            'askVolume' => null,
            'vwap' => null,
            'open' => null,
            'close' => $last,
            'last' => $last,
            'previousClose' => null,
            'change' => floatval ($ticker['percentChange']),
            'percentage' => null,
            'average' => null,
            'baseVolume' => floatval ($ticker['quoteVolume']),
            'quoteVolume' => floatval ($ticker['baseVolume']),
            'info' => $ticker,
        );
    }

    public function fetch_tickers ($symbols = null, $params = array ()) {
        $this->load_markets();
        $tickers = $this->publicGetTickers ($params);
        $result = array ();
        $ids = is_array ($tickers) ? array_keys ($tickers) : array ();
        for ($i = 0; $i < count ($ids); $i++) {
            $id = $ids[$i];
            list ($baseId, $quoteId) = explode ('_', $id);
            $base = strtoupper ($baseId);
            $quote = strtoupper ($quoteId);
            $base = $this->common_currency_code($base);
            $quote = $this->common_currency_code($quote);
            $symbol = $base . '/' . $quote;
            $ticker = $tickers[$id];
            $market = null;
            if (is_array ($this->markets) && array_key_exists ($symbol, $this->markets))
                $market = $this->markets[$symbol];
            if (is_array ($this->markets_by_id) && array_key_exists ($id, $this->markets_by_id))
                $market = $this->markets_by_id[$id];
            $result[$symbol] = $this->parse_ticker($ticker, $market);
        }
        return $result;
    }

    public function fetch_ticker ($symbol, $params = array ()) {
        $this->load_markets();
        $market = $this->market ($symbol);
        $ticker = $this->publicGetTickerId (array_merge (array (
            'id' => $market['id'],
        ), $params));
        return $this->parse_ticker($ticker, $market);
    }

    public function parse_trade ($trade, $market) {
        // exchange reports local time (UTC+8)
        $timestamp = $this->parse8601 ($trade['date']) - 8 * 60 * 60 * 1000;
        return array (
            'id' => $trade['tradeID'],
            'info' => $trade,
            'timestamp' => $timestamp,
            'datetime' => $this->iso8601 ($timestamp),
            'symbol' => $market['symbol'],
            'type' => null,
            'side' => $trade['type'],
            'price' => floatval ($trade['rate']),
            'amount' => $this->safe_float($trade, 'amount'),
        );
    }

    public function fetch_trades ($symbol, $since = null, $limit = null, $params = array ()) {
        $this->load_markets();
        $market = $this->market ($symbol);
        $response = $this->publicGetTradeHistoryId (array_merge (array (
            'id' => $market['id'],
        ), $params));
        return $this->parse_trades($response['data'], $market, $since, $limit);
    }

    public function create_order ($symbol, $type, $side, $amount, $price = null, $params = array ()) {
        if ($type === 'market')
            throw new ExchangeError ($this->id . ' allows limit orders only');
        $this->load_markets();
        $method = 'privatePost' . $this->capitalize ($side);
        $order = array (
            'currencyPair' => $this->market_id($symbol),
            'rate' => $price,
            'amount' => $amount,
        );
        $response = $this->$method (array_merge ($order, $params));
        return array (
            'info' => $response,
            'id' => $response['orderNumber'],
        );
    }

    public function cancel_order ($id, $symbol = null, $params = array ()) {
        $this->load_markets();
        return $this->privatePostCancelOrder (array ( 'orderNumber' => $id ));
    }

    public function query_deposit_address ($method, $currency, $params = array ()) {
        $method = 'privatePost' . $method . 'Address';
        $response = $this->$method (array_merge (array (
            'currency' => $currency,
        ), $params));
        $address = null;
        if (is_array ($response) && array_key_exists ('addr', $response))
            $address = $this->safe_string($response, 'addr');
        if (($address !== null) && (mb_strpos ($address, 'address') !== false))
            throw new InvalidAddress ($this->id . ' queryDepositAddress ' . $address);
        return array (
            'currency' => $currency,
            'address' => $address,
            'status' => ($address !== null) ? 'ok' : 'none',
            'info' => $response,
        );
    }

    public function create_deposit_address ($currency, $params = array ()) {
        return $this->query_deposit_address ('New', $currency, $params);
    }

    public function fetch_deposit_address ($currency, $params = array ()) {
        return $this->query_deposit_address ('Deposit', $currency, $params);
    }

    public function withdraw ($currency, $amount, $address, $tag = null, $params = array ()) {
        $this->check_address($address);
        $this->load_markets();
        $response = $this->privatePostWithdraw (array_merge (array (
            'currency' => strtolower ($currency),
            'amount' => $amount,
            'address' => $address, // Address must exist in you AddressBook in security settings
        ), $params));
        return array (
            'info' => $response,
            'id' => null,
        );
    }

    public function sign ($path, $api = 'public', $method = 'GET', $params = array (), $headers = null, $body = null) {
        $prefix = ($api === 'private') ? ($api . '/') : '';
        $url = $this->urls['api'][$api] . $this->version . '/1/' . $prefix . $this->implode_params($path, $params);
        $query = $this->omit ($params, $this->extract_params($path));
        if ($api === 'public') {
            if ($query)
                $url .= '?' . $this->urlencode ($query);
        } else {
            $this->check_required_credentials();
            $nonce = $this->nonce ();
            $request = array ( 'nonce' => $nonce );
            $body = $this->urlencode (array_merge ($request, $query));
            $signature = $this->hmac ($this->encode ($body), $this->encode ($this->secret), 'sha512');
            $headers = array (
                'Key' => $this->apiKey,
                'Sign' => $signature,
                'Content-Type' => 'application/x-www-form-urlencoded',
            );
        }
        return array ( 'url' => $url, 'method' => $method, 'body' => $body, 'headers' => $headers );
    }

    public function request ($path, $api = 'public', $method = 'GET', $params = array (), $headers = null, $body = null) {
        $response = $this->fetch2 ($path, $api, $method, $params, $headers, $body);
        if (is_array ($response) && array_key_exists ('result', $response)) {
            $result = $response['result'];
            $message = $this->id . ' ' . $this->json ($response);
            if ($result === null)
                throw new ExchangeError ($message);
            if (gettype ($result) == 'string') {
                if ($result !== 'true')
                    throw new ExchangeError ($message);
            } else if (!$result) {
                throw new ExchangeError ($message);
            }
        }
        return $response;
    }
}
