<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use function Symfony\Component\String\u;

class UserCanViewProfileTest extends TestCase
{
    use RefreshDatabase;

    /** @get_user_info | người dùng có thể xem hồ sơ của nhau*/
    public function a_user_can_view_user_profiles()
    {
        $this->actingAs($user = User::factory()->create(), 'api');
        $posts = Post::factory()->create();

        $response = $this->get('/api/users/' . $user->id);

        $response->assertStatus(200)->assertJson([
            'data' => [
                'type' => 'users',
                'user_id' => $user->id,
                'attributes' => [
                    'name' => $user->name
                ]
            ],
            'links' => [
                'self' => url('/users/' . $user->id)
            ]
        ]);
    }

    /** @set_user_info | Có thể sửa chữa thông tin cá nhân của chính mình*/
    public function a_user_can_fetch_posts_for_a_profile()
    {
        $this->withoutExceptionHandling();

        $this->actingAs($user = User::factory()->create(), 'api');
        $post = Post::factory()->create(['user_id' => $user->id]);

        $response = $this->get('/api/users/' . $user->id . '/posts');

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    [
                        'data' => [
                            'type' => 'posts',
                            'post_id' => $post->id,
                            'attributes' => [
                                'body' => $post->body,
                                'image' => url($post->image),
                                'posted_at' => $post->created_at->diffForHumans(),
                                'posted_by' => [
                                    'data' => [
                                        'attributes' => [
                                            'name' => $user->name,
                                        ]
                                    ]
                                ]
                            ]
                        ],
                        'links' => [
                            'self' => url('/posts/' . $post->id),
                        ]
                    ]
                ]
            ]);
    }

}
