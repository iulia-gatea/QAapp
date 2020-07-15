<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Question;
use App\Answer;
use JWTAuth;

class AnswerController extends Controller
{
    public function __construct()
    {
        $this->middleware('jwt.auth', ['only' => [
            'update', 'store', 'destroy'
        ]]);
    }
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'description' => 'required',
            'question_id' => 'required'
        ]);

        if (! $user = JWTAuth::parseToken()->authenticate()) {
            return response()->json(['msg' => 'User not found'], 404);
        }

        $question_id = $request->input('question_id');
        $description = $request->input('description');

        $question = Question::findOrFail($question_id);
        $answer = new Answer([
            'description' => $description
        ]);
        $answer->user_id = $user->id;

        if ($question->answers()->save($answer))
        {
            $question->view_question = [
                'href' => '/api/v1/question/'.$question->id,
                'method' => 'GET'
            ];

            $response = [
                'message' => 'Question answered',
                'question' => $question
            ];

            return response()->json($response, 201);
        }

        $response = [
            'message' => 'Question cannot be answered'
        ];

        return response()->json($response, 404);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        if (! $user = JWTAuth::parseToken()->authenticate()) {
            return response()->json(['msg' => 'User not found'], 404);
        }

        $question_id = $request->input('question_id');
        $answer = $request->input('answer');

        $answer = Answer::findOrFail($id);
        $answer->description = $request->input('description');

        if ($answer->user_id != $user->id){
            return response()->json(['msg' => 'You can only update your own answers, update not successful'], 401);
        }

        if($answer->update())
        {
            $answer->view_question = [
                'href' => '/api/v1/question/'.$answer->question_id,
                'method' => 'GET'
            ];

            $response = [
                'message' => 'Answer updated',
                'answer' => $answer
            ];

            return response()->json($response, 201);
        }

        $response = [
            'message' => 'Answer cannot be updated'
        ];

        return response()->json($response, 404);  
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if (! $user = JWTAuth::parseToken()->authenticate()) {
            return response()->json(['msg' => 'User not found'], 404);
        }

        $answer = Answer::findOrFail($id);

        if ($answer->user_id != $user->id){
            return response()->json(['msg' => 'You can only delete your own answers, delete not successful'], 401);
        }

        if($answer){
            $answer->delete();

            $response = [
                'message' => 'Answer with id: '.$id.' is deleted'
            ];

            return response()->json($response, 200);
        }
        $response = [
            'message' => 'There is no answer with id: '.$id
        ];

        return response()->json($response, 200);
    }
}
