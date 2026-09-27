<?php

namespace Tests\Feature\SuperAdmin;

use App\Models\ErrorLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class ErrorLogCaptureTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_huge_nested_field_is_truncated_so_the_error_itself_still_gets_logged(): void
    {
        $blob    = str_repeat('A', 5_000_000);
        $request = Request::create('/screenshots/upload', 'POST', [
            'password' => 'secret', 'note' => $blob,
            'attendances' => ['7' => ['image' => $blob, 'status' => 'present']],
        ]);

        ErrorLog::capture(new \RuntimeException('boom'), $request);

        $log = ErrorLog::first();
        $this->assertNotNull($log, 'the error must be recorded');
        $this->assertSame('boom', $log->message);
        $this->assertSame(500, mb_strlen($log->request_data['note']));
        $this->assertSame(500, mb_strlen($log->request_data['attendances']['7']['image']));
        $this->assertSame('present', $log->request_data['attendances']['7']['status']);
        $this->assertArrayNotHasKey('password', $log->request_data);
    }
}
