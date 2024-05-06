<?php

namespace Bl\FatooraZatca\Actions;

use Bl\FatooraZatca\Helpers\ConfigHelper;

class PostRequestAction
{
    /**
     * handle sending the request to zatca portal.
     *
     * @param  string   $route
     * @param  array    $data
     * @param  array    $headers
     * @param  string   $USERPWD
     * @param  string   $method
     * @return array
     */
    public function handle(string $route, array $data, array $headers, string $USERPWD, string $method = 'POST'): array
    {
        $portal = ConfigHelper::portal();

        $ch     = curl_init($portal . $route);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        if(strlen($USERPWD)) {
            curl_setopt($ch, CURLOPT_USERPWD,  $USERPWD);
        }

        // Set the HTTP request method to PATCH
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        // execute!
        $response = curl_exec($ch);

        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        $response = json_decode($response,true);

        // close the connection, release resources used
        curl_close($ch);

        return (new HandleResponseAction)->handle($httpcode, $response);
    }
}
