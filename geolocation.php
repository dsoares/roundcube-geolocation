<?php
/**
 * Geolocation Roundcube Plugin
 *
 * Roundcube plugin to provide geolocation utilities.
 *
 * @version 0.1.2
 * @author Diana Soares
 * @requires php-geoip
 *
 * Copyright (C) Diana Soares
 *
 * This program is a Roundcube (http://www.roundcube.net) plugin.
 * For more information see README.md.
 * For configuration see config.inc.php.dist.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Roundcube. If not, see http://www.gnu.org/licenses/.
 */

class geolocation extends rcube_plugin
{
    public $task = '';
    private $cache;
    private static $instance;

    /**
     * Plugin initialization.
     */
    public function init()
    {
        self::$instance = $this;
        $this->load_config();
    }

    /**
     * Singleton getter to allow direct access from other plugins
     */
    public static function get_instance()
    {
        return self::$instance;
    }

    /**
     * Get geolocation information.
     */
    public function get_geolocation($ip)
    {
        $geo = $this->cache_load($ip);

        if (!$geo) {
            $geo = self::get_internal_info($ip);

            if ($geo === false) {
                $rcmail = rcube::get_instance();
                $method = $rcmail->config->get('geolocation_fetch_method', 'system');
                $geo = self::get_geolocation_info($ip, $method);
            }

            $this->cache_save($ip, $geo);
        }

        return $geo;
    }

    /**
     * Fetch geolocation information from and external GeoIP service.
     */
    public static function get_geolocation_info($ip, $source='system')
    {
        $context = stream_context_create(array('http' => array('timeout' => 20)));
        $geo = false;

        switch ($source) {
        case 'geoplugin':
            // using www.geoplugin.net
            $d = unserialize(file_get_contents("http://www.geoplugin.net/php.gp?ip=".$ip, false, $context));
            if ($d['geoplugin_status'] == 200) {
                $geo = array('city'    => $d['geoplugin_city'],
                             'region'  => $d['geoplugin_regionName'],
                             'country' => $d['geoplugin_countryName']
                );
            }
            break;

        case 'geoiptool':
            // using geoiptool.com
            $d = file_get_contents("https://www.geoiptool.com/en/?IP=".$ip, false, $context);

            if (preg_match_all("|<div class=\"data-item\">.*</div>|Us", $d, $items)) {
                $d = array();

                foreach ($items[0] as $i) {
                    $i = preg_replace("/\s+/s", " ", strip_tags($i));
                    list($k, $v) = explode(":", $i);
                    $d[trim($k)] = trim($v);
                }

                $geo = array('city'    => $d['City'],
                             'region'  => $d['Region'],
                             'country' => $d['Country']
                );
            }
            break;

	case 'maxminddb':
	    $geo = array('city' => 'unknown', 'region'  => 'unknown','country' => '??');
	    if (extension_loaded('maxminddb')) {
        	$rcmail = rcube::get_instance();
		$db_location = $rcmail->config->get('geolocation_db', '/usr/share/GeoIP/GeoLite2-City.mmdb');
		if(!$db_location) break;
		$reader = new \MaxMind\Db\Reader($db_location);
		if(!$reader) break;
		$rd = $reader->get($ip);
		if(!$rd) break;
		$geo = array(
			'city' => utf8_encode($rd['city']['names']['en']),
			'country' => utf8_encode($rd['country']['names']['en']));
	    }
	    break;
        case 'system':
        default:
            // using system database
            $d = geoip_record_by_name($ip);
            $r = ($d['country_code'] != '' && $d['region'] != '')
                ? geoip_region_name_by_code($d['country_code'], $d['region']) : $d['region'];
            $geo = array('city'    => utf8_encode($d['city']),
                         'region'  => utf8_encode($r),
                         'country' => utf8_encode($d['country_name'])
            );
            break;
        }

        return $geo;
    }

    /**
     * Check if IP belongs to our organization internal network.
     */
    public static function get_internal_info($ip)
    {
        $rcmail = rcube::get_instance();
        $networks = $rcmail->config->get('geolocation_internal_networks', array());

        foreach ($networks as $cidr => $descr) {
            if (self::net_match($cidr, $ip)) {
                return $descr;
            }
        }

        return false;
    }

    /**
     * Checks if an IPv4 or IPv6 address is contained in the given CIDR.
     */
    public static function net_match($cidr, $ip)
    {
        if (substr_count($ip, ':') > 1) {
            $method = 'net_match_ipv6';
            $bits = 128;
        } else {
            $method = 'net_match_ipv4';
            $bits = 32;
        }

        if (strpos($cidr, '/') !== false) {
            list($net, $mask) = explode('/', $cidr, 2);
            if ($mask <= 0 || $mask > $bits) {
                return false;
            }
        } else {
            $net  = $cidr;
            $mask = $bits;
        }

        return self::$method($ip, $net, $mask);
    }

    /**
     * Checks if the given IPv4 address is part of the subnet.
     */
    private static function net_match_ipv4($ip, $net, $mask)
    {
        return (ip2long($ip) & ~((1 << (32 - $mask)) - 1)) === ip2long($net);
    }

    /**
     * Checks if the given IPv6 address is part of the subnet.
     *
     * @author David Soria Parra <dsp at php dot net>
     * @see https://github.com/dsp/v6tools
     */
    private static function net_match_ipv6($ip, $net, $mask)
    {
        $bytes_addr = unpack('n*', @inet_pton($net));
        $bytes_test = unpack('n*', @inet_pton($ip));
        if (!$bytes_addr || !$bytes_test) {
            return false;
        }
        for ($i = 1, $ceil = ceil($mask / 16); $i <= $ceil; ++$i) {
            $left = $mask - 16 * ($i - 1);
            $left = ($left <= 16) ? $left : 16;
            $mask = ~(0xffff >> $left) & 0xffff;
            if (($bytes_addr[$i] & $mask) != ($bytes_test[$i] & $mask)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Save in cache.
     */
    private function cache_save($ip, $geo)
    {
        $this->cache[$ip] = $geo;
    }

    /**
     * Load geo location from cache.
     */
    private function cache_load($ip)
    {
        if (isset($this->cache[$ip])) {
            return $this->cache[$ip];
        }
        return null;
    }
}
