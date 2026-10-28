<?php

namespace App\Http\Controllers;

use App\Models\WaterDelivery;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Inertia\Inertia;

class WaterDeliveryController extends Controller
{
    // Registra entrada de garrafões
    public function store(Request $request)
    {
        $validated = $request->validate([
            'operation_type' => 'required|in:PADRAO,BATE_VOLTA',
            'sector' => 'required|string|max:255',
            'empty_bottles_count' => 'required|integer|min:1',
            'received_from_person' => 'required|string|max:255',
            'reception_attendant' => 'required|string|max:255',
        ]);

        $isBateVolta = $validated['operation_type'] === 'BATE_VOLTA';
        $now = Carbon::now();
        $nextNum = WaterDelivery::max('id') + 1;
        $orderNumber = 'PCPA-' . $now->format('Y') . '-' . str_pad($nextNum, 4, '0', STR_PAD_LEFT);

        $delivery = WaterDelivery::create([
            'order_number' => $orderNumber,
            'operation_type' => $validated['operation_type'],
            'sector' => $validated['sector'],
            'empty_bottles_count' => $validated['empty_bottles_count'],
            'reception_date' => $now,
            'estimated_date' => $isBateVolta ? null : $this->calculateNextDeliveryDate($now),
            'received_from_person' => $validated['received_from_person'],
            'reception_attendant' => $validated['reception_attendant'],
            'delivery_date' => $isBateVolta ? $now : null,
            'delivered_to_person' => $isBateVolta ? $validated['received_from_person'] : null,
            'delivery_attendant' => $isBateVolta ? $validated['reception_attendant'] : null,
            'status' => $isBateVolta ? 'CONCLUIDO' : 'DISPONIVEL',
        ]);

        return back()->with('success', 'Protocolo gerado com sucesso!');
    }

    // Processa a baixa com trava contra duplo resgate
    public function redeem(Request $request, $orderNumber)
    {
        $request->validate([
            'delivered_to_person' => 'required|string|max:255',
            'delivery_attendant' => 'required|string|max:255',
        ]);

        $delivery = WaterDelivery::where('order_number', $orderNumber)->first();

        if (!$delivery) {
            return back()->withErrors(['message' => 'Comprovante não localizado.']);
        }

        // Validação Estrita Anti-Reuso
        if ($delivery->status === 'CONCLUIDO') {
            return back()->withErrors([
                'message' => "ATENÇÃO: Comprovante já utilizado em " . 
                             $delivery->delivery_date->format('d/m/Y H:i') . 
                             " por " . $delivery->delivery_attendant . "."
            ]);
        }

        $delivery->update([
            'delivery_date' => Carbon::now(),
            'delivered_to_person' => $request->delivered_to_person,
            'delivery_attendant' => $request->delivery_attendant,
            'status' => 'CONCLUIDO',
        ]);

        return back()->with('success', 'Baixa realizada com sucesso!');
    }

    private function calculateNextDeliveryDate($date)
    {
        $d = clone $date;
        $day = $d->dayOfWeek; // 1 = Seg, 4 = Qui
        if ($day === 1) return $d->addDays(3);
        if ($day >= 2 && $day <= 4) return $d->addDays(4 - $day);
        return $d->addDays((8 - $day) % 7);
    }
}