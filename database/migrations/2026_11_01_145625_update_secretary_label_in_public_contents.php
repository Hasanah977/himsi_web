<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $this->replaceLabel('Sekretaris', 'Sekertaris');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->replaceLabel('Sekertaris', 'Sekretaris');
    }

    private function replaceLabel(string $from, string $to): void
    {
        DB::table('public_contents')->where('section', 'leaders')->orderBy('id')->each(function (object $record) use ($from, $to): void {
            $payload = json_decode($record->payload, true, flags: JSON_THROW_ON_ERROR);
            if (isset($payload['role']) && is_string($payload['role'])) {
                $label = preg_replace('/\b'.$from.'\b/i', $to, $payload['role']);
                if ($label !== $payload['role']) {
                    $payload['role'] = $label;
                    DB::table('public_contents')->where('id', $record->id)->update(['payload' => json_encode($payload, JSON_THROW_ON_ERROR)]);
                }
            }
        });
    }
};
