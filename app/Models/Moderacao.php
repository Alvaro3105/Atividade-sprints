<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Moderacao extends Model
{
    // Estados possíveis da denúncia
    public const ANALISANDO = 'analisando';
    public const APROVADA = 'aprovada';   // pergunta liberada, denúncia descartada
    public const REJEITADA = 'rejeitada'; // pergunta rejeitada e ocultada do mural

    // O Laravel deduziria "moderacaos"; o nome correto da tabela é "moderacoes"
    protected $table = 'moderacoes';

    protected $fillable = ['pergunta_id', 'motivo', 'status'];

    /**
     * Ticket #014: a denúncia pertence a uma pergunta.
     */
    public function pergunta(): BelongsTo
    {
        return $this->belongsTo(Pergunta::class);
    }
}
