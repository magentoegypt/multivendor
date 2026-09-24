<?php
declare(strict_types=1);

namespace MagentoEgypt\CheckoutExtend\Plugin\GraphQl;

use GraphQL\Error\ClientAware;
use Magento\Framework\App\State;
use Magento\Framework\Exception\AggregateExceptionInterface;
use Magento\Framework\GraphQl\Query\ErrorHandler;
use Psr\Log\LoggerInterface;

/**
 * Hub Market — TC45 "critical lines" (2026-09-24).
 *
 * Core ErrorHandler::handle() logs EVERY GraphQL error at ERROR level, including
 * ones caused purely by the caller: syntax/validation errors, bad input, unknown
 * cart, not authorised. Bots probing /graphql (e.g. handlePayflowProResponse with a
 * made-up cart) therefore showed up as "critical lines" although nothing was wrong.
 *
 * When every error in the response is client-safe — webonyx marks an error safe only
 * if it has no cause or its cause is itself client-safe (GraphQlInputException,
 * GraphQlNoSuchEntityException, GraphQlAuthorizationException, ...) — log them at
 * INFO instead. The response is built exactly as core builds it. If ANY error is a
 * server-side failure (PDOException, InvalidArgumentException, ...) core runs
 * unchanged, so real problems are still logged at ERROR.
 */
class ClientErrorLogLevel
{
    /** @var LoggerInterface */
    private $logger;

    /** @var State */
    private $appState;

    public function __construct(LoggerInterface $logger, State $appState)
    {
        $this->logger = $logger;
        $this->appState = $appState;
    }

    /**
     * @param ErrorHandler $subject
     * @param callable $proceed
     * @param array $errors
     * @param callable $formatter
     * @return array
     */
    public function aroundHandle(ErrorHandler $subject, callable $proceed, array $errors, callable $formatter): array
    {
        foreach ($errors as $error) {
            if (!$error instanceof ClientAware || !$error->isClientSafe()) {
                return $proceed($errors, $formatter);
            }
        }

        // Same as core: outside developer mode only the first error is reported.
        if ($this->appState->getMode() !== State::MODE_DEVELOPER) {
            $errors = array_splice($errors, 0, 1);
        }

        $formattedErrors = [];
        foreach ($errors as $error) {
            $this->logger->info('GraphQL client error: ' . $error->getMessage());
            $previousError = $error->getPrevious();
            if ($previousError instanceof AggregateExceptionInterface && !empty($previousError->getErrors())) {
                foreach ($previousError->getErrors() as $aggregatedError) {
                    $this->logger->info('GraphQL client error: ' . $aggregatedError->getMessage());
                    $formattedErrors[] = $formatter($aggregatedError);
                }
            } else {
                $formattedErrors[] = $formatter($error);
            }
        }
        return $formattedErrors;
    }
}
