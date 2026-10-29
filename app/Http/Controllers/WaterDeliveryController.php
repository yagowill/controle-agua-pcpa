<?php

namespace App\Http\Controllers;

use App\Models\WaterDelivery;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class WaterDeliveryController extends Controller
{
    /**
     * Exibe a página de Nova Entrega (com preview do próximo número e último registro)
     */
    public function index(Request $request)
    {
        $now = Carbon::now();
        $year = $now->format('Y');
        
        // Próximo número de ordem do ano atual
        $lastThisYear = WaterDelivery::where('order_number', 'like', "PCPA-{$year}-%")
            ->orderByDesc('id')
            ->first();
            
        $nextSeq = 1;
        if ($lastThisYear && preg_match('/PCPA-\d{4}-(\d+)/', $lastThisYear->order_number, $matches)) {
            $nextSeq = intval($matches[1]) + 1;
        }
        $nextOrderNumber = 'PCPA-' . $year . '-' . str_pad($nextSeq, 4, '0', STR_PAD_LEFT);

        // Previsão padrão calculada
        $defaultEstimatedDate = $this->calculateNextDeliveryDate($now)->format('Y-m-d');

        // Entregas recentes para visualização rápida
        $recentDeliveries = WaterDelivery::orderByDesc('id')->take(5)->get();

        return Inertia::render('NovaEntrega', [
            'nextOrderNumber' => $nextOrderNumber,
            'defaultEstimatedDate' => $defaultEstimatedDate,
            'recentDeliveries' => $recentDeliveries,
            'flash' => [
                'success' => session('success'),
                'createdDelivery' => session('createdDelivery'),
            ]
        ]);
    }

    /**
     * Salva novo registro de entrega (Padrão ou Bate Volta)
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'operation_type' => 'required|in:PADRAO,BATE_VOLTA',
            'sector' => 'required|string|max:255',
            'empty_bottles_count' => 'required|integer|min:1|max:500',
            'received_from_person' => 'required|string|max:255',
            'reception_attendant' => 'required|string|max:255',
            'notes' => 'nullable|string|max:500',
            'estimated_date' => 'nullable|date',
            'delivered_to_person' => 'nullable|string|max:255',
        ], [
            'sector.required' => 'O campo setor / delegacia é obrigatório.',
            'empty_bottles_count.required' => 'Informe a quantidade de garrafões.',
            'empty_bottles_count.min' => 'A quantidade mínima é de 1 garrafão.',
            'received_from_person.required' => 'Informe o nome do servidor que entregou o seco.',
            'reception_attendant.required' => 'Informe o nome do atendente do almoxarifado.',
            'operation_type.required' => 'Selecione o tipo de operação.',
        ], [
            'sector' => 'setor',
            'empty_bottles_count' => 'quantidade de garrafões',
            'received_from_person' => 'servidor',
            'reception_attendant' => 'atendente',
        ]);

        $isBateVolta = $validated['operation_type'] === 'BATE_VOLTA';
        $now = Carbon::now();
        $year = $now->format('Y');

        $lastThisYear = WaterDelivery::where('order_number', 'like', "PCPA-{$year}-%")
            ->orderByDesc('id')
            ->first();

        $nextSeq = 1;
        if ($lastThisYear && preg_match('/PCPA-\d{4}-(\d+)/', $lastThisYear->order_number, $matches)) {
            $nextSeq = intval($matches[1]) + 1;
        }
        $orderNumber = 'PCPA-' . $year . '-' . str_pad($nextSeq, 4, '0', STR_PAD_LEFT);

        $estimatedDate = null;
        if (!$isBateVolta) {
            $estimatedDate = !empty($validated['estimated_date']) 
                ? Carbon::parse($validated['estimated_date']) 
                : $this->calculateNextDeliveryDate($now);
        }

        $delivery = WaterDelivery::create([
            'order_number' => $orderNumber,
            'operation_type' => $validated['operation_type'],
            'sector' => mb_strtoupper($validated['sector'], 'UTF-8'),
            'empty_bottles_count' => $validated['empty_bottles_count'],
            'reception_date' => $now,
            'estimated_date' => $estimatedDate,
            'received_from_person' => mb_strtoupper($validated['received_from_person'], 'UTF-8'),
            'reception_attendant' => mb_strtoupper($validated['reception_attendant'], 'UTF-8'),
            'delivery_date' => $isBateVolta ? $now : null,
            'delivered_to_person' => $isBateVolta 
                ? mb_strtoupper($validated['delivered_to_person'] ?? $validated['received_from_person'], 'UTF-8') 
                : null,
            'delivery_attendant' => $isBateVolta ? mb_strtoupper($validated['reception_attendant'], 'UTF-8') : null,
            'status' => $isBateVolta ? 'CONCLUIDO' : 'DISPONIVEL',
            'notes' => $validated['notes'] ? mb_strtoupper($validated['notes'], 'UTF-8') : null,
        ]);

        return redirect()->route('entregas.nova')->with([
            'success' => "Protocolo {$delivery->order_number} registrado com sucesso!",
            'createdDelivery' => $delivery,
        ]);
    }

    /**
     * Exibe a página de Baixa/Validação
     */
    public function baixaPage(Request $request)
    {
        return Inertia::render('BaixarEntrega');
    }

    /**
     * API/Endpoint de busca de ordem para baixa
     */
    public function searchOrder(Request $request, string $orderNumber)
    {
        $cleanNumber = trim(strtoupper($orderNumber));
        // Aceita buscar sem prefixo (ex: 1 -> PCPA-YYYY-0001 ou exato)
        $delivery = WaterDelivery::where('order_number', $cleanNumber)
            ->orWhere('order_number', 'like', "%-{$cleanNumber}")
            ->first();

        if (!$delivery) {
            return response()->json([
                'found' => false,
                'message' => "Nenhum protocolo localizado para a ordem: {$cleanNumber}",
            ], 404);
        }

        return response()->json([
            'found' => true,
            'delivery' => $delivery,
        ]);
    }

    /**
     * Processa a baixa com validação estrita anti-reuso
     */
    public function redeem(Request $request, string $orderNumber)
    {
        $validated = $request->validate([
            'delivered_to_person' => 'required|string|max:255',
            'delivery_attendant' => 'required|string|max:255',
        ], [
            'delivered_to_person.required' => 'Informe o nome do servidor que está retirando o cheio.',
            'delivery_attendant.required' => 'Informe o nome do atendente responsável pela entrega.',
        ], [
            'delivered_to_person' => 'servidor recebedor',
            'delivery_attendant' => 'atendente',
        ]);

        $delivery = WaterDelivery::where('order_number', $orderNumber)->first();

        if (!$delivery) {
            return back()->withErrors(['message' => "Comprovante {$orderNumber} não localizado."]);
        }

        // Validação Estrita Anti-Reuso
        if ($delivery->status === 'CONCLUIDO') {
            $formattedDate = $delivery->delivery_date 
                ? $delivery->delivery_date->format('d/m/Y \à\s H:i') 
                : 'data não registrada';

            return back()->withErrors([
                'message' => "ATENÇÃO: Este comprovante já foi baixado em {$formattedDate} pelo atendente " . 
                             ($delivery->delivery_attendant ?? 'NÃO INFORMADO') . "."
            ]);
        }

        $delivery->update([
            'delivery_date' => Carbon::now(),
            'delivered_to_person' => mb_strtoupper($validated['delivered_to_person'], 'UTF-8'),
            'delivery_attendant' => mb_strtoupper($validated['delivery_attendant'], 'UTF-8'),
            'status' => 'CONCLUIDO',
        ]);

        return back()->with('success', "Baixa da ordem {$delivery->order_number} realizada com sucesso!");
    }

    /**
     * Exibe a página de Relatórios e Histórico de Entregas
     */
    public function relatoriosPage(Request $request)
    {
        $query = WaterDelivery::query();

        // Filtro por termo de busca (Ordem, Setor ou Servidor)
        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                  ->orWhere('sector', 'like', "%{$search}%")
                  ->orWhere('received_from_person', 'like', "%{$search}%")
                  ->orWhere('delivered_to_person', 'like', "%{$search}%");
            });
        }

        // Filtro por status
        if ($request->filled('status') && in_array($request->status, ['DISPONIVEL', 'CONCLUIDO'])) {
            $query->where('status', $request->status);
        }

        // Filtro por mês/ano
        $month = $request->input('month', Carbon::now()->month);
        $year = $request->input('year', Carbon::now()->year);

        if ($request->filled('month')) {
            $query->whereMonth('reception_date', $month);
        }
        if ($request->filled('year')) {
            $query->whereYear('reception_date', $year);
        }

        // Estatísticas Consolidadas
        $statsBase = WaterDelivery::whereYear('reception_date', $year)
            ->when($request->filled('month'), fn($q) => $q->whereMonth('reception_date', $month));

        $totalBottles = (clone $statsBase)->sum('empty_bottles_count');
        $totalDeliveries = (clone $statsBase)->count();
        $totalPadrao = (clone $statsBase)->where('operation_type', 'PADRAO')->count();
        $totalBateVolta = (clone $statsBase)->where('operation_type', 'BATE_VOLTA')->count();
        $totalPendentes = (clone $statsBase)->where('status', 'DISPONIVEL')->count();
        $totalConcluidas = (clone $statsBase)->where('status', 'CONCLUIDO')->count();

        // Ranking de Setores com mais consumo
        $topSectors = (clone $statsBase)
            ->selectRaw('sector, SUM(empty_bottles_count) as total_bottles, COUNT(*) as total_orders')
            ->groupBy('sector')
            ->orderByDesc('total_bottles')
            ->take(5)
            ->get();

        $deliveries = $query->orderByDesc('id')->paginate(15)->withQueryString();

        return Inertia::render('Relatorios', [
            'deliveries' => $deliveries,
            'filters' => $request->only(['search', 'status', 'month', 'year']),
            'stats' => [
                'total_bottles' => (int) $totalBottles,
                'total_deliveries' => (int) $totalDeliveries,
                'total_padrao' => (int) $totalPadrao,
                'total_bate_volta' => (int) $totalBateVolta,
                'total_pendentes' => (int) $totalPendentes,
                'total_concluidas' => (int) $totalConcluidas,
                'top_sectors' => $topSectors,
            ],
        ]);
    }

    /**
     * Calcula a data estimada de entrega conforme regras:
     * - Seg a Qua -> Quinta-feira da mesma semana
     * - Qui -> Segunda-feira da próxima semana
     * - Sex, Sáb, Dom -> Quinta-feira da próxima semana
     */
    private function calculateNextDeliveryDate(Carbon $date): Carbon
    {
        $d = $date->copy();
        $dayOfWeek = $d->dayOfWeek; // 0 = Domingo, 1 = Segunda, ..., 4 = Quinta, 6 = Sábado

        if ($dayOfWeek >= 1 && $dayOfWeek <= 3) {
            // Segunda a Quarta -> Quinta-feira da mesma semana
            return $d->addDays(4 - $dayOfWeek);
        } elseif ($dayOfWeek === 4) {
            // Quinta-feira -> Próxima Segunda-feira
            return $d->addDays(4);
        } else {
            // Sexta (5), Sábado (6), Domingo (0) -> Quinta-feira seguinte
            $daysUntilThursday = (4 - $dayOfWeek + 7) % 7;
            if ($daysUntilThursday === 0) {
                $daysUntilThursday = 7;
            }
            return $d->addDays($daysUntilThursday);
        }
    }
}
