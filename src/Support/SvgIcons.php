<?php

declare(strict_types=1);

namespace MoonShine\MoonTrail\Support;

/**
 * Centralised SVG icon library for moontrail UI.
 *
 * All icons are Heroicons-compatible (outline style, 24×24 viewBox).
 * Size is controlled by the caller via the $size parameter (Tailwind w-/h- classes).
 * Extra classes (color, flex-shrink-0, etc.) are passed via $extraClass.
 */
final class SvgIcons
{
    // -------------------------------------------------------------------------
    // Event icons
    // -------------------------------------------------------------------------

    public static function created(string $size = 'w-4 h-4', string $extraClass = ''): string
    {
        $cls = self::cls($size, $extraClass);

        return "<svg class=\"{$cls}\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\">"
            . '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>'
            . '</svg>';
    }

    public static function updated(string $size = 'w-4 h-4', string $extraClass = ''): string
    {
        $cls = self::cls($size, $extraClass);

        return "<svg class=\"{$cls}\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\">"
            . '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>'
            . '</svg>';
    }

    public static function deleted(string $size = 'w-4 h-4', string $extraClass = ''): string
    {
        $cls = self::cls($size, $extraClass);

        return "<svg class=\"{$cls}\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\">"
            . '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>'
            . '</svg>';
    }

    public static function restored(string $size = 'w-4 h-4', string $extraClass = ''): string
    {
        $cls = self::cls($size, $extraClass);

        return "<svg class=\"{$cls}\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\">"
            . '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3"/>'
            . '</svg>';
    }

    public static function rolledBack(string $size = 'w-4 h-4', string $extraClass = ''): string
    {
        $cls = self::cls($size, $extraClass);

        return "<svg class=\"{$cls}\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\">"
            . '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>'
            . '</svg>';
    }

    public static function unknown(string $size = 'w-4 h-4', string $extraClass = ''): string
    {
        $cls = self::cls($size, $extraClass);

        return "<svg class=\"{$cls}\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\">"
            . '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"/>'
            . '</svg>';
    }

    // -------------------------------------------------------------------------
    // UI / section icons
    // -------------------------------------------------------------------------

    public static function info(string $size = 'w-4 h-4', string $extraClass = ''): string
    {
        $cls = self::cls($size, $extraClass);

        return "<svg class=\"{$cls}\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\">"
            . '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>'
            . '</svg>';
    }

    public static function link(string $size = 'w-4 h-4', string $extraClass = ''): string
    {
        $cls = self::cls($size, $extraClass);

        return "<svg class=\"{$cls}\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\">"
            . '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>'
            . '</svg>';
    }

    public static function diff(string $size = 'w-4 h-4', string $extraClass = ''): string
    {
        $cls = self::cls($size, $extraClass);

        return "<svg class=\"{$cls}\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\">"
            . '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>'
            . '</svg>';
    }

    public static function clock(string $size = 'w-4 h-4', string $extraClass = ''): string
    {
        $cls = self::cls($size, $extraClass);

        return "<svg class=\"{$cls}\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\">"
            . '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>'
            . '</svg>';
    }

    public static function externalLink(string $size = 'w-3 h-3', string $extraClass = ''): string
    {
        $cls = self::cls($size, $extraClass);

        return "<svg class=\"{$cls}\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\">"
            . '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>'
            . '</svg>';
    }

    public static function user(string $size = 'w-3.5 h-3.5', string $extraClass = ''): string
    {
        $cls = self::cls($size, $extraClass);

        return "<svg class=\"{$cls}\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\">"
            . '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>'
            . '</svg>';
    }

    public static function computer(string $size = 'w-4 h-4', string $extraClass = ''): string
    {
        $cls = self::cls($size, $extraClass);

        return "<svg class=\"{$cls}\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\">"
            . '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>'
            . '</svg>';
    }

    public static function document(string $size = 'w-5 h-5', string $extraClass = ''): string
    {
        $cls = self::cls($size, $extraClass);

        return "<svg class=\"{$cls}\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\">"
            . '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>'
            . '</svg>';
    }

    public static function dotsHorizontal(string $size = 'w-5 h-5', string $extraClass = ''): string
    {
        $cls = self::cls($size, $extraClass);

        return "<svg class=\"{$cls}\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\">"
            . '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h.01M12 12h.01M19 12h.01M6 12a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0z"/>'
            . '</svg>';
    }

    public static function error(string $size = 'w-4 h-4', string $extraClass = ''): string
    {
        $cls = self::cls($size, $extraClass);

        return "<svg class=\"{$cls}\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\">"
            . '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>'
            . '</svg>';
    }

    public static function check(string $size = 'w-3 h-3', string $extraClass = ''): string
    {
        $cls = self::cls($size, $extraClass);

        return "<svg class=\"{$cls}\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\">"
            . '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>'
            . '</svg>';
    }

    public static function chevronDown(string $size = 'w-3 h-3', string $extraClass = ''): string
    {
        $cls = self::cls($size, $extraClass);

        return "<svg class=\"{$cls}\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\">"
            . '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>'
            . '</svg>';
    }

    public static function filter(string $size = 'w-3 h-3', string $extraClass = ''): string
    {
        $cls = self::cls($size, $extraClass);

        return "<svg class=\"{$cls}\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\">"
            . '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2a1 1 0 01-.293.707L13 13.414V19a1 1 0 01-.553.894l-4 2A1 1 0 017 21v-7.586L3.293 6.707A1 1 0 013 6V4z"/>'
            . '</svg>';
    }

    // -------------------------------------------------------------------------
    // Internal helpers
    // -------------------------------------------------------------------------

    private static function cls(string $size, string $extraClass): string
    {
        return trim($size . ($extraClass !== '' ? ' ' . $extraClass : ''));
    }
}
