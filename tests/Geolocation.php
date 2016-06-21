<?php
/**
 * Geolocation plugin test class.
 *
 * Running in command-line:
 *   phpunit.phar --bootstrap ../../program/lib/Roundcube/bootstrap.php tests/*
 */
class Geolocation_Plugin extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        include_once __DIR__ . '/../geolocation.php';
    }

    /**
     * Plugin object construction test
     */
    public function test_constructor()
    {
        $rcube  = rcube::get_instance();
        $plugin = new geolocation($rcube->api);

        $this->assertInstanceOf('geolocation', $plugin);
        $this->assertInstanceOf('rcube_plugin', $plugin);
    }

    /**
     * @dataProvider ipNetsProvider
     */
    public function test_net_match($cidr, $ip, $expected)
    {
        $result = geolocation::net_match($cidr, $ip);
        $this->assertEquals($expected, $result);
    }

    /**
     * Data values to test net_match()
     *
     * @todo test more values!
     */
    public function ipNetsProvider()
    {
        return [ // test_name => [cidr, ip, expected_result],
            'ipv4 ip ip ok'        => ['193.194.195.196', '193.194.195.196', true],
            'ipv4 ip ip ko'        => ['193.194.195.196', '193.194.195.197', false],
            'ipv4 cidr ip fixed'   => ['193.194.195.196/32', '193.194.195.196', true],
            'ipv4 cidr ip inside'  => ['10.0.1.0/24', '10.0.1.128', true],
            'ipv4 cidr ip outside' => ['10.0.1.0/28', '10.0.1.200', false],
            'ipv6 ip_v6 ip_v4'     => ['2001:470:6d:80:a5f6:5a97:a53:71c8', '10.0.129.6', false],
            'ipv6 ip ip ok'        => ['2001:470:6d:80:c4f1:32a:4521:c38', '2001:470:6d:80:c4f1:32a:4521:c38', true],
            'ipv6 ip ip ko'        => ['2001:470:6d:80:c4f1:32a:4521:c38', '2001:470:6d:80:c4f1:32a:4521:c39', false],
            'ipv6 cidr ip fixed'   => ['2001:470:6d:80:c4f1:32a:4521:c38/128', '2001:470:6d:80:c4f1:32a:4521:c38', true],
            'ipv6 cidr ip inside'  => ['2001:470:6d:80:a5f6:5a97:a53:71c8/100', '2001:470:6d:80:a5f6:5a97:a53:71c9', true],
            'ipv6 cidr ip outside' => ['2001:470:6d:80:a5f6:5a97:a53:71c8/127', '2001:470:6d:80:a5f6:5a97:a53:71f9', false],
        ];
    }
}
