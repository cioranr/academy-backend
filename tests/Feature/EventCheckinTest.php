<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class EventCheckinTest extends TestCase
{
    use RefreshDatabase;

    private function makeEvent(array $attrs = []): Event
    {
        $event = new Event();
        $event->forceFill(array_merge([
            'title' => 'Workshop Test', 'slug' => 'workshop-test', 'date' => '2026-10-01',
            'location' => 'București', 'venue' => 'Sala A', 'status' => 'published',
        ], $attrs))->save();

        return $event;
    }

    private function makeUser(array $attrs = []): User
    {
        return User::factory()->create($attrs);
    }

    private function register(Event $event, User $user, array $attrs = []): EventRegistration
    {
        return EventRegistration::create(array_merge([
            'event_id' => $event->id, 'user_id' => $user->id,
            'first_name' => 'Ion', 'last_name' => 'Popescu', 'email' => $user->email,
            'status' => 'approved',
        ], $attrs));
    }

    public function test_checkin_token_is_hidden_from_public_event_payload(): void
    {
        $event = $this->makeEvent(['checkin_token' => 'secret-token']);

        $this->getJson('/api/events/workshop-test')
            ->assertOk()
            ->assertJsonMissing(['checkin_token' => 'secret-token']);
    }

    public function test_manager_can_generate_and_regenerate_qr(): void
    {
        $admin = $this->makeUser(['role' => 'admin']);
        $event = $this->makeEvent();
        Sanctum::actingAs($admin);

        $this->getJson("/api/events/{$event->id}/checkin-qr")->assertOk()->assertJson(['token' => null, 'url' => null]);

        $first = $this->postJson("/api/events/{$event->id}/checkin-qr")->assertOk()->json();
        $this->assertNotEmpty($first['token']);
        $this->assertStringEndsWith('/checkin/' . $first['token'], $first['url']);

        $second = $this->postJson("/api/events/{$event->id}/checkin-qr")->assertOk()->json();
        $this->assertNotEquals($first['token'], $second['token']);
    }

    public function test_participant_cannot_manage_qr(): void
    {
        $event = $this->makeEvent();
        Sanctum::actingAs($this->makeUser(['role' => 'participant']));

        $this->getJson("/api/events/{$event->id}/checkin-qr")->assertForbidden();
        $this->postJson("/api/events/{$event->id}/checkin-qr")->assertForbidden();
    }

    public function test_checkin_requires_auth(): void
    {
        $this->postJson('/api/checkin/whatever')->assertUnauthorized();
    }

    public function test_registered_user_checks_in_once(): void
    {
        Mail::fake();
        $event = $this->makeEvent(['checkin_token' => 'tok123']);
        $user  = $this->makeUser();
        $reg   = $this->register($event, $user);
        Sanctum::actingAs($user);

        $this->postJson('/api/checkin/tok123')
            ->assertOk()
            ->assertJsonPath('status', 'confirmed')
            ->assertJsonPath('participant.first_name', 'Ion')
            ->assertJsonPath('event.title', 'Workshop Test');

        $reg->refresh();
        $this->assertTrue($reg->is_present);
        $this->assertSame('qr', $reg->presence_method);
        $this->assertSame($user->id, $reg->presence_confirmed_by);

        $this->postJson('/api/checkin/tok123')
            ->assertOk()
            ->assertJsonPath('status', 'already_confirmed');

        $this->assertSame(1, EventRegistration::where('event_id', $event->id)->count());
    }

    public function test_unregistered_user_gets_not_registered(): void
    {
        $event = $this->makeEvent(['checkin_token' => 'tok123']);
        Sanctum::actingAs($this->makeUser());

        $this->postJson('/api/checkin/tok123')->assertNotFound()->assertJsonPath('code', 'not_registered');
    }

    public function test_cancelled_registration_counts_as_not_registered(): void
    {
        $event = $this->makeEvent(['checkin_token' => 'tok123']);
        $user  = $this->makeUser();
        $this->register($event, $user, ['status' => 'cancelled']);
        Sanctum::actingAs($user);

        $this->postJson('/api/checkin/tok123')->assertNotFound()->assertJsonPath('code', 'not_registered');
    }

    public function test_inactive_or_unknown_event_returns_event_inactive(): void
    {
        $this->makeEvent(['checkin_token' => 'draft-tok', 'status' => 'draft']);
        Sanctum::actingAs($this->makeUser());

        $this->postJson('/api/checkin/draft-tok')->assertStatus(410)->assertJsonPath('code', 'event_inactive');
        $this->postJson('/api/checkin/does-not-exist')->assertStatus(410)->assertJsonPath('code', 'event_inactive');
    }

    public function test_admin_can_toggle_presence(): void
    {
        Mail::fake();
        $admin = $this->makeUser(['role' => 'admin']);
        $event = $this->makeEvent();
        $reg   = $this->register($event, $this->makeUser());
        Sanctum::actingAs($admin);

        $this->patchJson("/api/registrations/{$reg->id}/presence", ['is_present' => true])
            ->assertOk()->assertJsonPath('is_present', true)->assertJsonPath('presence_method', 'manual');

        $this->patchJson("/api/registrations/{$reg->id}/presence", ['is_present' => false])
            ->assertOk()->assertJsonPath('is_present', false)->assertJsonPath('presence_method', null);

        $this->postJson("/api/registrations/{$reg->id}/mark-present")
            ->assertOk()->assertJsonPath('presence_method', 'manual');
    }
}
