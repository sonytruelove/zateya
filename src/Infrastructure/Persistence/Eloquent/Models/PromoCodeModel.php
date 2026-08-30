<?php

declare(strict_types=1);

namespace Src\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $campaign_id
 * @property string $code
 * @property string $code_upper
 * @property string|null $issued_to_participant_id
 * @property string|null $issued_at
 */
final class PromoCodeModel extends Model
{
    protected $table = 'promo_codes';
    public $incrementing = true;
    public $timestamps = false;
    protected $guarded = [];
}
