<?php

namespace App\Http\Controllers;

use App\Models\chat;
use GuzzleHttp\Promise\Create;
use Illuminate\Http\Request;
use App\Http\Requests\getchatrequest;
use App\Http\Requests\storechatrequest;

class chatcontroller extends Controller
{

    /*
    "جلب جميع المحادثات (الخاصة أو العامة حسب الطلب) التي يشارك فيها المستخدم الحالي،
     وتحتوي على رسائل، وترتيبها من الأحدث إلى الأقدم، مع إرجاع تفاصيل آخر رسالة والمشاركين."
    */
    public function index(getchatrequest $request)
    {
        $validated = $request->validated();
        $isprivate = 1;
        if($request->has('is_private')){
            $isprivate = (int)$validated['is_private'];
        }
        //يجلب الشاتات التي تطابق نوعها (خاصة / عامة )
        $chats = chat::where('is_private',$isprivate)
        //يجلب فقط المحادثات التي يكون فيها المستخدم الحالي مشاركا
        ->hasParticipant(auth()->user()->id)
        //يتاكد ان المحادثة تحتوي على رسالة واحدة فقط على الاقل
        ->whereHas('message')
        //معلومات المستخدم الذي ارسل اخر رسالة && معلومات كل المشاركين في المحادثة
        ->with('lastmessage.user','participants.user')
        //يرتب الشاتات حسب اخر تحديث
        ->latest('updated_at')
        //يجلب النتائج كمجموعة
        ->get();
        return $this->success($chats);
    }

    //create a new chat
    public function store(StoreChatRequest $request)
{
    $data = $this->prepareStoreData($request);

    if ($data['userId'] === $data['otherUserId']) {
        return $this->error('You cannot create a chat with yourself.');
    }

    $previousChat = $this->getPreviousChat($data['otherUserId']);

    if ($previousChat === null) {
        $chat = Chat::create($data['data']);

        $chat->participants()->createMany([
            ['user_id' => $data['userId']],
            ['user_id' => $data['otherUserId']],
        ]);

        $chat->refresh()->load(['lastmessage.user', 'participants.user']);

        return $this->success($chat);
    }

    // إذا كانت المحادثة موجودة مسبقاً
    return $this->success($previousChat->load(['lastmessage.user', 'participants.user']));
}

    //check if user and other user has previos chat or not
   private function getPreviousChat(int $otherUserId){
    $userId = auth()->user()->id;

    return Chat::where('is_private', 1)
        ->whereHas('participants', function($query) use($userId){
            $query->where('user_id', $userId);
        })
        ->whereHas('participants', function($query) use($otherUserId){
            $query->where('user_id', $otherUserId);
        })
        ->first();
}

    // preapers data for store a chat
    private function prepareStoreData(storechatrequest $request){
        $data = $request->validated();
        $otherUserId = (int)$data['user_Id'];
        unset($data['user_id']);
        $data['created_by'] = auth()->user()->id;
        return [
            'otherUserId' => $otherUserId,
            'userId' => auth()->user()->id,
            'data'=>  $data
        ];

    }

    //get a single chat
    public function show(chat $chat)
    {
        $chat->load('lastmessage.user','participants.user');
        return $this->success($chat);
    }



  
}
