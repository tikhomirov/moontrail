<?php

declare(strict_types=1);

namespace MoonShine\MoonTrail\Support;

/**
 * URL опубликованного CSS пакета с кеш-бастингом.
 *
 * Браузер кеширует `vendor/moontrail/moontrail.css` агрессивно, а имя файла
 * не меняется между релизами. Без параметра версии после обновления пакета
 * остаётся старая стилевая копия, поэтому в URL добавляется mtime файла.
 */
final class StylesheetAsset
{
    private const RELATIVE_PATH = 'vendor/moontrail/moontrail.css';

    /**
     * Ссылка на таблицу стилей; при отсутствии файла возвращает URL без версии.
     */
    public static function url(): string
    {
        $url = (string) asset(self::RELATIVE_PATH);
        $path = public_path(self::RELATIVE_PATH);
        $modifiedAt = is_file($path) ? filemtime($path) : false;

        if ($modifiedAt === false) {
            return $url;
        }

        return $url . '?v=' . $modifiedAt;
    }
}
