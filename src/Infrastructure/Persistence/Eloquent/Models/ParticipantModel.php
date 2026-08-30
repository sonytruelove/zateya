<?php

declare(strict_types=1);

namespace Src\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $campaign_id
 * @property string $channel
 * @property string $external_id
 * @property string $display_name
 * @property string $registered_at
 */
final class ParticipantModel extends Model
{
    protected $table = 'participants';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;
    protected $guarded = [];
}
