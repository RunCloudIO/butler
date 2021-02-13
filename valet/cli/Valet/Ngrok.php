<?php

namespace Valet;

use Httpful\Request;
use DomainException;

class Ngrok
{
    var $tunnelsEndpoints = [
        'http://127.0.0.1:4040/api/tunnels',
        'http://127.0.0.1:4041/api/tunnels',
    ];

    /**
     * Get the current tunnel URL from the Ngrok API.
     *
     * @return string
     */
    function currentTunnelUrl($domain = null)
    {
        // wait a second for ngrok to start before attempting to find available tunnels
        sleep(1);

        foreach ($this->tunnelsEndpoints as $endpoint) {
            $response = retry(20, function () use ($endpoint, $domain) {
                $body = Request::get($endpoint)->send()->body;

                if (isset($body->tunnels) && count($body->tunnels) > 0) {
                    return $this->findHttpTunnelUrl($body->tunnels, $domain);
                }
            }, 250);

            if (!empty($response)) {
                return $response;
            }
        }

        throw new DomainException("Tunnel not established.");
    }

    /**
     * Find the HTTP tunnel URL from the list of tunnels.
     *
     * @param  array  $tunnels
     * @return string|null
     */
    function findHttpTunnelUrl($tunnels, $domain)
    {
        // If there are active tunnels on the Ngrok instance we will spin through them and
        // find the one responding on HTTP. Each tunnel has an HTTP and a HTTPS address
        // but for local dev purposes we just desire the plain HTTP URL endpoint.
        foreach ($tunnels as $tunnel) {
            if ($tunnel->proto === 'http' && strpos($tunnel->config->addr, $domain) ) {
                return $tunnel->public_url;
            }
        }
    }
}
