<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Question;
use App\User;
use JWTAuth;

class QuestionController extends Controller
{
    public function __construct()
    {
        $this->middleware('jwt.auth', ['only' => [
            'update', 'store', 'destroy'
        ]]);
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $questions = Question::leftJoin('answers', 'questions.id', '=', 'answers.question_id')->with('answers')->withCount('answers')->orderBy('answers.created_at', 'desc')->orderBy('answers_count', 'desc')->get();

        foreach ($questions as $question) {
            $last_answer = $question->answers()->orderBy('answers.created_at', 'desc')->first();
            $question->view_question = $this->view($question);
            $question->add_answers = $this->add_answers($question);
            $question->last_answer = 'Last answer by ' . User::find($last_answer->user_id)->name . ' on ' . $last_answer->created_at;
        }

        $response = [
            'message' => 'List questions based on the date of the last answer added (newest answered on top) AND based on the number of answers (descending)',
            'questions' => $questions
        ];

        return response()->json($response, 200);
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
            'title' => 'required',
            'description' => 'required'
        ]);

        if (! $user = JWTAuth::parseToken()->authenticate()) {
            return response()->json(['msg' => 'User not found'], 404);
        }

        $title = $request->input('title');
        $description = $request->input('description');

        $question = new Question([
            'title' => $title,
            'description' => $description
        ]);

        if($user->questions()->save($question))
        {
            $question->view_question = $this->view($question);
            
            $response = [
                'message' => 'Question created',
                'question' => $question
            ];

            return response()->json($response, 201);
        }

        $response = [
            'message' => 'An error ocuured while saving question'
        ];

        return response()->json($response, 404);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $question = Question::findOrFail($id)->with('answers')->first();
        
        if($question)
        {
            $question->view_question = $this->view($question);
            $question->add_answers = $this->add_answers($question);
            $response = [
                'message' => 'List question ' . $id,
                'questions' => $question
            ];

            return response()->json($response, 200);
        }

        $response = [
            'message' => 'Question with id' . $id . ' was not found.'
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

        $question = Question::findOrFail($id);

        if ($question->user_id != $user->id) {
            return response()->json(['msg' => 'You can only update your own questions, update not successful'], 401);
        };
        $title = $request->input('title');
        $description = $request->input('description');

        $question->title = $title;
        $question->description = $description;
        
        if($question->update()){
            $question->view_question = $this->view($question);
            
            $response = [
                'message' => 'Question updated',
                'question' => $question
            ];

            return response()->json($response, 201);
        }

        $response = [
            'message' => 'An error ocuured while updating question'
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

        $question = Question::findOrFail($id);

        if ($question->user_id != $user->id)  {
            return response()->json(['msg' => 'You can only update your own questions, update not successful'], 401);
        };

        if($question){
            if ($question->user_id != $user->id){
                return response()->json(['msg' => 'You can only delete your own questions, delete not successful'], 401);
            }

            if($question->answers())
            {
                $question->delete();

                $response = [
                    'message' => 'Question with id: '.$id.' is deleted'
                ];

                return response()->json($response, 200);
            }
            
            $response = [
                'message' => 'Question with id: '.$id.' cannot be deleted. It has answers associated with it.'
            ];

            return response()->json($response, 401);
        }
        $response = [
            'message' => 'There is no question with id: '.$id
        ];

        return response()->json($response, 200);
    }

    public function delete_unanswered()
    {
        if (! $user = JWTAuth::parseToken()->authenticate()) {
            return response()->json(['msg' => 'User not found'], 404);
        }

        $questions = Question::has('answers', '=', 0)->get();
        $deleted_questions = [];

        foreach ($questions as $question) {
            if ($question->user_id != $user->id)  {
                if($question->delete()){
                    $deleted_questions << $question;
                }
            };
        }

        $response = [
            'message' => 'Your unanswered questions were deleted!',
            'questions_deleted' => $deleted_questions
        ];

        return response()->json($response, 200);

    }

    private function view($question)
    {
        return [
            'href' => '/api/v1/question/' . $question->id,
            'method' => 'GET'
        ];
    }

    private function add_answers($question)
    {
        return [
            'href' => '/api/v1/question/answer',
            'method' => 'POST',
            'params' => 'question_id, description'
        ];
    }
}
