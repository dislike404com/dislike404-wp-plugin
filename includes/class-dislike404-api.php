<?php

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Handles all HTTP communication with the dislike404.com REST API.
 */
class Dislike404_API
{

    /** @var string */
    private string $api_token;

    public function __construct(string $api_token)
    {
        $this->api_token = $api_token;
    }

    /**
     * Fetch all URLs registered for the authenticated user.
     *
     * @return array{success: bool, data?: array, message?: string}
     */
    public function get_urls(): array
    {
        $response = wp_remote_get(
            DISLIKE404_API_BASE . '/urls',
            $this->build_request_args()
        );

        return $this->parse_response($response);
    }

    /**
     * Trigger a manual scan for the given URL UUID.
     *
     * @param  string $uuid  The UUID of the URL to scan.
     * @return array{success: bool, message?: string}
     */
    public function trigger_scan(string $uuid): array
    {
        $response = wp_remote_post(
            DISLIKE404_API_BASE . '/urls/' . rawurlencode($uuid) . '/scan',
            $this->build_request_args()
        );

        return $this->parse_response($response);
    }

    /**
     * Build the common request arguments array.
     */
    private function build_request_args(array $extra = []): array
    {
        return array_merge([
            'headers' => [
                'Authorization' => 'Bearer ' . $this->api_token,
                'Accept'        => 'application/json',
                'Content-Type'  => 'application/json',
            ],
            'timeout' => 15,
        ], $extra);
    }

    /**
     * Poll the current scan status for the given URL UUID.
     *
     * @param  string $uuid
     * @return array{success: bool, data?: array, message?: string}
     */
    public function get_scan_status(string $uuid): array
    {
        $response = wp_remote_get(
            DISLIKE404_API_BASE . '/urls/' . rawurlencode($uuid) . '/status',
            $this->build_request_args()
        );

        return $this->parse_response($response);
    }

    /**
     * Parse a WP_Error or HTTP response into a unified array.
     *
     * @param  \WP_Error|array $response
     * @return array{success: bool, data?: array, message?: string}
     */
    private function parse_response($response): array
    {
        if (is_wp_error($response)) {
            return [
                'success' => false,
                'message' => $response->get_error_message(),
            ];
        }

        $status_code = (int) wp_remote_retrieve_response_code($response);
        $body        = wp_remote_retrieve_body($response);
        $decoded     = json_decode($body, true);

        if ($status_code >= 200 && $status_code < 300) {
            $data = $decoded['data'] ?? $decoded ?? [];

            return [
                'success' => true,
                'data'    => $data,
                'message' => $decoded['message'] ?? '',
            ];
        }

        return [
            'success'     => false,
            'status_code' => $status_code,
            'message'     => $decoded['message'] ?? sprintf('HTTP error %d', $status_code),
        ];
    }
}
