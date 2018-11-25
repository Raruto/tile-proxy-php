# Tile Proxy PHP: a simple caching-Proxy for WMTS Servers

This software should make it simple to use pre-rendered map tiles in your own projects, without flooding too many tiles-servers.

## Requirements:

- **Apache** webserver (with **_mod_rewrite_** / **_.htaccess_** support / **_php_curl_** extension)
- **PHP 5.3+**

## Installation:

Download project files and upload it into your web hosting of your choice.

Open the **"test.html"** file within your browser, your proxy server should display you a simple slippy maps rendering default Open Street Map tiles.
Or test directly  in your browser with a tile URL such as: http://www.example.com/tiles/15/17024/10792.png

**NB.** Remeber to check your web server folder permissions: **777** or **755**

---

> _Initally based on the [work](https://wiki.openstreetmap.org/wiki/ProxySimplePHP) of **Lizard**_

---

## How to use

1. **edit the default "config.php"**
    ```php
    /**
     * Whitelist of supported tile servers
     *
     * @link https://wiki.openstreetmap.org/wiki/Tile_servers
     *
     * @var array
     */
    $config['servers'] = array(
      'osm'  => 'https://{switch:a,b,c}.tile.openstreetmap.org/{z}/{x}/{y}.png',
      'otm'  => 'https://{switch:a,b,c}.tile.opentopomap.org/{z}/{x}/{y}.png',
      // ADD: more services here.
    );

    /**
     * Cached Bounding Box
     *
     * BBox = left, bottom, right, top
     * BBox = min_lng, min_lat, max_lng, max_lat
     *
     * Planet: bbox = '-180,-90,180,90'
     *
     * @link https://openmaptiles.com/extracts/
     *
     * @var string
     */
    // CHANGE: bounding box cache-area to fit your own needs
    $config['bbox'] = '6.602696,35.07638,19.12499,47.10169'; // CHANGE: bbox tiles (Italy) are cached, others are proxied!

    /**
     * Cache timeout in seconds
     *
     * 12 hour = 43200 sec
     * 1 day   = 86400 sec
     * 1 month = 2629800 sec
     *
     * @var int
     */
    $config['ttl'] = 86400;

    /**
     * Custom Proxy Server headers
     *
     * @var string
     */
    $config['headers'] = array(
      'Access-Control-Allow-Origin:' => '*',
    );
    ```

2. **create your first simple “tile-proxy-php” slippy map**

    a. **_include CSS & JavaScript_**
    ```html
    <head>
    ...
    <style>html, body, #map { width: 100%; height: 100%; margin: 0; padding: 0; }</style>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.3.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.3.4/dist/leaflet.js"></script>
    ...
    </head>
    ```

    a. **_choose the div container used for the slippy map_**
    ```html
    <body>
    ...
    <div id="map"></div>
    ...
    </body>
    ```
    b. **_create the slippy map_**
    ```html
    <script>
      var map = L.map('map').setView([0, 0], 0);

      var proxy_url = 'http://example.com/tiles/{id}/{z}/{x}/{y}.png'; // CHANGE: "http://example.com/tiles" to fit your own needs
      var tms_id = 'otm';

      var tileLayer = L.tileLayer(proxy_url, {
        attribution: 'map data: &copy; <a href="https://openstreetmap.org/copyright">OpenStreetMap</a> contributors, ' +
          '<a href="http://viewfinderpanoramas.org">SRTM</a> | ' +
          'map style: © <a href="https://opentopomap.org">OpenTopoMap</a> ' +
          '(<a href="https://creativecommons.org/licenses/by-sa/3.0/">CC-BY-SA</a>), ',
        id: tms_id
      });
      tileLayer.addTo(map);
    </script>
    ```
---

**Compatibile with:** php@5.38, curl@7.61

---

**Contributors:** [Lizard](https://wiki.openstreetmap.org/wiki/User:Lizard), [Raruto](https://github.com/Raruto/tile-proxy-php)
