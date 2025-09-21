<?php

namespace Pterodactyl\Models;

/**
 * UpdateSession Model Alias
 * 
 * This is an alias to maintain backward compatibility with imports
 * that expect Pterodactyl\Models\UpdateSession instead of 
 * Pterodactyl\Models\Updates\UpdateSession
 */
class UpdateSession extends \Pterodactyl\Models\Updates\UpdateSession
{
    // This class extends the actual UpdateSession model in Updates namespace
    // All functionality is inherited from the parent class
}