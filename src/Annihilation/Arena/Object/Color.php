<?php

namespace Annihilation\Arena\Object;


class Color
{

    const BIT_MASK = 0xff;

    const WHITE = 0xFFFFFF;
    const SILVER = 0xC0C0C0;
    const GRAY = 0x808080;
    const BLACK = 0x000000;
    const RED = 0xFF0000;
    const MAROON = 0x800000;
    const YELLOW = 0xFFFF00;
    const OLIVE = 0x808000;
    const LIME = 0x00FF00;
    const GREEN = 0x008000;
    const AQUA = 0x00FFFF;
    const TEAL = 0x008080;
    const BLUE = 0x0000FF;
    const NAVY = 0x000080;
    const FUCHSIA = 0xFF00FF;
    const PURPLE = 0x800080;
    const ORANGE = 0xFFA500;

    public static function toDecimal(int $bgr)
    {
        $r = $bgr >> 16 & self::BIT_MASK;
        $g = $bgr >> 8 & self::BIT_MASK;
        $b = $bgr >> 0 & self::BIT_MASK;

        return hexdec(self::rgb2hex([$r, $g, $b]));
        //echo "\n"."R: ".$bgr >> 16 & self::BIT_MASK."        G: ". $bgr >> 8 & self::BIT_MASK."             B: ".$bgr >> 0 & self::BIT_MASK;
        //return self::fromBGR($bgr >> 16 & self::BIT_MASK, $bgr >> 8 & self::BIT_MASK, $bgr >> 0 & self::BIT_MASK);
    }

    private static function rgb2hex(array $rgb) {
        $hex = "#";
        $hex .= str_pad(dechex($rgb[0]), 2, "0", STR_PAD_LEFT);
        $hex .= str_pad(dechex($rgb[1]), 2, "0", STR_PAD_LEFT);
        $hex .= str_pad(dechex($rgb[2]), 2, "0", STR_PAD_LEFT);

        return $hex; // returns the hex value including the number sign (#)
    }

}