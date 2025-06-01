<?php

return [
    /*
    |--------------------------------------------------------------------------
    | VPN Server Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration settings for the VPN server.
    |
    */

    // Maximum number of concurrent VPN connections per user
    'max_connections' => env('VPN_MAX_CONNECTIONS', 1),

    // VPN server address (domain or IP)
    'server_address' => env('VPN_SERVER_ADDRESS', 'vpn.pubwifi.com'),

    // VPN server port
    'port' => env('VPN_PORT', 51820),

    // VPN subnet (CIDR notation)
    'subnet' => env('VPN_SUBNET', '10.0.0.0/24'),

    // DNS servers to use for VPN clients
    'dns_servers' => [
        '1.1.1.1',
        '1.0.0.1',
    ],

    // Enable VPN connection logging
    'logging_enabled' => env('VPN_LOGGING_ENABLED', true),

    // Logging channel to use
    'logging_channel' => env('VPN_LOGGING_CHANNEL', 'daily'),

    // WireGuard server configuration path
    'wireguard_config_path' => env('WIREGUARD_CONFIG_PATH', '/etc/wireguard/'),

    // WireGuard client configuration storage path
    'wireguard_client_config_path' => env('WIREGUARD_CLIENT_CONFIG_PATH', 'wireguard/clients/'),
]; 

return [
    /*
    |--------------------------------------------------------------------------
    | VPN Server Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration settings for the VPN server.
    |
    */

    // Maximum number of concurrent VPN connections per user
    'max_connections' => env('VPN_MAX_CONNECTIONS', 1),

    // VPN server address (domain or IP)
    'server_address' => env('VPN_SERVER_ADDRESS', 'vpn.pubwifi.com'),

    // VPN server port
    'port' => env('VPN_PORT', 51820),

    // VPN subnet (CIDR notation)
    'subnet' => env('VPN_SUBNET', '10.0.0.0/24'),

    // DNS servers to use for VPN clients
    'dns_servers' => [
        '1.1.1.1',
        '1.0.0.1',
    ],

    // Enable VPN connection logging
    'logging_enabled' => env('VPN_LOGGING_ENABLED', true),

    // Logging channel to use
    'logging_channel' => env('VPN_LOGGING_CHANNEL', 'daily'),

    // WireGuard server configuration path
    'wireguard_config_path' => env('WIREGUARD_CONFIG_PATH', '/etc/wireguard/'),

    // WireGuard client configuration storage path
    'wireguard_client_config_path' => env('WIREGUARD_CLIENT_CONFIG_PATH', 'wireguard/clients/'),
]; 