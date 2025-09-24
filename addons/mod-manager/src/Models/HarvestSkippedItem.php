<?php

namespace PterodactylAddons\ModManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HarvestSkippedItem extends Model
{
    protected $table = 'mod_harvest_skipped_items';
    public $timestamps = false; // only created_at

    protected $fillable = [
        'harvest_log_id',
        'session_id',
        'item_type',
        'curse_id',
        'parent_curse_mod_id',
        'reason_code',
        'http_status',
        'endpoint',
        'message',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function harvestLog(): BelongsTo
    {
        return $this->belongsTo(DirectHarvestLog::class, 'harvest_log_id');
    }
}
