<?php
/**
 * LicenseRadar — Microsoft Graph API Client
 * Client credentials grant, token caching, paginated data fetching.
 */

declare(strict_types=1);

namespace LicenseRadar;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

final class GraphClient
{
    private Client $http;
    private string $tenantId;
    private string $clientId;
    private string $clientSecret;

    public function __construct()
    {
        $this->tenantId     = Config::get('AZURE_TENANT_ID');
        $this->clientId     = Config::get('AZURE_CLIENT_ID');
        $this->clientSecret = Config::get('AZURE_CLIENT_SECRET');

        $this->http = new Client([
            'base_uri' => 'https://graph.microsoft.com/v1.0/',
            'timeout'  => 30,
            'headers'  => [
                'Accept'       => 'application/json',
                'Content-Type' => 'application/json',
            ],
        ]);
    }

    /**
     * Get an access token using client credentials grant.
     * Cached in settings table until expiry.
     */
    public function getAccessToken(): string
    {
        // Check cache
        $cached = Database::fetchOne("SELECT setting_value FROM settings WHERE setting_key = 'graph_token'");
        if ($cached) {
            $data = json_decode($cached['setting_value'], true);
            if ($data && isset($data['token'], $data['expires']) && $data['expires'] > time()) {
                return $data['token'];
            }
        }

        // Request new token
        $tokenUrl = "https://login.microsoftonline.com/{$this->tenantId}/oauth2/v2.0/token";

        try {
            $response = (new Client(['timeout' => 10]))->post($tokenUrl, [
                'form_params' => [
                    'client_id'     => $this->clientId,
                    'client_secret' => $this->clientSecret,
                    'scope'         => 'https://graph.microsoft.com/.default',
                    'grant_type'    => 'client_credentials',
                ],
            ]);

            $body = json_decode($response->getBody()->getContents(), true);
            $token   = $body['access_token'] ?? '';
            $expires = time() + (int) ($body['expires_in'] ?? 3600) - 60; // 1 min buffer

            // Cache token
            $cacheValue = json_encode(['token' => $token, 'expires' => $expires]);
            Database::query(
                "INSERT INTO settings (setting_key, setting_value) VALUES ('graph_token', ?)
                 ON DUPLICATE KEY UPDATE setting_value = ?",
                [$cacheValue, $cacheValue]
            );

            return $token;
        } catch (GuzzleException $e) {
            audit_log('graph_token_error', $e->getMessage());
            throw new \RuntimeException('Failed to obtain Graph API access token.');
        }
    }

    /**
     * Fetch all licensed users with signInActivity (paginated, max 120/page).
     *
     * @return array<int, array<string, mixed>>
     */
    public function getUsers(): array
    {
        $token = $this->getAccessToken();
        $users = [];

        $url = 'users?$select=id,displayName,mail,userPrincipalName,accountEnabled,assignedLicenses,signInActivity&$top=120&$filter=assignedLicenses/$count ne 0&$count=true';

        $headers = [
            'Authorization'   => "Bearer {$token}",
            'ConsistencyLevel' => 'eventual', // Required for $count
        ];

        do {
            try {
                $response = $this->http->get($url, ['headers' => $headers]);
                $body     = json_decode($response->getBody()->getContents(), true);

                if (isset($body['value'])) {
                    foreach ($body['value'] as $user) {
                        $users[] = $user;
                    }
                }

                // Next page
                $url = $body['@odata.nextLink'] ?? null;
                if ($url) {
                    // nextLink is a full URL, so use it directly
                    $this->http = new Client(['timeout' => 30]);
                }
            } catch (GuzzleException $e) {
                audit_log('graph_users_error', $e->getMessage());
                break;
            }
        } while ($url !== null);

        return $users;
    }

    /**
     * Fetch all subscribed SKUs (license plans).
     *
     * @return array<int, array<string, mixed>>
     */
    public function getSubscribedSkus(): array
    {
        $token = $this->getAccessToken();

        try {
            $response = $this->http->get('subscribedSkus', [
                'headers' => ['Authorization' => "Bearer {$token}"],
            ]);

            $body = json_decode($response->getBody()->getContents(), true);
            return $body['value'] ?? [];
        } catch (GuzzleException $e) {
            audit_log('graph_skus_error', $e->getMessage());
            return [];
        }
    }

    /**
     * Test the Graph API connection (used by setup wizard).
     *
     * @return array{success: bool, error: string}
     */
    public function testConnection(): array
    {
        try {
            $this->getAccessToken();
            return ['success' => true, 'error' => ''];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
