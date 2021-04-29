<?php

/*
 * Today is the tomorrow you promised to act yesterday.
 */

namespace Mitoop\Robot\Traits;

use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;

trait HttpRequestTrait
{
    protected function postJson($endpoint, $params = [], $headers = [])
    {
        return $this->request('post', $endpoint, [
            'headers' => $headers,
            'json' => $params,
        ]);
    }

    protected function request($method, $endpoint, $options = [])
    {
        return $this->unwrapResponse($this->getHttpClient($this->getBaseOptions())->{$method}($endpoint, $options));
    }

    protected function getBaseOptions()
    {
        $options = method_exists($this, 'getGuzzleOptions') ? $this->getGuzzleOptions() : [];

        return array_merge($options, [
            'verify' => false,
            'timeout' => method_exists($this, 'getTimeout') ? $this->getTimeout() : 3,
        ]);
    }

    protected function getHttpClient(array $options = [])
    {
        return new Client($options);
    }

    /**
     * @return array|string|mixed
     */
    protected function unwrapResponse(ResponseInterface $response)
    {
        $contentType = $response->getHeaderLine('Content-Type');
        $contents = $response->getBody()->getContents();

        if (false !== stripos($contentType, 'json') || stripos($contentType, 'javascript')) {
            return json_decode($contents, true);
        } elseif (false !== stripos($contentType, 'xml')) {
            return json_decode(json_encode(simplexml_load_string($contents)), true);
        }

        return $contents;
    }
}
