<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('treatments', function (Blueprint $table) {
            $table->foreignId('treatment_category_id')->nullable()->constrained()->nullOnDelete();
        });

        // Migrate data
        $categories = DB::table('treatments')->select('category')->distinct()->pluck('category');
        foreach ($categories as $categoryName) {
            if ($categoryName) {
                $categoryId = DB::table('treatment_categories')->insertGetId([
                    'name' => $categoryName,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                DB::table('treatments')->where('category', $categoryName)->update(['treatment_category_id' => $categoryId]);
            }
        }

        Schema::table('treatments', function (Blueprint $table) {
            $table->dropColumn('category');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('treatments', function (Blueprint $table) {
            $table->string('category')->default('General');
        });

        // Restore data (roughly)
        $treatments = DB::table('treatments')->get();
        foreach ($treatments as $treatment) {
            if ($treatment->treatment_category_id) {
                $category = DB::table('treatment_categories')->where('id', $treatment->treatment_category_id)->first();
                if ($category) {
                    DB::table('treatments')->where('id', $treatment->id)->update(['category' => $category->name]);
                }
            }
        }

        Schema::table('treatments', function (Blueprint $table) {
            $table->dropForeign(['treatment_category_id']);
            $table->dropColumn('treatment_category_id');
        });
    }
};
