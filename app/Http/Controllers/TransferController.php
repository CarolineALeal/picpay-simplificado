<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use App\Models\User;
use App\Models\Transaction;

class TransferController extends Controller
{
    public function transfer(Request $request)
    {
        // 1. Validação básica
        $request->validate([
            'amount'     => 'required|numeric|min:0.01',
            'payerEmail' => 'required|email',
            'payeeEmail' => 'required|email',
        ]);

        $amount     = $request->input('amount');
        $payerEmail = $request->input('payerEmail');
        $payeeEmail = $request->input('payeeEmail');

        if ($payerEmail === $payeeEmail) {
            return response()->json(['error' => 'Payer and payee cannot be the same'], 400);
        }

        // 2. Busca usuários
        $payer = User::where('email', $payerEmail)->first();
        $payee = User::where('email', $payeeEmail)->first();
        if (!$payer || !$payee) {
            return response()->json(['error' => 'Payer or payee not found'], 404);
        }

        // 3. Tipo e saldo
        if ($payer->type !== 'common') {
            return response()->json(['error' => 'Payer must be a common user'], 403);
        }
        if ($payer->balance < $amount) {
            return response()->json(['error' => 'Insufficient balance'], 400);
        }

        // 4. Autorização externa
        try {
            $response = Http::retry(3, 100, function ($e) {
                    return $e instanceof ConnectionException;
                })
                ->withOptions(['verify' => false])
                ->timeout(5)
                ->get('https://util.devi.tools/api/v2/authorize');

            if ($response->status() !== 200) {
                return response()->json(['error' => 'Authorization service unavailable'], 503);
            }

            $body = $response->json();
            $authorized = data_get($body, 'data.authorization', false);
            if (! $authorized) {
                return response()->json(['error' => 'Transfer not authorized'], 403);
            }

        } catch (RequestException $e) {
            Log::warning('Authorization service error', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Authorization service unavailable'], 503);
        }

        // 5. Transação atômica
        DB::transaction(function () use ($payer, $payee, $amount) {
            $payer->decrement('balance', $amount);
            $payee->increment('balance', $amount);

            Transaction::create([
                'amount'      => $amount,
                'payer_email' => $payer->email,
                'payee_email' => $payee->email,
            ]);
        });

        // 6. Notificações externas (fire-and-forget)
        try {
            Http::post('https://util.devi.tools/api/v1/notify', [
                'to'      => $payer->email,
                'message' => "Você realizou uma transferência de R\${$amount}.",
            ]);

            Http::post('https://util.devi.tools/api/v1/notify', [
                'to'      => $payee->email,
                'message' => "Você recebeu R\${$amount} de {$payer->email}.",
            ]);
        } catch (\Exception $e) {
            Log::error('Notification service failed', [
                'error' => $e->getMessage(),
                'payer' => $payer->email,
                'payee' => $payee->email,
            ]);
        }

        // 7. Retorno
        return response()->json([
            'message'     => 'Transfer successful',
            'transaction' => [
                'amount'      => $amount,
                'payer_email' => $payer->email,
                'payee_email' => $payee->email,
            ],
        ]);
    }
}
