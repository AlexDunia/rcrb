<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Allblogpost;

class AllblogpostSeeder extends Seeder
{
    public function run(): void
    {
        Allblogpost::create(['title' => 'First Blog Post', 'content' => 'Content of the first blog post.']);
        Allblogpost::create(['title' => 'Second Blog Post', 'content' => 'Content of the second blog post.']);
    }
}