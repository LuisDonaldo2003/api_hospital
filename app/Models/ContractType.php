<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ContractType extends Model
{
    use SoftDeletes;

    protected $fillable = ['name', 'state'];

    // Relaciones futuras (si usuarios están relacionados)
    // public function users() {
    //     return $this->hasMany(User::class);
    // }}
}
