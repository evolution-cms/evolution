<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSiteContentTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('site_content', function(Blueprint $table)
		{
            $indexPrefix = \DB::getTablePrefix() . $table->getTable();
            $table->integer('id', true, true);
			$table->string('type', 20)->default('document')->index("{$indexPrefix}_typeidx");
			$table->string('contentType', 50)->default('text/html');
			$table->string('pagetitle')->default('');
			$table->string('longtitle')->default('');
			$table->string('description')->default('');
			$table->string('alias', 245)->nullable()->default('')->index("{$indexPrefix}_aliasidx");
			$table->string('link_attributes')->default('')->comment('Link attriubtes');
			$table->integer('published')->default(0);
			$table->integer('pub_date')->default(0);
			$table->integer('unpub_date')->default(0);
			$table->integer('parent')->default(0)->index("{$indexPrefix}_parent");
			$table->integer('isfolder')->default(0);
			$table->text('introtext', 65535)->nullable()->comment('Used to provide quick summary of the document');
			$table->longText('content')->nullable();
			$table->boolean('richtext')->default(1);
			$table->integer('template')->default(0);
			$table->integer('menuindex')->default(0);
			$table->integer('searchable')->default(1);
			$table->integer('cacheable')->default(1);
			$table->integer('createdby')->default(0);
			$table->integer('createdon')->default(0);
			$table->integer('editedby')->default(0);
			$table->integer('editedon')->default(0);
			$table->integer('deleted')->default(0);
			$table->integer('deletedon')->default(0);
			$table->integer('deletedby')->default(0);
			$table->integer('publishedon')->default(0)->comment('Date the document was published');
			$table->integer('publishedby')->default(0)->comment('ID of user who published the document');
			$table->string('menutitle')->default('')->comment('Menu title');
			$table->boolean('donthit')->default(0)->comment('Disable page hit count');
			$table->boolean('privateweb')->default(0)->comment('Private web document');
			$table->boolean('privatemgr')->default(0)->comment('Private manager document');
			$table->boolean('content_dispo')->default(0)->comment('0-inline, 1-attachment');
			$table->boolean('hidemenu')->default(0)->comment('Hide document from menu');
			$table->integer('alias_visible')->default(1);
		});

        $prefix = DB::getTablePrefix();
        $site_content_table_name = (new \EvolutionCMS\Models\SiteContent())->getTable();
        $indexPrefix = $prefix . $site_content_table_name;
        if (isset($_POST['database_type']) && $_POST['database_type'] === 'mysql') {
            DB::statement(
                'ALTER TABLE ' . $prefix . $site_content_table_name
                . " ADD FULLTEXT {$indexPrefix}_content_ft_idx(pagetitle, description, content)"
            );
        }
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
        Schema::table('posts', function($table) {
            $indexPrefix = \DB::getTablePrefix() . $table->getTable();
            $table->dropIndex("{$indexPrefix}_content_ft_idx");
        });
		Schema::drop('site_content');
	}

}
