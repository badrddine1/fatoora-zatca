<?php

namespace Bl\FatooraZatca\Actions;

use Exception;

class HandleResponseAction
{
    /**
     * handle the response of zatca portal.
     *
     * @param  mixed $httpcode
     * @param  mixed $response
     * @return array
     */
    public function handle($httpcode, $response): array
    {
        if((int) $httpcode === 200) {

            return $response;

        }
        else if((int) $httpcode === 401) {

            throw new Exception('Unauthoroized zatca settings!');

        }
        else {
            if(! empty($response)) {
                throw new Exception(json_encode($response));
            }
            else {
                throw new Exception('Unhandeled zatca error exception!');
            }
        }
    }
}
