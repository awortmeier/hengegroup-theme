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
<div class="flex min-h-screen flex-col">
    <?php
// Fixierter Header ueber dem Inhalt (kein Abstandshalter). header.js setzt data-scrolled fuer
// Transparenz/Blur. Der Verlauf oben als border-image, weil border-* keinen Verlauf kann.
?>
    <header
        class="elevation-dark fixed inset-x-0 top-0 z-50 shadow-raised border-t-2 border-t-transparent bg-grey-dark px-6 py-4.5 transition-[background-color,backdrop-filter] duration-300 ease-out sm:px-12 data-[scrolled=true]:bg-grey-dark/70 data-[scrolled=true]:backdrop-blur-[6px] [border-image:linear-gradient(90deg,var(--color-henge-grey),var(--color-henge-green),var(--color-henge-blue))_1]"
        id="siteHeader"
        data-site-header
        data-scrolled="false"
    >
        <div class="mx-auto flex max-w-[2000px] items-center justify-between gap-4">
            <a
                class="inline-flex shrink-0 items-center"
                href="<?php echo esc_url(home_url('/')); ?>"
                aria-label="<?php echo esc_attr(get_bloginfo('name')); ?>"
            >
                <img
                    class="hidden h-11 w-auto sm:block"
                    src="<?php echo esc_url(
                        get_theme_file_uri('assets/images/logos/logo-hengegroup-wbm-claim.svg'),
                    ); ?>"
                    alt=""
                    width="387"
                    height="73"
                >
                <img
                    class="h-10 w-auto sm:hidden"
                    src="<?php echo esc_url(
                        get_theme_file_uri('assets/images/logos/logo-hengegroup-wm.svg'),
                    ); ?>"
                    alt=""
                    width="140"
                    height="130"
                >
            </a>

            <div class="flex flex-wrap items-center justify-end gap-3">
                <?php get_template_part(
                    'template-parts/base/navigation-menu/navigation-menu',
                    null,
                    [
                        'config' => [
                            'aria_label' => __('Hauptnavigation', 'hengegroup-theme'),
                            'color' => 'light',
                            'items' => hengegroup_theme_primary_navigation_items(),
                        ],
                    ],
                ); ?>

                <?php get_template_part('template-parts/base/button', null, [
                    'config' => [
                        'text' => __('Kontakt', 'hengegroup-theme'),
                        'href' => home_url('/kontakt/'),
                        'variant' => 'grey-light',
                    ],
                ]); ?>

                <?php
                // Sprachumschalter ist bis zum Multisite-Netzwerk nur Optik. Der Trigger ist kein
                // button.php, weil <summary> schon das interaktive Element ist.
                $language_trigger = sprintf(
                    '<span class="inline-flex items-center gap-1.5 rounded-full border border-grey-light/35 px-3.5 py-2 text-sm font-medium text-grey-light transition-colors hover:bg-grey-light/10">%1$s%2$s</span>',
                    esc_html__('DE', 'hengegroup-theme'),
                    hengegroup_theme_render_icon([
                        'name' => 'chevron-down',
                        'set' => 'lucide',
                        'class' => 'size-2.5',
                    ]),
                );

                ob_start();
                get_template_part('template-parts/base/dropdown-menu/dropdown-menu-item', null, [
                    'config' => ['text' => __('Deutsch', 'hengegroup-theme'), 'href' => '#'],
                ]);
                get_template_part('template-parts/base/dropdown-menu/dropdown-menu-item', null, [
                    'config' => ['text' => __('English', 'hengegroup-theme'), 'href' => '#'],
                ]);
                $language_menu_content = (string) ob_get_clean();

                get_template_part('template-parts/base/dropdown-menu/dropdown-menu', null, [
                    'config' => [
                        'trigger' => $language_trigger,
                        'content' => $language_menu_content,
                        'align' => 'end',
                    ],
                ]);
                ?>
            </div>
        </div>
    </header>

    <?php
// flex-1: Footer bleibt auch bei wenig Inhalt unten.
?>
    <main class="flex-1">
