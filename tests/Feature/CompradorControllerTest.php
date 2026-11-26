<?php

namespace Tests\Feature;

use App\Enums\TipoDocumento;
use App\Models\Comprador;
use App\Models\MovimientoCartera;
use App\Models\Remision;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class CompradorControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_search_filters_by_name_document_or_city(): void
    {
        Comprador::factory()->create(['nombre' => 'Ferretería El Maestro', 'ciudad' => 'Villavicencio']);
        Comprador::factory()->create(['nombre' => 'Marta Bautista', 'ciudad' => 'Cumaral']);

        $response = $this->actingAs(User::factory()->create())->get(route('compradores.index', ['q' => 'cumaral']));

        $response->assertOk();
        $response->assertSee('Marta Bautista');
        $response->assertDontSee('Ferretería El Maestro');
    }

    public function test_seller_creates_buyer_and_panel_opens(): void
    {
        $vendedor = User::factory()->create();

        $response = $this->actingAs($vendedor)->post(route('compradores.store'), [
            'nombre' => '  Obras   del Llano  ',
            'tipo_documento' => 'NIT',
            'documento' => '901.555.000-1',
            'ciudad' => 'Acacías',
        ]);

        $comprador = Comprador::query()->firstOrFail();
        $response->assertRedirect(route('compradores.index', ['ver' => $comprador->id]));
        $this->assertSame('Obras del Llano', $comprador->nombre);
        $this->assertSame(TipoDocumento::NIT, $comprador->tipo_documento);
    }

    public function test_rejects_duplicate_document(): void
    {
        Comprador::factory()->create(['documento' => '17.345.220']);

        $response = $this->actingAs(User::factory()->create())->post(route('compradores.store'), [
            'nombre' => 'Otro',
            'tipo_documento' => 'CC',
            'documento' => '17.345.220',
            'ciudad' => 'Restrepo',
        ]);

        $response->assertSessionHasErrors(['documento' => 'Ya existe un comprador con ese documento.'], errorBag: 'comprador');
        $this->assertSame(1, Comprador::query()->count());
    }

    public function test_requires_all_fields_and_valid_document(): void
    {
        $response = $this->actingAs(User::factory()->create())->post(route('compradores.store'), [
            'nombre' => '',
            'tipo_documento' => 'PAS',
            'documento' => 'ABC123',
            'ciudad' => '',
        ]);

        $response->assertSessionHasErrors(['nombre', 'tipo_documento', 'documento', 'ciudad'], errorBag: 'comprador');
    }

    public function test_only_admin_can_edit_buyer(): void
    {
        $comprador = Comprador::factory()->create(['nombre' => 'Original']);
        $datos = ['nombre' => 'Cambiado', 'tipo_documento' => 'CC', 'documento' => $comprador->documento, 'ciudad' => 'Granada'];

        $this->actingAs(User::factory()->create())->patch(route('compradores.update', $comprador), $datos)->assertForbidden();
        $this->assertSame('Original', $comprador->fresh()->nombre);

        $this->actingAs(User::factory()->admin()->create())->patch(route('compradores.update', $comprador), $datos)
            ->assertRedirect(route('compradores.index', ['ver' => $comprador->id]));
        $this->assertSame('Cambiado', $comprador->fresh()->nombre);
        $this->assertDatabaseHas('auditoria', ['accion' => 'comprador.editado', 'auditable_id' => $comprador->id]);
    }

    public function test_panel_shows_balances_and_only_the_sellers_own_history(): void
    {
        $vendedor = User::factory()->create();
        $comprador = Comprador::factory()->create();
        MovimientoCartera::factory()->for($comprador)->deuda(450_000)->create();
        $propia = Remision::factory()->for($comprador)->for($vendedor, 'vendedor')->create(['numero' => 41]);
        $ajena = Remision::factory()->for($comprador)->create(['numero' => 42]);

        $response = $this->actingAs($vendedor)->get(route('compradores.index', ['ver' => $comprador->id]));

        $response->assertOk();
        $response->assertSee('$ 450.000');
        $response->assertSee($propia->numeroFormateado());
        $response->assertDontSee($ajena->numeroFormateado());
    }
}
