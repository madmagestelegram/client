<?php declare( strict_types=1 );

namespace MadmagesTelegram\Client\Exception;

use Throwable;

/**
 * Base api exception
 */
class ApiException extends ResponseException
{

    /** @var array */
    private $error;

    public function __construct(
        array $error,
        string $message,
        int $code,
        Throwable $previous
    ) {
        parent::__construct($message, $code, $previous);
        $this->error = $error;
    }

    /**
     * @return array
     */
    public function getError(): array
    {
        return $this->error;
    }
}