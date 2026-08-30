<?php

namespace Tests\Feature;

use App\Services\DemoDataBuilder;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** TIJDELIJK: dumpt de demo-uitvoer om NL byte-voor-byte te vergelijken. */
class TmpDemoDumpTest extends TestCase
{
    use RefreshDatabase;

    public function test_dump(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-30 12:00:00'));
        if (getenv('DEMO_BRAND')) {
            config(['brand.active' => getenv('DEMO_BRAND')]);
        }
        app(DemoDataBuilder::class)->build();

        $skip = [
            'companies' => ['kvk_number', 'vat_number', 'ob_number', 'public_slug'],
            'users' => ['email', 'password', 'remember_token'],
            'attachments' => ['file_data', 'size_bytes'],
        ];
        $out = [];
        foreach (DB::select("select name from sqlite_master where type='table' and name not like 'sqlite_%' order by name") as $t) {
            $rows = DB::table($t->name)->orderBy('rowid')->get()->map(fn ($r) => (array) $r)->all();
            foreach ($rows as &$r) {
                foreach ($skip[$t->name] ?? [] as $c) {
                    unset($r[$c]);
                }
                foreach (array_keys($r) as $c) {
                    if (str_contains($c, 'token')) {
                        unset($r[$c]);
                    }
                }
            }
            if ($rows) {
                $out[$t->name] = $rows;
            }
        }
        file_put_contents(getenv('DEMO_DUMP') ?: 'demo-dump.json', json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $this->assertTrue(true);
    }
}
