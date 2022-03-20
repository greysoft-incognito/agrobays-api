<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use App\Models\User;


if (!function_exists('get_domain'))
{
    function get_domain($url, $getSubdomain = false)
    {
        if (is_bool($url))
        {
            $getSubdomain = $url;
        }

        if (empty($url) || is_bool($url))
        {
            $url = request()->getHost();
        }

        if (!preg_match("/^http/", $url))
        {
            $url = 'http://' . $url;
        }

        if ($url[strlen($url) - 1] != '/')
        {
            $url .= '/';
        }

        $pieces = parse_url($url);
        $domain = isset($pieces['host']) ? $pieces['host'] : '';

        if ($getSubdomain === true)
        {
            $sub = explode('.', $domain);
            return count($sub) >= 3 ? $sub[0] : null;
        }

        if (preg_match('/(?P<domain>[a-z0-9][a-z0-9\-]{1,63}\.[a-z\.]{2,6})$/i', $domain, $regs))
        {
            $res = preg_replace('/^www\./', '', $regs['domain']);
            return $res;
        }

        return false;
    }
}


if (!function_exists('carbonDate')) {
    function carbonDate($format = 'Y-m-d H:i:s', $date = 'NOW')
    {
        if (is_numeric($date)) {
            $date = date('Y-m-d H:i:s', $date);
        }
        return Carbon::parse($date)->format($format);
    }
}

if (!function_exists('profile_photo')) {
    function profile_photo($user, $type = 'avatar', $cached_size = 'medium-square')
    {
        return img($user->profile_photo_path, $type, $cached_size);
    }
}

if (!function_exists('img')) {
    function img($image, $type = 'avatar', $cached_size = 'original', $no_default = false)
    {
        if (filter_var($image, FILTER_VALIDATE_URL)) {
            return $image;
        }

        if ($image && Storage::exists($image)) {
            $fpath    = preg_match("/^(media\/|home\/){1,2}\w+/", $image) ? $image : 'media/' . $image;
            $photo    = asset((config('filesystems.default') === 'local' ? $fpath : Storage::url($image)));
        } else {
            if ($no_default === true) return null;

            $default = Storage::exists($image);
            $photo = asset((config('filesystems.default') === 'local'
                ? env('default_' . $type, 'media/' . $type . (in_array($type, ['logo', 'avatar']) ? '.svg' : '.png'))
                : Storage::url(env('default_' . $type, 'media/' . $type . (in_array($type, ['logo', 'avatar']) ? '.svg' : '.png')))));

            $photo = config('settings.default_' . $type, $photo);
        }

        if (($cache = config('imagecache.route')) && !Str::contains($photo, ['.svg'])) {
            $filename = basename($photo);
            return url("$cache/$cached_size/$filename");
        }

        $file_scheme = parse_url($photo, PHP_URL_SCHEME);
        $site_scheme = parse_url(config('app.url'), PHP_URL_SCHEME);

        return Str::of($photo)->replace($file_scheme . '://', $site_scheme . '://');
    }
}

if (!function_exists('money')) {
    /**
     * Shorten long numbers to K/M/B
     * @param  integer|string $number The input number to shorten
     * @return string         A string representing the reformatted and shortened number
     */
    function money($number, $abbrev = false)
    {
        return config('settings.currency_symbol') . ($abbrev === false
            ? number_format($number, 2)
            : numberAbbr($number)
        );
    }
}

if (!function_exists('numberAbbr')) {
    /**
     * Converts a number into a short version, eg: 1000 -> 1k
     * Based on: http://stackoverflow.com/a/4371114
     * @author Nivesh Saharan https://stackoverflow.com/users/5083810/nivesh-saharan
     * @author 3m1n3nc3 https://stackoverflow.com/users/10685553/3m1n3n3
     * @param  integer|string $n The input number to shorten
     * @return string         A string representing the reformatted and shortened number
     */
    function numberAbbr($n, $precision = 1)
    {
        if ($n < 900) {
            // 0 - 900
            $n_format = number_format($n, $precision);
            $suffix = '';
        } else if ($n < 900000) {
            // 0.9k-850k
            $n_format = number_format($n / 1000, $precision);
            $suffix = 'K';
        } else if ($n < 900000000) {
            // 0.9m-850m
            $n_format = number_format($n / 1000000, $precision);
            $suffix = 'M';
        } else if ($n < 900000000000) {
            // 0.9b-850b
            $n_format = number_format($n / 1000000000, $precision);
            $suffix = 'B';
        } else {
            // 0.9t+
            $n_format = number_format($n / 1000000000000, $precision);
            $suffix = 'T';
        }

        // Remove unecessary zeroes after decimal. "1.0" -> "1"; "1.00" -> "1"
        // Intentionally does not affect partials, eg "1.50" -> "1.50"
        if ($precision > 0) {
            $dotzero = '.' . str_repeat('0', $precision);
            $n_format = str_replace($dotzero, '', $n_format);
        }
        return $n_format . $suffix;
    }
}


if (!function_exists('num2word')) {
    /**
     * Convert a number to it's words equivalent.
     * @param  integer|string $number The input number
     * @return string         The new number in words
     */
    function num2word($number)
    {
        return (new NumberFormatter('en', NumberFormatter::SPELLOUT))
            ->format($number);
    }
}


if (!function_exists('newliner')) {
    /**
     * Break long strings to different segment
     * @param  string  $txt         The string to break
     * @param  integer $break_point After how many words should string be broken
     * @param  string  $divider     The separator for the broken pieces
     * @return string               The reformatted broken string
     */
    function newliner($txt, $break_point = 3, $divider = "\n")
    {
        $sub = Str::of($txt)->split('/[\s]+/')->chunk($break_point);

        $title = Str::of("\n");

        foreach ($sub as $group) {
            foreach ($group as $word) {
                $title = $title->append($word);

                if ($group->last() === $word && $group->count() >= $break_point)
                    $title = $title->append($divider);
                elseif ($group->count() <= $break_point)
                    $title = $title->append(" ");
            }
        }
        return $title->trim();
    }
}

if (!function_exists('linkORroute')) {
    function linkORroute(string $string = null, $absolute = true)
    {
        if (empty($string)) {
            return;
        }

        if (filter_var($string, FILTER_VALIDATE_URL)) {
            return $absolute ? $string : parse_url($string, PHP_URL_PATH);
        } elseif (Str::containsAll($string, ['.', '/'])) {
            $route   = Str::of($string)->beforeLast('/')->rtrim('.')->__toString();
            $pre_seg = Str::remove(Str::of($route)->explode('.')->last() . '/', Str::of($string)->explode('.')->last());
            $segment = Str::of($pre_seg)->explode('/');
            return route($route, $segment->toArray(), $absolute);
        }

        return url($string);
    }
}

if (!function_exists('valid_json')) {
    /**
     * Matches a valid json string
     * Note that everything is atomic, JSON does not need backtracking if it is valid
     * and this prevents catastrophic backtracking
     * @param   $str
     * @param   $get
     */
    function valid_json(String $str, $get = false)
    {
        $data = json_decode($str);
        $isValid = (json_last_error() == JSON_ERROR_NONE);
        return $get === true && $isValid ? $data : $isValid;
    }
}