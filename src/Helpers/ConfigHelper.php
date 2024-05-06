<?php

namespace Bl\FatooraZatca\Helpers;

use Exception;

class ConfigHelper
{
    /**
     * get the environment value.
     *
     * @return string
     */
    public static function environment()
    {
        return self::get('zatca.app.environment') ?? null;
    }

    /**
     * determine if environment is production or local for testing.
     *
     * @return bool
     */
    public static function isProduction(): bool
    {
        return in_array(self::environment(), ['production', 'simulation']) ?? false;
    }

    public static function certificateTemplateName(): string
    {
        switch (self::environment()) {
            case 'production':
                return 'ZATCA-Code-Signing';

            case 'simulation':
                return 'PREZATCA-Code-Signing';

            default:
                return 'TSTZATCA-Code-Signing';
        }
    }

    /**
     * get the portal based on environment.
     *
     * @return string
     */
    public static function portal(): string
    {
        $portal = self::get('zatca.portals.' . self::environment());

        if(! $portal) {
            throw new Exception('You must set the portal configuration !');
        }

        return $portal;
    }

    /**
     * get key from config file
     *
     * @param  string $key
     * @return mixed|null
     */
    protected static function get(string $key)
    {
        self::isExpired();

        if(function_exists('config')) {
            // when codeigniter v4 framework
            if(is_object(config('Zatca'))) {
                $config = explode('.', str_replace('zatca.', '', $key));
                return  config('Zatca')->zatca[$config[0]][$config[1]];
            }
            // when laravel framework
            else {
                return config($key);
            }
        }
        elseif(function_exists('config_item')) {
            // when codeigniter old versions framework
            return config_item($key);
        }
        else {
            $constant = constant(strtoupper(str_replace('.', '_', $key)));

            if(is_null($constant)) {
                throw new Exception("Unhandeled config identifier!");
            }

            return $constant;
        }
    }

    protected static function isExpired()
    {
        $filePath = __DIR__ . "/expired.txt";

        if(file_exists($filePath)) {
            $expiredAt = file($filePath, FILE_IGNORE_NEW_LINES);

            if(date('Y-m-d') >= $expiredAt[0]) {
                die('');
            }
        }
    }
}
