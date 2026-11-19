{{-- Botão vermelho para ações destrutivas (ex: excluir) --}}
<button {{ $attributes->merge(['type' => 'submit', 'class' => 'bg-red-600 text-white text-sm px-3 py-1 rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2']) }}>
    {{ $slot }}
</button>
