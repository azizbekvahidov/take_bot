<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BotUser extends Model
{
    protected $primaryKey = 'chat_id';

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    protected $fillable = ['chat_id', 'name', 'phone', 'username', 'is_finished', 'language'];

    /**
     * @return string
     */
    public function phone(): string
    {
        preg_match("/(998)(\d{2})(\d{3})(\d{2})(\d{2})/", $this->phone, $matches);
        return "+{$matches[1]} {$matches[2]} {$matches[3]} {$matches[4]} {$matches[5]}";
    }
}
