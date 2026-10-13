<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach ([
            'Section A' => 'Magalang',
            'Section B' => 'Masunurin',
            'Section C' => 'Matapat',
        ] as $oldName => $newName) {
            DB::table('users')->where('section', $oldName)->update(['section' => $newName]);
        }
    }

    public function down(): void
    {
        foreach ([
            'Magalang' => 'Section A',
            'Masunurin' => 'Section B',
            'Matapat' => 'Section C',
            'Masipag' => null,
        ] as $newName => $oldName) {
            DB::table('users')->where('section', $newName)->update(['section' => $oldName]);
        }
    }
};
