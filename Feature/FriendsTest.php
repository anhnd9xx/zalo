<?php

namespace Tests\Feature;

use App\Models\Friend;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class FriendsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @set_request_friend | người dùng có thể gửi yêu cầu kết bạn
     *
     */
    public function a_user_can_send_a_friend_request()
    {
        $this->actingAs($user = User::factory()->create(), 'api');
        $anotherUser = User::factory()->create();

        $response = $this->post('/api/friend-request', [
            'friend_id' => $anotherUser->id,
        ])->assertStatus(200);

        $friendRequest = \App\Models\Friend::first();

        $this->assertNotNull($friendRequest);
        $this->assertEquals($anotherUser->id, $friendRequest->friend_id);
        $this->assertEquals($user->id, $friendRequest->user_id);
        $response->assertJson([
            'data' => [
                'type' => 'friend-request',
                'friend_request_id' => $friendRequest->id,
                'attributes' => [
                    'confirmed_at' => null,
                ]
            ],
            'links' => [
                'self' => url('/users/' . $anotherUser->id),
            ]
        ]);
    }

    /**
     * @get_requested_friend | người dùng chỉ có thể gửi yêu cầu kết bạn một lần
     *
     */
    public function a_user_can_send_a_friend_request_only_once()
    {
        $this->actingAs($user = User::factory()->create(), 'api');
        $anotherUser = User::factory()->create();

        $this->post('/api/friend-request', [
            'friend_id' => $anotherUser->id,
        ])->assertStatus(200);
        $this->post('/api/friend-request', [
            'friend_id' => $anotherUser->id,
        ])->assertStatus(200);

        $friendRequest = \App\Models\Friend::first();
        $this->(1, $friendRequest);

    }

    /** @friend-request | chỉ những người dùng hợp lệ mới có thể được yêu cầu kết bạn */
    public function only_valid_users_can_be_friend_requested()
    {
//        $this->withoutExceptionHandling();

        $this->actingAs($user = User::factory()->create(), 'api');

        $response = $this->post('/api/friend-request', [
            'friend_id' => 12,
        ])->assertStatus(404);

        $this->assertNull(\App\Models\Friend::first());
        $response->assertJson([
            'errors' => [
                'code' => 404,
                'title' => 'User Not Found',
                'detail' => 'Unable to locate the user with the given information'
            ]
        ]);
    }

    /** @set_accept_friend | chấp nhận yêu cầu kết bạn */
    public function friend_requests_can_be_accepted()
    {
        $this->actingAs($user = User::factory()->create(), 'api');
        $anotherUser = User::factory()->create();
        $this->post('/api/friend-request', [
            'friend_id' => $anotherUser->id,
        ])->assertStatus(200);

        $response = $this->actingAs($anotherUser, 'api')
            ->post('/api/friend-request-response', [
                'user_id' => $user->id,
                'status' => 1,
            ])->assertStatus(200);

        $friendRequest = \App\Models\Friend::first();
        $this->assertNotNull($friendRequest->confirmed_at);
        $this->assertInstanceOf(Carbon::class, $friendRequest->confirmed_at);
        $this->assertEquals(now()->startOfSecond(), $friendRequest->confirmed_at);
        $this->assertEquals(1, $friendRequest->status);
        $response->assertJson([
            'data' => [
                'type' => 'friend-request',
                'friend_request_id' => $friendRequest->id,
                'attributes' => [
                    'confirmed_at' => $friendRequest->confirmed_at->diffForHumans(),
                    'friend_id' => $friendRequest->friend_id,
                    'user_id' => $friendRequest->user_id,
                ]
            ],
            'links' => [
                'self' => url('/users/' . $anotherUser->id),
            ]
        ]);
    }

    /** @set_accept_friend | chỉ những yêu cầu kết bạn hợp lệ mới có thể được chấp nhận*/
    public function only_valid_friend_requests_can_be_accepted()
    {
        $anotherUser = User::factory()->create();

        $response = $this->actingAs($anotherUser, 'api')
            ->post('/api/friend-request-response', [
                'user_id' => 12,
                'status' => 1,
            ])->assertStatus(404);

        $this->assertNull(\App\Models\Friend::first());
        $response->assertJson([
            'errors' => [
                'code' => 404,
                'title' => 'Friend Request Not Found',
                'detail' => 'Unable to locate the friend request with the given information'
            ]
        ]);
    }

    /** @set_accept_friend | chỉ người nhận mới có thể chấp nhận yêu cầu kết bạn */
    public function only_the_recipient_can_accept_a_friend_request()
    {
        $this->actingAs($user = User::factory()->create(), 'api');
        $anotherUser = User::factory()->create();

        $this->post('/api/friend-request', [
            'friend_id' => $anotherUser->id,
        ])->assertStatus(200);

        $response = $this->actingAs(User::factory()->create(), 'api')
            ->post('/api/friend-request-response', [
                'user_id' => $user->id,
                'status' => 1,
            ])->assertStatus(404);

        $friendRequest = Friend::first();
        $this->assertNull($friendRequest->confirmed_at);
        $this->assertNull($friendRequest->status);
        $response->assertJson([
            'errors' => [
                'code' => 404,
                'title' => 'Friend Request Not Found',
                'detail' => 'Unable to locate the friend request with the given information'
            ]
        ]);
    }

    /** @get_requested_friend | cần có id bạn bè cho các yêu cầu kết bạn */
    public function a_friend_id_required_for_friend_requests()
    {
        $response = $this->actingAs($user = User::factory()->create(), 'api')
            ->post('/api/friend-request', [
                'friend_id' => '',
            ]);

        $responseString = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('friend_id', $responseString['errors']['meta']);

    }

    /** @friend-request-response | cần có id người dùng và trạng thái để trả lời yêu cầu kết bạn*/
    public function a_user_id_and_status_is_required_for_friend_request_responses()
    {
        $response = $this->actingAs($user = User::factory()->create(), 'api')
            ->post('/api/friend-request-response', [
                'user_id' => '',
                'status' => '',
            ])->assertStatus(422);

        $responseString = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('user_id', $responseString['errors']['meta']);
        $this->assertArrayHasKey('status', $responseString['errors']['meta']);
    }

    /** @friend-request-response/delete | cần có id người dùng để bỏ qua phản hồi yêu cầu kết bạn*/
    public function a_user_id_is_required_for_ignoring_a_friend_request_responses()
    {
        $response = $this->actingAs($user = User::factory()->create(), 'api')
            ->delete('/api/friend-request-response/delete', [
                'user_id' => '',
            ])->assertStatus(422);

        $responseString = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('user_id', $responseString['errors']['meta']);
    }

    /** @friend-request-response | Sẽ là bạn bè nếu cả 2 bên đều có trong danh bạ điện thoại của nhau*/
    public function a_friendship_is_retrieved_when_fetching_the_profile()
    {
        $this->actingAs($user = User::factory()->create(), 'api');
        $anotherUser = User::factory()->create();
        $friendRequest = \App\Models\Friend::create([
            'user_id' => $user->id,
            'friend_id' => $anotherUser->id,
            'confirmed_at' => now()->subDay(),
            'status' => 1,
        ]);

        $this->get('/api/users/' . $anotherUser->id)
            ->assertStatus(200)
            ->assertJson([
                'data' => [
                    'attributes' => [
                        'friendship' => [
                            'data' => [
                                'friend_request_id' => $friendRequest->id,
                                'attributes' => [
                                    'confirmed_at' => '1 day ago',
                                ]
                            ]
                        ]
                    ]
                ],
            ]);
    }

    /** @friend-request-response | Có thể trở thành bạn bè nếu có hồ sơ của nhau*/
    public function an_inverse_friendship_is_retrieved_when_fetching_the_profile()
    {
        $this->actingAs($user = User::factory()->create(), 'api');
        $anotherUser = User::factory()->create();
        $friendRequest = \App\Models\Friend::create([
            'friend_id' => $user->id,
            'user_id' => $anotherUser->id,
            'confirmed_at' => now()->subDay(),
            'status' => 1,
        ]);

        $this->get('/api/users/' . $anotherUser->id)
            ->assertStatus(200)
            ->assertJson([
                'data' => [
                    'attributes' => [
                        'friendship' => [
                            'data' => [
                                'friend_request_id' => $friendRequest->id,
                                'attributes' => [
                                    'confirmed_at' => '1 day ago',
                                ]
                            ]
                        ]
                    ]
                ],
            ]);
    }

    /** @friend-request-response | yêu cầu kết bạn có thể bị bỏ qua*/
    public function friend_requests_can_be_ignored()
    {
        $this->withoutExceptionHandling();

        $this->actingAs($user = User::factory()->create(), 'api');
        $anotherUser = User::factory()->create();

        $this->post('/api/friend-request', [
            'friend_id' => $anotherUser->id,
        ])->assertStatus(200);

        $response = $this->actingAs($anotherUser, 'api')
            ->delete('/api/friend-request-response/delete', [
                'user_id' => $user->id,
            ])->assertStatus(204);

        $friendRequest = \App\Models\Friend::first();
        $this->assertNull($friendRequest);
        $response->assertNoContent();
    }

    /** @friend-request-response | chỉ người nhận mới có thể bỏ qua yêu cầu kết bạn */
    public function only_the_recipient_can_ignore_a_friend_request()
    {
        $this->actingAs($user = User::factory()->create(), 'api');
        $anotherUser = User::factory()->create();

        $this->post('/api/friend-request', [
            'friend_id' => $anotherUser->id,
        ])->assertStatus(200);

        $response = $this->actingAs(User::factory()->create(), 'api')
            ->delete('/api/friend-request-response/delete', [
                'user_id' => $user->id,
            ])->assertStatus(404);

        $friendRequest = Friend::first();
        $this->assertNull($friendRequest->confirmed_at);
        $this->assertNull($friendRequest->status);
        $response->assertJson([
            'errors' => [
                'code' => 404,
                'title' => 'Friend Request Not Found',
                'detail' => 'Unable to locate the friend request with the given information'
            ]
        ]);
    }


}
