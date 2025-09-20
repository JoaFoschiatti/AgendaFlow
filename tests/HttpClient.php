<?php

class HttpClient
{
    private string $baseUrl;
    private string $cookieFile;

    public function __construct(string $baseUrl)
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->cookieFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'agendaflow_cookies_' . getmypid() . '.txt';
        if (file_exists($this->cookieFile)) {
            @unlink($this->cookieFile);
        }
    }

    public function get(string $path, array $headers = []): array
    {
        return $this->request('GET', $path, null, $headers);
    }

    public function post(string $path, $data = null, array $headers = []): array
    {
        return $this->request('POST', $path, $data, $headers);
    }

    private function request(string $method, string $path, $data = null, array $headers = []): array
    {
        $url = $this->baseUrl . '/' . ltrim($path, '/');
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookieFile);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookieFile);
        curl_setopt($ch, CURLOPT_HEADER, true);

        $httpHeaders = [];
        foreach ($headers as $k => $v) {
            $httpHeaders[] = $k . ': ' . $v;
        }
        if (!empty($httpHeaders)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $httpHeaders);
        }

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if (is_array($data)) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
            } elseif (is_string($data)) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            }
        }

        $raw = curl_exec($ch);
        if ($raw === false) {
            $err = curl_error($ch);
            curl_close($ch);
            throw new \RuntimeException('HTTP request error: ' . $err);
        }

        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $headersRaw = substr($raw, 0, $headerSize);
        $body = substr($raw, $headerSize);
        curl_close($ch);

        return [
            'status' => $status,
            'headers' => $headersRaw,
            'body' => $body,
        ];
    }
}

