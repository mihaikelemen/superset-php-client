<?php

declare(strict_types=1);

namespace Superset\Enum;

enum HttpStatusCode: int
{
    case HTTP_UNKNOWN = 0;
    case HTTP_OK = 200;
    case HTTP_CREATED = 201;
    case HTTP_ACCEPTED = 202;
    case HTTP_NO_CONTENT = 204;
    case HTTP_BAD_REQUEST = 400;
    case HTTP_UNAUTHORIZED = 401;
    case HTTP_FORBIDDEN = 403;
    case HTTP_NOT_FOUND = 404;
    case HTTP_METHOD_NOT_ALLOWED = 405;
    case HTTP_CONFLICT = 409;
    case HTTP_GONE = 410;
    case HTTP_PRECONDITION_FAILED = 412;
    case HTTP_INTERNAL_SERVER_ERROR = 500;
    case HTTP_SERVICE_UNAVAILABLE = 503;

    public function message(): string
    {
        return match ($this) {
            self::HTTP_OK => 'OK',
            self::HTTP_CREATED => 'Created',
            self::HTTP_ACCEPTED => 'Accepted',
            self::HTTP_NO_CONTENT => 'No Content',
            self::HTTP_BAD_REQUEST => 'Bad Request',
            self::HTTP_UNAUTHORIZED => 'Unauthorized',
            self::HTTP_FORBIDDEN => 'Forbidden',
            self::HTTP_NOT_FOUND => 'Not Found',
            self::HTTP_METHOD_NOT_ALLOWED => 'Method Not Allowed',
            self::HTTP_CONFLICT => 'Conflict',
            self::HTTP_GONE => 'Gone',
            self::HTTP_PRECONDITION_FAILED => 'Precondition Failed',
            self::HTTP_INTERNAL_SERVER_ERROR => 'Internal Server Error',
            self::HTTP_SERVICE_UNAVAILABLE => 'Service Unavailable',
            self::HTTP_UNKNOWN => 'Unknown',
        };
    }
}
