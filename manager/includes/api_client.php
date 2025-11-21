<?php
class ApiClient {
    private $baseUrl;
    private $timeout;

    public function __construct($baseUrl, $timeout = 30) {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->timeout = $timeout;
    }

    public function request($method, $endpoint, $data = null) {
        $url = $this->baseUrl . $endpoint;
        
        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json'
        ]);

        if ($data && in_array($method, ['POST', 'PUT', 'PATCH'])) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        curl_close($ch);

        if ($error) {
            return ['error' => true, 'message' => 'CURL Error: ' . $error];
        }

        $decodedResponse = json_decode($response, true) ?? $response;

        return [
            'success' => $httpCode >= 200 && $httpCode < 300,
            'status' => $httpCode,
            'data' => $decodedResponse,
            'error' => $httpCode >= 400 ? $decodedResponse : null
        ];
    }

    public function get($endpoint) {
        return $this->request('GET', $endpoint);
    }

    public function post($endpoint, $data) {
        return $this->request('POST', $endpoint, $data);
    }

    public function put($endpoint, $data) {
        return $this->request('PUT', $endpoint, $data);
    }

    public function delete($endpoint) {
        return $this->request('DELETE', $endpoint);
    }
}

// Initialize API Client
$apiClient = new ApiClient(API_BASE_URL, API_TIMEOUT);
?>