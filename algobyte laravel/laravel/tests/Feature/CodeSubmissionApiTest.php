<?php

namespace Tests\Feature;

use App\Models\CodeSubmission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CodeSubmissionApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'piston.base_url' => 'http://127.0.0.1:2000/api/v2',
            'piston.http_timeout_seconds' => 10,
        ]);
    }

    public function test_it_executes_javascript_code_and_stores_the_output(): void
    {
        Http::fake(function (Request $request) {
            if ($request->method() === 'POST' && str_contains($request->url(), '/execute')) {
                return Http::response([
                    'language' => 'javascript',
                    'version' => '18.15.0',
                    'run' => [
                        'stdout' => "Hello AlgoByte\n",
                        'stderr' => '',
                        'code' => 0,
                        'signal' => null,
                        'output' => "Hello AlgoByte\n"
                    ],
                ], 200);
            }

            return Http::response([], 404);
        });

        $response = $this->postJson('/api/code-submissions', [
            'language' => 'javascript',
            'code' => 'console.log("Hello AlgoByte");',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('message', 'Code executed successfully through Piston.')
            ->assertJsonPath('data.language', 'javascript')
            ->assertJsonPath('data.status', 'completed')
            ->assertJsonPath('data.stdout', 'Hello AlgoByte')
            ->assertJsonPath('data.exit_code', 0)
            ->assertJsonPath('data.runtime', 'javascript')
            ->assertJsonPath('data.runtime_version', '18.15.0')
            ->assertJsonPath('data.signal', null);

        $this->assertDatabaseHas(CodeSubmission::class, [
            'language' => 'javascript',
            'status' => 'completed',
            'exit_code' => 0,
            'runtime' => 'javascript',
            'runtime_version' => '18.15.0',
        ]);

        Http::assertSentCount(1);
    }

    public function test_it_returns_compile_errors_for_invalid_c_code(): void
    {
        Http::fake(function (Request $request) {
            if ($request->method() === 'POST' && str_contains($request->url(), '/execute')) {
                return Http::response([
                    'language' => 'c',
                    'version' => '10.2.0',
                    'compile' => [
                        'stdout' => '',
                        'stderr' => "main.c:2:13: error: expected ';' before '}' token\n",
                        'code' => 1,
                        'signal' => null,
                        'output' => "main.c:2:13: error: expected ';' before '}' token\n"
                    ],
                ], 200);
            }

            return Http::response([], 404);
        });

        $response = $this->postJson('/api/code-submissions', [
            'language' => 'c',
            'code' => "int main(void) {\n    return 0\n}\n",
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('message', 'Code compilation failed in Piston.')
            ->assertJsonPath('data.language', 'c')
            ->assertJsonPath('data.status', 'compile_error')
            ->assertJsonPath('data.runtime', 'c')
            ->assertJsonPath('data.runtime_version', '10.2.0');

        $this->assertNotEmpty($response->json('data.compile_output'));
    }

    public function test_it_validates_the_submission_payload(): void
    {
        $response = $this->postJson('/api/code-submissions', [
            'language' => 'cpp',
            'code' => '',
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['language', 'code']);
    }

    public function test_it_returns_a_clear_message_when_piston_is_offline(): void
    {
        Http::fake([
            '*' => Http::failedConnection('cURL error 7: Failed to connect to 127.0.0.1 port 2000'),
        ]);

        $response = $this->postJson('/api/code-submissions', [
            'language' => 'python',
            'code' => 'print("Hello")',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.status', 'runner_unavailable')
            ->assertJsonPath('message', 'Piston is unreachable at http://127.0.0.1:2000/api/v2. Check your local Piston container status and try again.');

        $this->assertStringContainsString(
            'cURL error 7',
            (string) $response->json('data.stderr')
        );
    }
}
