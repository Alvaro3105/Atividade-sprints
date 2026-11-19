<?php

namespace Tests\Feature;

use App\Models\Evento;
use App\Models\Pergunta;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Sprint 05: só o autor da pergunta ou o dono do evento podem excluí-la.
 */
class PerguntaPolicyTest extends TestCase
{
    use RefreshDatabase;

    private User $donoEvento;
    private User $autor;
    private User $intruso;
    private Evento $evento;
    private Pergunta $pergunta;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();

        $this->donoEvento = User::factory()->create();
        $this->autor = User::factory()->create();
        $this->intruso = User::factory()->create();

        $this->evento = Evento::create(['titulo' => 'Evento', 'user_id' => $this->donoEvento->id]);
        $this->pergunta = Pergunta::create([
            'evento_id' => $this->evento->id,
            'user_id' => $this->autor->id,
            'texto' => 'Pergunta de teste do autor',
            'is_public' => true,
        ]);
    }

    public function test_autor_pode_excluir_a_propria_pergunta(): void
    {
        $this->actingAs($this->autor)
            ->delete(route('perguntas.destroy', $this->pergunta))
            ->assertRedirect(route('eventos.show', $this->evento->id));

        $this->assertDatabaseMissing('perguntas', ['id' => $this->pergunta->id]);
    }

    public function test_dono_do_evento_pode_excluir_pergunta(): void
    {
        $this->actingAs($this->donoEvento)
            ->delete(route('perguntas.destroy', $this->pergunta))
            ->assertRedirect();

        $this->assertDatabaseMissing('perguntas', ['id' => $this->pergunta->id]);
    }

    public function test_outro_usuario_recebe_403(): void
    {
        $this->actingAs($this->intruso)
            ->delete(route('perguntas.destroy', $this->pergunta))
            ->assertForbidden();

        $this->assertDatabaseHas('perguntas', ['id' => $this->pergunta->id]);
    }

    public function test_visitante_nao_logado_nao_exclui(): void
    {
        $this->delete(route('perguntas.destroy', $this->pergunta))
            ->assertRedirect(route('login.create'));

        $this->assertDatabaseHas('perguntas', ['id' => $this->pergunta->id]);
    }

    public function test_botao_excluir_so_aparece_para_quem_pode(): void
    {
        $url = route('eventos.show', $this->evento->id);

        $this->actingAs($this->autor)->get($url)->assertSee('Excluir');
        $this->actingAs($this->donoEvento)->get($url)->assertSee('Excluir');
        $this->actingAs($this->intruso)->get($url)->assertDontSee('Excluir');
    }
}
