<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

final class ErrorPagesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware('web')->group(function (): void {
            Route::get('/__error/{code}', static fn (int $code) => abort($code))->whereNumber('code');
            Route::get('/__boom', static function (): never {
                throw new RuntimeException('SECRET-DETAIL-FOR-LOGS-ONLY');
            });
        });
    }

    /** @return array<string, array{int, string}> */
    public static function statuses(): array
    {
        return [
            '403' => [403, 'لا تملكين صلاحية الوصول'],
            '404' => [404, 'لم نجد هذه الصفحة'],
            '419' => [419, 'انتهت صلاحية الصفحة'],
            '429' => [429, 'محاولات كثيرة'],
            '503' => [503, 'ميثاق تحت الصيانة'],
        ];
    }

    #[DataProvider('statuses')]
    public function test_each_error_gets_a_branded_arabic_page_with_the_right_status(int $status, string $title): void
    {
        config(['app.debug' => false]);

        $this->withoutVite()->get('/__error/'.$status)
            ->assertStatus($status)
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Error', false)
                ->where('status', $status)
                ->where('title', $title)
                ->where('meta.noindex', true));
    }

    public function test_server_errors_never_reveal_the_exception_or_a_stack_trace(): void
    {
        config(['app.debug' => false]);

        $response = $this->withoutVite()->get('/__boom')->assertStatus(500);

        $response->assertInertia(fn (AssertableInertia $page) => $page->component('Error', false)->where('status', 500));
        $response->assertDontSee('SECRET-DETAIL-FOR-LOGS-ONLY')->assertDontSee('RuntimeException')->assertDontSee('#0 ');
    }

    public function test_an_unknown_invitation_address_looks_like_any_missing_page(): void
    {
        $this->withoutVite()->get('http://nobody-here.'.config('methaq.invitation_domain').'/')
            ->assertNotFound()
            ->assertInertia(fn (AssertableInertia $page) => $page->component('Error', false)->where('status', 404)->missing('event'));
    }

    public function test_json_clients_keep_plain_json_errors(): void
    {
        $this->getJson('/__error/404')->assertNotFound()->assertJsonMissingPath('component');
    }
}
