<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

class Reservation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'mew_id',
        'customer_name',
        'customer_email',
        'start_date',
        'end_date',
        'status',
        'number_of_guests',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'number_of_guests' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Global scope to filter only active reservations by default
     */
    protected static function booted(): void
    {
        static::addGlobalScope('active', function (Builder $builder) {
            $builder->whereIn('status', ['pending', 'confirmed']);
        });
    }

    /**
     * Relationship: A reservation belongs to a mew
     */
    public function mew()
    {
        return $this->belongsTo(Mew::class);
    }
}
