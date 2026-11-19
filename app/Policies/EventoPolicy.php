<?php

namespace App\Policies;

use App\Models\Evento;
use App\Models\User;

class EventoPolicy
{
    /**
     * Ticket #016: só o dono do evento modera as perguntas dele.
     */
    public function moderate(User $user, Evento $evento): bool
    {
        return (int) $user->id === (int) $evento->user_id;
    }
}
