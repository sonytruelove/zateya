<?php

declare(strict_types=1);

namespace Src\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $campaign_id
 * @property string $type
 * @property array<string, mixed> $settings
 */
final class MechanicConfigModel extends Model
{
    protected $table = 'mechanic_configs';
    protected $primaryKey = 'campaign_id';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = true;
    protected $guarded = [];
    protected $casts = ['settings' => 'array'];
}
