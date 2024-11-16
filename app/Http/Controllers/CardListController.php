<?php

namespace App\Http\Controllers;

use App\Http\Resources\CardListResource;
use App\Models\Board;
use App\Models\CardList;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class CardListController extends Controller
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
     * Store a newly created resource in storage.
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
     * Display the specified resource.
     */
    public function show(CardList $list)
    {
        if($list->board->user_id != Auth::user()->id)
            throw new AuthorizationException();

        return new CardListResource($list);
    }

    /**
     * Update the specified resource in storage.
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
     * Remove the specified resource from storage.
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
