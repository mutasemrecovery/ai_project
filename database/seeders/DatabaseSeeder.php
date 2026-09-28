<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $seeders = [
            PermissionSeeder::class,
            LeadGenerationSeeder::class,
            SiteSettingSeeder::class,
            HeroSectionSeeder::class,
            AgencySeeder::class,
            AboutSectionSeeder::class,
            ClientSeeder::class,
        ];

        foreach ($seeders as $seeder) {
            if (class_exists($seeder)) {
                $this->call($seeder);
            }
        }
    }
}
