<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreModeracaoRequest;
use App\Models\Evento;
use App\Models\Moderacao;
use App\Models\Pergunta;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ModeracaoController extends Controller
{
    /**
     * Fila de Moderação do evento (apenas o dono do evento).
     */
    public function index(Evento $evento)
    {
        $this->authorize('moderate', $evento);

        // Eager load de "pergunta" evita o problema N+1 na listagem
        $moderacoes = Moderacao::with('pergunta')
            ->whereHas('pergunta', fn ($q) => $q->where('evento_id', $evento->id))
            ->latest()
            ->paginate(15);

        return view('moderacoes.index', compact('evento', 'moderacoes'));
    }

    /**
     * Registra uma denúncia sobre a pergunta (qualquer usuário autenticado).
     */
    public function store(StoreModeracaoRequest $request, Pergunta $pergunta)
    {
        // Cria a moderação já associada à pergunta (pergunta_id preenchido pelo hasMany)
        $pergunta->moderacoes()->create([
            'motivo' => $request->validated('motivo'),
        ]);

        return back()->with('sucesso', 'Denúncia enviada. Obrigado! Ela será analisada pelo moderador.');
    }

    /**
     * Aprova ou rejeita a pergunta denunciada (apenas o dono do evento).
     */
    public function updateStatus(Request $request, Moderacao $moderacao)
    {
        $this->authorize('moderate', $moderacao->pergunta->evento);

        $dados = $request->validate([
            'status' => ['required', Rule::in([Moderacao::APROVADA, Moderacao::REJEITADA])],
        ]);

        $moderacao->update(['status' => $dados['status']]);

        // Pergunta rejeitada sai do mural público
        if ($dados['status'] === Moderacao::REJEITADA) {
            $moderacao->pergunta->update(['is_public' => false, 'status' => 'rejeitada']);
        }

        return back()->with('sucesso', 'Moderação atualizada: ' . $dados['status'] . '.');
    }
}
