<?php

namespace Tests\Feature;

use App\Models\Evento;
use App\Models\Pergunta;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Sprint 06: upvotes (N:M) e ranking de perguntas.
 */
class UpvotePerguntaTest extends TestCase
{
    use RefreshDatabase;

    private Evento $evento;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->evento = Evento::create(['titulo' => 'Palestra']);
    }

    private function criarPergunta(string $texto): Pergunta
    {
        return Pergunta::create([
            'evento_id' => $this->evento->id,
            'texto' => $texto,
            'is_public' => true,
        ]);
    }

    public function test_tabela_pivot_existe_com_chave_unica_composta(): void
    {
        $this->assertTrue(Schema::hasTable('pergunta_user'));
        $this->assertTrue(Schema::hasColumns('pergunta_user', ['pergunta_id', 'user_id', 'created_at', 'updated_at']));

        $user = User::factory()->create();
        $pergunta = $this->criarPergunta('Pergunta duplicada?');
        DB::table('pergunta_user')->insert(['pergunta_id' => $pergunta->id, 'user_id' => $user->id]);

        $this->expectException(QueryException::class);
        DB::table('pergunta_user')->insert(['pergunta_id' => $pergunta->id, 'user_id' => $user->id]);
    }

    public function test_relacionamentos_sao_belongs_to_many(): void
    {
        $this->assertInstanceOf(BelongsToMany::class, (new Pergunta)->votos());
        $this->assertInstanceOf(BelongsToMany::class, (new User)->perguntasVotadas());
        $this->assertSame('pergunta_user', (new User)->perguntasVotadas()->getTable());
    }

    public function test_anonimo_nao_pode_votar(): void
    {
        $pergunta = $this->criarPergunta('Pergunta anônima?');

        $this->post(route('perguntas.votar', $pergunta))
            ->assertRedirect(route('login.create'));

        $this->assertDatabaseCount('pergunta_user', 0);
    }

    public function test_primeiro_clique_vota_e_segundo_remove(): void
    {
        $user = User::factory()->create();
        $pergunta = $this->criarPergunta('Vale a pena aprender Laravel?');

        $this->actingAs($user)->from(route('eventos.show', $this->evento->id))
            ->post(route('perguntas.votar', $pergunta))
            ->assertRedirect(route('eventos.show', $this->evento->id))
            ->assertSessionHas('sucesso', 'Voto registrado!');

        $this->assertDatabaseHas('pergunta_user', ['pergunta_id' => $pergunta->id, 'user_id' => $user->id]);
        $this->assertTrue($user->perguntasVotadas->contains($pergunta));

        $this->actingAs($user)->post(route('perguntas.votar', $pergunta))
            ->assertSessionHas('sucesso', 'Voto removido.');

        $this->assertDatabaseMissing('pergunta_user', ['pergunta_id' => $pergunta->id, 'user_id' => $user->id]);
    }

    public function test_perguntas_ordenadas_por_votos_decrescente(): void
    {
        $poucos = $this->criarPergunta('Pergunta com um voto');
        $muitos = $this->criarPergunta('Pergunta com tres votos');
        $nenhum = $this->criarPergunta('Pergunta sem votos');

        $users = User::factory()->count(3)->create();
        $muitos->votos()->attach($users->pluck('id'));
        $poucos->votos()->attach($users->first()->id);

        $response = $this->get(route('eventos.show', $this->evento->id));

        $response->assertSeeInOrder([$muitos->texto, $poucos->texto, $nenhum->texto]);
        $this->assertSame(
            [3, 1, 0],
            $response->viewData('perguntas')->pluck('votos_count')->all()
        );
    }

    public function test_botao_destacado_quando_usuario_ja_votou(): void
    {
        $user = User::factory()->create();
        $pergunta = $this->criarPergunta('Pergunta votada pelo usuario');
        $pergunta->votos()->attach($user->id);

        $this->actingAs($user)->get(route('eventos.show', $this->evento->id))
            ->assertSee('Clique para remover o voto', false);
    }
}
