<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class PostCommentsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic feature test example.
     *
     * @comment  | Nhận xét bài đăng
     */
    public function a_user_can_comment_on_a_post()
    {
        $this->actingAs($user = User::factory()->create(), 'api');
        $post = Post::factory()->create(['id' => 1]);

        $response = $this->post('/api/posts/' . $post->id . '/comment', [
            'body' => 'Test comment',
        ])
            ->assertStatus(200);

        $comment = Comment::first();
        $this->assertCount(1, Comment::all());
        $this->assertEquals($user->id, $comment->user_id);
        $this->assertEquals($user->id, $comment->post_id);
        $this->assertEquals('Test comment', $comment->body);
        $response->assertJson([
            'data' => [
                [
                    'data' => [
                        'type' => 'comments',
                        'comment_id' => 1,
                        'attributes' => [
                            'commented_by' => [
                                'data' => [
                                    'user_id' => $user->id,
                                    'attributes' => [
                                        'name' => $user->name,
                                    ]
                                ]
                            ],
                            'body' => 'Test comment',
                            'commented_at' => $comment->created_at->diffForHumans(),
                        ]
                    ],
                    'links' => [
                        'self' => url('/posts/1'),
                    ]
                ]
            ],
            'links' => [
                'self' => url('/posts'),
            ]
        ]);
    }

    /** @comment | Cần phải có nội dung để có thể nhận xét bài đăng */
    public function a_body_is_required_to_leave_a_comment_on_a_post()
    {
        $this->actingAs($user = User::factory()->create(), 'api');
        $post = Post::factory()->create(['id' => 123]);

        $response = $this->post('/api/posts/' . $post->id . '/comment', [
            'body' => '',
        ])->assertStatus(422);

        $responseString = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('body', $responseString['errors']['meta']);
    }

    /** @comment | Bình luận hiện lên bài đăng */
    public function posts_are_returned_with_comments()
    {
        $this->actingAs($user = User::factory()->create(), 'api');
        $post = Post::factory()->create(['id' => 1, 'user_id' => $user->id]);
        $this->post('/api/posts/' . $post->id . '/comment', [
            'body' => 'Test comment',
        ]);

        $response = $this->get('/api/posts');

        $comment = Comment::first();
        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    [
                        'data' => [
                            'type' => 'posts',
                            'attributes' => [
                                'comments' => [
                                    'data' => [
                                        [
                                            'data' => [
                                                'type' => 'comments',
                                                'comment_id' => 1,
                                                'attributes' => [
                                                    'commented_by' => [
                                                        'data' => [
                                                            'user_id' => $user->id,
                                                            'attributes' => [
                                                                'name' => $user->name,
                                                            ]
                                                        ]
                                                    ],
                                                    'body' => 'Test comment',
                                                    'commented_at' => $comment->created_at->diffForHumans(),
                                                ]
                                            ],
                                            'links' => [
                                                'self' => url('/posts/1'),
                                            ]
                                        ]
                                    ],
                                    'comment_count' => 1,
                                ],
                            ]
                        ]
                    ]
                ]
            ]);
    }
}
