## IP Address Helper Library

IP address helper PHP library for working with IPv4 and IPv6 addresses. Convert any IP address into decimal, hex or binary and back again.

__This library is a work-in-progress (WIP).__ 

### Notes

This library is currently missing support for Subnets and other miscellaneous IP features. I will work on those soon. I have also not decided on a final structure for the classes. Right now everything is simply a static class method _(so I don't pollute the global namespace)_. 

### Examples

The translation routines are IP agnostic, meaning they don't care if you pass in an IPv4 or IPv6 address.

```php
use Lifo\IP\IP;

// IPv4
echo '127.0.0.1 = ', IP::inet_ptod('127.0.0.1'), "\n";
echo IP::inet_dtop('2130706433'), " = 2130706433\n";
echo '127.0.0.1 = ', IP::inet_ptoh('127.0.0.1'), " (hex)\n";

// IPv6
echo '2001:4056::1 = ', IP::inet_ptod('2001:4056::1'), "\n";
echo IP::inet_dtop('42541793049812452694190522094162280449'), " = 42541793049812452694190522094162280449\n";
echo '2001:4056::1 = ', IP::inet_ptoh('2001:4056::1'), " (hex)\n";
```

```
// expected output:
127.0.0.1 = 2130706433
127.0.0.1 = 2130706433
127.0.0.1 = 7f000001
2001:4056::1 = 42541793049812452694190522094162280449
2001:4056::1 = 42541793049812452694190522094162280449
2001:4056::1 = 20014056000000000000000000000001
```
