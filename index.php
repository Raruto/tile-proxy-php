<?php
/**
 * Map Tile PHP Proxy
 *
 * Examples of usage:
 * /tiles/{r}/{z}/{x}/{y}.png
 * /tiles.php?r={r}&z={z}&x={x}&y={y}&bbox={left,bottom,right,top}
 *
 * /tiles/osm/10/539/369.png
 * /tiles.php?r=osm&z=10&x=539&y=369&bbox=9.19791,44.55588,10.08314,45.13950
 *
 * @package   tile-proxy-php
 * @author    Raruto <raruto.github.io>
 * @link      https://github.com/Raruto/tile-proxy-php
 * @license   https://www.gnu.org/licenses/gpl-3.0.txt GNU/GPLv3
 */

// User configs.
require_once 'config.php';
// Tile functions.
require_once 'functions.php';

// Default configs.
$user_agent = @$config['user_agent'] ?: '';
$servers    = @$config['servers'] ?: array(
	'osm' => 'https://{switch:a,b,c}.tile.openstreetmap.org/{z}/{x}/{y}.png',
	'otm' => 'https://{switch:a,b,c}.tile.opentopomap.org/{z}/{x}/{y}.png',
);
$bbox       = @$config['bbox'] ?: '-180,-90,180,90';
$ttl        = @$config['ttl'] ?: 86400;
$headers    = array_change_key_case(
	@$config['headers'] ?: array(
		'Access-Control-Allow-Origin:' => '*',
	), CASE_LOWER
);

$bbox = empty( $_GET['bbox'] ) ? $bbox : $_GET['bbox'];
$z    = intval( $_GET['z'] );
$x    = intval( $_GET['x'] );
$y    = intval( $_GET['y'] );
$r    = strip_tags( $_GET['r'] );

// allow only servers wich are defined within the tiles-config.php.
if ( ! isset( $servers[ $r ] ) ) {
	print_404_page();
	exit;
}

/* // proxy //////////////////////////////////////////////////////////// */

$url = generate_tile_server_url( $servers[ $r ] );

$folder = "${r}/${z}/${x}";
$file   = $folder . "/${y}.png";

$bbox        = explode( ',', $bbox );
$bbox_left   = $bbox[0];
$bbox_bottom = $bbox[1];
$bbox_right  = $bbox[2];
$bbox_top    = $bbox[3];

// useful parameters to detect which tiles are within the bbox area.
$min_tile_x     = lng_to_tile_x( $bbox_left, $z );
$max_tile_x     = lng_to_tile_x( $bbox_right, $z );
$min_tile_y     = lat_to_tile_y( $bbox_top, $z );
$max_tile_y     = lat_to_tile_y( $bbox_bottom, $z );
$num_tiles_row  = ( $max_tile_x - $min_tile_x ) + 1;
$num_tiles_col  = ( $max_tile_y - $min_tile_y ) + 1;
$num_tiles_bbox = $num_tiles_row * $num_tiles_col;

// if (x, y, z) out bbox range: we don't cache it on this server.
if ( ! in_range( $x, $min_tile_x, $max_tile_x ) || ! in_range( $y, $min_tile_y, $max_tile_y ) ) {
	proxy_remote_file( $url );
	exit;
}

// if (x, y, z) in bbox range: we cache it on this server.
if ( ! is_file( $file ) || ( is_file_expired( $file ) && remote_file_exists( $url ) ) ) {
	download_remote_file( $url, $folder, $file );
}

// Send to browser any previously cached tile.
output_local_file( $file );
exit;
