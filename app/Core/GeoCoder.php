<?php

namespace App\Core;

class GeoCoder
{
    public static function geocodeAddress($address)
    {
        $address = trim((string) $address);
        if ($address === '') {
            return null;
        }

        $url = 'https://nominatim.openstreetmap.org/search?format=json&limit=1&q=' . rawurlencode($address);
        $context = stream_context_create(array(
            'http' => array(
                'method' => 'GET',
                'header' => "User-Agent: GeoMapCRM/1.0\r\nAccept: application/json\r\n",
                'timeout' => 10,
            ),
        ));

        $json = @file_get_contents($url, false, $context);
        if ($json === false) {
            return null;
        }

        $decoded = json_decode($json, true);
        if (!is_array($decoded) || empty($decoded[0]['lat']) || empty($decoded[0]['lon'])) {
            return null;
        }

        return array(
            'lat' => (float) $decoded[0]['lat'],
            'lng' => (float) $decoded[0]['lon'],
        );
    }
}
