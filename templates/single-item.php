<?php
/**
 * Single Item Page - Elegant Design
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
?>
<script>
if (typeof aihAjax === 'undefined') {
    var aihAjax = {
        ajaxurl: '<?php echo esc_url(admin_url('admin-ajax.php')); ?>',
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
                <div class="aih-ornament">&#10022;</div>
                <h1>Sign In Required</h1>
                <p>Please sign in to view this piece</p>
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
        var $btn = $(this);
        var $msg = $('#aih-login-msg');
        if (!code) { $msg.addClass('error').text('Enter your code').show(); return; }
        $btn.prop('disabled', true).addClass('loading');
        $.post(aihAjax.ajaxurl, {action:'aih_verify_code', nonce:aihAjax.nonce, code:code}, function(r) {
            if (r.success) location.reload();
            else { $msg.addClass('error').text(r.data.message).show(); $btn.prop('disabled', false).removeClass('loading'); }
        }).fail(function() {
            $btn.prop('disabled', false).removeClass('loading');
            $msg.text('Network error. Please try again.').addClass('error').show();
        });
    });
    $('#aih-login-code').on('keypress', function(e) { if (e.which === 13) $('#aih-login-btn').click(); })
        .on('input', function() { this.value = this.value.toUpperCase(); });
});
</script>
<?php return; endif;

// Get art piece
$favorites = new AIH_Favorites();
$bid_model = new AIH_Bid();
$art_images = new AIH_Art_Images();
$bid_increment = floatval(get_option('aih_bid_increment', 1));

$is_favorite = $bidder_id ? $favorites->is_favorite($bidder_id, $art_piece->id) : false;
$is_winning = $bidder_id ? $bid_model->is_bidder_winning($art_piece->id, $bidder_id) : false;
$current_bid = $bid_model->get_highest_bid_amount($art_piece->id);
$has_bids = $current_bid > 0;
$display_bid = $has_bids ? $current_bid : $art_piece->starting_bid;
$min_bid = $has_bids ? $current_bid + $bid_increment : $art_piece->starting_bid;

// Get bidder's successful bid history for this piece
$my_bid_history = $bidder_id ? $bid_model->get_bidder_bids_for_art_piece($art_piece->id, $bidder_id) : array();

// Proper status calculation - check computed_status first, then calculate from dates
$computed_status = isset($art_piece->computed_status) ? $art_piece->computed_status : null;
$is_ended = false;
$is_upcoming = false;
if ($computed_status === 'ended') {
    $is_ended = true;
} elseif ($computed_status === 'upcoming') {
    $is_upcoming = true;
} elseif ($computed_status === 'active') {
    $is_ended = false;
} else {
    // Fallback: calculate from status and dates
    $is_ended = $art_piece->status === 'ended' || (!empty($art_piece->auction_end) && strtotime($art_piece->auction_end) && strtotime($art_piece->auction_end) <= time());
    $is_upcoming = !$is_ended && !empty($art_piece->auction_start) && strtotime($art_piece->auction_start) && strtotime($art_piece->auction_start) > time();
}

$images = $art_images->get_images($art_piece->id);
$primary_image = !empty($images) ? $images[0]->watermarked_url : ($art_piece->watermarked_url ?: $art_piece->image_url);

// Navigation - include active and ended pieces, exclude upcoming
// TODO: optimize to fetch only IDs for navigation
$art_model = new AIH_Art_Piece();
$nav_active = $art_model->get_all(array('status' => 'active', 'bidder_id' => $bidder_id));
$nav_ended = $art_model->get_all(array('status' => 'ended', 'bidder_id' => $bidder_id));
$all_pieces = array_merge($nav_active, $nav_ended);
$current_index = -1;
foreach ($all_pieces as $i => $p) {
    if ($p->id == $art_piece->id) { $current_index = $i; break; }
}
$prev_id = $current_index > 0 ? $all_pieces[$current_index - 1]->id : null;
$next_id = $current_index < count($all_pieces) - 1 ? $all_pieces[$current_index + 1]->id : null;

$checkout_url = AIH_Template_Helper::get_checkout_url();

$cart_count = 0;
$checkout = AIH_Checkout::get_instance();
$cart_count = count($checkout->get_won_items($bidder_id));
?>

<div id="aih-single-wrapper" data-server-time="<?php echo esc_attr(time() * 1000); ?>">
<div class="aih-page aih-single-page">
<script>(function(){var t=localStorage.getItem('aih-theme');if(t==='dark'){document.currentScript.parentElement.classList.add('dark-mode');}})();</script>
    <header class="aih-header">
        <div class="aih-header-inner">
            <a href="<?php echo esc_url($gallery_url); ?>" class="aih-logo">Art in Heaven</a>
            <nav class="aih-nav">
                <a href="<?php echo esc_url($gallery_url); ?>" class="aih-nav-link">Gallery</a>
                <?php if ($my_bids_url): ?>
                <a href="<?php echo esc_url($my_bids_url); ?>" class="aih-nav-link">My Bids</a>
                <?php endif; ?>
            </nav>
            <div class="aih-header-actions">
                <button type="button" class="aih-theme-toggle" id="aih-theme-toggle" title="Toggle dark mode"><svg class="aih-theme-icon aih-icon-sun" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg><svg class="aih-theme-icon aih-icon-moon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg><span class="aih-theme-toggle-label">Theme</span></button>
                <?php if ($checkout_url && $cart_count > 0): ?>
                <a href="<?php echo esc_url($checkout_url); ?>" class="aih-cart-link">
                    <span>&#128722;</span>
                    <span class="aih-cart-count"><?php echo $cart_count; ?></span>
                </a>
                <?php endif; ?>
                <div class="aih-user-menu">
                    <?php if ($my_bids_url): ?>
                    <a href="<?php echo esc_url($my_bids_url); ?>" class="aih-user-name aih-user-name-link"><?php echo esc_html($bidder_name); ?></a>
                    <?php else: ?>
                    <span class="aih-user-name"><?php echo esc_html($bidder_name); ?></span>
                    <?php endif; ?>
                    <button type="button" class="aih-logout-btn" id="aih-logout">Sign Out</button>
                </div>
            </div>
        </div>
    </header>

    <main class="aih-main">
        <div class="aih-single-nav-bar">
            <a href="<?php echo esc_url($gallery_url); ?>" class="aih-back-link">&larr; Back to Gallery</a>
            <div class="aih-nav-center">
                <?php if ($prev_id): ?>
                <a href="?art_id=<?php echo intval($prev_id); ?>" class="aih-nav-arrow" title="Previous">&larr;</a>
                <?php else: ?>
                <span class="aih-nav-arrow disabled">&larr;</span>
                <?php endif; ?>
                <span class="aih-piece-counter"><?php echo $current_index + 1; ?> / <?php echo count($all_pieces); ?></span>
                <?php if ($next_id): ?>
                <a href="?art_id=<?php echo intval($next_id); ?>" class="aih-nav-arrow" title="Next">&rarr;</a>
                <?php else: ?>
                <span class="aih-nav-arrow disabled">&rarr;</span>
                <?php endif; ?>
            </div>
            <div class="aih-nav-spacer"></div>
        </div>

        <div class="aih-single-content-wrapper">

            <div class="aih-single-content">
                <div class="aih-single-image <?php echo count($images) > 1 ? 'has-multiple-images' : ''; ?>">
                    <?php if ($primary_image): ?>
                    <img src="<?php echo esc_url($primary_image); ?>" alt="<?php echo esc_attr($art_piece->title); ?>" id="aih-main-image">
                    <?php if (count($images) > 1): ?>
                    <button type="button" class="aih-img-nav aih-img-nav-prev" aria-label="Previous image">&lsaquo;</button>
                    <button type="button" class="aih-img-nav aih-img-nav-next" aria-label="Next image">&rsaquo;</button>
                    <?php endif; ?>
                    <?php else: ?>
                    <div class="aih-single-placeholder">
                        <span class="aih-placeholder-id"><?php echo esc_html($art_piece->art_id); ?></span>
                        <span class="aih-placeholder-text">No Image Available</span>
                    </div>
                    <?php endif; ?>

                    <!-- Status Badge on image -->
                    <?php if ($is_winning && !$is_ended): ?>
                    <span class="aih-badge aih-badge-winning aih-badge-single">Winning</span>
                    <?php elseif ($is_ended): ?>
                    <span class="aih-badge aih-badge-ended aih-badge-single"><?php echo $is_winning ? 'Won' : 'Ended'; ?></span>
                    <?php endif; ?>

                    <!-- Art ID Badge on image -->
                    <span class="aih-art-id-badge-single"><?php echo esc_html($art_piece->art_id); ?></span>

                    <button type="button" class="aih-fav-btn <?php echo $is_favorite ? 'active' : ''; ?>" data-id="<?php echo intval($art_piece->id); ?>">
                        <span class="aih-fav-icon">&#9829;</span>
                    </button>

                    <?php if (count($images) > 1): ?>
                    <div class="aih-image-dots">
                        <?php foreach ($images as $i => $img): ?>
                        <span class="aih-image-dot <?php echo $i === 0 ? 'active' : ''; ?>" data-index="<?php echo $i; ?>" data-src="<?php echo esc_url($img->watermarked_url); ?>"></span>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="aih-single-details">
                    <div class="aih-single-meta">
                        <span class="aih-art-id"><?php echo esc_html($art_piece->art_id); ?></span>
                    </div>

                    <h1><?php echo esc_html($art_piece->title); ?></h1>
                    <p class="aih-artist"><?php echo esc_html($art_piece->artist); ?></p>

                    <div class="aih-piece-info">
                        <?php if ($art_piece->medium): ?>
                        <div class="aih-info-row">
                            <span class="aih-info-label">Medium</span>
                            <span><?php echo esc_html($art_piece->medium); ?></span>
                        </div>
                        <?php endif; ?>
                        <?php if ($art_piece->dimensions): ?>
                        <div class="aih-info-row">
                            <span class="aih-info-label">Dimensions</span>
                            <span><?php echo esc_html($art_piece->dimensions); ?></span>
                        </div>
                        <?php endif; ?>
                        <?php if ($art_piece->description): ?>
                        <div class="aih-info-row aih-description-row">
                            <span class="aih-info-label">Description</span>
                            <div class="aih-description-text"><?php echo wpautop(esc_html($art_piece->description)); ?></div>
                        </div>
                        <?php endif; ?>
                    </div>

                    <?php if ($is_upcoming): ?>
                    <div class="aih-bid-section">
                        <div class="aih-upcoming-notice">
                            Bidding starts <?php echo esc_html(wp_date('M j, Y \a\t g:i A', strtotime($art_piece->auction_start))); ?>
                        </div>
                    </div>
                    <?php elseif (!$is_ended): ?>
                    <div class="aih-bid-section">
                        <?php if ($art_piece->auction_end && !empty($art_piece->show_end_time)): ?>
                        <div class="aih-time-remaining-single" data-end="<?php echo esc_attr($art_piece->auction_end); ?>">
                            <span class="aih-time-label">Time Remaining</span>
                            <span class="aih-time-value">--:--:--</span>
                        </div>
                        <?php endif; ?>

                        <div class="aih-bid-info">
                            <span class="aih-bid-label">Starting Bid</span>
                            <span class="aih-bid-amount">$<?php echo number_format($art_piece->starting_bid); ?></span>
                        </div>

                        <div class="aih-bid-form-single">
                            <div class="aih-field">
                                <label>Your Bid</label>
                                <input type="text" inputmode="numeric" pattern="[0-9]*" id="bid-amount" data-min="<?php echo esc_attr($min_bid); ?>" placeholder="$">
                            </div>
                            <button type="button" id="place-bid" class="aih-bid-btn" data-id="<?php echo intval($art_piece->id); ?>">
                                Bid
                            </button>
                        </div>
                        <div id="bid-message" class="aih-message"></div>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($my_bid_history)): ?>
                    <div class="aih-bid-history">
                        <h3>Your Bid History</h3>
                        <div class="aih-bid-history-list">
                            <?php foreach ($my_bid_history as $bid): ?>
                            <div class="aih-bid-history-item <?php echo $bid->is_winning ? 'winning' : ''; ?>">
                                <span class="aih-bid-history-amount">$<?php echo number_format($bid->bid_amount); ?></span>
                                <span class="aih-bid-history-time"><?php echo esc_html(date_i18n('M j, g:i A', strtotime($bid->bid_time))); ?></span>
                                <?php if ($bid->is_winning): ?>
                                <span class="aih-bid-history-status">&#10003; Winning</span>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <footer class="aih-footer">
        <p>&copy; <?php echo esc_html(wp_date('Y')); ?> Art in Heaven. All rights reserved.</p>
    </footer>
</div>
</div>

<!-- Scroll to Top Button -->
<button type="button" class="aih-scroll-top" id="aih-scroll-top" title="Scroll to top">&uarr;</button>

<!-- Lightbox for image viewing -->
<div class="aih-lightbox" id="aih-lightbox">
    <button type="button" class="aih-lightbox-close" aria-label="Close">&times;</button>
    <button type="button" class="aih-lightbox-nav aih-lightbox-prev" aria-label="Previous image">&lsaquo;</button>
    <button type="button" class="aih-lightbox-nav aih-lightbox-next" aria-label="Next image">&rsaquo;</button>
    <div class="aih-lightbox-content">
        <img src="" alt="" class="aih-lightbox-image" id="aih-lightbox-img">
    </div>
    <div class="aih-lightbox-dots"></div>
    <div class="aih-lightbox-counter"><span id="aih-lb-current">1</span> / <?php echo count($images); ?></div>
</div>

<script>
jQuery(document).ready(function($) {
    $('#aih-logout').on('click', function() {
        $.post(aihAjax.ajaxurl, {action:'aih_logout', nonce:aihAjax.nonce}, function() { location.reload(); });
    });

    // Favorite
    $('.aih-fav-btn').on('click', function() {
        var $btn = $(this);
        if ($btn.hasClass('loading')) return;
        $btn.addClass('loading');
        $.post(aihAjax.ajaxurl, {action:'aih_toggle_favorite', nonce:aihAjax.nonce, art_piece_id:$btn.data('id')}, function(r) {
            if (r.success) {
                $btn.toggleClass('active');
            }
        }).always(function() {
            $btn.removeClass('loading');
        });
    });

    // Image navigation - current index
    var currentImgIndex = 0;
    var totalImages = $('.aih-image-dot').length || 1;

    function showImage(index) {
        if (index < 0) index = totalImages - 1;
        if (index >= totalImages) index = 0;
        currentImgIndex = index;

        var $dot = $('.aih-image-dot[data-index="' + index + '"]');
        var src = $dot.data('src');
        if (src) {
            $('#aih-main-image').attr('src', src);
            $('.aih-image-dot').removeClass('active');
            $dot.addClass('active');
        }
    }

    // Dot navigation
    $('.aih-image-dot').on('click', function() {
        var index = parseInt($(this).data('index'));
        showImage(index);
    });

    // Arrow navigation
    $('.aih-img-nav-prev').on('click', function() {
        showImage(currentImgIndex - 1);
    });

    $('.aih-img-nav-next').on('click', function() {
        showImage(currentImgIndex + 1);
    });

    // Lightbox functionality
    var $lightbox = $('#aih-lightbox');
    var $lightboxImg = $('#aih-lightbox-img');
    var lightboxIndex = 0;

    // Image sources from PHP - include primary image as fallback
    var allImages = <?php
        $image_urls = array_map(function($img) { return $img->watermarked_url; }, $images);
        // If no images in array, use primary image
        if (empty($image_urls) && $primary_image) {
            $image_urls = array($primary_image);
        }
        echo wp_json_encode($image_urls);
    ?>;
    // Additional fallback if PHP array is still empty
    if (!allImages || allImages.length === 0) {
        var mainSrc = $('#aih-main-image').attr('src');
        if (mainSrc) allImages = [mainSrc];
    }


    // Generate lightbox dots dynamically based on actual image count
    var $dotsContainer = $lightbox.find('.aih-lightbox-dots');
    $dotsContainer.empty();
    for (var i = 0; i < allImages.length; i++) {
        var activeClass = i === 0 ? ' active' : '';
        $dotsContainer.append('<span class="aih-lightbox-dot' + activeClass + '" data-index="' + i + '"></span>');
    }

    // Bind click events for dynamically created dots
    $dotsContainer.on('click', '.aih-lightbox-dot', function() {
        var index = parseInt($(this).data('index'));
        lightboxIndex = index;
        $lightboxImg.attr('src', allImages[index]);
        updateLightboxDots(index);
    });

    function updateLightboxDots(index) {
        $lightbox.find('.aih-lightbox-dot').removeClass('active');
        $lightbox.find('.aih-lightbox-dot[data-index="' + index + '"]').addClass('active');
    }

    function openLightbox(index) {

        if (allImages.length === 0) {

            return;
        }
        // Ensure index is valid
        if (index < 0 || index >= allImages.length) {
            index = 0;
        }
        lightboxIndex = index;
        var imgSrc = allImages[index];
        $lightboxImg.attr('src', imgSrc);
        $('#aih-lb-current').text(index + 1);
        updateLightboxDots(index);
        $lightbox.addClass('active');
        $('html').addClass('aih-lightbox-open');

        // Show/hide navigation based on image count
        if (allImages.length > 1) {

            $lightbox.addClass('has-multiple');
        } else {

            $lightbox.removeClass('has-multiple');
        }
    }

    function closeLightbox() {
        $lightbox.removeClass('active has-multiple');
        $('html').removeClass('aih-lightbox-open');
        // Ensure body scroll is restored
        $('body').css('overflow', '');
    }

    function lightboxNav(direction) {
        lightboxIndex += direction;
        if (lightboxIndex < 0) lightboxIndex = allImages.length - 1;
        if (lightboxIndex >= allImages.length) lightboxIndex = 0;
        $lightboxImg.attr('src', allImages[lightboxIndex]);
        $('#aih-lb-current').text(lightboxIndex + 1);
        updateLightboxDots(lightboxIndex);
    }

    // Open lightbox on main image click
    $('#aih-main-image').on('click', function(e) {
        e.preventDefault();
        e.stopPropagation();

        openLightbox(currentImgIndex);
    });

    // Close lightbox
    $('.aih-lightbox-close').on('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        closeLightbox();
    });
    $lightbox.on('click', function(e) {
        // Only close if clicking directly on the lightbox background
        if (e.target === this) {
            closeLightbox();
        }
    });

    // Lightbox navigation
    $('.aih-lightbox-prev').on('click', function() {
        lightboxNav(-1);
    });
    $('.aih-lightbox-next').on('click', function() {
        lightboxNav(1);
    });

    // Keyboard navigation
    $(document).on('keydown', function(e) {
        if (!$lightbox.hasClass('active')) return;
        if (e.key === 'Escape') closeLightbox();
        if (e.key === 'ArrowLeft') lightboxNav(-1);
        if (e.key === 'ArrowRight') lightboxNav(1);
    });

    // Place bid
    $('#place-bid').on('click', function() {
        var $btn = $(this);
        var amount = parseInt($('#bid-amount').val());
        var $msg = $('#bid-message');

        if (!amount) { $msg.addClass('error').text('Enter a bid amount').show(); return; }

        // Confirm bid amount to prevent fat-finger mistakes
        var formatted = '$' + amount.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        window.aihConfirmBid(formatted, function() {
        $btn.prop('disabled', true).addClass('loading');
        $msg.hide().removeClass('error success');

        $.post(aihAjax.ajaxurl, {action:'aih_place_bid', nonce:aihAjax.nonce, art_piece_id:$btn.data('id'), bid_amount:amount}, function(r) {
            if (r.success) {
                if (navigator.vibrate) navigator.vibrate(100);
                $msg.removeClass('error').addClass('success').text('Bid placed successfully!').show();
                $('.aih-single-image').find('.aih-badge').remove();
                $('.aih-single-image').prepend('<span class="aih-badge aih-badge-winning aih-badge-single">Winning</span>');
                $('#bid-amount').val('');
                // Update minimum bid
                var increment = <?php echo intval($bid_increment); ?>;
                var newMin = amount + increment;
                $('#bid-amount').data('min', newMin).attr('data-min', newMin);
            } else {
                $msg.removeClass('success').addClass('error').text(r.data.message || 'Failed').show();
            }
            $btn.prop('disabled', false).removeClass('loading');
        }).fail(function() {
            $msg.removeClass('success').addClass('error').text('Connection error. Please try again.').show();
            $btn.prop('disabled', false).removeClass('loading');
        });
        }); // end aihConfirmBid
    });

    $('#bid-amount').on('keypress', function(e) {
        if (e.which === 13) $('#place-bid').click();
    });

    // Countdown timer for single item page
    var serverTime = parseInt($('#aih-single-wrapper').data('server-time')) || new Date().getTime();
    var timeOffset = serverTime - new Date().getTime();

    function updateCountdown() {
        $('.aih-time-remaining-single').each(function() {
            var $el = $(this);
            var endTime = $el.attr('data-end');
            if (!endTime) return;

            var end = new Date(endTime.replace(/-/g, '/')).getTime();
            var now = new Date().getTime() + timeOffset;
            var diff = end - now;

            if (diff <= 0) {
                $el.find('.aih-time-value').text('Ended');
                $el.addClass('ended');
                // Disable bid form
                $('#bid-amount').prop('disabled', true).attr('placeholder', 'Ended');
                $('#place-bid').prop('disabled', true).text('Ended');

                // Update status badge
                var $badge = $('.aih-badge-single');
                if ($badge.length) {
                    if ($badge.text().trim() === 'Winning') {
                        $badge.attr('class', 'aih-badge aih-badge-won aih-badge-single').text('Won');
                    } else {
                        $badge.attr('class', 'aih-badge aih-badge-ended aih-badge-single').text('Ended');
                    }
                } else {
                    $('.aih-single-image').append('<span class="aih-badge aih-badge-ended aih-badge-single">Ended</span>');
                }
                return;
            }

            var days = Math.floor(diff / (1000 * 60 * 60 * 24));
            var hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            var minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
            var seconds = Math.floor((diff % (1000 * 60)) / 1000);

            var timeStr = '';
            if (days > 0) {
                timeStr = days + 'd ' + hours + 'h ' + minutes + 'm';
            } else if (hours > 0) {
                timeStr = hours + 'h ' + minutes + 'm ' + seconds + 's';
            } else {
                timeStr = minutes + 'm ' + seconds + 's';
            }

            $el.find('.aih-time-value').text(timeStr);

            if (diff < 3600000) {
                $el.addClass('urgent');
            }
        });
    }

    updateCountdown();
    var countdownTimer = setInterval(updateCountdown, 1000);
    document.addEventListener('visibilitychange', function() {
        if (document.hidden) {
            clearInterval(countdownTimer);
        } else {
            updateCountdown();
            countdownTimer = setInterval(updateCountdown, 1000);
        }
    });

    // Scroll to Top functionality
    var $scrollBtn = $('#aih-scroll-top');
    $(window).on('scroll', function() {
        if ($(this).scrollTop() > 300) {
            $scrollBtn.addClass('visible');
        } else {
            $scrollBtn.removeClass('visible');
        }
    });

    $scrollBtn.on('click', function() {
        $('html, body').animate({ scrollTop: 0 }, 400);
    });

    // === Live bid status polling ===
    var pieceId = <?php echo intval($art_piece->id); ?>;
    var isEnded = <?php echo $is_ended ? 'true' : 'false'; ?>;
    var pollTimer = null;
    var POLL_INTERVAL = 5000;

    function pollStatus() {
        if (!aihAjax.isLoggedIn || isEnded) return;

        $.post(aihAjax.ajaxurl, {
            action: 'aih_poll_status',
            nonce: aihAjax.nonce,
            art_piece_ids: [pieceId]
        }, function(r) {
            if (!r.success || !r.data || !r.data.items) return;
            var info = r.data.items[pieceId];
            if (!info || info.status === 'ended') return;

            var $badge = $('.aih-badge-single');
            var wasWinning = $badge.length && $badge.text().trim() === 'Winning';

            if (info.is_winning && !wasWinning) {
                if ($badge.length) {
                    $badge.attr('class', 'aih-badge aih-badge-winning aih-badge-single').text('Winning');
                } else {
                    $('.aih-single-image').prepend('<span class="aih-badge aih-badge-winning aih-badge-single">Winning</span>');
                }
            } else if (!info.is_winning && wasWinning) {
                $badge.remove();
            }

            // Update min bid
            var $bidInput = $('#bid-amount');
            if ($bidInput.length) {
                $bidInput.attr('data-min', info.min_bid).data('min', info.min_bid);
            }

            // Update cart count
            var $cartCount = $('.aih-cart-count');
            if (r.data.cart_count > 0) {
                if ($cartCount.length) {
                    $cartCount.text(r.data.cart_count);
                } else {
                    var checkoutUrl = '<?php echo esc_url($checkout_url); ?>';
                    if (checkoutUrl) {
                        $('.aih-header-actions .aih-theme-toggle').after(
                            '<a href="' + checkoutUrl + '" class="aih-cart-link"><span>&#128722;</span><span class="aih-cart-count">' + r.data.cart_count + '</span></a>'
                        );
                    }
                }
            }
        });
    }

    function startPolling() {
        if (pollTimer) return;
        pollTimer = setInterval(pollStatus, POLL_INTERVAL);
    }

    function stopPolling() {
        if (pollTimer) {
            clearInterval(pollTimer);
            pollTimer = null;
        }
    }

    if (!isEnded) {
        setTimeout(function() {
            startPolling();
        }, POLL_INTERVAL);
    }

    document.addEventListener('visibilitychange', function() {
        if (document.hidden) {
            stopPolling();
        } else if (!isEnded) {
            pollStatus();
            startPolling();
        }
    });
});
</script>


