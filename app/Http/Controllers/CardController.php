<?php

namespace App\Http\Controllers;

use App\Http\Resources\CardResource;
use App\Models\Card;
use App\Models\CardList;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

/**
 * @OA\Tag(
 *     name="Cards",
 *     description=""
 * )
 */
class CardController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * @OA\Get(
     *   path="/cards",
     *   security={{"bearer_token": {} }},
     *   tags={"Cards"},
     *   @OA\Parameter(
     *     name="card_list_id",
     *     required=true,
     *     in="query",
     *     @OA\Schema(type="string")
     *   ),
     *   description="Get cards",
     *   operationId="get_cards",
     *   @OA\Response(
     *     response=200,
     *     description="Success",
     *   ),
     * )
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
     * @OA\Post(
     *   path="/cards",
     *   security={{"bearer_token": {} }},
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\MediaType(
     *       mediaType="multipart/form-data",
     *       @OA\Schema(
     *         required={"card_list_id", "title", "order"},
     *         @OA\Property(property="card_list_id", type="integer", example=""),
     *         @OA\Property(property="title", type="string", example=""),
     *         @OA\Property(property="order", type="integer", example=""),
     *       )
     *     )
     *   ),
     *   tags={"Cards"},
     *   description="Create new card",
     *   operationId="create_new_card",
     *   @OA\Response(
     *     response=200,
     *     description="successful operation",
     *   ),
     * )
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
     * @OA\Get(
     *   path="/cards/{id}",
     *   security={{"bearer_token": {} }},
     *   tags={"Cards"},
     *   description="Get specific card",
     *   operationId="get_card",
     *   @OA\Parameter(
     *     name="id",
     *     required=true,
     *     in="path",
     *     @OA\Schema(type="string")
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Success",
     *   ),
     * )
    */
    public function show(Card $card)
    {
        if($card->cardList->board->user_id != Auth::user()->id)
            throw new AuthorizationException();

        return new CardResource($card);
    }

    /**
     * @OA\Post(
     *   path="/cards/{id}",
     *   @OA\Parameter(
     *     in="path",
     *     name="id",
     *     required=true,
     *     @OA\Schema(type="string"),
     *   ),
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\MediaType(
     *       mediaType="multipart/form-data",
     *       @OA\Schema(
     *         @OA\Property(property="title", type="string", example=""),
     *         @OA\Property(property="description", type="string", example=""),
     *         @OA\Property(property="checked", type="string", enum={"0","1"}),
     *         @OA\Property(property="priority", type="string", enum={"low","medium","hight","urgent"}),
     *         @OA\Property(property="color", type="string", example=""),
     *         @OA\Property(property="_method", type="string", format="string", example="PUT"),
     *       )
     *     )
     *   ),
     *   security={{"bearer_token": {} }},
     *   tags={"Cards"},
     *   description="Edit specific card",
     *   operationId="edit_card",
     *   @OA\Response(
     *     response="200",
     *     description="Success"
     *   ),
     * )
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

    /**
     * @OA\Post(
     *   path="/cards/{id}/reorder",
     *   @OA\Parameter(
     *     in="path",
     *     name="id",
     *     required=true,
     *     @OA\Schema(type="string"),
     *   ),
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\MediaType(
     *       mediaType="multipart/form-data",
     *       @OA\Schema(
     *         @OA\Property(property="card_list_id", type="integer", example=""),
     *         @OA\Property(property="order", type="integer", example=""),
     *         @OA\Property(property="_method", type="string", format="string", example="PUT"),
     *       )
     *     )
     *   ),
     *   security={{"bearer_token": {} }},
     *   tags={"Cards"},
     *   description="Edit specific card order",
     *   operationId="edit_card_order",
     *   @OA\Response(
     *     response="200",
     *     description="Success"
     *   ),
     * )
    */
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
     * @OA\Delete(
     *   path="/cards/{id}",
     *   @OA\Parameter(
     *     in="path",
     *     name="id",
     *     required=true,
     *     @OA\Schema(type="string"),
     *   ),
     *   security={{"bearer_token": {} }},
     *   tags={"Cards"},
     *   description="Delete specific card",
     *   operationId="delete_card",
     *   @OA\Response(
     *     response=200,
     *     description="Success"
     *   )
     * )
    */
    public function destroy(Card $card)
    {
        if($card->cardList->board->user_id != Auth::user()->id)
            throw new AuthorizationException();

        $card->delete();

        return response()->json(['message' => 'Card deleted successfully']);
    }
}
