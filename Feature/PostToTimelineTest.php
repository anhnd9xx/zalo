<?php

namespace Tests\Feature;

use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PostToTimelineTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    /**
     * A basic feature test example.
     * @add_post | Một người dùng có thể đăng một bài đăng văn bản
     * @return void
     */
    public function a_user_can_post_a_text_post()
    {
        $this->actingAs($user = \App\Models\User::factory()->create(), 'api');

        $response = $this->post('/api/posts', [
            'body' => 'Testing Body',
        ]);

        $post = Post::first();

        $this->assertCount(1, Post::all());
        $this->assertEquals($user->id, $post->user_id);
        $this->assertEquals('Testing Body', $post->body);
        $response->assertStatus(201)
            ->assertJson([
                'data' => [
                    'type' => 'posts',
                    'post_id' => $post->id,
                    'attributes' => [
                        'posted_by' => [
                            'data' => [
                                'attributes' => [
                                    'name' => $user->name,
                                ]
                            ]
                        ],
                        'body' => 'Testing Body',
                    ]
                ],
                'links' => [
                    'self' => url('/posts/' . $post->id),
                ]
            ]);
    }

    /** @add_post | Có thể đăng bài với nội dung và hình ảnh */
    public function a_user_can_post_a_text_post_with_an_image()
    {
        $this->withoutExceptionHandling();
        $this->actingAs($user = \App\Models\User::factory()->create(), 'api');

        $file = UploadedFile::fake()->image('user-post.jpg');

        $response = $this->post('/api/posts', [
            'body' => 'Testing Body',
            'images' => $file,
            'width' => 100,
            'height' => 100,
        ]);

        Storage::disk('public')->assertExists('post-images/' . $file->hashName());
        $response->assertStatus(201)
            ->assertJson([
                'data' => [
                    'attributes' => [
                        'body' => 'Testing Body',
                        'image' => url('post-images/' . $file->hashName()),
                    ]
                ],
            ]);
    }
}
