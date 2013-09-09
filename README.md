## IP Address Helper Library

IP address helper PHP library for working with IPv4 and IPv6 addresses. Convert any IP address into decimal, hex or binary and back again.

### Notes

This library is not complete and is missing certain CIDR, Subnet and other miscellaneous IP features. 
Most of the IP conversion routines use `BCMATH` to do calculations which means this library is not the fastest it
could be. Once the library is in a more stable state I may start attempting to optimize certain bits.

Feel free to send pull requests with missing functionality.

### Examples

The translation routines are IP agnostic, meaning they don't care if you pass in an IPv4 or IPv6 address.
All IP calculations are done in `Decimal` which is perfect for storing in databases. 

```php
use Lifo\IP\IP;
use Lifo\IP\CIDR:

// IPv4
echo '127.0.0.1 = ', IP::inet_ptod('127.0.0.1'), "\n";
echo IP::inet_dtop('2130706433'), " = 2130706433\n";
echo '127.0.0.1 = ', IP::inet_ptoh('127.0.0.1'), " (hex)\n";

// IPv6
echo '2001:4056::1 = ', IP::inet_ptod('2001:4056::1'), "\n";
echo IP::inet_dtop('42541793049812452694190522094162280449'), " = 42541793049812452694190522094162280449\n";
echo '2001:4056::1 = ', IP::inet_ptoh('2001:4056::1'), " (hex)\n";

// CIDR 

// note: the true CIDR block is calculated from the prefix (the ::1 is ignored)
$ip = new CIDR('2001:4056::1/96');

echo "$ip\n", implode(' - ', $ip->getRange()), " (" . number_format($ip->getTotal()) . " hosts)\n";
```

```
// expected output:
127.0.0.1 = 2130706433
127.0.0.1 = 2130706433
127.0.0.1 = 7f000001 (hex)

2001:4056::1 = 42541793049812452694190522094162280449
2001:4056::1 = 42541793049812452694190522094162280449
2001:4056::1 = 20014056000000000000000000000001 (hex)

2001:4056::1/96
2001:4056:: - 2001:4056::ffff:ffff (4,294,967,296 hosts)
```
