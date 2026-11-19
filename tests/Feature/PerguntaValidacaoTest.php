<?php

namespace Tests\Feature;

use App\Models\Evento;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tickets #007 e #008: validação do formulário de perguntas.
 */
class PerguntaValidacaoTest extends TestCase
{
    use RefreshDatabase;

    private function criarEvento(): Evento
    {
        return Evento::create(['titulo' => 'Evento de Teste']);
    }

    public function test_pergunta_em_branco_retorna_erro_no_campo_texto(): void
    {
        $this->withoutVite();
        $user = User::factory()->create();
        $evento = $this->criarEvento();

        $response = $this->actingAs($user)
            ->from(route('eventos.show', $evento->id))
            ->post(route('eventos.perguntas.store', $evento->id), ['texto' => '']);

        $response->assertRedirect(route('eventos.show', $evento->id));
        $response->assertSessionHasErrors(['texto' => 'A pergunta não pode ficar em branco.']);
        $response->assertSessionDoesntHaveErrors('evento_id');

        // Após o redirect, a mensagem aparece em vermelho e a borda fica vermelha
        $page = $this->actingAs($user)->get(route('eventos.show', $evento->id));
        $page->assertSee('A pergunta não pode ficar em branco.');
        $page->assertSee('text-red-500', false);
        $page->assertSee('border-red-500', false);
    }

    public function test_texto_digitado_e_preservado_apos_falha(): void
    {
        $this->withoutVite();
        $user = User::factory()->create();
        $evento = $this->criarEvento();

        $this->actingAs($user)
            ->from(route('eventos.show', $evento->id))
            ->post(route('eventos.perguntas.store', $evento->id), ['texto' => 'curta']);

        $this->actingAs($user)->get(route('eventos.show', $evento->id))
            ->assertSee('>curta</textarea>', false);
    }

    public function test_pergunta_valida_e_salva(): void
    {
        $user = User::factory()->create();
        $evento = $this->criarEvento();

        $this->actingAs($user)
            ->post(route('eventos.perguntas.store', $evento->id), ['texto' => 'Qual é o tema da palestra?'])
            ->assertRedirect(route('eventos.show', $evento->id))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('perguntas', [
            'evento_id' => $evento->id,
            'user_id' => $user->id,
            'texto' => 'Qual é o tema da palestra?',
        ]);
    }

    public function test_visitante_nao_logado_e_redirecionado_para_login(): void
    {
        $evento = $this->criarEvento();

        $this->post(route('eventos.perguntas.store', $evento->id), ['texto' => 'Qual é o tema da palestra?'])
            ->assertRedirect();

        $this->assertDatabaseCount('perguntas', 0);
    }
}
