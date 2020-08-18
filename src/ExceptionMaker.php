<?php declare(strict_types = 1);

namespace MadmagesTelegram\Client;

use MadmagesTelegram\Client\Exception\ApiException;
use MadmagesTelegram\Client\Exception\BotBlockedByUserException;
use MadmagesTelegram\Client\Exception\QueryIsTooOldException;
use MadmagesTelegram\Client\Exception\ReplyMessageNotFound;
use Throwable;

class ExceptionMaker
{

    private const DESCRIPTION_BLOCKED_BY_USER                = 'Forbidden: bot was blocked by the user';
    private const DESCRIPTION_QUERY_IS_TOO_OLD_OR_ID_INVALID = 'Bad Request: query is too old and response timeout expired or query ID is invalid';
    private const DESCRIPTION_REPLY_NOT_FOUND                = 'Bad Request: reply message not found';

    /**
     * @param array     $error
     * @param Throwable $throwable
     * @return Throwable
     */
    public static function make(array $error, Throwable $throwable): Throwable
    {
        if (!isset($error['description'], $error['error_code'], $error['ok'])) {
            return $throwable;
        }

        // Should be false
        if ($error['ok']) {
            return $throwable;
        }

        if ($error['description'] === self::DESCRIPTION_BLOCKED_BY_USER) {
            return new BotBlockedByUserException($error, $error['description'], $error['error_code'], $throwable);
        }

        if ($error['description'] === self::DESCRIPTION_QUERY_IS_TOO_OLD_OR_ID_INVALID) {
            return new QueryIsTooOldException($error, $error['description'], $error['error_code'], $throwable);
        }

        if ($error['description'] === self::DESCRIPTION_REPLY_NOT_FOUND) {
            return new ReplyMessageNotFound($error, $error['description'], $error['error_code'], $throwable);
        }

        return new ApiException($error, $error['description'], $error['error_code'], $throwable);
    }
}