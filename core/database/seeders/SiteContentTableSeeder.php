<?php namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use EvolutionCMS\Models\SiteContent;
use Illuminate\Support\Facades\Schema;

/**
 * Seeds an initial "install success" document for a fresh installation.
 *
 * Safety: this seeder is idempotent and will not overwrite existing site content.
 * If the `site_content` table already contains any rows, it does nothing.
 */
class SiteContentTableSeeder extends Seeder
{
    /**
     * Execute the database seeder.
     *
     * Inserts the default "install success" document only when the `site_content` table is empty.
     */
    public function run(): void
    {
        if (!Schema::hasTable('site_content')) {
            return;
        }

        if (DB::table('site_content')->count() > 0) {
            return;
        }

        $resource = SiteContent::create(
            [
                'type'            => 'document',
                'contentType'     => 'text/html',
                'pagetitle'       => 'Evolution CMS Install Success',
                'longtitle'       => 'Welcome to the Evolution CMS Content Management System',
                'description'     => '',
                'alias'           => 'minimal-base',
                'link_attributes' => '',
                'published'       => 1,
                'pub_date'        => 0,
                'unpub_date'      => 0,
                'parent'          => 0,
                'isfolder'        => 0,
                'introtext'       => '',
                'content'         => '<h3>Install Successful!</h3>
<p>You have successfully installed Evolution CMS.</p>

<h3>Getting Help</h3>
<p>The <a href="http://evo.im/" target="_blank">Evolution CMS Community</a> provides a great starting point to learn all things Evolution CMS, or you can also <a href="http://evo.im/">see some great learning resources</a> (books, tutorials, blogs and screencasts).</p>
<p>Welcome to Evolution CMS!</p>
',
                'richtext'        => 1,
                'template'        => 1,
                'searchable'      => 1,
                'cacheable'       => 1,
                'createdby'       => 1,
                'createdon'       => time(),
                'editedby'        => 1,
                'editedon'        => time(),
                'deleted'         => 0,
                'deletedon'       => 0,
                'deletedby'       => 0,
                'publishedon'     => time(),
                'publishedby'     => 1,
                'menutitle'       => 'Base Install',
                'hide_from_tree'  => 0,
                'privateweb'      => 0,
                'privatemgr'      => 0,
                'content_dispo'   => 0,
                'hidemenu'        => 0,
                'alias_visible'   => 1,
            ]);
        $resource->save();
    }
}
