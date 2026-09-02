<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Existing organizations were created before ownership existed; the
     * sign-up admin is their owner. Falls back to the oldest member when
     * an organization has no admin left.
     */
    public function up(): void
    {
        $organizationIds = DB::table('organizations')->pluck('id');

        foreach ($organizationIds as $organizationId) {
            $ownerId = DB::table('users')
                ->where('organization_id', $organizationId)
                ->orderByRaw("case when role = 'admin' then 0 else 1 end")
                ->orderBy('created_at')
                ->orderBy('id')
                ->value('id');

            if ($ownerId !== null) {
                DB::table('organizations')
                    ->where('id', $organizationId)
                    ->update(['owner_id' => $ownerId]);
            }
        }
    }

    public function down(): void
    {
        DB::table('organizations')->update(['owner_id' => null]);
    }
};
