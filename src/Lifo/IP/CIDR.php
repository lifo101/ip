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
    const INTERSECT_NO              = 0;
    const INTERSECT_YES             = 1;
    const INTERSECT_LOW             = 2;
    const INTERSECT_HIGH            = 3;

    protected $start;
    protected $end;
    protected $prefix;
    protected $version;

    private $cache;

    /**
     * Create a new CIDR object.
     *
     * The IP range can be arbitrary and does not have to fall on a valid CIDR
     * range. Some methods will return different values depending if you ignore
     * the prefix or not. By default all prefix sensitive methods will assume
     * the prefix is used.
     *
     * @param string $cidr An IP address (1.2.3.4), CIDR block (1.2.3.4/24),
     *                     or range "1.2.3.4-1.2.3.10"
     */
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
        if (($this->version == 4 and $this->prefix != 32) ||
            ($this->version == 6 and $this->prefix != 128)) {
            return $this->start . '/' . $this->prefix;
        }
        return $this->start;
    }

    /**
     * Set an arbitrary IP range.
     * The closest matching prefix will be calculated but the actual range
     * stored in the object can be arbitrary.
     * @param string $start Starting IP or combination "start-end" string.
     * @param string $end   Ending IP or null.
     */
    public function setRange($ip, $end = null)
    {
        if (strpos($ip, '-') !== false) {
            list($ip, $end) = array_map('trim', explode('-', $ip, 2));
        }

        if (false === filter_var($ip, FILTER_VALIDATE_IP) ||
            false === filter_var($end, FILTER_VALIDATE_IP)) {
            throw new \InvalidArgumentException("Invalid IP range \"$ip-$end\"");
        }

        // determine version (4 or 6)
        $this->version = (false === filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) ? 6 : 4;
        $this->start = $ip;
        $this->end = $end;

        $ip1 = IP::inet_ptod($this->start);
        $ip2 = IP::inet_ptod($this->end);

        $len = $this->version == 4 ? 32 : 128;

        //echo BC::bcdecbin($ip1, $len), "\n";
        //echo BC::bcdecbin($ip2, $len), "\n";
        //echo BC::bcdecbin(BC::bcxor($ip1, $ip2)), "\n";

        $this->prefix = $len - strlen(BC::bcdecbin(BC::bcxor($ip1, $ip2)));
    }

    /**
     * Returns true if the current IP is a true cidr block
     */
    public function isTrueCidr()
    {
        return $this->start == $this->getNetwork();
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
        if (strpos($cidr, '-') !== false) {
            return $this->setRange($cidr);
        }

        list($ip, $bits) = array_pad(explode('/', $cidr, 2), 2, null);
        if (false === filter_var($ip, FILTER_VALIDATE_IP)) {
            throw new \InvalidArgumentException("Invalid IP address \"$cidr\"");
        }

        // determine version (4 or 6)
        $this->version = (false === filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) ? 6 : 4;

        $this->start = $ip;
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
     * Get the prefix.
     *
     * Always returns the "proper" prefix, even if the IP range is arbitrary.
     *
     * @return integer
     */
    public function getPrefix()
    {
        return $this->prefix;
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
     * Prefix sensitive.
     *
     * @param boolean $ignorePrefix If true the arbitrary start-end range is
     *                              returned. default=false.
     */
    public function getRange($ignorePrefix = false)
    {
        return $ignorePrefix
            ? array($this->start, $this->end)
            : self::cidr_to_range($this->start, $this->prefix);
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
        return IP::inet_expand($this->start);
    }

    /**
     * Get network IP of the CIDR block
     *
     * Prefix sensitive.
     *
     * @param boolean $ignorePrefix If true the arbitrary start-end range is
     *                              returned. default=false.
     */
    public function getNetwork($ignorePrefix = false)
    {
        // micro-optimization to prevent calling getRange repeatedly
        $k = $ignorePrefix ? 1 : 0;
        if (!isset($this->cache['range'][$k])) {
            $this->cache['range'][$k] = $this->getRange($ignorePrefix);
        }
        return $this->cache['range'][$k][0];
    }

    /**
     * Get broadcast IP of the CIDR block
     *
     * Prefix sensitive.
     *
     * @param boolean $ignorePrefix If true the arbitrary start-end range is
     *                              returned. default=false.
     */
    public function getBroadcast($ignorePrefix = false)
    {
        // micro-optimization to prevent calling getRange repeatedly
        $k = $ignorePrefix ? 1 : 0;
        if (!isset($this->cache['range'][$k])) {
            $this->cache['range'][$k] = $this->getRange($ignorePrefix);
        }
        return $this->cache['range'][$k][1];
    }

    /**
     * Get total hosts within CIDR range
     *
     * Prefix sensitive.
     *
     * @param boolean $ignorePrefix If true the arbitrary start-end range is
     *                              returned. default=false.
     */
    public function getTotal($ignorePrefix = false)
    {
        // micro-optimization to prevent calling getRange repeatedly
        $k = $ignorePrefix ? 1 : 0;
        if (!isset($this->cache['range'][$k])) {
            $this->cache['range'][$k] = $this->getRange($ignorePrefix);
        }
        return bcadd(bcsub(IP::inet_ptod($this->cache['range'][$k][1]),
                           IP::inet_ptod($this->cache['range'][$k][0])), '1');
    }

    public function intersects($cidr)
    {
        return self::cidr_intersect((string)$this, $cidr);
    }

    /**
     * Determines the intersection between an IP (with optional prefix) and a
     * CIDR block.
     *
     * The IP will be checked against the CIDR block given and will either be
     * inside or outside the CIDR completely, or partially.
     *
     * NOTE: The caller should explicitly check against the INTERSECT_*
     * constants because this method will return a value > 1 even for partial
     * matches.
     *
     * @param mixed $ip The IP/cidr to match
     * @param mixed $cidr The CIDR block to match within
     * @return integer Returns an INTERSECT_* constant
     * @throws \InvalidArgumentException if either $ip or $cidr is invalid
     */
    public static function cidr_intersect($ip, $cidr)
    {
        // use fixed length HEX strings so we can easily do STRING comparisons
        // instead of using slower bccomp() math.
        list($lo,$hi)   = array_map(function($v){ return sprintf("%032s", IP::inet_ptoh($v)); }, CIDR::cidr_to_range($ip));
        list($min,$max) = array_map(function($v){ return sprintf("%032s", IP::inet_ptoh($v)); }, CIDR::cidr_to_range($cidr));

        /** visualization of logic used below
            lo-hi   = $ip to check
            min-max = $cidr block being checked against
            --- --- --- lo  --- --- hi  --- --- --- --- --- IP/prefix to check
            --- min --- --- max --- --- --- --- --- --- --- Partial "LOW" match
            --- --- --- --- --- min --- --- max --- --- --- Partial "HIGH" match
            --- --- --- --- min max --- --- --- --- --- --- No match "NO"
            --- --- --- --- --- --- --- --- min --- max --- No match "NO"
            min --- max --- --- --- --- --- --- --- --- --- No match "NO"
            --- --- min --- --- --- --- max --- --- --- --- Full match "YES"
         */

        // IP is exact match or completely inside the CIDR block
        if ($lo >= $min and $hi <= $max) {
            return self::INTERSECT_YES;
        }

        // IP is completely outside the CIDR block
        if ($max < $lo || $min > $hi) {
            return self::INTERSECT_NO;
        }

        // @todo is it useful to return LOW/HIGH partial matches?

        // IP matches the lower end
        if ($max <= $hi and $min <= $lo) {
            return self::INTERSECT_LOW;
        }

        // IP matches the higher end
        if ($min >= $lo and $max >= $hi) {
            return self::INTERSECT_HIGH;
        }

        return self::INTERSECT_NO;
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

        return array(IP::inet_dtop($ip1), IP::inet_dtop($ip2));
    }

    public static function cidr_is_true($ip)
    {
        $ip = new CIDR($ip);
        return $ip->isTrueCidr();
    }
}
