<?php

namespace App\Http\Controllers;

use App\Http\Requests\Withdrawal\MakeWithdrawalRequest;
use App\Models\User;
use App\Models\Withdrawal;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function getBalance(): JsonResponse
    {
        $user = User::findOrFail(Auth::id());
        $balance = $user->balance;

        return response()->json([
            'balance'=>$balance,
        ]);
    }

    public function makeWithdrawal(MakeWithdrawalRequest $request): JsonResponse
    {
        Withdrawal::create([
            'amount'=>$request->input('amount'),
            'user_id'=>Auth::id(),
        ]);

        return response()->json([
            'message'=>'Withdrawal request successfully created'
        ]);
    }

    public function validateWithdrawal($id): JsonResponse
    {
        $withdrawal=Withdrawal::findOrFail($id);
        $withdrawal->done='true';
        $withdrawal->save();

        return response()->json([
            'message'=>'Withdrawal validated successfully',
        ]);
    }

    public function getWithdrawalRequests(): JsonResponse
    {
        $withdrawal=Withdrawal::all()->paginate(15);

        return response()->json([
            'result'=>$withdrawal,
        ]);
    }
}
