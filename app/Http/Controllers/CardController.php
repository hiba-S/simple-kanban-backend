<?php

namespace App\Http\Controllers;

use App\Http\Resources\CardResource;
use App\Models\Card;
use App\Models\CardList;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class CardController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $request->validate([
            'card_list_id' => ['required', 'integer', 'exists:card_lists,id'],
        ]);

        $card_list = CardList::find($request->card_list_id);
        if($card_list->board->user_id != Auth::user()->id)
            throw new AuthorizationException();

        $cards = $card_list->cards;
        return CardResource::collection($cards);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'card_list_id' => ['required', 'integer', 'exists:card_lists,id'],
            'title' => ['required', 'string', 'max:255'],
            'order' => ['required', 'integer', 'min:0'],
        ]);

        $card_list = CardList::find($request->card_list_id);
        if($card_list->board->user_id != Auth::user()->id)
            throw new AuthorizationException();

        $cardsCount = Card::where('card_list_id', $request->card_list_id)->count();
        if($request->order > $cardsCount)
            throw ValidationException::withMessages(['order' => __('Order should not be bigger than '.$cardsCount)]);

        $cards = Card::where('card_list_id', $request->card_list_id)->where('order', '>=', $request->order)->increment('order');
        $card = Card::create([
            'card_list_id' => $request->card_list_id,
            'title' => $request->title,
            'order' => $request->order,
        ]);

        return new CardResource($card);
    }

    /**
     * Display the specified resource.
     */
    public function show(Card $card)
    {
        if($card->cardList->board->user_id != Auth::user()->id)
            throw new AuthorizationException();

        return new CardResource($card);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Card $card)
    {
        $request->validate([
            'title' => ['string', 'max:255'],
            'description' => ['string'],
            'checked' => ['boolean'],
            'priority' => ['string', 'in:low,medium,hight,urgent'],
            'color' => ['string', 'regex:/^#(?:[0-9a-fA-F]{3}){1,2}$/i'],
        ]);

        if($card->cardList->board->user_id != Auth::user()->id)
            throw new AuthorizationException();

        $card->update([
            'title' => $request->title??$card->title,
            'description' => $request->description??$card->description,
            'checked' => ($request->checked!==null)?$request->checked:$card->checked,
            'priority' => $request->priority??$card->priority,
            'color' => $request->color??$card->color,
        ]);

        return new CardResource($card);
    }

    public function reorder(Request $request, Card $card)
    {
        $request->validate([
            'card_list_id' => ['required', 'integer', 'exists:card_lists,id'],
            'order' => ['required', 'integer', 'min:0'],
        ]);

        if($card->cardList->board->user_id != Auth::user()->id)
            throw new AuthorizationException();

        if($request->card_list_id == $card->card_list_id){
            $cardCount = Card::where('card_list_id', $request->card_list_id)->count();
            if($request->order >= $cardCount)
                throw ValidationException::withMessages(['order' => __('Order should not be bigger than '.($cardCount-1))]);

            if($request->order>$request->order){
                Card::where('card_list_id', $card->card_list_id)
                    ->where('order', '<=', $request->order)
                    ->where('order', '>', $card->order)
                    ->decrement('order');
            }else{
                Card::where('card_list_id', $card->card_list_id)
                    ->where('order', '>=', $request->order)
                    ->where('order', '<', $card->order)
                    ->increment('order');
            }
        }else{
            Card::where('card_list_id', $card->card_list_id)
                ->where('order', '>', $card->order)
                ->decrement('order');

            Card::where('card_list_id', $request->card_list_id)
                ->where('order', '>=', $request->order)
                ->increment('order');
        }

        $card->update([
            'card_list_id' => $request->card_list_id??$card->card_list_id,
            'order' => $request->order??$card->order,
        ]);

        return new CardResource($card);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Card $card)
    {
        if($card->cardList->board->user_id != Auth::user()->id)
            throw new AuthorizationException();

        $card->delete();

        return response()->json(['message' => 'Card deleted successfully']);
    }
}
