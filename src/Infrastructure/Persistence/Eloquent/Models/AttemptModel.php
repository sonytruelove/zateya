<?php

declare(strict_types=1);

namespace Src\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $campaign_id
 * @property string $participant_id
 * @property bool $won
 * @property int $score
 * @property string $detail
 * @property string|null $prize_id
 * @property string|null $promo_code
 * @property string $played_at
 */
final class AttemptModel extends Model
{
    protected $table = 'attempts';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;
    protected $guarded = [];
    protected $casts = ['won' => 'boolean', 'score' => 'integer'];
}
