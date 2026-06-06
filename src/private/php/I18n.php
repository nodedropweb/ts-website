<?php

namespace Wruczek\TSWebsite;

/**
 * File-based i18n using .po files.
 * Domains: frontend, installer
 * Files:   src/private/locales/{locale}/{domain}.po
 * Placeholder syntax: {0}, {1}, ...
 */
class I18n {

    private static string $locale = 'en';

    /** [domain => [msgid => msgstr]] and [domain_fallback => [...]] */
    private static array $translations = [];

    // ── Public API ────────────────────────────────────────────────────────

    /**
     * Detect locale from cookie → Accept-Language → $default.
     * Call once during bootstrap before any t() calls.
     */
    public static function detectLocale(string $default = 'en'): void {
        $locale = null;

        if (!empty($_COOKIE['tswebsite_language'])) {
            $locale = self::sanitizeLocale($_COOKIE['tswebsite_language']);
        }

        if ($locale === null && !empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            foreach (self::parseAcceptLanguage($_SERVER['HTTP_ACCEPT_LANGUAGE']) as $lang) {
                $candidate = self::sanitizeLocale($lang);
                if ($candidate !== null) {
                    $locale = $candidate;
                    break;
                }
            }
        }

        self::$locale = $locale ?? $default;
    }

    public static function getLocale(): string {
        return self::$locale;
    }

    /** Explicitly set locale (e.g. from admin language switcher). Clears cache. */
    public static function setLocale(string $locale): void {
        self::$locale = self::sanitizeLocale($locale) ?? 'en';
        self::$translations = [];
    }

    /**
     * Translate $msgid in $domain. Returns $msgid when no translation found.
     * Falls back to English if the active locale has no translation.
     */
    public static function t(string $msgid, string $domain = 'frontend', array $args = []): string {
        self::ensureLoaded($domain);

        $str = self::$translations[$domain][$msgid]
            ?? self::$translations[$domain . '_fallback'][$msgid]
            ?? $msgid;

        return self::interpolate($str, $args);
    }

    /** List locale codes that have a .po file for $domain. */
    public static function availableLocales(string $domain = 'frontend'): array {
        $dir = __PRIVATE_DIR . '/locales';
        if (!is_dir($dir)) return ['en'];
        $locales = [];
        foreach (scandir($dir) as $entry) {
            if ($entry[0] === '.') continue;
            if (file_exists("$dir/$entry/$domain.po")) {
                $locales[] = $entry;
            }
        }
        return $locales ?: ['en'];
    }

    // ── Internal ──────────────────────────────────────────────────────────

    private static function ensureLoaded(string $domain): void {
        if (isset(self::$translations[$domain])) return;

        self::$translations[$domain] = self::loadDomain($domain, self::$locale);

        if (self::$locale !== 'en' && !isset(self::$translations[$domain . '_fallback'])) {
            self::$translations[$domain . '_fallback'] = self::loadDomain($domain, 'en');
        }
    }

    private static function loadDomain(string $domain, string $locale): array {
        $path = __PRIVATE_DIR . "/locales/$locale/$domain.po";
        if (!file_exists($path)) return [];
        return self::parsePo($path);
    }

    /** Parse a .po file → [msgid => msgstr]. Handles multiline strings. */
    public static function parsePo(string $path): array {
        $result   = [];
        $lines    = file($path, FILE_IGNORE_NEW_LINES);
        $msgid    = null;
        $msgstr   = null;
        $inMsgid  = false;
        $inMsgstr = false;

        $unescape = static function (string $s): string {
            return str_replace(
                ['\\n', '\\t', '\\"', '\\\\'],
                ["\n",  "\t",  '"',   '\\'],
                $s
            );
        };

        $extractQuoted = static function (string $line) use ($unescape): ?string {
            if (preg_match('/^"(.*)"$/s', trim($line), $m)) {
                return $unescape($m[1]);
            }
            return null;
        };

        $save = function () use (&$result, &$msgid, &$msgstr): void {
            if ($msgid !== null && $msgid !== '' && $msgstr !== null && $msgstr !== '') {
                $result[$msgid] = $msgstr;
            }
            $msgid = $msgstr = null;
        };

        foreach ($lines as $line) {
            $line = rtrim($line);

            if ($line === '' || $line[0] === '#') {
                if ($msgid !== null) $save();
                $inMsgid = $inMsgstr = false;
                continue;
            }

            if (str_starts_with($line, 'msgid ')) {
                $save();
                $msgid    = $extractQuoted(substr($line, 6));
                $inMsgid  = true;
                $inMsgstr = false;
                continue;
            }

            if (str_starts_with($line, 'msgstr ')) {
                $msgstr   = $extractQuoted(substr($line, 7));
                $inMsgid  = false;
                $inMsgstr = true;
                continue;
            }

            if ($line !== '' && $line[0] === '"') {
                $part = $extractQuoted($line);
                if ($part !== null) {
                    if ($inMsgid  && $msgid  !== null) $msgid  .= $part;
                    if ($inMsgstr && $msgstr !== null) $msgstr .= $part;
                }
            }
        }

        $save();
        return $result;
    }

    private static function interpolate(string $str, array $args): string {
        if (empty($args)) return $str;
        foreach ($args as $i => $v) {
            $str = str_replace('{' . $i . '}', (string)$v, $str);
        }
        return $str;
    }

    /**
     * Sanitize locale: only [a-z0-9_-], max 10 chars.
     * Returns null if no locales/ subdirectory exists for this locale.
     */
    private static function sanitizeLocale(string $raw): ?string {
        $clean = strtolower(preg_replace('/[^a-zA-Z0-9_\-]/', '', $raw));
        $clean = substr($clean, 0, 10);
        if ($clean === '') return null;

        if (is_dir(__PRIVATE_DIR . '/locales/' . $clean)) return $clean;

        // Try language prefix only (e.g. "de" from "de-AT")
        $prefix = explode('-', $clean)[0];
        if ($prefix !== $clean && is_dir(__PRIVATE_DIR . '/locales/' . $prefix)) {
            return $prefix;
        }

        return null;
    }

    /** Parse Accept-Language header, return locales sorted by q-value descending. */
    private static function parseAcceptLanguage(string $header): array {
        $locales = [];
        foreach (explode(',', $header) as $part) {
            $part = trim($part);
            if (preg_match('/^([a-zA-Z\-]+)(?:;q=([\d.]+))?/', $part, $m)) {
                $q         = isset($m[2]) ? (float)$m[2] : 1.0;
                $locales[] = [$m[1], $q];
            }
        }
        usort($locales, fn($a, $b) => $b[1] <=> $a[1]);
        return array_column($locales, 0);
    }
}
