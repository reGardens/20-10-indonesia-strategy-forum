<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class ExportSeeders extends Command
{
    protected $signature = 'export:seeders';
    protected $description = 'Export database tables to seeder files';

    public function handle()
    {
        $tables = [
            'roles',
            'permissions',
            'users',
            'model_has_roles',
            'model_has_permissions',
            'role_has_permissions',
            'sections',
            'section_items',
            'menus',
            'navbar_settings',
            'footer_settings',
            'seo_settings',
            'marketplace_settings',
            'products',
        ];

        foreach ($tables as $table) {
            $this->info("Exporting table: {$table}");
            $this->exportTable($table);
        }

        $this->info('Seeders exported successfully!');
    }

    protected function exportTable($table)
    {
        $data = DB::table($table)->get();
        $className = str_replace(' ', '', ucwords(str_replace('_', ' ', $table))) . 'Seeder';
        $filePath = database_path("seeders/{$className}.php");

        $content = "<?php\n\nnamespace Database\Seeders;\n\nuse Illuminate\Database\Seeder;\nuse Illuminate\Support\Facades\DB;\n\nclass {$className} extends Seeder\n{\n    public function run(): void\n    {\n        DB::table('{$table}')->delete();\n        \n        DB::table('{$table}')->insert(" . $this->formatData($data) . ");\n    }\n}\n";

        File::put($filePath, $content);
    }

    protected function formatData($data)
    {
        $array = $data->map(function ($item) {
            return (array) $item;
        })->toArray();

        if (empty($array)) {
            return '[]';
        }

        $export = var_export($array, true);
        
        // Convert array() to []
        $export = preg_replace('/array\s*\(/', '[', $export);
        $export = str_replace(')', ']', $export);
        
        return $export;
    }
}
