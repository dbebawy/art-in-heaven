<?php
/**
 * My Wins Page - View Purchased Art Pieces
 */
if (!defined('ABSPATH')) exit;

// Use consolidated helper for bidder info and page URLs
$bidder_info = AIH_Template_Helper::get_current_bidder_info();
$is_logged_in = $bidder_info['is_logged_in'];
$bidder = $bidder_info['bidder'];
$bidder_id = $bidder_info['id'];
$bidder_name = $bidder_info['name'];

$gallery_url = AIH_Template_Helper::get_gallery_url();
$my_bids_url = AIH_Template_Helper::get_my_bids_url();
$checkout_url = AIH_Template_Helper::get_checkout_url();
?>
<script>
if (typeof aihAjax === 'undefined') {
    var aihAjax = {
        ajaxurl: '<?php echo admin_url('admin-ajax.php'); ?>',
        nonce: '<?php echo wp_create_nonce('aih_nonce'); ?>',
        isLoggedIn: <?php echo $is_logged_in ? 'true' : 'false'; ?>
    };
}
</script>

<?php if (!$is_logged_in): ?>
<div class="aih-page">
    <header class="aih-header">
        <div class="aih-header-inner">
            <a href="<?php echo esc_url($gallery_url); ?>" class="aih-logo">Art in Heaven</a>
        </div>
    </header>
    <main class="aih-main aih-main-centered">
        <div class="aih-login-card">
            <div class="aih-login-header">
                <div class="aih-ornament">✦</div>
                <h1>Sign In Required</h1>
                <p>Please sign in to view your collection</p>
            </div>
            <div class="aih-login-form">
                <div class="aih-field">
                    <label>Confirmation Code</label>
                    <input type="text" id="aih-login-code" placeholder="XXXXXXXX" autocomplete="off">
                </div>
                <button type="button" id="aih-login-btn" class="aih-btn">Sign In</button>
                <div id="aih-login-msg" class="aih-message"></div>
            </div>
        </div>
    </main>
</div>
<script>
jQuery(document).ready(function($) {
    $('#aih-login-btn').on('click', function() {
        var code = $('#aih-login-code').val().trim().toUpperCase();
        if (!code) { $('#aih-login-msg').addClass('error').text('Enter your code').show(); return; }
        $(this).prop('disabled', true).addClass('loading');
        $.post(aihAjax.ajaxurl, {action:'aih_verify_code', nonce:aihAjax.nonce, code:code}, function(r) {
            if (r.success) location.reload();
            else { $('#aih-login-msg').addClass('error').text(r.data.message).show(); $('#aih-login-btn').prop('disabled', false).removeClass('loading'); }
        }).fail(function() {
            $('#aih-login-msg').addClass('error').text('Connection error. Please try again.').show();
            $('#aih-login-btn').prop('disabled', false).removeClass('loading');
        });
    });
    $('#aih-login-code').on('keypress', function(e) { if (e.which === 13) $('#aih-login-btn').click(); })
        .on('input', function() { this.value = this.value.toUpperCase(); });
});
</script>
<?php return; endif;

// Get purchased items from paid orders
global $wpdb;
$orders_table = AIH_Database::get_table('orders');
$items_table = AIH_Database::get_table('order_items');
$art_table = AIH_Database::get_table('art_pieces');
$images_table = AIH_Database::get_table('art_images');

$purchases = $wpdb->get_results($wpdb->prepare(
    "SELECT
        oi.id as item_id,
        oi.winning_bid,
        o.order_number,
        o.payment_status,
        o.pickup_status,
        o.created_at as order_date,
        ap.id as art_piece_id,
        ap.art_id,
        ap.title,
        ap.artist,
        ap.medium,
        ap.dimensions,
        ap.description,
        COALESCE(ai.watermarked_url, ap.watermarked_url, ap.image_url) as image_url
    FROM {$items_table} oi
    JOIN {$orders_table} o ON oi.order_id = o.id
    JOIN {$art_table} ap ON oi.art_piece_id = ap.id
    LEFT JOIN {$images_table} ai ON ap.id = ai.art_piece_id AND ai.is_primary = 1
    WHERE o.bidder_id = %s
    AND o.payment_status = 'paid'
    ORDER BY o.created_at DESC",
    $bidder_id
));

$cart_count = 0;
$checkout = AIH_Checkout::get_instance();
$cart_count = count($checkout->get_won_items($bidder_id));
?>

<div class="aih-page aih-mywins-page">
<script>(function(){var t=localStorage.getItem('aih-theme');if(t==='dark'){document.currentScript.parentElement.classList.add('dark-mode');}})();</script>
    <header class="aih-header">
        <div class="aih-header-inner">
            <a href="<?php echo esc_url($gallery_url); ?>" class="aih-logo">Art in Heaven</a>
            <nav class="aih-nav">
                <a href="<?php echo esc_url($gallery_url); ?>" class="aih-nav-link">Gallery</a>
                <?php if ($my_bids_url): ?>
                <a href="<?php echo esc_url($my_bids_url); ?>" class="aih-nav-link">My Bids</a>
                <?php endif; ?>
                <a href="#" class="aih-nav-link aih-nav-active">My Collection</a>
            </nav>
            <div class="aih-header-actions">
                <button type="button" class="aih-theme-toggle" id="aih-theme-toggle" title="Toggle dark mode"><svg class="aih-theme-icon aih-icon-sun" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg><svg class="aih-theme-icon aih-icon-moon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg><span class="aih-theme-toggle-label">Theme</span></button>
                <?php if ($checkout_url && $cart_count > 0): ?>
                <a href="<?php echo esc_url($checkout_url); ?>" class="aih-cart-link">
                    <span>🛒</span>
                    <span class="aih-cart-count"><?php echo $cart_count; ?></span>
                </a>
                <?php endif; ?>
                <div class="aih-user-menu">
                    <span class="aih-user-name"><?php echo esc_html($bidder_name); ?></span>
                    <button type="button" class="aih-logout-btn" id="aih-logout">Sign Out</button>
                </div>
            </div>
        </div>
    </header>

    <main class="aih-main">
        <div class="aih-gallery-header">
            <div class="aih-gallery-title">
                <h1>My Collection</h1>
                <p class="aih-subtitle"><?php echo count($purchases); ?> pieces purchased</p>
            </div>
        </div>

        <?php if (empty($purchases)): ?>
        <div class="aih-empty-state">
            <div class="aih-ornament">✦</div>
            <h2>No Purchases Yet</h2>
            <p>Art pieces you've won and paid for will appear here.</p>
            <a href="<?php echo esc_url($gallery_url); ?>" class="aih-btn aih-btn--inline">Browse Gallery</a>
        </div>
        <?php else: ?>
        <div class="aih-wins-grid">
            <?php foreach ($purchases as $item): ?>
            <article class="aih-win-card" data-id="<?php echo $item->art_piece_id; ?>">
                <div class="aih-win-image">
                    <?php if ($item->image_url): ?>
                    <a href="<?php echo esc_url($gallery_url); ?>?art_id=<?php echo $item->art_piece_id; ?>">
                        <img src="<?php echo esc_url($item->image_url); ?>" alt="<?php echo esc_attr($item->title); ?>" loading="lazy">
                    </a>
                    <?php else: ?>
                    <a href="<?php echo esc_url($gallery_url); ?>?art_id=<?php echo $item->art_piece_id; ?>" class="aih-placeholder-link">
                        <div class="aih-placeholder">
                            <span class="aih-placeholder-id"><?php echo esc_html($item->art_id); ?></span>
                            <span class="aih-placeholder-text">No Image</span>
                        </div>
                    </a>
                    <?php endif; ?>
                    <?php if ($item->image_url && $item->art_id): ?>
                    <span class="aih-art-id-badge"><?php echo esc_html($item->art_id); ?></span>
                    <?php endif; ?>
                    <div class="aih-badge aih-badge-owned">Owned</div>
                    <?php if ($item->pickup_status === 'picked_up'): ?>
                    <div class="aih-badge aih-badge-pickup">Picked Up</div>
                    <?php endif; ?>
                </div>

                <div class="aih-win-body">
                    <h3 class="aih-win-title">
                        <a href="<?php echo esc_url($gallery_url); ?>?art_id=<?php echo $item->art_piece_id; ?>"><?php echo esc_html($item->title); ?></a>
                    </h3>
                    <p class="aih-win-artist"><?php echo esc_html($item->artist); ?></p>
                    <?php if ($item->medium || $item->dimensions): ?>
                    <p class="aih-win-details">
                        <?php echo esc_html($item->medium); ?>
                        <?php if ($item->medium && $item->dimensions) echo ' • '; ?>
                        <?php echo esc_html($item->dimensions); ?>
                    </p>
                    <?php endif; ?>
                </div>

                <div class="aih-win-footer">
                    <div class="aih-win-price">
                        <span class="aih-win-label">Purchase Price</span>
                        <span class="aih-win-amount">$<?php echo number_format($item->winning_bid); ?></span>
                    </div>
                    <div class="aih-win-order">
                        <span class="aih-win-label">Order</span>
                        <span class="aih-win-order-num"><?php echo esc_html($item->order_number); ?></span>
                    </div>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </main>

    <footer class="aih-footer">
        <p>&copy; <?php echo date('Y'); ?> Art in Heaven. All rights reserved.</p>
    </footer>
</div>

<script>
jQuery(document).ready(function($) {
    $('#aih-logout').on('click', function() {
        $.post(aihAjax.ajaxurl, {action:'aih_logout', nonce:aihAjax.nonce}, function() { location.reload(); });
    });
});
</script>


