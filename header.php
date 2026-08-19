<?php declare(strict_types=1) ?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
  <meta charset="<?php bloginfo('charset'); ?>">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <?php wp_head(); ?>
</head>
<body>
<?php wp_body_open(); ?>
<div class="min-h-screen">
  <div class="relative h-20">
        <div class="pointer-events-none invisible absolute inset-0" aria-hidden="true" data-header-sentinel></div>

        <header
        class="fixed left-1/2 z-999 border -translate-x-1/2 transition-[max-width,border-radius,background-color,border-color,box-shadow,backdrop-filter] duration-200 ease-out"
        id="siteHeader"
        data-site-header
        >
            <div class="wrapper flex items-center justify-between gap-4 h-20 px-8">
                <?php if (has_custom_logo()):
                    the_custom_logo();
                else:
                     ?>
                    <a
                        class="inline-flex items-center text-xl font-semibold no-underline"
                        href="<?php echo esc_url(home_url('/')); ?>"
                        aria-label="<?php echo esc_attr(get_bloginfo('name')); ?>"
                    >
                        <span><?php bloginfo('name'); ?></span>
                    </a>
                <?php
                endif; ?>

                <nav aria-label="<?php esc_attr_e('Primary Navigation', 'base-theme'); ?>">
                    <?php wp_nav_menu([
                        'theme_location' => 'primary',
                        'container' => false,
                        'fallback_cb' => false,
                        'menu_class' =>
                            'header-nav-list m-0 flex list-none items-center gap-8 p-0 text-lg',
                    ]); ?>
                </nav>
            </div>

        </header>
    </div>
  
    <main class="min-h-1000">
