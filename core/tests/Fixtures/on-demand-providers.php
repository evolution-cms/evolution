<?php

namespace OnDemandFixture\Gallery {

    use Illuminate\Support\ServiceProvider;

    final class GalleryServiceProvider extends ServiceProvider
    {
        public function register(): void
        {
            $count = $this->app->bound('gallery.registered') ? $this->app->make('gallery.registered') : 0;
            $this->app->instance('gallery.registered', $count + 1);
        }

        public function boot(): void
        {
            $this->app->singleton('gallery', fn () => new Gallery());
            $this->app->alias('gallery', Gallery::class);
            // Like sGallery: the provider declares its facade alias itself, unconditionally.
            $alias = $this->app['config']->get('gallery.alias');
            if (is_string($alias)) {
                class_alias(Facades\GalleryFacade::class, $alias);
            }
        }
    }

    final class Gallery
    {
        public function name(): string
        {
            return 'gallery';
        }
    }
}

namespace OnDemandFixture\Gallery\Facades {

    final class GalleryFacade
    {
    }
}

namespace OnDemandFixture\Editor {

    use Illuminate\Support\ServiceProvider;

    final class EditorServiceProvider extends ServiceProvider
    {
        public function register(): void
        {
            $this->app['events']->listen('evolution.OnRichTextEditorInit', fn () => 'editor');
        }
    }
}

namespace OnDemandFixture\Always {

    use Illuminate\Support\ServiceProvider;

    final class AlwaysServiceProvider extends ServiceProvider
    {
        public function register(): void
        {
            $this->app->instance('always.registered', true);
        }
    }
}
