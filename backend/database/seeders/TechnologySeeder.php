<?php

namespace Database\Seeders;

use App\Models\Technology;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TechnologySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $technologies = [
            // Programming Languages
            [
                'name' => 'PHP',
                'slug' => 'php',
                'description' => 'Server-side scripting language for web development',
                'type' => 'language',
                'color' => '#777BB4',
                'website_url' => 'https://php.net',
                'sort_order' => 1,
            ],
            [
                'name' => 'JavaScript',
                'slug' => 'javascript',
                'description' => 'Dynamic programming language for web development',
                'type' => 'language',
                'color' => '#F7DF1E',
                'website_url' => 'https://developer.mozilla.org/en-US/docs/Web/JavaScript',
                'sort_order' => 2,
            ],
            [
                'name' => 'TypeScript',
                'slug' => 'typescript',
                'description' => 'Typed superset of JavaScript',
                'type' => 'language',
                'color' => '#3178C6',
                'website_url' => 'https://typescriptlang.org',
                'sort_order' => 3,
            ],
            [
                'name' => 'Python',
                'slug' => 'python',
                'description' => 'High-level programming language',
                'type' => 'language',
                'color' => '#3776AB',
                'website_url' => 'https://python.org',
                'sort_order' => 4,
            ],

            // Frameworks
            [
                'name' => 'Laravel',
                'slug' => 'laravel',
                'description' => 'PHP web framework for artisan developers',
                'type' => 'framework',
                'color' => '#FF2D20',
                'website_url' => 'https://laravel.com',
                'sort_order' => 10,
            ],
            [
                'name' => 'Vue.js',
                'slug' => 'vue-js',
                'description' => 'Progressive JavaScript framework',
                'type' => 'framework',
                'color' => '#4FC08D',
                'website_url' => 'https://vuejs.org',
                'sort_order' => 11,
            ],
            [
                'name' => 'React',
                'slug' => 'react',
                'description' => 'JavaScript library for building user interfaces',
                'type' => 'framework',
                'color' => '#61DAFB',
                'website_url' => 'https://react.dev',
                'sort_order' => 12,
            ],
            [
                'name' => 'Next.js',
                'slug' => 'next-js',
                'description' => 'React framework for production',
                'type' => 'framework',
                'color' => '#000000',
                'website_url' => 'https://nextjs.org',
                'sort_order' => 13,
            ],
            [
                'name' => 'Nuxt.js',
                'slug' => 'nuxt-js',
                'description' => 'Vue.js framework for universal applications',
                'type' => 'framework',
                'color' => '#00DC82',
                'website_url' => 'https://nuxt.com',
                'sort_order' => 14,
            ],

            // Libraries & UI
            [
                'name' => 'Tailwind CSS',
                'slug' => 'tailwind-css',
                'description' => 'Utility-first CSS framework',
                'type' => 'library',
                'color' => '#06B6D4',
                'website_url' => 'https://tailwindcss.com',
                'sort_order' => 20,
            ],
            [
                'name' => 'PrimeVue',
                'slug' => 'primevue',
                'description' => 'Vue.js UI component library',
                'type' => 'library',
                'color' => '#41B883',
                'website_url' => 'https://primevue.org',
                'sort_order' => 21,
            ],
            [
                'name' => 'Bootstrap',
                'slug' => 'bootstrap',
                'description' => 'Popular CSS framework',
                'type' => 'library',
                'color' => '#7952B3',
                'website_url' => 'https://getbootstrap.com',
                'sort_order' => 22,
            ],

            // Databases
            [
                'name' => 'MySQL',
                'slug' => 'mysql',
                'description' => 'Open-source relational database',
                'type' => 'database',
                'color' => '#4479A1',
                'website_url' => 'https://mysql.com',
                'sort_order' => 30,
            ],
            [
                'name' => 'PostgreSQL',
                'slug' => 'postgresql',
                'description' => 'Advanced open-source relational database',
                'type' => 'database',
                'color' => '#336791',
                'website_url' => 'https://postgresql.org',
                'sort_order' => 31,
            ],
            [
                'name' => 'Redis',
                'slug' => 'redis',
                'description' => 'In-memory data structure store',
                'type' => 'database',
                'color' => '#DC382D',
                'website_url' => 'https://redis.io',
                'sort_order' => 32,
            ],

            // Tools & Services
            [
                'name' => 'Docker',
                'slug' => 'docker',
                'description' => 'Platform for developing, shipping, and running applications',
                'type' => 'tool',
                'color' => '#2496ED',
                'website_url' => 'https://docker.com',
                'sort_order' => 40,
            ],
            [
                'name' => 'Vite',
                'slug' => 'vite',
                'description' => 'Next generation frontend tooling',
                'type' => 'tool',
                'color' => '#646CFF',
                'website_url' => 'https://vitejs.dev',
                'sort_order' => 41,
            ],
            [
                'name' => 'AWS',
                'slug' => 'aws',
                'description' => 'Amazon Web Services cloud platform',
                'type' => 'service',
                'color' => '#FF9900',
                'website_url' => 'https://aws.amazon.com',
                'sort_order' => 50,
            ],
            [
                'name' => 'DigitalOcean',
                'slug' => 'digitalocean',
                'description' => 'Cloud infrastructure for developers',
                'type' => 'service',
                'color' => '#0080FF',
                'website_url' => 'https://digitalocean.com',
                'sort_order' => 51,
            ],
            [
                'name' => 'Stripe',
                'slug' => 'stripe',
                'description' => 'Payment processing platform',
                'type' => 'service',
                'color' => '#635BFF',
                'website_url' => 'https://stripe.com',
                'sort_order' => 52,
            ],
        ];

        foreach ($technologies as $technology) {
            Technology::create($technology);
        }
    }
}