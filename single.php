<?php get_header(); ?>

<article class="w-full bg-background">
    <div class="mx-auto max-w-3xl px-6 py-12 md:py-16">

        <a href="<?php echo esc_url( snel_url( '/' ) ); ?>" class="mb-8 inline-flex items-center gap-2 text-sm font-bold text-muted-foreground hover:text-foreground transition-colors">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/></svg>
            <?php echo esc_html( snel__( 'Back to home' ) ); ?>
        </a>

        <div class="flex flex-wrap items-center gap-3 mb-6">
            <?php
            $categories = get_the_category();
            if ( $categories ) :
                foreach ( $categories as $cat ) : ?>
                    <span class="inline-block border-2 border-foreground bg-accent px-2.5 py-0.5 text-xs font-bold uppercase tracking-wide text-accent-foreground">
                        <?php echo esc_html( $cat->name ); ?>
                    </span>
                <?php endforeach;
            endif; ?>
            <span class="text-sm font-medium text-muted-foreground"><?php echo get_the_date(); ?></span>
            <span class="text-sm text-muted-foreground">&middot;</span>
            <span class="text-sm font-medium text-muted-foreground">
                <?php
                $words   = str_word_count( wp_strip_all_tags( get_the_content() ) );
                $minutes = max( 1, (int) ceil( $words / 200 ) );
                echo $minutes . ' ' . esc_html( snel__( 'min read' ) );
                ?>
            </span>
        </div>

        <h1 class="text-3xl font-extrabold leading-tight tracking-tight text-foreground md:text-4xl lg:text-5xl">
            <?php echo esc_html( snel_title() ); ?>
        </h1>

        <?php $excerpt = snel_excerpt(); if ( $excerpt ) : ?>
        <p class="mt-6 text-lg leading-relaxed text-muted-foreground">
            <?php echo esc_html( $excerpt ); ?>
        </p>
        <?php endif; ?>

        <?php if ( has_post_thumbnail() ) : ?>
        <div class="mt-10 aspect-video w-full overflow-hidden border-2 border-foreground">
            <?php the_post_thumbnail( 'full', [ 'class' => 'h-full w-full object-cover' ] ); ?>
        </div>
        <?php endif; ?>

        <div class="mt-12 prose prose-lg max-w-none">
            <?php the_content(); ?>
        </div>

        <div class="mt-12 flex items-center gap-4 border-t-2 border-foreground pt-8">
            <?php $avatar = get_avatar_url( get_the_author_meta( 'ID' ) ); ?>
            <?php if ( $avatar ) : ?>
                <img src="<?php echo esc_url( $avatar ); ?>" alt="" class="h-14 w-14 border-2 border-foreground object-cover">
            <?php else : ?>
                <div class="h-14 w-14 border-2 border-foreground bg-muted flex items-center justify-center text-sm font-bold text-muted-foreground">
                    <?php echo esc_html( strtoupper( substr( get_the_author_meta( 'display_name' ), 0, 2 ) ) ); ?>
                </div>
            <?php endif; ?>
            <div>
                <p class="text-sm font-extrabold text-foreground"><?php echo esc_html( get_the_author_meta( 'display_name' ) ); ?></p>
                <?php $bio = get_the_author_meta( 'description' ); if ( $bio ) : ?>
                <p class="text-sm text-muted-foreground"><?php echo esc_html( $bio ); ?></p>
                <?php endif; ?>
            </div>
        </div>

    </div>
</article>

<?php get_footer(); ?>
