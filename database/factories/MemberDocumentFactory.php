<?php

namespace Database\Factories;

use App\Models\MemberDocument;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MemberDocument>
 */
class MemberDocumentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'category' => 'KRS',
            'title' => 'KRS Semester 5',
            'path' => 'member-archives/'.fake()->uuid().'.pdf',
            'original_name' => 'krs.pdf',
            'size' => 1024,
            'mime_type' => 'application/pdf',
        ];
    }
}
