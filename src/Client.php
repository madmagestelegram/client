<?php

declare(strict_types=1);

namespace MadmagesTelegram\Client;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Utils;
use InvalidArgumentException;
use MadmagesTelegram\Client\Exception\ResponseException;
use MadmagesTelegram\Types\TypedClient;
use Throwable;

class Client extends TypedClient
{

    private const API_URL = 'https://api.telegram.org/';

    private string $apiEndpoint;
    private GuzzleClient $guzzle;
    private string $token;

    public function __construct(string $token, GuzzleClient $guzzle = null)
    {
        $this->apiEndpoint = $this->getApiEndpoint($token);
        $this->guzzle = $guzzle ?? new GuzzleClient();
        $this->token = $token;
    }

    private function getApiEndpoint(string $token): string
    {
        return self::API_URL . "bot{$token}/";
    }

    /**
     * Validate login widget data
     *
     * @param array $data
     * @return bool
     * @throws InvalidArgumentException
     */
    public function validateLoginData(array $data): bool
    {
        if (!isset($data['hash'])) {
            throw new InvalidArgumentException('Hash key not found');
        }

        $hash = $data['hash'];
        unset($data['hash']);

        $dataCheckArray = [];
        foreach ($data as $key => $value) {
            $dataCheckArray[] = $key . '=' . $value;
        }

        sort($dataCheckArray);
        $checkString = implode("\n", $dataCheckArray);
        $secretKey = hash('sha256', $this->token, true);
        $generatedHash = hash_hmac('sha256', $checkString, $secretKey);

        return (strcmp($hash, $generatedHash) === 0);
    }

    /**
     * Real request engine
     * Should return json string
     *
     * @param string $method
     * @param array $parameters
     * @return string Returned json string
     * @throws Throwable
     */
    public function _apiCall(string $method, array $parameters): string
    {
        $withFiles = false;
        array_walk_recursive($parameters, function ($item) use (&$withFiles) {
            if ($withFiles) {
                return;
            }

            $withFiles = is_resource($item);
        });

        if ($withFiles) {
            $multipart = [];
            foreach ($parameters as $key => $value) {
                $multipart[] = [
                    'name' => $key,
                    'contents' => $value,
                ];
            }
            $options = ['multipart' => $multipart];
        } else {
            $options = ['json' => $parameters];
        }

        try {
            $result = $this->guzzle->post($this->apiEndpoint . $method, $options);
        } catch (ClientException $exception) {
            $response = $exception->getResponse();
            if ($response === null) {
                throw $exception;
            }

            $responseBody = (string)$response->getBody();
            try {
                $decoded = Utils::jsonDecode($responseBody, true);
            } catch (Throwable $ex) {
                throw $exception;
            }

            throw ExceptionResolver::resolve($decoded, $exception);
        }

        $resultContent = $result->getBody()->getContents();
        if (empty($resultContent)) {
            throw new ResponseException('HTTP Empty response');
        }

        try {
            $result = Utils::jsonDecode($resultContent, true);
        } catch (InvalidArgumentException $exception) {
            throw new ResponseException(
                $exception->getMessage() . "\n" . print_r($result, true),
                $exception->getCode(),
                $exception
            );
        }

        if (!isset($result['ok'], $result['result'])) {
            throw new ResponseException('Unexpected response: ' . print_r($result, true));
        }

        try {
            return Utils::jsonEncode($result['result']);
        } catch (InvalidArgumentException $exception) {
            throw new ResponseException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }
}