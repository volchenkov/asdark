<?php

namespace App\Vk;

use App\Connection;
use \GuzzleHttp\Client;

/**
 *
 */
class ApiClient
{

    const VERSION = '5.103';

    private ?string $clientId = null;
    private Connection $connection;

    public function setClientId(?int $clientId): self
    {
        $this->clientId = $clientId;

        return $this;
    }

    public function getClientId(): ?int
    {
        return $this->clientId;
    }

    public function getConnection(): Connection
    {
        if (!isset($this->connection)) {
            $this->connection = Connection::where('system', 'vk')->firstOrFail();
        }

        return $this->connection;
    }

    public function getClients(): ?array
    {
        if (!$this->getConnection()->isAgency()) {
            return null;
        }

        return $this->get('ads.getClients');
    }

    /**
     * @param string $code
     * @param null $captcha
     * @param null $captchaKey
     * @return mixed
     * @throws CaptchaException
     */
    public function execute(string $code, $captcha = null, $captchaKey = null)
    {
        $payload = ['code' => $code];

        if ($captcha && $captchaKey) {
            $payload['captcha_sid'] = $this->fetchCaptchaSid($captcha);
            $payload['captcha_key'] = $captchaKey;
        }

        return $this->post('execute', $payload);
    }

    public function get(string $method, array $queryParams = [])
    {
        $rsp = $this->api()->get($method, ['query' => $this->addDefaultParams($queryParams)]);

        $data = \json_decode($rsp->getBody()->getContents(), true);

        if (is_null($data)) {
            throw new \RuntimeException("No response data: {$method} " . (string)$rsp->getBody());
        }

        if (isset($data['error']) && is_array($data['error'])) {
            $error = $data['error'];

            if (isset($error['error_code']) && $error['error_code'] == 9) {
                throw new FloodControlException($error['error_msg'] ?? 'Unexpected error');
            }

            throw new ErrorResponseException($error['error_msg'] ?? 'Unexpected error');
        }

        if (!isset($data['response'])) {
            throw new \RuntimeException("Failed to decode response: {$method}" . (string)$rsp->getBody());
        }

        sleep(1);

        return $data['response'];
    }

    /**
     * @param string $method
     * @param array $body
     * @param array $queryParams
     * @return mixed
     * @throws CaptchaException
     */
    private function post(string $method, array $body = [], array $queryParams = [])
    {
        $rsp = $this->api()->post($method, ['form_params' => $body, 'query' => $this->addDefaultParams($queryParams)]);
        $data = \json_decode($rsp->getBody()->getContents(), true);

        if (!is_array($data)) {
            throw new \RuntimeException("Failed to decode response: {$method} - " . (string)$rsp->getBody());
        }
        if (isset($data['error']) && $err = $data['error']) {
            $msg = $err['error_msg'] ?? null;
            if ($msg == 'Captcha needed') {
                $e = new CaptchaException('Captcha needed');
                $e->sid = $err['captcha_sid'];
                $e->img = $err['captcha_img'];

                throw $e;
            }
        }
        if (!array_key_exists('response', $data)) {
            throw new \RuntimeException("Unexpected response: {$method} " . (string)$rsp->getBody());
        }

        return $data['response'];
    }

    private function addDefaultParams(array $params): array
    {
        $conn = $this->getConnection();
        $defaults = [
            'access_token' => $conn->data['access_token'],
            'v'            => self::VERSION
        ];

        if (isset($conn->data['account_id'])) {
            $defaults['account_id'] = $conn->data['account_id'];
        }

        if ($this->clientId) {
            $defaults['client_id'] = $this->clientId;
        }

        return array_replace($defaults, $params);
    }

    private function fetchCaptchaSid(string $captcha): ?string
    {
        if ($captcha && strpos($captcha, 'sid=') !== false) {
            $query = [];
            parse_str(parse_url($captcha, PHP_URL_QUERY), $query);

            return $query['sid'];
        }

        return null;
    }

    private function api(): Client
    {
        return new Client([
            'base_uri' => 'https://api.vk.com/method/',
            'timeout'  => 60.0
        ]);
    }

}
