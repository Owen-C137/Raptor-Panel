<?php

namespace PterodactylAddons\ModManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Pterodactyl\Models\User;
use Pterodactyl\Models\Server;

class ModInstallation extends Model
{
    protected $table = 'mod_installations';

    protected $fillable = [
        'user_id',
        'server_id',
        'mod_id',
        'file_id',
        'installation_path',
        'status',
        'installed_version',
        'target_version',
        'is_enabled',
        'auto_update',
        'installed_at',
        'removed_at',
        'last_check_at',
        'error_message',
        'retry_count',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'server_id' => 'integer',
        'mod_id' => 'integer',
        'file_id' => 'integer',
        'is_enabled' => 'boolean',
        'auto_update' => 'boolean',
        'installed_at' => 'datetime',
        'removed_at' => 'datetime',
        'last_check_at' => 'datetime',
        'retry_count' => 'integer',
    ];

    /**
     * Get the user who installed the mod
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the server this mod is installed on
     */
    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    /**
     * Get the mod that was installed
     */
    public function mod(): BelongsTo
    {
        return $this->belongsTo(Mod::class);
    }

    /**
     * Get the mod file that was installed
     */
    public function file(): BelongsTo
    {
        return $this->belongsTo(ModFile::class);
    }
}