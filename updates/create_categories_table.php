<?php namespace Octoshop\Categories\Updates;

use Schema;
use Octoshop\Core\Updates\Migration;
use October\Rain\Database\Schema\Blueprint;

class CreateCategoriesTable extends Migration
{
    public function up()
    {
        Schema::create('octoshop_categories', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('name')->index();
            $table->string('slug')->index()->unique();
            $table->text('description')->nullable();
            $table->boolean('is_enabled')->default(false);
            $table->boolean('is_visible')->default(true);
            $table->integer('parent_id')->unsigned()->index()->nullable();
            $table->integer('nest_left')->default(0);
            $table->integer('nest_right')->default(0);
            $table->integer('nest_depth')->default(0);
            $table->timestamps();
        });

        Schema::create('octoshop_product_categories', function (Blueprint $table) {
            $table->integer('product_id')->unsigned();
            $table->integer('category_id')->unsigned();
            $table->timestamps();

            $table->primary(['product_id', 'category_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('octoshop_categories');
        Schema::dropIfExists('octoshop_product_categories');
    }
}
