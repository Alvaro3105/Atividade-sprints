<?php

namespace App\Policies;

use App\Models\Pergunta;
use App\Models\User;

class PerguntaPolicy
{
    /**
     * Pode excluir a pergunta: o autor da pergunta OU o dono do evento.
     */
    public function delete(User $user, Pergunta $pergunta): bool
    {
        // Cast para int: alguns drivers de banco retornam IDs como string
        return (int) $user->id === (int) $pergunta->user_id
            || (int) $user->id === (int) $pergunta->evento?->user_id;
    }
}
