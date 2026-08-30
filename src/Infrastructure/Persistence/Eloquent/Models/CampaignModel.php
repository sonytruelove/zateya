<?php

declare(strict_types=1);

namespace Src\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $slug
 * @property string $title
 * @property string $mechanic
 * @property string $status
 * @property string $starts_at
 * @property string $ends_at
 * @property string $color_hex
 * @property string $emoji
 * @property int $attempts_per_participant
 */
final class CampaignModel extends Model
{
    protected $table = 'campaigns';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = true;
    protected $guarded = [];
}
