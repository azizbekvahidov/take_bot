<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property-read string $action
 * @property-read string $sub_action
 * @property-read string $bot_user_id
 */
class Action extends Model
{
    protected $guarded = ['id'];
}
