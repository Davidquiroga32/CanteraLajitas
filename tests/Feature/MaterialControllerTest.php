<?php

namespace Tests\Feature;

use App\Models\Material;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class MaterialControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_seller_sees_catalog_without_admin_actions(): void
    {
        Material::factory()->create(['nombre' => 'Recebo morado']);

        $response = $this->actingAs(User::factory()->create())->get(route('materiales.index'));

        $response->assertOk();
        $response->assertSee('Recebo morado');
        $response->assertDontSee('Ajustar stock');
        $response->assertDontSee('+ Nuevo material');
    }

    public function test_admin_creates_material_with_formatted_price(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post(route('materiales.store'), [
            'nombre' => 'Recebo morado',
            'precio_m3' => '$ 70.000',
            'stock_m3' => 300,
            'stock_minimo_m3' => 100,
            'color' => '#7b4a5a',
        ]);

        $response->assertRedirect(route('materiales.index'));
        $this->assertDatabaseHas('materiales', ['nombre' => 'Recebo morado', 'precio_m3' => 70_000, 'stock_m3' => 300, 'activo' => true]);
        $this->assertDatabaseHas('auditoria', ['accion' => 'material.creado', 'user_id' => $admin->id]);
    }

    public function test_rejects_duplicate_material_name(): void
    {
        Material::factory()->create(['nombre' => 'Relleno']);

        $response = $this->actingAs(User::factory()->admin()->create())->post(route('materiales.store'), [
            'nombre' => 'Relleno',
            'precio_m3' => 25_000,
            'stock_m3' => 10,
            'stock_minimo_m3' => 5,
            'color' => '#9a8d79',
        ]);

        $response->assertSessionHasErrors('nombre', errorBag: 'material');
        $this->assertSame(1, Material::query()->count());
    }

    public function test_price_change_is_audited_and_does_not_touch_stock(): void
    {
        $admin = User::factory()->admin()->create();
        $material = Material::factory()->create(['precio_m3' => 50_000, 'stock_m3' => 80]);

        $response = $this->actingAs($admin)->patch(route('materiales.update', $material), [
            'nombre' => $material->nombre,
            'precio_m3' => 55_000,
            'stock_m3' => 9999,
            'stock_minimo_m3' => $material->stock_minimo_m3,
            'color' => $material->color,
            'activo' => 1,
        ]);

        $response->assertRedirect(route('materiales.index'));
        $material->refresh();
        $this->assertSame(55_000, $material->precio_m3);
        $this->assertSame('80.00', $material->stock_m3);
        $this->assertDatabaseHas('auditoria', ['accion' => 'material.editado', 'auditable_id' => $material->id]);
    }

    public function test_stock_entry_adds_to_current_stock(): void
    {
        $material = Material::factory()->create(['stock_m3' => 40]);

        $this->actingAs(User::factory()->admin()->create())->post(route('materiales.stock', $material), [
            'tipo' => 'entrada',
            'cantidad_m3' => 120.5,
        ]);

        $this->assertSame('160.50', $material->fresh()->stock_m3);
    }

    public function test_physical_count_replaces_current_stock(): void
    {
        $material = Material::factory()->create(['stock_m3' => 40]);

        $this->actingAs(User::factory()->admin()->create())->post(route('materiales.stock', $material), [
            'tipo' => 'conteo',
            'cantidad_m3' => 0,
            'nota' => 'Patio vacío',
        ]);

        $this->assertSame('0.00', $material->fresh()->stock_m3);
        $this->assertDatabaseHas('auditoria', ['accion' => 'material.stock', 'auditable_id' => $material->id]);
    }

    public function test_forbids_sellers_from_changing_materials(): void
    {
        $vendedor = User::factory()->create();
        $material = Material::factory()->create(['precio_m3' => 50_000, 'stock_m3' => 40]);

        $this->actingAs($vendedor)->patch(route('materiales.update', $material), ['precio_m3' => 1])->assertForbidden();
        $this->actingAs($vendedor)->post(route('materiales.stock', $material), ['tipo' => 'entrada', 'cantidad_m3' => 10])->assertForbidden();
        $this->actingAs($vendedor)->post(route('materiales.store'), ['nombre' => 'X'])->assertForbidden();

        $material->refresh();
        $this->assertSame(50_000, $material->precio_m3);
        $this->assertSame('40.00', $material->stock_m3);
    }
}
