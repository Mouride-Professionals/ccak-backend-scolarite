<?php

namespace Tests\Feature\Notification;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\InteractsWithPermissions;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;
    use InteractsWithPermissions;

    protected User $user;
    protected User $admin;
    protected array $permissions = [
        'notifications.view',
        'notifications.create',
        'notifications.update',
        'notifications.delete',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        \Spatie\Permission\Models\Role::create(['name' => 'ADMIN', 'guard_name' => 'api']);
        \Spatie\Permission\Models\Role::create(['name' => 'STUDENT', 'guard_name' => 'api']);

        $this->user = User::factory()->create(['email' => 'user@test.com']);
        $this->admin = User::factory()->create(['email' => 'admin@test.com']);
        $this->admin->assignRole('ADMIN');

        $this->seedPermissions($this->permissions);
        $this->user->givePermissionTo([
            'notifications.view',
            'notifications.update',
            'notifications.delete',
        ]);
        $this->admin->givePermissionTo($this->permissions);
    }

    #[Test]
    public function user_can_get_their_notifications()
    {
        // Create notifications for the user
        Notification::factory()->count(3)->create(['user_id' => $this->user->id]);
        // Create notifications for another user
        Notification::factory()->count(2)->create();

        $response = $this->actingAs($this->user, 'api')
            ->getJson('/api/v1/notifications');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'title',
                        'message',
                        'type',
                        'is_read',
                        'created_at',
                    ],
                ],
                'meta' => [
                    'current_page',
                    'per_page',
                    'total',
                ],
            ])
            ->assertJsonCount(3, 'data');
    }

    #[Test]
    public function user_can_filter_notifications_by_type()
    {
        Notification::factory()->create([
            'user_id' => $this->user->id,
            'type' => Notification::TYPE_GRADE_PUBLISHED,
        ]);
        Notification::factory()->create([
            'user_id' => $this->user->id,
            'type' => Notification::TYPE_ENROLLMENT_CONFIRMED,
        ]);

        $response = $this->actingAs($this->user, 'api')
            ->getJson('/api/v1/notifications?filter[type]=' . Notification::TYPE_GRADE_PUBLISHED);

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.type', Notification::TYPE_GRADE_PUBLISHED);
    }

    #[Test]
    public function user_can_filter_notifications_by_read_status()
    {
        Notification::factory()->create([
            'user_id' => $this->user->id,
            'is_read' => true,
        ]);
        Notification::factory()->count(2)->create([
            'user_id' => $this->user->id,
            'is_read' => false,
        ]);

        $response = $this->actingAs($this->user, 'api')
            ->getJson('/api/v1/notifications?filter[is_read]=false');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    #[Test]
    public function admin_can_send_notification()
    {
        $payload = [
            'recipient_ids' => [$this->user->id],
            'title' => 'Test Notification',
            'message' => 'This is a test message',
            'type' => Notification::TYPE_SYSTEM,
            'channels' => ['in_app'],
        ];

        $response = $this->actingAs($this->admin, 'api')
            ->postJson('/api/v1/notifications', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('data.count', 1);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->user->id,
            'title' => 'Test Notification',
            'message' => 'This is a test message',
        ]);
    }

    #[Test]
    public function non_admin_cannot_send_notification()
    {
        $payload = [
            'recipient_ids' => [$this->user->id],
            'title' => 'Test Notification',
            'message' => 'This is a test message',
            'type' => Notification::TYPE_SYSTEM,
        ];

        $response = $this->actingAs($this->user, 'api')
            ->postJson('/api/v1/notifications', $payload);

        $response->assertStatus(403);
    }

    #[Test]
    public function user_can_mark_notification_as_read()
    {
        $notification = Notification::factory()->create([
            'user_id' => $this->user->id,
            'is_read' => false,
        ]);

        $response = $this->actingAs($this->user, 'api')
            ->putJson("/api/v1/notifications/{$notification->id}/read");

        $response->assertStatus(200)
            ->assertJsonPath('data.notification.is_read', true);

        $this->assertTrue($notification->fresh()->is_read);
        $this->assertNotNull($notification->fresh()->read_at);
    }

    #[Test]
    public function user_cannot_mark_other_users_notification_as_read()
    {
        $otherUser = User::factory()->create();
        $notification = Notification::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $response = $this->actingAs($this->user, 'api')
            ->putJson("/api/v1/notifications/{$notification->id}/read");

        $response->assertStatus(404);
    }

    #[Test]
    public function user_can_mark_all_notifications_as_read()
    {
        Notification::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'is_read' => false,
        ]);

        $response = $this->actingAs($this->user, 'api')
            ->postJson('/api/v1/notifications/read-all');

        $response->assertStatus(200)
            ->assertJsonPath('data.count', 3);

        $this->assertEquals(0, Notification::where('user_id', $this->user->id)
            ->where('is_read', false)
            ->count());
    }

    #[Test]
    public function user_can_get_unread_count()
    {
        Notification::factory()->count(5)->create([
            'user_id' => $this->user->id,
            'is_read' => false,
        ]);
        Notification::factory()->count(2)->create([
            'user_id' => $this->user->id,
            'is_read' => true,
        ]);

        $response = $this->actingAs($this->user, 'api')
            ->getJson('/api/v1/notifications/unread-count');

        $response->assertStatus(200)
            ->assertJsonPath('data.count', 5);
    }

    #[Test]
    public function user_can_delete_their_notification()
    {
        $notification = Notification::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user, 'api')
            ->deleteJson("/api/v1/notifications/{$notification->id}");

        $response->assertStatus(200);
        $this->assertSoftDeleted($notification);
    }

    #[Test]
    public function user_cannot_delete_other_users_notification()
    {
        $otherUser = User::factory()->create();
        $notification = Notification::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $response = $this->actingAs($this->user, 'api')
            ->deleteJson("/api/v1/notifications/{$notification->id}");

        $response->assertStatus(404);
        $this->assertDatabaseHas('notifications', ['id' => $notification->id]);
    }

    #[Test]
    public function unauthenticated_user_cannot_access_notifications()
    {
        $response = $this->getJson('/api/v1/notifications');

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Unauthenticated.',
            ]);
    }
}
