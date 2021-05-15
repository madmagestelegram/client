<?php declare(strict_types=1);

namespace MadmagesTelegram\Client;

use MadmagesTelegram\Client\Exception\ApiException;
use MadmagesTelegram\Client\Exception\BotBlockedByUserException;
use MadmagesTelegram\Client\Exception\QueryIsTooOldException;
use MadmagesTelegram\Client\Exception\ReplyMessageNotFound;
use Throwable;

class ExceptionResolver
{

    private const DESCRIPTION_BLOCKED_BY_USER = 'Forbidden: bot was blocked by the user';
    private const DESCRIPTION_QUERY_IS_TOO_OLD_OR_ID_INVALID = 'Bad Request: query is too old and response timeout expired or query ID is invalid';
    private const DESCRIPTION_REPLY_NOT_FOUND = 'Bad Request: reply message not found';

    private const EXCEPTION_TO_DESCRIPTION_MAP = [
        BotBlockedByUserException::class => self::DESCRIPTION_BLOCKED_BY_USER,
        QueryIsTooOldException::class => self::DESCRIPTION_QUERY_IS_TOO_OLD_OR_ID_INVALID,
        ReplyMessageNotFound::class => self::DESCRIPTION_REPLY_NOT_FOUND,
    ];

    /**
     * @param array $error
     * @param Throwable $throwable
     * @return Throwable
     */
    public static function resolve(array $error, Throwable $throwable): Throwable
    {
        if (!isset($error['description'], $error['error_code'], $error['ok'])) {
            return $throwable;
        }

        // Should be false
        if ($error['ok']) {
            return $throwable;
        }

        foreach (self::EXCEPTION_TO_DESCRIPTION_MAP as $class => $description) {
            if ($error['description'] === $description) {
                return new $class($error, $error['description'], $error['error_code'], $throwable);
            }
        }

        return new ApiException($error, $error['description'], $error['error_code'], $throwable);
    }
}