<?php

namespace Tests\Feature;

use App\Models\Specialty;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SpecialtyTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_list_is_seeded_and_ordered(): void
    {
        $values = $this->getJson('/api/specialties')->assertOk()->json('*.value');

        $this->assertContains('radioterapie', $values);
        $this->assertSame('cardiologie', $values[0]);
        $this->assertSame('alta', end($values));
    }

    public function test_manager_can_create_update_and_delete(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'admin']));

        $created = $this->postJson('/api/specialties', ['label' => 'Neurologie'])
            ->assertCreated()->assertJsonPath('value', 'neurologie')->json();

        $this->patchJson("/api/specialties/{$created['id']}", ['label' => 'Neurologie pediatrică', 'order' => 5])
            ->assertOk()->assertJsonPath('label', 'Neurologie pediatrică')->assertJsonPath('value', 'neurologie');

        $this->postJson('/api/specialties', ['label' => 'Neurologie'])->assertStatus(422);

        $this->deleteJson("/api/specialties/{$created['id']}")->assertOk();
        $this->assertDatabaseMissing('specialties', ['id' => $created['id']]);
    }

    public function test_protected_specialties_cannot_be_deleted(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'admin']));

        $alta = Specialty::where('value', 'alta')->firstOrFail();
        $this->deleteJson("/api/specialties/{$alta->id}")->assertStatus(422);
    }

    public function test_participant_cannot_modify(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'participant']));

        $this->postJson('/api/specialties', ['label' => 'X'])->assertForbidden();
    }
}
