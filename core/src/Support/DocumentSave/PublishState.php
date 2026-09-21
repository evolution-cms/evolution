<?php

namespace EvolutionCMS\Support\DocumentSave;

/**
 * Resolves the published flag, dates and publisher of a document being saved.
 * @since 3.5.9
 */
final class PublishState
{
    /**
     * A publish date in the past publishes, one in the future or a passed unpublish date unpublishes.
     */
    public static function fromDates(int $published, int $pubDate, int $unpubDate, int $now): int
    {
        if ($pubDate > 0 && $pubDate < $now) {
            $published = 1;
        } elseif ($pubDate > $now) {
            $published = 0;
        }
        if ($unpubDate > 0 && $unpubDate < $now) {
            $published = 0;
        }

        return $published;
    }

    /**
     * @return array{published:int, pub_date:int, unpub_date:int, publishedon:int, publishedby:int}
     */
    public static function forNew(int $published, int $pubDate, int $unpubDate, int $now, int $userId, bool $mayPublish): array
    {
        $published = self::fromDates($published, $pubDate, $unpubDate, $now);
        if (!$mayPublish) {
            $published = $pubDate = $unpubDate = 0;
        }

        return [
            'published' => $published,
            'pub_date' => $pubDate,
            'unpub_date' => $unpubDate,
            'publishedon' => $published ? ($pubDate ?: $now) : 0,
            'publishedby' => $published ? $userId : 0,
        ];
    }

    /**
     * @param array $existing the stored row: published, pub_date, unpub_date, publishedon, publishedby
     * @return array{published:int, pub_date:int, unpub_date:int, publishedon:int, publishedby:int}
     */
    public static function forEdit(int $published, int $pubDate, int $unpubDate, int $now, int $userId, bool $mayPublish, array $existing): array
    {
        // without publish_document the publish state stays as it was
        if (!$mayPublish) {
            return [
                'published' => (int) $existing['published'],
                'pub_date' => (int) $existing['pub_date'],
                'unpub_date' => (int) $existing['unpub_date'],
                'publishedon' => (int) $existing['publishedon'],
                'publishedby' => (int) $existing['publishedby'],
            ];
        }

        $published = self::fromDates($published, $pubDate, $unpubDate, $now);
        $wasPublished = (int) $existing['published'];

        if (!$wasPublished && $published) {
            $publishedon = $now;
            $publishedby = $userId;
        } elseif ($pubDate > 0 && $pubDate <= $now && $published) {
            $publishedon = $pubDate;
            $publishedby = $userId;
        } elseif ($wasPublished && !$published) {
            $publishedon = 0;
            $publishedby = 0;
        } else {
            $publishedon = (int) $existing['publishedon'];
            $publishedby = (int) $existing['publishedby'];
        }

        return [
            'published' => $published,
            'pub_date' => $pubDate,
            'unpub_date' => $unpubDate,
            'publishedon' => $publishedon,
            'publishedby' => $publishedby,
        ];
    }
}
