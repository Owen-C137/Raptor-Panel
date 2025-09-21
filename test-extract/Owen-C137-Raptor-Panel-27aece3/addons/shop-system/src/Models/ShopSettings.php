<?php

namespace PterodactylAddons\ShopSystem\Models;

use Illuminate\Database\Eloquent\Model;

class ShopSettings extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'shop_settings';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'key',
        'value',
        'type',
        'description',
        'group',
        'is_public',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'is_public' => 'boolean',
    ];

    /**
     * Get setting value with proper type casting.
     */
    public function getCastedValue()
    {
        switch ($this->type) {
            case 'boolean':
                return filter_var($this->value, FILTER_VALIDATE_BOOLEAN);
            case 'integer':
                return (int) $this->value;
            case 'float':
                return (float) $this->value;
            case 'json':
                return json_decode($this->value, true);
            case 'array':
                return json_decode($this->value, true);
            default:
                return $this->value;
        }
    }

    /**
     * Set setting value with proper type handling.
     */
    public function setCastedValue($value): void
    {
        switch ($this->type) {
            case 'boolean':
                $this->value = $value ? 'true' : 'false';
                break;
            case 'json':
            case 'array':
                $this->value = json_encode($value);
                break;
            default:
                $this->value = (string) $value;
                break;
        }
    }

    /**
     * Scope to get settings by group.
     */
    public function scopeGroup($query, $group)
    {
        return $query->where('group', $group);
    }

    /**
     * Scope to get public settings.
     */
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    /**
     * Get a setting value by key.
     */
    public static function getValue(string $key, $default = null)
    {
        $setting = static::where('key', $key)->first();
        
        if (!$setting) {
            return $default;
        }

        return $setting->getCastedValue();
    }

    /**
     * Set a setting value by key.
     */
    public static function setValue(string $key, $value, string $type = 'string'): void
    {
        $setting = static::firstOrNew(['key' => $key]);
        $setting->type = $type;
        $setting->setCastedValue($value);
        $setting->save();
    }
}
