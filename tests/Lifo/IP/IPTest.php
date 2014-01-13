<?php

use Lifo\IP\IP;

class IPTest extends \PHPUnit_Framework_TestCase
{

    public function testIP()
    {
        // IPv4
        $this->assertTrue(IP::isIPv4('10.0.0.1'));
        $this->assertFalse(IP::isIPv4('2007::1'));
        $this->assertEquals(IP::inet_ptod('10.0.0.1'), '167772161', 'IPv4 inet_ptod (decimal)');
        $this->assertEquals(IP::inet_ptod('10.0.0.1/24'), '167772161', 'IPv4 inet_ptod with cidr (decimal)');
        $this->assertEquals(IP::inet_ptoh('10.0.0.1'), '0a000001', 'IPv4 inet_ptoh (hex)');
        $this->assertEquals(IP::inet_ptob('10.0.0.1'), '00001010000000000000000000000001', 'IPv4 inet_ptob (binary)');
        $this->assertEquals(IP::inet_dtop('167772161'), '10.0.0.1', 'IPv4 inet_dtop (presentational)');
        $this->assertEquals(IP::inet_htop('0a000001'), '10.0.0.1', 'IPv4 inet_htop (hex)');
        $this->assertEquals(IP::inet_btop('00001010000000000000000000000001'), '10.0.0.1', 'IPv4 inet_btop (binary)');
        $this->assertEquals(IP::inet_expand('10.0.0.1'), '10.0.0.1', 'IPv4 expand');
        $this->assertEquals(IP::inet_expand('10.0.0.1/24'), '10.0.0.1', 'IPv4 expand with cidr');
        $this->assertEquals(IP::to_ipv6('10.0.0.1'), 'a00:1', 'IPv6 to_ipv6');
        $this->assertEquals(IP::to_ipv6('10.0.0.1', true), '0:0:0:0:0:ffff:a00:1', 'IPv6 to_ipv6 mapped');

        // IPv6
        $this->assertTrue(IP::isIPv6('2007:1234:5678::1'));
        $this->assertFalse(IP::isIPv6('10.0.0.1'));
        $this->assertEquals(IP::inet_ptod('2007:1234:5678::1'), '42572011173125150141124156729380044801', 'IPv6 inet_ptod (decimal)');
        $this->assertEquals(IP::inet_ptod('2007:1234:5678::1/64'), '42572011173125150141124156729380044801', 'IPv6 inet_ptod with cidr (decimal)');
        $this->assertEquals(IP::inet_ptoh('2007:1234:5678::1'), '20071234567800000000000000000001', 'IPv6 inet_ptoh (hex)');
        $this->assertEquals(IP::inet_ptob('2007:1234:5678::1'), '00100000000001110001001000110100010101100111100000000000000000000000000000000000000000000000000000000000000000000000000000000001', 'IPv6 inet_ptob (binary)');
        $this->assertEquals(IP::inet_dtop('42572011173125150141124156729380044801'), '2007:1234:5678::1', 'IPv6 inet_dtop (presentational)');
        $this->assertEquals(IP::inet_htop('20071234567800000000000000000001'), '2007:1234:5678::1', 'IPv6 inet_htop (hex)');
        $this->assertEquals(IP::inet_btop('00100000000001110001001000110100010101100111100000000000000000000000000000000000000000000000000000000000000000000000000000000001'), '2007:1234:5678::1', 'IPv6 inet_btop (binary)');
        $this->assertEquals(IP::inet_expand('2007:1234:5678::1'), '2007:1234:5678:0000:0000:0000:0000:0001', 'IPv6 expand');
        $this->assertEquals(IP::inet_expand('2007:1234:5678::1/64'), '2007:1234:5678:0000:0000:0000:0000:0001', 'IPv6 expand with cidr');

        // Misc tests for coverage
        $this->assertEquals(IP::inet_dtop('167772161'), '10.0.0.1', 'IPv6 inet_dtop low-end no version (decimal)');
        $this->assertEquals(IP::inet_dtop('167772161', 6), '::10.0.0.1', 'IPv6 inet_dtop low-end forced v6 (decimal)');
        $this->assertEquals(IP::inet_dtop('167772161', 4), '10.0.0.1', 'IPv6 inet_dtop low-end forced v4 (decimal)');
    }

    public function testIPv4cmp()
    {
        $lo = '10.0.0.1';
        $hi = '192.168.1.2';

        $this->assertTrue(IP::cmp($lo, $lo) == 0);
        $this->assertTrue(IP::cmp($lo, $hi) == -1);
        $this->assertTrue(IP::cmp($hi, $lo) == 1);
    }

    public function testIPv6cmp()
    {
        $lo = '2001::1';
        $hi = '9504::2';

        $this->assertTrue(IP::cmp($lo, $lo) == 0);
        $this->assertTrue(IP::cmp($lo, $hi) == -1);
        $this->assertTrue(IP::cmp($hi, $lo) == 1);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testIPException()
    {
        // param 1 must be an IPv4 address
        IP::to_ipv6('2007::1');
    }
}
