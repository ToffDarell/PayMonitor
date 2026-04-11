<?php

declare(strict_types=1);

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE members MODIFY occupation TEXT NULL');

        Schema::table('loans', function (Blueprint $table): void {
            $table->index('status');
        });

        Schema::table('loan_schedules', function (Blueprint $table): void {
            $table->index(['loan_id', 'status']);
        });

        DB::table('members')
            ->select(['id', 'address', 'occupation'])
            ->orderBy('id')
            ->chunkById(100, function ($members): void {
                foreach ($members as $member) {
                    $updates = [];

                    if (! blank($member->address) && ! $this->isEncrypted((string) $member->address)) {
                        $updates['address'] = Crypt::encryptString((string) $member->address);
                    }

                    if (! blank($member->occupation) && ! $this->isEncrypted((string) $member->occupation)) {
                        $updates['occupation'] = Crypt::encryptString((string) $member->occupation);
                    }

                    if ($updates !== []) {
                        DB::table('members')
                            ->where('id', $member->id)
                            ->update($updates);
                    }
                }
            });
    }

    public function down(): void
    {
        Schema::table('loan_schedules', function (Blueprint $table): void {
            $table->dropIndex(['loan_id', 'status']);
        });

        Schema::table('loans', function (Blueprint $table): void {
            $table->dropIndex(['status']);
        });

        DB::statement('ALTER TABLE members MODIFY occupation VARCHAR(255) NULL');
    }

    private function isEncrypted(string $value): bool
    {
        try {
            Crypt::decryptString($value);

            return true;
        } catch (DecryptException) {
            return false;
        }
    }
};
