<?php
/**
 * Map Tile PHP Proxy Configuration File
 *
 * @package   tile-proxy-php
 * @author    Raruto <raruto.github.io>
 * @link      https://github.com/Raruto/tile-proxy-php
 * @license   https://www.gnu.org/licenses/gpl-3.0.txt GNU/GPLv3
 */

/**
 * Whitelist of supported tile servers
 *
 * @var array
 */
$tiles_config['servers'] = array(
	'osm'  => 'https://{switch:a,b,c}.tile.openstreetmap.org/{z}/{x}/{y}.png',
	'otm'  => 'https://{switch:a,b,c}.tile.opentopomap.org/{z}/{x}/{y}.png',
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
$tiles_config['bbox'] = '6.602696,35.07638,19.12499,47.10169'; // Italy.

/**
 * Cache timeout in seconds
 *
 * 12 hour = 43200 sec
 * 1 day   = 86400 sec
 * 1 month = 2629800 sec
 *
 * @var int
 */
$tiles_config['ttl'] = 86400;

/**
 * Custom Proxy Server headers
 *
 * @var string
 */
$tiles_config['headers'] = array(
	'Access-Control-Allow-Origin:' => '*',
);
