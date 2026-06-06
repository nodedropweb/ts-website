<?php

namespace Wruczek\TSWebsite\Utils;

/**
 * Server-side rewriter for YouTube / Vimeo iframes.
 * Converts <iframe src="youtube/vimeo URL"> to <iframe data-src="..." data-name="...">
 * so Klaro can block them until the user gives consent.
 */
class MediaConsent {

    private const SERVICES = [
        'youtube' => '/https?:\/\/(?:www\.)?(?:youtube(?:-nocookie)?\.com|youtu\.be)\//i',
        'vimeo'   => '/https?:\/\/(?:www\.)?(?:player\.)?vimeo\.com\//i',
    ];

    public static function rewrite(string $html): string {
        if ($html === '') return '';

        return preg_replace_callback(
            '/<iframe\b([^>]*)>/i',
            function (array $m) {
                $attrs = $m[1];

                // Already handled
                if (preg_match('/\bdata-name\s*=/i', $attrs)) return $m[0];

                // Extract src
                if (!preg_match('/\bsrc\s*=\s*(["\'])([^"\']+)\1/i', $attrs, $srcMatch)) {
                    return $m[0];
                }

                $src  = $srcMatch[2];
                $name = null;
                foreach (self::SERVICES as $service => $pattern) {
                    if (preg_match($pattern, $src)) {
                        $name = $service;
                        break;
                    }
                }

                if ($name === null) return $m[0];

                // Remove src, add data-src + data-name
                $newAttrs = preg_replace('/\bsrc\s*=\s*(["\'])[^"\']+\1/i', '', $attrs);
                $newAttrs = ' data-name="' . $name . '" data-src="' . htmlspecialchars($src, ENT_QUOTES) . '"' . $newAttrs;

                return '<iframe' . $newAttrs . '>';
            },
            $html
        );
    }
}
