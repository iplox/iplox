<?php

namespace Iplox\Http;

class StatusCode {
    // 2xx Success
    const OK = 200;
    const CREATED = 201;
    const ACCEPTED = 202;

    // 3xx Redirection
    const MOVE_PERMANENTLY = 301;
    const NOT_MODIFIED = 304;
    const TEMPORARY_REDIRECT = 307;

    // 4xx Server Errors
    const BAD_REQUEST = 400;
    const UNAUTHORIZED = 401;
    const FORBIDDEN = 403;
    const NOT_FOUND = 404;
    const METHOD_NOT_ALLOWED = 405;
    const NOT_ACCEPTABLE = 406;

    // 5xx Server Errors
    const SERVER_ERROR = 500;
    const NOT_IMPLEMENTED = 501;
    const SERVICE_UNAVAILABLE = 503;
}
