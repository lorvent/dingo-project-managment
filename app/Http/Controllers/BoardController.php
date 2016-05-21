<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use DB;

use Auth;
use \App\Models\User;
use \App\Models\Board;
use \App\Models\CardTag;
use \App\Models\BoardList;

class BoardController extends Controller
{
    /**
     * Creates a new Board
     * @param  Request $request have the input data
     * @return object return the newly created board            
     */
    public function postBoard(Request $request)
    {
        $this->validate($request, [
            'boardTitle'        => 'required|unique:board,boardTitle',
            'boardPrivacyType'  => 'required',   
        ]);
        
        $boardPrivacyType = $request->get('boardPrivacyType');  
        $boardTitle = $request->get('boardTitle');  
        $userId = Auth::id();
        
        return Board::create([
            'user_id' => $userId,
            'boardTitle' => $boardTitle,
            'boardPrivacyType' => $boardPrivacyType,  
        ]);
    }

    /**
     * Get the Board details
     * @param  Request $request have the input data
     * @return view board page or view
     */
    public function getBoardDetail(Request $request)
    {
       $boardId = $request->id;
       $boardDetail = Board::findOrFail(['id' => $boardId])->first();
       $lists = BoardList::where(["board_id" => $boardId,])->get();

        $cards =  DB::table('board_card')->select([
                'board_card.*',
                DB::raw("COUNT(comment.id) as totalComments"),
            ])
            ->leftJoin('comment', 'board_card.id', '=', 'comment.card_id')
            ->groupBy('board_card.id')
            ->get();
        $cards = json_decode(json_encode($cards), True);
        
        $cardTaskCount =  DB::table('board_card')->select([
            'board_card.*',
            DB::raw("COUNT(card_task.id) as totalTasks"),
        ])
        ->leftJoin('card_task', 'board_card.id', '=', 'card_task.card_id')
        ->groupBy('board_card.id')
        ->get();
        $cardTaskCount = json_decode(json_encode($cardTaskCount), True);

        $boards = Board::where(['user_id' => Auth::id(), ])->get();
        $recentBoards = Board::where(['user_id' => Auth::id(), ])->orderBy('created_at', 'desc')->take(3)->get();

        return view('user.board', compact('boardDetail', 'lists', 'cards', 'cardTaskCount', 'boards', 'recentBoards'));
    }

    /**
     * Update the board is_favourite attribute in the database. 
     * @param  Request $request have the input data
     * @return object the updated data           
     */
    public function updateBoardFavourite(Request $request)
    {
        $boardId = $request->get("boardId");
        $isFavourite = $request->get("isFavourite");

        return Board::where("id", $boardId)->update(["is_starred" => $isFavourite,]);
    }

}