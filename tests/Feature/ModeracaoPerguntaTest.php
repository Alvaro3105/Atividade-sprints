<?php

namespace Tests\Feature;

use App\Models\Evento;
use App\Models\Moderacao;
use App\Models\Pergunta;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Sprint 07: sistema de moderação de perguntas.
 */
class ModeracaoPerguntaTest extends TestCase
{
    use RefreshDatabase;

    private User $dono;
    private User $participante;
    private Evento $evento;
    private Pergunta $pergunta;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();

        $this->dono = User::factory()->create();
        $this->participante = User::factory()->create();
        $this->evento = Evento::create(['titulo' => 'Palestra', 'user_id' => $this->dono->id]);
        $this->pergunta = Pergunta::create([
            'evento_id' => $this->evento->id,
            'user_id' => $this->participante->id,
            'texto' => 'Pergunta fora do escopo',
            'is_public' => true,
        ]);
    }

    // ---------- Fase 1: Fundação de dados ----------

    public function test_migration_cria_tabela_com_status_padrao_analisando(): void
    {
        $this->assertTrue(Schema::hasColumns('moderacoes', ['id', 'pergunta_id', 'motivo', 'status', 'created_at', 'updated_at']));

        $id = DB::table('moderacoes')->insertGetId(['pergunta_id' => $this->pergunta->id, 'motivo' => 'Motivo qualquer']);
        $this->assertSame('analisando', DB::table('moderacoes')->find($id)->status);
    }

    public function test_excluir_pergunta_remove_moderacoes_em_cascata(): void
    {
        $this->pergunta->moderacoes()->create(['motivo' => 'Conteúdo ofensivo']);
        $this->pergunta->delete();

        $this->assertDatabaseCount('moderacoes', 0);
    }

    public function test_model_fillable_e_relacionamentos_1_n(): void
    {
        $this->assertSame(['pergunta_id', 'motivo', 'status'], (new Moderacao)->getFillable());
        $this->assertInstanceOf(BelongsTo::class, (new Moderacao)->pergunta());
        $this->assertInstanceOf(HasMany::class, (new Pergunta)->moderacoes());

        $moderacao = $this->pergunta->moderacoes()->create(['motivo' => 'Conteúdo ofensivo']);
        $this->assertTrue($moderacao->pergunta->is($this->pergunta));
        $this->assertCount(1, $this->pergunta->fresh()->moderacoes);
    }

    // ---------- Fase 2: Entrada segura & persistência ----------

    public function test_participante_denuncia_pergunta(): void
    {
        $this->actingAs($this->participante)
            ->from(route('eventos.show', $this->evento->id))
            ->post(route('perguntas.moderar', $this->pergunta), ['motivo' => 'Pergunta fora do escopo da palestra'])
            ->assertRedirect(route('eventos.show', $this->evento->id))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('moderacoes', [
            'pergunta_id' => $this->pergunta->id,
            'motivo' => 'Pergunta fora do escopo da palestra',
            'status' => 'analisando',
        ]);
    }

    public function test_motivo_e_validado(): void
    {
        foreach (['', 'curto', str_repeat('a', 256)] as $motivo) {
            $this->actingAs($this->participante)
                ->post(route('perguntas.moderar', $this->pergunta), ['motivo' => $motivo])
                ->assertSessionHasErrors('motivo');
        }

        $this->assertDatabaseCount('moderacoes', 0);
    }

    public function test_anonimo_nao_denuncia(): void
    {
        $this->post(route('perguntas.moderar', $this->pergunta), ['motivo' => 'Pergunta fora do escopo'])
            ->assertRedirect(route('login.create'));

        $this->assertDatabaseCount('moderacoes', 0);
    }

    // ---------- Fase 3: Autorização & desempenho ----------

    public function test_dono_ve_fila_de_moderacao_com_eager_load(): void
    {
        $this->pergunta->moderacoes()->create(['motivo' => 'Conteúdo ofensivo aqui']);

        $response = $this->actingAs($this->dono)->get(route('eventos.moderacoes', $this->evento));

        $response->assertOk()->assertSee('Conteúdo ofensivo aqui')->assertSee($this->pergunta->texto);
        $this->assertTrue($response->viewData('moderacoes')->first()->relationLoaded('pergunta'));
    }

    public function test_fila_nao_mostra_denuncias_de_outros_eventos(): void
    {
        $outro = Evento::create(['titulo' => 'Outro', 'user_id' => $this->participante->id]);
        $perguntaOutro = Pergunta::create(['evento_id' => $outro->id, 'texto' => 'Pergunta do outro evento']);
        $perguntaOutro->moderacoes()->create(['motivo' => 'Denuncia de outro evento']);

        $this->actingAs($this->dono)->get(route('eventos.moderacoes', $this->evento))
            ->assertOk()
            ->assertDontSee('Denuncia de outro evento');
    }

    public function test_quem_nao_e_dono_nao_ve_fila(): void
    {
        $this->actingAs($this->participante)->get(route('eventos.moderacoes', $this->evento))
            ->assertForbidden();
    }

    public function test_dono_aprova_pergunta(): void
    {
        $moderacao = $this->pergunta->moderacoes()->create(['motivo' => 'Conteúdo ofensivo']);

        $this->actingAs($this->dono)
            ->patch(route('moderacoes.status', $moderacao), ['status' => 'aprovada'])
            ->assertRedirect();

        $this->assertSame('aprovada', $moderacao->fresh()->status);
        $this->assertTrue((bool) $this->pergunta->fresh()->is_public);
    }

    public function test_dono_rejeita_pergunta_e_ela_sai_do_mural(): void
    {
        $moderacao = $this->pergunta->moderacoes()->create(['motivo' => 'Conteúdo ofensivo']);

        $this->actingAs($this->dono)
            ->patch(route('moderacoes.status', $moderacao), ['status' => 'rejeitada'])
            ->assertRedirect();

        $this->assertSame('rejeitada', $moderacao->fresh()->status);
        $this->assertFalse((bool) $this->pergunta->fresh()->is_public);
    }

    public function test_quem_nao_e_dono_nao_muda_status(): void
    {
        $moderacao = $this->pergunta->moderacoes()->create(['motivo' => 'Conteúdo ofensivo']);

        $this->actingAs($this->participante)
            ->patch(route('moderacoes.status', $moderacao), ['status' => 'aprovada'])
            ->assertForbidden();

        $this->assertSame('analisando', $moderacao->fresh()->status);
    }

    public function test_status_invalido_e_recusado(): void
    {
        $moderacao = $this->pergunta->moderacoes()->create(['motivo' => 'Conteúdo ofensivo']);

        $this->actingAs($this->dono)
            ->patch(route('moderacoes.status', $moderacao), ['status' => 'qualquer'])
            ->assertSessionHasErrors('status');

        $this->assertSame('analisando', $moderacao->fresh()->status);
    }

    public function test_tela_do_evento_mostra_link_da_fila_so_para_o_dono_e_form_de_denuncia(): void
    {
        $url = route('eventos.show', $this->evento->id);

        $this->actingAs($this->dono)->get($url)->assertSee('Fila de Moderação');
        $this->actingAs($this->participante)->get($url)
            ->assertDontSee('Fila de Moderação')
            ->assertSee('Denunciar');
    }
}
