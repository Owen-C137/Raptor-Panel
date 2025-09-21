<?php

namespace Pterodactyl\Models;

/**
 * PanelVersion Model Alias
 * 
 * This is an alias to maintain backward compatibility with imports
 * that expect Pterodactyl\Models\PanelVersion instead of 
 * Pterodactyl\Models\Updates\PanelVersion
 */
class PanelVersion extends \Pterodactyl\Models\Updates\PanelVersion
{
    // This class extends the actual PanelVersion model in Updates namespace
    // All functionality is inherited from the parent class
}