<?php
/**
 * This file is part of the Lifo\IP PHP Library.
 *
 * (c) Jason Morriss <lifo2013@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Lifo\IP;

/**
 * CIDR Block helper class.
 *
 * Most routines can be used statically or by instantiating an object and
 * calling its methods.
 *
 * Provides routines to do various calculations on IP addresses and ranges.
 * Convert to/from CIDR to ranges, etc.
 */
class CIDR
{
    protected $ip;
    protected $prefix;
    protected $version;

    private $cache;

    public function __construct($cidr)
    {
        $this->setCidr($cidr);
    }

    /**
     * Returns the string representation of the CIDR block.
     */
    public function __toString()
    {
        // do not include the prefix if its a single IP
        if (($this->version == 4 and $this->prefix != 32) || ($this->version == 6 and $this->prefix != 128)) {
            return $this->ip . '/' . $this->prefix;
        }
        return $this->ip;
    }

    /**
     * Set the CIDR block.
     *
     * The prefix length is optional and will default to 32 ot 128 depending on
     * the version detected.
     *
     * @param string $cidr CIDR block string, eg: "192.168.0.0/24" or "2001::1/64"
     * @throws \InvalidArgumentException If the CIDR block is invalid
     */
    public function setCidr($cidr)
    {
        list($ip, $bits) = array_pad(explode('/', $cidr, 2), 2, null);
        if (false === filter_var($ip, FILTER_VALIDATE_IP)) {
            throw new \InvalidArgumentException("Invalid IP address \"$cidr\"");
        }

        // determine version (4 or 6)
        $this->version = (false === filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) ? 6 : 4;

        $this->ip = $ip;
        if ($bits !== null and $bits !== '') {
            $this->prefix = $bits;
        } else {
            $this->prefix = $this->version == 4 ? 32 : 128;
        }

        if (($this->prefix < 0)
            || ($this->prefix > 32 and $this->version == 4)
            || ($this->prefix > 128 and $this->version == 6)) {
            throw new \InvalidArgumentException("Invalid IP address \"$cidr\"");
        }

        $this->cache = array();
    }

    /**
     * Get the IP version. 4 or 6.
     *
     * @return integer
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * Returns true if the IP is an IPv4
     *
     * @return boolean
     */
    public function isIPv4()
    {
        return $this->version == 4;
    }

    /**
     * Returns true if the IP is an IPv6
     *
     * @return boolean
     */
    public function isIPv6()
    {
        return $this->version == 6;
    }

    /**
     * Get the [low,high] range of the CIDR block
     *
     * @see CIDR::cidr_to_range
     */
    public function getRange()
    {
        return self::cidr_to_range($this->ip, $this->prefix);
    }

    /**
     * Return the IP in its fully expanded form.
     *
     * For example: 2001::1 == 2007:0000:0000:0000:0000:0000:0000:0001
     *
     * @see IP::inet_expand
     */
    public function getExpanded()
    {
        return IP::inet_expand($this->ip);
    }

    /**
     * Get network IP of the CIDR block
     *
     * @see CIDR::cidr_to_range
     */
    public function getNetwork()
    {
        // micro-optimization to prevent calling cidr_to_range repeatedly
        if (!isset($this->cache['range'])) {
            $this->cache['range'] = $this->getRange();
        }
        return $this->cache['range'][0];
    }

    /**
     * Get broadcast IP of the CIDR block
     *
     * @see CIDR::cidr_to_range
     */
    public function getBroadcast()
    {
        // micro-optimization to prevent calling cidr_to_range repeatedly
        if (!isset($this->cache['range'])) {
            $this->cache['range'] = $this->getRange();
        }
        return $this->cache['range'][1];
    }

    /**
     * Get total hosts within CIDR range
     *
     * @see CIDR::cidr_to_range
     */
    public function getTotal()
    {
        // micro-optimization to prevent calling cidr_to_range repeatedly
        if (!isset($this->cache['range'])) {
            $this->cache['range'] = $this->getRange();
        }
        return bcadd(bcsub(IP::inet_ptod($this->cache['range'][1]), IP::inet_ptod($this->cache['range'][0])), '1');
    }

    /**
     * Converts an IPv4 or IPv6 CIDR block into its range.
     *
     * @todo May not be the fastest way to do this.
     *
     * @static
     * @param string       $cidr CIDR block or IP address string.
     * @param integer|null $bits If /bits is not specified on string they can be
     *                           passed via this parameter instead.
     * @return array             A 2 element array with the low, high range
     */
    public static function cidr_to_range($cidr, $bits = null)
    {
        if (strpos($cidr, '/') !== false) {
            list($ip, $_bits) = array_pad(explode('/', $cidr, 2), 2, null);
        } else {
            $ip = $cidr;
            $_bits = $bits;
        }

        if (false === filter_var($ip, FILTER_VALIDATE_IP)) {
            throw new \InvalidArgumentException("IP address \"$cidr\" is invalid");
        }

        // force bit length to 32 or 128 depending on type of IP
        // @todo not sure this should be used; It's possible to use strlen($ipbin)
        //       to determine approximate bit length required.
        $bitlen = (false === filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) ? 128 : 32;
        $ipdec = IP::inet_ptod($ip);
        $ipbin = BC::bcdecbin($ipdec, $bitlen);
        //$bitlen = strlen($ipbin);

        if ($bits === null) {
            // if no prefix is given use the length of the binary string which
            // will give us 32 or 128 and result in a single IP being returned.
            $bits = $_bits !== null ? $_bits : $bitlen;
        }

        // calculate network
        $netmask = BC::bcbindec(str_pad(str_repeat('1',$bits), $bitlen, '0'));
        $ip1 = BC::bcand($ipdec, $netmask);

        // calculate "broadcast" (not technically a broadcast in IPv6)
        $ip2 = BC::bcor($ip1, BC::bcnot($netmask));

        // @todo should total be calculated here? It's quick to do since the
        //       IP addresses are already converted to decimal.
        //$total = bcadd(bcsub($ip2, $ip1), '1');

        return array(IP::inet_dtop($ip1), IP::inet_dtop($ip2)); //, $total);
    }
}
