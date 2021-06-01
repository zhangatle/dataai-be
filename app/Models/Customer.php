<?php

namespace App\Models;

use Dcat\Admin\Traits\HasDateTimeFormatter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class Customer
 * @package App\Models
 *
 */
class Customer extends Model
{
    public function user(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
