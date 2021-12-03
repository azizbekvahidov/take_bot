<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Basket extends Model
{
    protected $guarded = ['id'];

    /**
     * @return string
     */
    public function phone(): string
    {
        preg_match("/(998)(\d{2})(\d{3})(\d{2})(\d{2})/", $this->phone, $matches);
        return "+{$matches[1]} {$matches[2]} {$matches[3]} {$matches[4]} {$matches[5]}";
    }
}
