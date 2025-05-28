<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function index($email, Request $request)
    {
        // 1) Buscar usuário
        $user = User::where('email', $email)->first();
        if (! $user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        // 2) Puxar transações onde ele foi pagador ou recebedor
        $query = Transaction::where('payer_email', $email)
            ->orWhere('payee_email', $email)
            ->orderBy('created_at', 'desc');

        // 3) Paginar (opcional, com ?page=)
        $perPage = 15;
        $transactions = $query->paginate($perPage);

        // 4) Formatar a resposta
        return response()->json([
            'user' => [
                'name'  => $user->name,
                'email' => $user->email,
            ],
            'transactions' => $transactions,
        ]);
    }
}
