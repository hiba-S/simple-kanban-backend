<?php

namespace App\Http\Controllers;

use App\Http\Resources\CardListResource;
use App\Models\Board;
use App\Models\CardList;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

/**
 * @OA\Tag(
 *     name="Lists",
 *     description=""
 * )
 */
class CardListController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * @OA\Get(
     *   path="/lists",
     *   security={{"bearer_token": {} }},
     *   tags={"Lists"},
     *   @OA\Parameter(
     *     name="board_id",
     *     required=true,
     *     in="query",
     *     @OA\Schema(type="string")
     *   ),
     *   description="Get lists",
     *   operationId="get_lists",
     *   @OA\Response(
     *     response=200,
     *     description="Success",
     *   ),
     * )
    */
    public function index(Request $request)
    {
        $request->validate([
            'board_id' => ['required', 'integer', 'exists:boards,id'],
        ]);

        $board = Board::find($request->board_id);
        if($board->user_id != Auth::user()->id)
            throw new AuthorizationException();

        $lists = $board->cardLists;
        // $card_list = CardList::where('board_id', $request->board_id)->get();
        return CardListResource::collection($lists);
    }

    /**
     * @OA\Post(
     *   path="/lists",
     *   security={{"bearer_token": {} }},
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\MediaType(
     *       mediaType="multipart/form-data",
     *       @OA\Schema(
     *         required={"board_id", "name", "order"},
     *         @OA\Property(property="board_id", type="integer", example=""),
     *         @OA\Property(property="name", type="string", example=""),
     *         @OA\Property(property="order", type="integer", example=""),
     *       )
     *     )
     *   ),
     *   tags={"Lists"},
     *   description="Create new list",
     *   operationId="create_new_list",
     *   @OA\Response(
     *     response=200,
     *     description="successful operation",
     *   ),
     * )
    */
    public function store(Request $request)
    {
        $request->validate([
            'board_id' => ['required', 'integer', 'exists:boards,id'],
            'name' => ['required', 'string', 'max:255'],
            'order' => ['required', 'integer', 'min:0'],
        ]);


        $listsCount = CardList::where('board_id', $request->board_id)->count();
        if($request->order > $listsCount)
            throw ValidationException::withMessages(['order' => __('Order should not be bigger than '.$listsCount)]);

        $lists = CardList::where('board_id', $request->board_id)->where('order', '>=', $request->order)->increment('order');
        $list = CardList::create([
            'board_id' => $request->board_id,
            'name' => $request->name,
            'order' => $request->order,
        ]);

        return new CardListResource($list);
    }

    /**
     * @OA\Get(
     *   path="/lists/{id}",
     *   security={{"bearer_token": {} }},
     *   tags={"Lists"},
     *   description="Get specific list",
     *   operationId="get_list",
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
    public function show(CardList $list)
    {
        if($list->board->user_id != Auth::user()->id)
            throw new AuthorizationException();

        return new CardListResource($list);
    }

    /**
     * @OA\Post(
     *   path="/lists/{id}",
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
     *         @OA\Property(property="name", type="string", example=""),
     *         @OA\Property(property="order", type="string", example=""),
     *         @OA\Property(property="_method", type="string", format="string", example="PUT"),
     *       )
     *     )
     *   ),
     *   security={{"bearer_token": {} }},
     *   tags={"Lists"},
     *   description="Edit specific list",
     *   operationId="edit_list",
     *   @OA\Response(
     *     response="200",
     *     description="Success"
     *   ),
     * )
    */
    public function update(Request $request, CardList $list)
    {
        if($list->board->user_id != Auth::user()->id)
            throw new AuthorizationException();

        $request->validate([
            'name' => ['string', 'max:255'],
            'order' => ['integer', 'min:0'],
        ]);

        if($request->order){
            $listsCount = CardList::where('board_id', $request->board_id)->count();
            if($request->order >= $listsCount)
                throw ValidationException::withMessages(['order' => __('Order should not be bigger than '.($listsCount-1))]);

            if($request->order>$list->order){
                CardList::where('board_id', $request->board_id)
                            ->where('order', '<=', $request->order)
                            ->where('order', '>', $list->order)
                            ->decrement('order');
            }else{
                CardList::where('board_id', $request->board_id)
                            ->where('order', '>=', $request->order)
                            ->where('order', '<', $list->order)
                            ->increment('order');
            }
        }

        $list->update([
            'name' => $request->name??$list->name,
            'order' => $request->order??$list->order,
        ]);

        return new CardListResource($list);
    }

    /**
     * @OA\Delete(
     *   path="/lists/{id}",
     *   @OA\Parameter(
     *     in="path",
     *     name="id",
     *     required=true,
     *     @OA\Schema(type="string"),
     *   ),
     *   security={{"bearer_token": {} }},
     *   tags={"Lists"},
     *   description="Delete specific list",
     *   operationId="delete_list",
     *   @OA\Response(
     *     response=200,
     *     description="Success"
     *   )
     * )
    */
    public function destroy(CardList $list)
    {
        if($list->board->user_id != Auth::user()->id)
            throw new AuthorizationException();

        CardList::where('board_id', $list->board_id)
                    ->where('order', '>', $list->order)
                    ->decrement('order');
        $list->delete();

        return response()->json(['message' => 'List deleted successfully']);
    }
}
