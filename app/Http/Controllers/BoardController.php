<?php

namespace App\Http\Controllers;

use App\Http\Resources\BoardResource;
use App\Models\Board;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BoardController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $boards = Board::where('user_id', Auth::user()->id)->get();
        return BoardResource::collection($boards);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $board = Board::create([
            'name' => $request->name,
            'user_id' => Auth::user()->id,
        ]);

        return new BoardResource($board);
    }

    /**
     * Display the specified resource.
     */
    public function show(Board $board)
    {
        if($board->user_id != Auth::user()->id)
            throw new AuthorizationException();

        return new BoardResource($board);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Board $board)
    {
        if($board->user_id != Auth::user()->id)
            throw new AuthorizationException();

        $request->validate([
            'name' => ['string', 'max:255'],
        ]);

        $board->update([
            'name' => $request->name??$board->name,
        ]);

        return new BoardResource($board);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Board $board)
    {
        if($board->user_id != Auth::user()->id)
            throw new AuthorizationException();

        $board->delete();

        return response()->json(['message' => 'Board deleted successfully']);
    }
}
