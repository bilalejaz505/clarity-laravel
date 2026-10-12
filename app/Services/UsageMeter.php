<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UsageMeter
{
    public function __construct(public EntitlementService $access) {}

    public function counter(int $user, string $bucket): string
    {
        return hash('sha256', $user.':'.$bucket);
    }

    public function counterKeys(User $user, string $bucket): array
    {
        return [$this->counter($user->id, $bucket)];
    }

    public function reserve(Request $request, string $product, string $kind, int $messages): array
    {
        $entitlement = $this->access->requireProduct($request, $product);
        abort_unless($this->access->settings->get()['aiEnabled'], 403, 'AI processing is disabled.');
        $key = $this->counter($request->user()->id, $entitlement['bucket']);

        return DB::transaction(function () use ($request, $entitlement, $key, $product, $kind, $messages): array {
            DB::table('usage_counters')->insertOrIgnore(['bucket_key' => $key, 'user_id' => $request->user()->id, 'messages' => 0, 'requests' => 0, 'created_at' => now(), 'updated_at' => now()]);
            $counter = DB::table('usage_counters')->where('bucket_key', $key)->where('user_id', $request->user()->id)->lockForUpdate()->first();
            abort_if($counter->messages + $messages > $entitlement['messages'] || $counter->requests + 1 > $entitlement['requests'], 429, 'Your plan usage limit has been reached.');
            DB::table('usage_counters')->where('id', $counter->id)->update(['messages' => $counter->messages + $messages, 'requests' => $counter->requests + 1, 'updated_at' => now()]);
            $id = DB::table('usage_reservations')->insertGetId(['user_id' => $request->user()->id, 'counter_id' => $counter->id, 'product' => $product, 'kind' => $kind, 'messages' => $messages, 'status' => 'reserved', 'created_at' => now(), 'updated_at' => now()]);

            return ['id' => $id, 'counter' => (int) $counter->id, 'messages' => $messages];
        }, 3);
    }

    public function finish(array $reservation, bool $success): void
    {
        DB::transaction(function () use ($reservation, $success): void {
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
