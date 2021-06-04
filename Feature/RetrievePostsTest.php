<?php

namespace Tests\Feature;

use App\Models\Friend;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class RetrievePostsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic feature test example.
     * @get_list_posts | Người dùng có thể truy xuất các bài đăng
     * @return void
     */
    public function a_user_can_retrieve_posts()
    {
        $this->actingAs($user = User::factory()->create(), 'api');
        $anotherUser = $user = User::factory()->create();
        $posts = Post::factory(2)->create(['user_id' => $anotherUser->id]);
        Friend::create([
            'user_id' => $user->id,
            'friend_id' => $anotherUser->id,
            'confirmed_at' => now(),
            'status' => 1,
        ]);

        $response = $this->get('/api/posts');

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    [
                        'data' => [
                            'type' => 'posts',
                            'post_id' => $posts->last()->id,
                            'attributes' => [
                                'body' => $posts->last()->body,
                                'image' => url($posts->last()->image),
                                'posted_at' => $posts->last()->created_at->diffForHumans(),
                            ]
                        ]
                    ],
                    [
                        'data' => [
                            'type' => 'posts',
                            'post_id' => $posts->first()->id,
                            'attributes' => [
                                'body' => $posts->first()->body,
                                'image' => url($posts->first()->image),
                                'posted_at' => $posts->first()->created_at->diffForHumans(),
                            ]
                        ]
                    ]
                ],
                'links' => [
                    'self' => url('/posts'),
                ]
            ]);
    }

    /** @get_list_posts |Người dùng chỉ có thể truy xuất các bài đăng của họ*/
    public function a_user_can_only_retrieve_their_posts()
    {
        $this->withoutExceptionHandling();

        $this->actingAs($user = User::factory()->create(), 'api');
        $posts = Post::factory()->create();

        $response = $this->get('/api/posts');

        $response->assertStatus(200)
            ->assertExactJson([
                'data' => [],
                'links' => [
                    'self' => url('/posts')
                ]
            ]);
    }
}
