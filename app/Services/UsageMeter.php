<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class UsageMeter
{
    public function __construct(public EntitlementService $access) {}

    public function counter(string $user, string $bucket): string
    {
        return hash('sha256', $user.':'.$bucket);
    }

    public function reserve(Request $request, string $product, string $kind, int $messages): array
    {
        $e = $this->access->requireProduct($request, $product);
        abort_unless($this->access->settings->get()['aiEnabled'], 403, 'AI processing is disabled.');
        $counter = $this->counter($request->user()->id, $e['bucket']);
        $id = (string) Str::uuid();
        DB::transaction(function () use ($request, $e, $counter, $id, $product, $kind, $messages) {
            DB::table('usage_counters')->insertOrIgnore(['id' => $counter, 'user_id' => $request->user()->id, 'messages' => 0, 'requests' => 0, 'created_at' => now(), 'updated_at' => now()]);
            $row = DB::table('usage_counters')->where('id', $counter)->lockForUpdate()->first();
            abort_if($row->messages + $messages > $e['messages'] || $row->requests + 1 > $e['requests'], 429, 'Your plan usage limit has been reached.');
            DB::table('usage_counters')->where('id', $counter)->update(['messages' => $row->messages + $messages, 'requests' => $row->requests + 1, 'updated_at' => now()]);
            DB::table('usage_reservations')->insert(['id' => $id, 'user_id' => $request->user()->id, 'counter_id' => $counter, 'product' => $product, 'kind' => $kind, 'messages' => $messages, 'status' => 'reserved', 'created_at' => now(), 'updated_at' => now()]);
        }, 3);

        return ['id' => $id, 'counter' => $counter, 'messages' => $messages];
    }

    public function finish(array $reservation, bool $success): void
    {
        DB::transaction(function () use ($reservation, $success) {
            $row = DB::table('usage_reservations')->where('id', $reservation['id'])->lockForUpdate()->first();
            if (! $row || $row->status !== 'reserved') {
                return;
            }
            if (! $success) {
                $counter = DB::table('usage_counters')->where('id', $row->counter_id)->lockForUpdate()->first();
                DB::table('usage_counters')->where('id', $row->counter_id)->update(['messages' => max(0, $counter->messages - $row->messages), 'requests' => max(0, $counter->requests - 1), 'updated_at' => now()]);
            }
            DB::table('usage_reservations')->where('id', $row->id)->update(['status' => $success ? 'completed' : 'failed', 'updated_at' => now()]);
        }, 3);
    }
}
