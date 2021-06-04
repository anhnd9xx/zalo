<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use function Symfony\Component\String\u;

class LikesTest extends TestCase
{
    use RefreshDatabase;

    /** @like | bạn bè có thể like bài đăng */
    public function a_user_can_like_a_post()
    {
        $this->withoutExceptionHandling();
        $this->actingAs($user = User::factory()->create(), 'api');
        $post = Post::factory()->create(['id' => 123]);

        $response = $this->post('/api/posts/' . $post->id . '/like')
            ->assertStatus(200);

        $this->assertCount(1, $user->likedPosts);
        $response->assertJson([
            'data' => [
                [
                    'data' => [
                        'type' => 'likes',
                        'like_id' => 1,
                        'attributes' => []
                    ],
                    'links' => [
                        'self' => url('/posts/123'),
                    ]
                ]
            ],
            'links' => [
                'self' => url('/posts'),
            ]
        ]);
    }

    /** @like | bài đăng hiển thị đã có lượt like */
    public function posts_are_returned_with_likes()
    {
        $this->actingAs($user = User::factory()->create(), 'api');
        $post = Post::factory()->create(['id' => 123, 'user_id' => $user->id]);
        $this->post('/api/posts/' . $post->id . '/like')
            ->assertStatus(200);

        $response = $this->get('api/posts')
            ->assertStatus(200)
            ->assertJson([
                'data' => [
                    [
                        'data' => [
                            'type' => 'posts',
                            'attributes' => [
                                'likes' => [
                                    'data' => [
                                        [
                                            'data' => [
                                                'type' => 'likes',
                                                'like_id' => 1,
                                                'attributes' => []
                                            ]
                                        ]
                                    ],
                                    'like_count' => 1,
                                    'user_likes_post' => true,
                                ]
                            ]
                        ]
                    ]
                ]
            ]);
    }
}
