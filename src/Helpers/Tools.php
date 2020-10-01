<?php

declare(strict_types=1);

namespace DBP\API\LocationCheckInBundle\Helpers;


class Tools
{
    /**
     * Convert binary data to a data url.
     */
    public static function getDataURI(string $data, string $mime): string
    {
        return 'data:'.$mime.';base64,'.base64_encode($data);
    }

    public static function getMimeType(string $data): string {
        $info = finfo_open();

        return finfo_buffer($info, $data, FILEINFO_MIME_TYPE);
    }

    public static function getFileExtensionForMimeType(string $mimeType): string {
        $extensions = array(
            'application/pdf' => 'pdf',
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'text/xml' => 'xml'
        );

        return $extensions[$mimeType] ?? 'dump';
    }

    public static function endsWith($haystack, $needle) {
        $length = strlen($needle);

        if (!$length) {
            return true;
        }

        return substr($haystack, -$length) === $needle;
    }
}
