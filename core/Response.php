<?php

namespace Core;

class Response
{
    public static function redirect(string $path, int $code = 302): never
    {
        $target = str_starts_with($path, 'http') ? $path : url($path);
        header("Location: {$target}", true, $code);
        exit;
    }

    public static function json(array $data, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public static function xml(string $xml, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/xml; charset=UTF-8');
        echo $xml;
        exit;
    }

    public static function text(string $text, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: text/plain; charset=UTF-8');
        echo $text;
        exit;
    }
}
