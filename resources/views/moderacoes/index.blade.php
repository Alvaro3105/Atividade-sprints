@extends('layouts.app')

@section('title', 'Fila de Moderação — ' . $evento->titulo)

@section('content')
<div class="flex items-center justify-between mb-4">
    <h2 class="fw-bold m-0">🛡️ Fila de Moderação — {{ $evento->titulo }}</h2>
    <a href="{{ route('eventos.show', $evento->id) }}" class="text-blue-400 hover:underline">← Voltar ao evento</a>
</div>

@forelse($moderacoes as $moderacao)
    @php
        $cores = [
            'analisando' => 'bg-yellow-100 text-yellow-800',
            'aprovada'   => 'bg-green-100 text-green-800',
            'rejeitada'  => 'bg-red-100 text-red-800',
        ];
    @endphp
    <div class="mb-4 px-5 py-4 bg-gray-100 rounded-2xl shadow-md">
        <div class="flex items-start justify-between gap-4">
            <div>
                <p class="text-gray-500 text-xs uppercase mb-1">Pergunta denunciada</p>
                <p class="text-gray-800 text-lg">{{ $moderacao->pergunta->texto }}</p>
            </div>
            <span class="px-2 py-1 rounded-md text-xs font-bold {{ $cores[$moderacao->status] ?? 'bg-gray-200 text-gray-800' }}">
                {{ ucfirst($moderacao->status) }}
            </span>
        </div>

        <p class="text-gray-700 mt-3"><strong>Motivo:</strong> {{ $moderacao->motivo }}</p>
        <p class="text-gray-500 text-sm mt-1">Denunciada em {{ $moderacao->created_at->format('d/m/Y H:i') }}</p>

        @if($moderacao->status === 'analisando')
            <div class="flex gap-2 mt-3">
                <form action="{{ route('moderacoes.status', $moderacao) }}" method="POST">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="status" value="aprovada">
                    <button type="submit" class="bg-green-600 text-white text-sm px-3 py-1 rounded-md hover:bg-green-700">
                        Aprovar pergunta
                    </button>
                </form>
                <form action="{{ route('moderacoes.status', $moderacao) }}" method="POST">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="status" value="rejeitada">
                    <x-danger-button>Rejeitar pergunta</x-danger-button>
                </form>
            </div>
        @endif
    </div>
@empty
    <div class="text-center text-gray-500">Nenhuma denúncia para este evento. 🎉</div>
@endforelse

<div class="d-flex justify-content-center mt-4">
    {{ $moderacoes->links() }}
</div>
@endsection
