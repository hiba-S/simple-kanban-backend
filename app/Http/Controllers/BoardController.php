<?php

namespace App\Http\Controllers;

use App\Http\Resources\BoardResource;
use App\Models\Board;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * @OA\Tag(
 *     name="Boards",
 *     description=""
 * )
 */
class BoardController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * @OA\Get(
     *   path="/boards",
     *   security={{"bearer_token": {} }},
     *   tags={"Boards"},
     *   description="Get boards",
     *   operationId="get_boards",
     *   @OA\Response(
     *     response=200,
     *     description="Success",
     *   ),
     * )
    */
    public function index()
    {
        $boards = Board::where('user_id', Auth::user()->id)->get();
        return BoardResource::collection($boards);
    }

    /**
     * @OA\Post(
     *   path="/boards",
     *   security={{"bearer_token": {} }},
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\MediaType(
     *       mediaType="multipart/form-data",
     *       @OA\Schema(
     *         required={"name"},
     *         @OA\Property(property="name", type="string", example=""),
     *       )
     *     )
     *   ),
     *   tags={"Boards"},
     *   description="Create new board",
     *   operationId="create_new_board",
     *   @OA\Response(
     *     response=200,
     *     description="successful operation",
     *   ),
     * )
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
     * @OA\Get(
     *   path="/boards/{id}",
     *   security={{"bearer_token": {} }},
     *   tags={"Boards"},
     *   description="Get specific board",
     *   operationId="get_board",
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
    public function show(Board $board)
    {
        if($board->user_id != Auth::user()->id)
            throw new AuthorizationException();

        return new BoardResource($board);
    }

    /**
     * @OA\Post(
     *   path="/boards/{id}",
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
     *         @OA\Property(property="_method", type="string", format="string", example="PUT"),
     *       )
     *     )
     *   ),
     *   security={{"bearer_token": {} }},
     *   tags={"Boards"},
     *   description="Edit specific board",
     *   operationId="edit_board",
     *   @OA\Response(
     *     response="200",
     *     description="Success"
     *   ),
     * )
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
     * @OA\Delete(
     *   path="/boards/{id}",
     *   @OA\Parameter(
     *     in="path",
     *     name="id",
     *     required=true,
     *     @OA\Schema(type="string"),
     *   ),
     *   security={{"bearer_token": {} }},
     *   tags={"Boards"},
     *   description="Delete specific board",
     *   operationId="delete_board",
     *   @OA\Response(
     *     response=200,
     *     description="Success"
     *   )
     * )
    */
    public function destroy(Board $board)
    {
        if($board->user_id != Auth::user()->id)
            throw new AuthorizationException();

        $board->delete();

        return response()->json(['message' => 'Board deleted successfully']);
    }
}
