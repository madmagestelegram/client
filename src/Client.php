<?php

declare(strict_types=1);

namespace MadmagesTelegram\Client;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ClientException;
use InvalidArgumentException;
use MadmagesTelegram\Client\Exception\ApiException;
use MadmagesTelegram\Client\Exception\ResponseException;
use MadmagesTelegram\Types\TypedClient;
use Throwable;

use function GuzzleHttp\json_decode as jsonDecode;
use function GuzzleHttp\json_encode as jsonEncode;

class Client extends TypedClient
{

    private const API_URL = 'https://api.telegram.org/';

    /** @var string */
    private $apiEndpoint;
    /** @var GuzzleClient */
    private $guzzle;
    /** @var string */
    private $token;

    public function __construct(string $token, GuzzleClient $guzzle = null)
    {
        $this->apiEndpoint = self::API_URL . "bot{$token}/";
        $this->guzzle = $guzzle ?? new GuzzleClient();
        $this->token = $token;
    }

    /**
     * Validate login widget data
     *
     * @param array $data
     * @return bool
     * @throws ApiException
     */
    public function validateLoginData(array $data): bool
    {
        if (!isset($data['hash'])) {
            throw new ApiException('Hash key not found');
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
     * @param bool $withFiles
     * @return string Returned json string
     * @throws Throwable
     */
    public function _rawApiCall(string $method, array $parameters, bool $withFiles = false): string
    {
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
                $decoded = jsonDecode($responseBody, true);
            } catch (InvalidArgumentException $ex) {
                throw $exception;
            }

            throw ExceptionMaker::make($decoded, $exception);
        }

        $resultContent = $result->getBody()->getContents();
        if (empty($resultContent)) {
            throw new ResponseException('HTTP Empty response');
        }

        try {
            $result = jsonDecode($resultContent, true);
        } catch (InvalidArgumentException $exception) {
            throw new ResponseException($exception->getMessage() . "\n" . print_r($result, true), $exception->getCode(), $exception);
        }

        if (!isset($result['ok'], $result['result'])) {
            throw new ResponseException('Unexpected response: ' . print_r($result, true));
        }

        try {
            return jsonEncode($result['result']);
        } catch (InvalidArgumentException $exception) {
            throw new ResponseException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }
}