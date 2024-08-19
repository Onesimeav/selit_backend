<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\TestMail;

class MailController extends Controller
{
    public function index(): \Illuminate\Http\JsonResponse
    {
        Mail::to('exauceavalla@gmail.com')->send(new TestMail([
            'title' => 'The Title',
            'body' => 'The Body',
        ]));

       return response()->json([
          'message'=>'Test email sent successfully'
       ],200);
    }
}
