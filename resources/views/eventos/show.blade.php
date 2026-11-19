@extends('layouts.app')

@section('title', $evento->titulo . ' — FalaQ')

@section('content')
<div class="row">
    <!-- Formulário de envio de Pergunta -->
    <div class="col-md-5 mb-4">
        <div class="card shadow-sm p-3">
            <h4 class="fw-bold mb-3">💬 Faça sua Pergunta</h4>
            <form action="{{ route('eventos.perguntas.store', $evento->id) }}" method="POST">
                @csrf
                <div class="mb-4">
                    <label for="texto" class="block text-gray-700 font-medium mb-2">Texto da Pergunta</label>

                    <!-- Textarea com validação e preservação de texto -->
                    <textarea name="texto" id="texto" rows="4"
                              class="w-full p-3 border rounded-md @error('texto') border-red-500 @enderror"
                              placeholder="Digite sua dúvida ou comentário para o palestrante...">{{ old('texto') }}</textarea>

                    <!-- Mensagem de erro -->
                    @error('texto')
                        <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Botão estilizado com Tailwind -->
                <button type="submit"
                        class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 w-full">
                    Enviar Pergunta
                </button>
            </form>
        </div>
    </div>

    <!-- Lista de Perguntas -->
    <div class="col-md-7">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="fw-bold m-0">📋 Perguntas do Evento</h4>
            <span class="text-secondary small">Total no Banco: {{ $evento->perguntas->count() }}</span>
        </div>

        @forelse($perguntas as $pergunta)
            <!-- Card estilizado com Tailwind -->
            <div class="mb-4 px-5 py-4 bg-gray-100 rounded-2xl rounded-tl-sm shadow-md max-w-xl">
                <p class="text-gray-800 text-lg">{{ $pergunta->texto }}</p>
                <div class="text-sm text-gray-600 mt-2">
                    <span>Autor: {{ $pergunta->user->name ?? 'Anônimo' }}</span>
                    <span class="ml-4">{{ $pergunta->created_at->format('d/m/Y H:i') }}</span>
                </div>

                <!-- Ticket 4: só aparece para o autor da pergunta ou o dono do evento -->
                @can('delete', $pergunta)
                    <form action="{{ route('perguntas.destroy', $pergunta) }}" method="POST" class="mt-3"
                          onsubmit="return confirm('Tem certeza que deseja excluir esta pergunta?');">
                        @csrf
                        @method('DELETE')
                        <x-danger-button>Excluir</x-danger-button>
                    </form>
                @endcan
            </div>
        @empty
            <div class="text-center text-gray-500">
                Nenhuma pergunta foi enviada ainda. Seja o primeiro!
            </div>
        @endforelse

        <!-- Renderização dos Botões de Paginação -->
        @if(method_exists($perguntas, 'links'))
            <div class="d-flex justify-content-center mt-4">
                {{ $perguntas->links() }}
            </div>
        @endif
    </div>
</div>
@endsection