<?php

declare(strict_types=1);

namespace Src\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $campaign_id
 * @property string $title
 * @property int $total_quantity
 * @property int $remaining
 */
final class PrizeModel extends Model
{
    protected $table = 'prizes';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = true;
    protected $guarded = [];
    protected $casts = ['total_quantity' => 'integer', 'remaining' => 'integer'];
}
