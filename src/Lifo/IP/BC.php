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
 * BCMath helper class.
 *
 * Provides a handful of BCMath routines that are not included in the native
 * PHP library.
 *
 */
abstract class BC
{
    /**
     * BC Math function to convert a HEX string into a DECIMAL
     */
    public static function bchexdec($hex)
    {
        if (strlen($hex) == 1) {
            return hexdec($hex);
        }

        $remain = substr($hex, 0, -1);
        $last = substr($hex, -1);
        return bcadd(bcmul(16, self::bchexdec($remain)), hexdec($last));
    }

    /**
     * BC Math function to convert a DECIMAL string into a BINARY string
     */
    public static function bcdecbin($dec, $pad = null) {
        $bin = '';
        while ($dec) {
            $m = bcmod($dec, 2);
            $dec = bcdiv($dec, 2);
            $bin = abs($m) . $bin;
        }
        return $pad ? sprintf("%0{$pad}s", $bin) : $bin;
    }

    /**
     * BC Math function to convert a DECIMAL into a HEX string
     */
    public static function bcdechex($dec)
    {
        $last = bcmod($dec, 16);
        $remain = bcdiv(bcsub($dec, $last), 16);
        return $remain == 0 ? dechex($last) : self::bcdechex($remain) . dechex($last);
    }

    /**
     * BC Math function to return an arbitrarily large random number.
     */
    public static function bcrand($min, $max = null)
    {
        if ($max === null) {
            $max = $min;
            $min = 0;
        }

        // swap values if $min > $max
        if (bccomp($min, $max) == 1) {
            list($min,$max) = array($max,$min);
        }

        return bcadd(
            bcmul(
                bcdiv(
                    mt_rand(0, mt_getrandmax()),
                    mt_getrandmax(),

                    strlen($max)
                ),
                bcsub(
                    bcadd($max, '1'),
                    $min
                )

            ),
            $min
        );
    }
}
