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

// TODO: code refactoring ( wrap it within a dedicated class.. )

// User configs.
require_once 'config.php';

// Default configs.
$servers = @$config['servers'] ?: array(
	'osm' => 'https://{switch:a,b,c}.tile.openstreetmap.org/{z}/{x}/{y}.png',
	'otm' => 'https://{switch:a,b,c}.tile.opentopomap.org/{z}/{x}/{y}.png',
);
$bbox    = @$config['bbox'] ?: '-180,-90,180,90';
$ttl     = @$config['ttl'] ?: 86400;
$headers = array_change_key_case(
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

/* // functions ///////////////////////////////////////////////////////////// */

/**
 * Generate a real tile server url
 *
 * @param  string $url eg: 'https://{switch:a,b,c}.tile.openstreetmap.org/{z}/{x}/{y}.png'.
 * @return string      eg: 'https://a.tile.openstreetmap.org/0/0/0.png'.
 */
function generate_tile_server_url( $url ) {
	global $z, $x, $y;
	$has_matches = preg_match( '/{switch:(.*?)}/', $url, $domains );
	if ( ! $has_matches ) {
		$domains = [ '', '' ];
	}
	$domains = explode( ',', $domains[1] );
	$url     = preg_replace( '/{switch:(.*?)}/', '{s}', $url );
	$url     = preg_replace( '/{s}/', $domains[ array_rand( $domains ) ], $url );
	$url     = preg_replace( '/{z}/', $z, $url );
	$url     = preg_replace( '/{x}/', $x, $url );
	$url     = preg_replace( '/{y}/', $y, $url );
	return $url;
}

/**
 * Convert Map Degrees to Radiant
 *
 * @param  float $deg map degrees.
 * @return float
 */
function deg_to_rad( $deg ) {
	return $deg * M_PI / 180;
}
/**
 * Convert Map Longitude to Tile-X Coordinates
 *
 * @param  float $lng  map longitude.
 * @param  int   $zoom map zoom level.
 * @return int
 */
function lng_to_tile_x( $lng, $zoom ) {
	return floor( ( ( $lng + 180 ) / 360 ) * pow( 2, $zoom ) );
}

/**
 * Convert Map Latitude to Tile-Y Coordinates
 *
 * @param  float $lat  map latitude.
 * @param  int   $zoom map zoom level.
 * @return int
 */
function lat_to_tile_y( $lat, $zoom ) {
	return floor( ( 1 - log( tan( deg_to_rad( $lat ) ) + 1 / cos( deg_to_rad( $lat ) ) ) / M_PI ) / 2 * pow( 2, $zoom ) );
}
/**
 * Finds if a number is within a given range
 *
 * @param  float $number number to check.
 * @param  float $min   left range number.
 * @param  float $max   right range number.
 * @return bool
 */
function in_range( $number, $min, $max ) {
		return $number >= $min && $number <= $max;
}

/**
 * Check if cached file is expired
 *
 * @param  string $file filename path.
 * @return bool
 */
function is_file_expired( $file ) {
	global $ttl;
	return filemtime( $file ) < time() - ( $ttl * 30 );
}

/**
 * Check if a remote resource exists
 *
 * @param  string $url remote file url.
 * @return bool
 */
function remote_file_exists( $url ) {
	$ch = curl_init( $url );
	curl_setopt( $ch, CURLOPT_NOBODY, true );
	curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true );
	curl_exec( $ch );
	$http_code = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
	curl_close( $ch );
	if ( $http_code == 200 ) {
		return true;
	}
	return false;
}

/**
 * Download a remote resource
 *
 * @param  string $url    remote file url.
 * @param  string $folder folder where to download the remote resource.
 * @param  string $file   file onto download the remote resource.
 * @return void
 */
function download_remote_file( $url, $folder, $file ) {
	if ( ! is_dir( $folder ) ) {
		mkdir( $folder, 0755, true );
	}

	$fp = fopen( $file, 'wb' );

	$ch = curl_init( $url );
	curl_setopt( $ch, CURLOPT_FILE, $fp );
	curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true );
	curl_setopt( $ch, CURLOPT_HEADER, 0 );
	curl_exec( $ch );
	curl_close( $ch );

	fflush( $fp ); // need to insert this line for proper output when tile is first requested.
	fclose( $fp );
}

/**
 * Output a local file
 *
 * @param  string $file file of the local resource.
 * @return void
 */
function output_local_file( $file ) {
	print_local_file_headers( $file );
	readfile( $file );
}

/**
 * Proxy a remote resource
 *
 * @param  string $url    remote file url.
 * @return void
 */
function proxy_remote_file( $url ) {
	$fp = @fopen( $url, 'rb' );
	if ( ! $fp ) {
		print_404_page();
		return;
	}
	print_remote_file_headers( $url );
	fpassthru( $fp );
}

/**
 * Get remote file headers, similar to: get_headers( $url )
 *
 * @param  string $url    remote file url.
 * @return array
 * @link https://stackoverflow.com/a/41135574
 */
function get_remote_file_headers( $url ) {
	$headers = [];
	$ch      = curl_init();

	curl_setopt( $ch, CURLOPT_URL, $url );
	curl_setopt( $ch, CURLOPT_HEADER, true );
	curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true );
	curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );

	// this function is called by curl for each header received.
	curl_setopt(
		$ch, CURLOPT_HEADERFUNCTION,
		function( $curl, $header ) use ( &$headers ) {
			$headers[] = $header;
			return strlen( $header );
		}
	);
	curl_exec( $ch );
	curl_close( $ch );

	return $headers;
}

/**
 * Print the default apache 404 page
 *
 * @return void
 */
function print_404_page() {
	header( $_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found' );
	$request_uri = htmlspecialchars( $_SERVER['REQUEST_URI'] );
	echo '<!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML 2.0//EN">';
	echo "<html><head><title>404 Not Found</title></head><body><h1>Not Found</h1><p>The requested URL {$request_uri} was not found on this server.</p></body></html>";
}

/**
 * Send to browser remote file headers
 *
 * @param  string $url    remote file url.
 * @return void
 */
function print_remote_file_headers( $url ) {
	$headers = get_remote_file_headers( $url );
	print_file_headers( $headers );
}

/**
 * Send to browser local file headers
 *
 * @param  string $file    local file.
 * @return void
 */
function print_local_file_headers( $file ) {
	global $ttl;
	$exp_gmt = gmdate( 'D, d M Y H:i:s', time() + $ttl * 60 ) . ' GMT';
	$mod_gmt = gmdate( 'D, d M Y H:i:s', filemtime( $file ) ) . ' GMT';
	$max_age = $ttl * 60;
	$headers = array(
		'Expires:'       => $exp_gmt,
		'Last-Modified:' => $mod_gmt,
		'Cache-Control:' => 'public, max-age=' . $max_age, // for MSIE 5.
		'Content-Type:'  => 'image/png',
	);
	print_file_headers( $headers );
}

/**
 * Send to browser file headers filtering it with the previously array in config file
 *
 * @param array $file_headers eg: array( 'Content-Type:'  => 'image/png' ) or array( 'Access-Control-Allow-Origin: *' ).
 *
 * @return void
 */
function print_file_headers( &$file_headers ) {
	global $headers;
	// send headers only if not previously defined in config file.
	foreach ( $file_headers as $header => $value ) {
		if ( is_string( $header ) ) {
			header( $header . ' ' . $value );
		} else {
			header( $value );
		}
	}
	// send all remaing default headers defined in config file.
	foreach ( $headers as $header => $value ) {
		header_remove( rtrim( $header, ':' ) );
		header( $header . ' ' . $value );
	}
}
