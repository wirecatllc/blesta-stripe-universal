<?php

class MockHttpClient implements \Stripe\HttpClient\ClientInterface
{
    private static $responseQueue = [];
    private static $requestLog = [];

    public static function enqueueResponse($body, $code = 200, $headers = [])
    {
        self::$responseQueue[] = [$body, $code, $headers];
    }

    public static function getLastRequest()
    {
        $last = end(self::$requestLog);
        return $last !== false ? $last : null;
    }

    public static function getAllRequests()
    {
        return self::$requestLog;
    }

    public static function reset()
    {
        self::$responseQueue = [];
        self::$requestLog = [];
    }

    public function request($method, $absUrl, $headers, $params, $hasFile)
    {
        self::$requestLog[] = compact('method', 'absUrl', 'headers', 'params');

        if (!empty(self::$responseQueue)) {
            return array_shift(self::$responseQueue);
        }

        return ['{}', 200, []];
    }
}
