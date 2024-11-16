<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Card extends Model
{
    use HasFactory;

    protected $fillable = [
        'card_list_id',
        'title',
        'description',
        'order',
        'checked',
        'priority',
        'color',
    ];

    public function cardList()
    {
        return $this->belongsTo(CardList::class);
    }
}
