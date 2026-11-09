<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domains\Editor\Models\DesignProposal;
use App\Domains\Editor\Services\ProposalDocumentValidator;
use App\Domains\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

final class IvoryProposalTest extends TestCase
{
    use RefreshDatabase;

    private const URL = '/app/proposals/ivory-wedding';

    protected function beforeRefreshingDatabase(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            $this->markTestSkipped('Requires isolated MySQL.');
        }
        if (! str_ends_with(DB::connection()->getDatabaseName(), '_test')) {
            throw new \RuntimeException('Unsafe test database.');
        }
    }

    /** @return array<string, mixed> */
    private function document(): array
    {
        return app(ProposalDocumentValidator::class)->initialDocument();
    }

    public function test_guest_cannot_read_or_save_and_get_does_not_create_records(): void
    {
        $this->get(self::URL)->assertRedirect('/login');
        $this->patchJson(self::URL, [])->assertUnauthorized();
        $owner = User::factory()->create();
        $this->actingAs($owner)->get(self::URL)->assertInertia(fn (AssertableInertia $page) => $page->component('App/Proposals/IvoryWedding')->where('revision', 0)->where('document.template', 'ivory-wedding'));
        $this->assertDatabaseCount('design_proposals', 0);
    }

    public function test_independent_parts_roundtrip_without_creating_events_or_templates(): void
    {
        $owner = User::factory()->create();
        $document = $this->document();
        $document['parts'][0]['visible'] = false;
        $document['parts'][3]['x'] = 35;
        $document['parts'][4]['x'] = -14;
        $document['parts'][8]['content'] = '';
        $this->actingAs($owner)->patchJson(self::URL, ['document' => $document, 'expectedRevision' => 0])
            ->assertOk()->assertJsonPath('revision', 1)->assertJsonPath('document.parts.8.content', '');
        $this->assertEquals($document, DesignProposal::query()->sole()->document);
        $this->get(self::URL)->assertInertia(fn (AssertableInertia $page) => $page->where('revision', 1)->where('document.parts.3.x', 35));
        $this->assertDatabaseCount('templates', 0);
        $this->assertDatabaseCount('events', 0);
    }

    public function test_user_drafts_are_isolated_and_revision_conflicts_do_not_overwrite(): void
    {
        $first = User::factory()->create();
        $second = User::factory()->create();
        $document = $this->document();
        $this->actingAs($first)->patchJson(self::URL, ['document' => $document, 'expectedRevision' => 0])->assertOk();
        $document['parts'][8]['content'] = 'دعوة أخرى';
        $this->actingAs($second)->get(self::URL)->assertInertia(fn (AssertableInertia $page) => $page->where('revision', 0));
        $this->patchJson(self::URL, ['document' => $document, 'expectedRevision' => 5])->assertConflict()->assertJsonPath('snapshot.revision', 0);
        $this->patchJson(self::URL, ['document' => $document, 'expectedRevision' => 0])->assertOk();
        $this->actingAs($first)->patchJson(self::URL, ['document' => $document, 'expectedRevision' => 0])->assertConflict();
        $this->assertSame('Wedding Invitation', DesignProposal::query()->where('user_id', $first->id)->sole()->document['parts'][8]['content']);
        $this->assertDatabaseCount('design_proposals', 2);
    }

    public function test_malformed_parts_external_images_and_unknown_fields_are_rejected(): void
    {
        $this->actingAs(User::factory()->create());
        foreach ([
            fn (array $doc): array => array_replace($doc, ['owner_id' => 15]),
            function (array $doc): array {
                $doc['parts'][0]['source'] = 'https://example.com/track.png';

                return $doc;
            },
            function (array $doc): array {
                $doc['parts'][0]['source'] = 'data:image/png;base64,SGVsbG8=';

                return $doc;
            },
            function (array $doc): array {
                $doc['parts'][0]['opacity'] = 5;

                return $doc;
            },
            function (array $doc): array {
                $doc['parts'][1]['id'] = 'architecture';

                return $doc;
            },
            function (array $doc): array {
                $doc['layerOrder'][1] = $doc['layerOrder'][0];

                return $doc;
            },
            function (array $doc): array {
                $doc['parts'][8]['content'] = '<script>alert(1)</script>';

                return $doc;
            },
        ] as $mutate) {
            $this->patchJson(self::URL, ['document' => $mutate($this->document()), 'expectedRevision' => 0])->assertUnprocessable()->assertJsonValidationErrors('document');
        }
        $this->assertDatabaseCount('design_proposals', 0);
    }

    public function test_valid_custom_png_is_saved_as_part_of_the_private_draft(): void
    {
        $document = $this->document();
        $document['parts'][0]['source'] = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aZxsAAAAASUVORK5CYII=';
        $this->actingAs(User::factory()->create())->patchJson(self::URL, ['document' => $document, 'expectedRevision' => 0])->assertOk();
        $this->assertSame($document['parts'][0]['source'], DesignProposal::query()->sole()->document['parts'][0]['source']);
    }
}
