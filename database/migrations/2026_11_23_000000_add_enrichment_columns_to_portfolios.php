<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('portfolios', function (Blueprint $t): void {
            $t->string('amount_status')->default('unavailable'); // source|derived|estimated|unavailable
            $t->string('amount_source')->nullable();
            $t->string('amount_currency', 3)->nullable();
            $t->decimal('amount_npr_bn', 14, 2)->nullable();
            $t->string('amount_basis')->nullable();
            $t->unsignedTinyInteger('layer_no')->nullable();
            $t->string('layer_name')->nullable();
            $t->unsignedInteger('parent_id')->nullable()->index();
            $t->json('keywords')->nullable();
            $t->char('source_hash', 40)->nullable();
            $t->timestamp('imported_at')->nullable();
            $t->index(['dataset', 'is_overview']);
            $t->index(['dataset', 'sdg_num']);
        });

        DB::table('portfolios')->whereNotNull('total')->update(['amount_status' => 'source']);
        DB::table('portfolios')->whereIn('dataset', ['sdg_intervention', 'sdg_indicator'])->whereNotNull('total')
            ->update(['amount_currency' => 'NPR', 'amount_npr_bn' => DB::raw('total')]);
        DB::table('portfolios')->where('dataset', 'sdg_indicator')->update(['amount_basis' => 'allocated_equal_share']);

        $roman = ['I' => 1, 'II' => 2, 'III' => 3, 'IV' => 4, 'V' => 5, 'VI' => 6, 'VII' => 7, 'VIII' => 8, 'IX' => 9, 'X' => 10];
        foreach (DB::table('portfolios')->whereNotNull('layer')->get(['portfolio_id', 'layer']) as $r) {
            if (preg_match('/^Layer\s+([IVX]+)\s*[—–-]\s*(.+)$/u', trim($r->layer), $m)) {
                DB::table('portfolios')->where('portfolio_id', $r->portfolio_id)
                    ->update(['layer_no' => $roman[$m[1]] ?? null, 'layer_name' => trim($m[2])]);
            }
        }

        $ov = [];
        foreach (DB::table('portfolios')->where('is_overview', true)->whereNotNull('sdg_num')->get(['portfolio_id', 'dataset', 'sdg_num']) as $o) {
            $ov[$o->dataset.'|'.$o->sdg_num] ??= $o->portfolio_id;
        }
        foreach (DB::table('portfolios')->where('is_overview', false)->whereNotNull('sdg_num')->get(['portfolio_id', 'dataset', 'sdg_num']) as $r) {
            if (isset($ov[$r->dataset.'|'.$r->sdg_num])) {
                DB::table('portfolios')->where('portfolio_id', $r->portfolio_id)->update(['parent_id' => $ov[$r->dataset.'|'.$r->sdg_num]]);
            }
        }

        foreach (DB::table('portfolios')->get(['portfolio_id', 'code', 'title', 'total']) as $r) {
            DB::table('portfolios')->where('portfolio_id', $r->portfolio_id)
                ->update(['source_hash' => sha1($r->code.'|'.$r->title.'|'.$r->total), 'imported_at' => now()]);
        }
    }

    public function down(): void
    {
        Schema::table('portfolios', function (Blueprint $t): void {
            $t->dropIndex(['dataset', 'is_overview']);
            $t->dropIndex(['dataset', 'sdg_num']);
            $t->dropIndex(['parent_id']);
            $t->dropColumn(['amount_status', 'amount_source', 'amount_currency', 'amount_npr_bn', 'amount_basis', 'layer_no', 'layer_name', 'parent_id', 'keywords', 'source_hash', 'imported_at']);
        });
    }
};
