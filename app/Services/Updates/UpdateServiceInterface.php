<?php

namespace Pterodactyl\Services\Updates;

/**
 * Base Update Service Interface
 * 
 * Defines common methods that all update services should implement
 * for consistency and testability.
 */
interface UpdateServiceInterface
{
    /**
     * Get the service name for logging and identification.
     */
    public function getServiceName(): string;

    /**
     * Check if the service is properly configured and ready to use.
     */
    public function isConfigured(): bool;

    /**
     * Get any configuration errors or requirements.
     */
    public function getConfigurationErrors(): array;
}