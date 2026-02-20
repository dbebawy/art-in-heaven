<?php
/**
 * My Bids Page - Elegant Design
 */
if (!defined('ABSPATH')) exit;

// Use consolidated helper for bidder info and page URLs
$bidder_info = AIH_Template_Helper::get_current_bidder_info();
$is_logged_in = $bidder_info['is_logged_in'];
$bidder = $bidder_info['bidder'];
$bidder_id = $bidder_info['id'];
$bidder_name = $bidder_info['name'];

$gallery_url = AIH_Template_Helper::get_gallery_url();
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
                <p>Please sign in to view your bids</p>
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

// Get user's bids - returns only the highest valid bid per art piece
$bid_model = new AIH_Bid();
$favorites = new AIH_Favorites();
$art_images = new AIH_Art_Images();
$my_bids = $bid_model->get_bidder_bids($bidder_id);
$bid_increment = floatval(get_option('aih_bid_increment', 1));

$cart_count = 0;
$checkout = AIH_Checkout::get_instance();
$cart_count = count($checkout->get_won_items($bidder_id));
$my_orders = $checkout->get_bidder_orders($bidder_id);
$payment_statuses = $checkout->get_bidder_payment_statuses($bidder_id);
?>

<div id="aih-mybids-wrapper" data-server-time="<?php echo esc_attr(time() * 1000); ?>">
<div class="aih-page aih-mybids-page">
<script>(function(){var t=localStorage.getItem('aih-theme');if(t==='dark'){document.currentScript.parentElement.classList.add('dark-mode');}})();</script>
    <header class="aih-header">
        <div class="aih-header-inner">
            <a href="<?php echo esc_url($gallery_url); ?>" class="aih-logo">Art in Heaven</a>
            <nav class="aih-nav">
                <a href="<?php echo esc_url($gallery_url); ?>" class="aih-nav-link">Gallery</a>
                <a href="#" class="aih-nav-link aih-nav-active">My Bids</a>
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
                    <a href="<?php echo esc_url($gallery_url); ?>" class="aih-user-name aih-user-name-link"><?php echo esc_html($bidder_name); ?></a>
                    <button type="button" class="aih-logout-btn" id="aih-logout">Sign Out</button>
                </div>
            </div>
        </div>
    </header>

    <div class="aih-ptr-indicator"><span class="aih-ptr-spinner"></span></div>

    <main class="aih-main">
        <div class="aih-gallery-header">
            <div class="aih-gallery-title">
                <h1>My Bids</h1>
                <p class="aih-subtitle"><?php echo count($my_bids); ?> items</p>
            </div>
        </div>

        <?php if (empty($my_bids)): ?>
        <div class="aih-empty-state">
            <div class="aih-ornament">✦</div>
            <h2>No Bids Yet</h2>
            <p>Browse the gallery and place your first bid!</p>
            <a href="<?php echo esc_url($gallery_url); ?>" class="aih-btn aih-btn--inline">View Gallery</a>
        </div>
        <?php else: ?>
        <div class="aih-gallery-grid">
            <?php foreach ($my_bids as $bid):
                $is_winning = ($bid->is_winning == 1);
                $bid_status = isset($bid->computed_status) ? $bid->computed_status : (isset($bid->auction_status) ? $bid->auction_status : 'active');
                $is_ended = $bid_status === 'ended' || (!empty($bid->auction_end) && strtotime($bid->auction_end) && strtotime($bid->auction_end) <= current_time('timestamp'));
                $images = $art_images->get_images($bid->art_piece_id);
                $bid_title = isset($bid->title) ? $bid->title : (isset($bid->art_title) ? $bid->art_title : '');
                $image_url = !empty($images) ? $images[0]->watermarked_url : (isset($bid->watermarked_url) ? $bid->watermarked_url : (isset($bid->image_url) ? $bid->image_url : ''));
                $highest_bid = $bid_model->get_highest_bid_amount($bid->art_piece_id);
                $min_bid = $highest_bid + $bid_increment;

                $is_paid = isset($payment_statuses[$bid->art_piece_id]) && $payment_statuses[$bid->art_piece_id] === 'paid';

                if ($is_ended && $is_winning && $is_paid) {
                    $status_class = 'paid';
                    $status_text = 'Paid';
                } elseif ($is_ended && $is_winning) {
                    $status_class = 'won';
                    $status_text = 'Won';
                } elseif ($is_ended) {
                    $status_class = 'ended';
                    $status_text = 'Ended';
                } elseif ($is_winning) {
                    $status_class = 'winning';
                    $status_text = 'Winning';
                } else {
                    $status_class = 'outbid';
                    $status_text = 'Outbid';
                }
            ?>
            <article class="aih-card <?php echo $status_class; ?>" data-id="<?php echo intval($bid->art_piece_id); ?>" <?php if (!empty($bid->auction_end)): ?>data-end="<?php echo esc_attr($bid->auction_end); ?>"<?php endif; ?>>
                <div class="aih-card-image">
                    <?php if ($image_url): ?>
                    <a href="<?php echo esc_url($gallery_url); ?>?art_id=<?php echo intval($bid->art_piece_id); ?>">
                        <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($bid_title); ?>" loading="lazy">
                    </a>
                    <?php else: ?>
                    <a href="<?php echo esc_url($gallery_url); ?>?art_id=<?php echo intval($bid->art_piece_id); ?>" class="aih-placeholder-link">
                        <div class="aih-placeholder">
                            <span class="aih-placeholder-id"><?php echo esc_html(isset($bid->art_id) ? $bid->art_id : ''); ?></span>
                            <span class="aih-placeholder-text">No Image</span>
                        </div>
                    </a>
                    <?php endif; ?>
                    <?php if ($image_url): ?>
                    <span class="aih-art-id-badge"><?php echo esc_html(isset($bid->art_id) ? $bid->art_id : ''); ?></span>
                    <?php endif; ?>
                    <div class="aih-badge aih-badge-<?php echo $status_class; ?>"><?php echo $status_text; ?></div>

                    <?php if (!$is_ended && !empty($bid->auction_end) && !empty($bid->show_end_time)): ?>
                    <div class="aih-time-remaining" data-end="<?php echo esc_attr($bid->auction_end); ?>">
                        <span class="aih-time-value">--:--:--</span>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="aih-card-body">
                    <h3 class="aih-card-title">
                        <a href="<?php echo esc_url($gallery_url); ?>?art_id=<?php echo intval($bid->art_piece_id); ?>"><?php echo esc_html($bid_title); ?></a>
                    </h3>
                    <p class="aih-card-artist"><?php echo esc_html(isset($bid->artist) ? $bid->artist : ''); ?></p>
                </div>
                
                <div class="aih-card-footer">
                    <div class="aih-bid-info aih-bid-info-centered">
                        <div>
                            <span class="aih-bid-label">Your Bid</span>
                            <span class="aih-bid-amount">$<?php echo number_format($bid->bid_amount); ?></span>
                            <?php if (isset($bid->bid_count) && $bid->bid_count > 1): ?>
                            <span class="aih-bid-count">(<?php echo intval($bid->bid_count); ?> bids)</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if (!$is_ended && !$is_winning): ?>
                    <div class="aih-bid-form">
                        <input type="text" inputmode="numeric" pattern="[0-9]*" class="aih-bid-input" data-min="<?php echo esc_attr($min_bid); ?>" placeholder="$">
                        <button type="button" class="aih-bid-btn" data-id="<?php echo intval($bid->art_piece_id); ?>">Bid</button>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="aih-bid-message"></div>
            </article>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($my_orders)): ?>
        <div class="aih-previous-orders">
            <h2 class="aih-orders-heading">My Orders</h2>
            <div class="aih-orders-grid">
                <?php foreach ($my_orders as $order): ?>
                <div class="aih-order-card aih-order-clickable" data-order="<?php echo esc_attr($order->order_number); ?>">
                    <div class="aih-order-header">
                        <strong><?php echo esc_html($order->order_number); ?></strong>
                        <span class="aih-order-status aih-status-<?php echo esc_attr($order->payment_status); ?>">
                            <?php echo esc_html(ucfirst($order->payment_status)); ?>
                        </span>
                    </div>
                    <div class="aih-order-details">
                        <p><?php echo intval($order->item_count); ?> item<?php echo $order->item_count != 1 ? 's' : ''; ?> &bull; $<?php echo number_format($order->total); ?></p>
                        <p class="aih-order-date"><?php echo esc_html(date('M j, Y', strtotime($order->created_at))); ?></p>
                    </div>
                    <div class="aih-order-view-link">
                        <span>View Details →</span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Order Details Modal -->
        <div id="aih-order-modal" class="aih-modal" role="dialog" aria-modal="true" aria-labelledby="aih-modal-title" style="display: none;">
            <div class="aih-modal-backdrop" aria-hidden="true"></div>
            <div class="aih-modal-content">
                <div class="aih-modal-header">
                    <h3 id="aih-modal-title">Order Details</h3>
                    <button type="button" class="aih-modal-close" aria-label="Close">&times;</button>
                </div>
                <div class="aih-modal-body" id="aih-modal-body">
                    <div class="aih-loading">Loading...</div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </main>

    <footer class="aih-footer">
        <p>&copy; <?php echo date('Y'); ?> Art in Heaven. All rights reserved.</p>
    </footer>
</div>
</div>

<script>
jQuery(document).ready(function($) {
    function escapeHtml(text) {
        if (!text) return '';
        return String(text).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }
    $('#aih-logout').on('click', function() {
        $.post(aihAjax.ajaxurl, {action:'aih_logout', nonce:aihAjax.nonce}, function() { location.reload(); });
    });
    
    // Order details modal
    var lastFocusedElement;
    $('.aih-order-clickable').on('click', function() {
        lastFocusedElement = this;
        var orderNumber = $(this).data('order');
        var $modal = $('#aih-order-modal');
        var $body = $('#aih-modal-body');

        $modal.show();
        $modal.find('.aih-modal-close').focus();
        $body.html('<div class="aih-loading">Loading order details...</div>');
        $('#aih-modal-title').text('Order ' + orderNumber);

        $.post(aihAjax.ajaxurl, {
            action: 'aih_get_order_details',
            nonce: aihAjax.nonce,
            order_number: orderNumber
        }).done(function(r) {
            if (r.success) {
                var data = r.data;
                var html = '<div class="aih-order-modal-info">';
                html += '<div class="aih-order-meta">';
                var safeStatus = escapeHtml(data.payment_status);
                var statusClass = ['paid', 'pending', 'refunded', 'cancelled'].indexOf(safeStatus) > -1 ? safeStatus : 'pending';
                html += '<span class="aih-order-status aih-status-' + statusClass + '">' + safeStatus.charAt(0).toUpperCase() + safeStatus.slice(1) + '</span>';
                if (data.pickup_status === 'picked_up') {
                    html += ' <span class="aih-pickup-badge">Picked Up</span>';
                }
                html += '<span class="aih-order-date">' + escapeHtml(data.created_at) + '</span>';
                html += '</div>';
                if (data.payment_reference) {
                    html += '<div class="aih-order-txn"><span class="aih-txn-label">Transaction ID:</span> <span class="aih-txn-value">' + escapeHtml(data.payment_reference) + '</span></div>';
                }
                html += '</div>';

                html += '<div class="aih-order-items-list">';
                html += '<h4>Items Purchased</h4>';
                if (data.items && data.items.length > 0) {
                    data.items.forEach(function(item) {
                        html += '<div class="aih-order-item-row">';
                        html += '<div class="aih-order-item-image">';
                        if (item.image_url) {
                            html += '<img src="' + escapeHtml(item.image_url) + '" alt="' + escapeHtml(item.title || '') + '">';
                        }
                        if (item.art_id) {
                            html += '<span class="aih-art-id-badge">' + escapeHtml(item.art_id) + '</span>';
                        }
                        html += '</div>';
                        html += '<div class="aih-order-item-info">';
                        html += '<h5>' + escapeHtml(item.title || 'Untitled') + '</h5>';
                        html += '<p>' + escapeHtml(item.artist || '') + '</p>';
                        html += '</div>';
                        html += '<div class="aih-order-item-price">$' + item.winning_bid.toLocaleString() + '</div>';
                        html += '</div>';
                    });
                }
                html += '</div>';

                html += '<div class="aih-order-totals">';
                html += '<div class="aih-order-total-row"><span>Subtotal</span><span>$' + data.subtotal.toLocaleString() + '</span></div>';
                if (data.tax > 0) {
                    html += '<div class="aih-order-total-row"><span>Tax</span><span>$' + data.tax.toFixed(2) + '</span></div>';
                }
                html += '<div class="aih-order-total-row aih-order-total-final"><span>Total</span><span>$' + data.total.toFixed(2) + '</span></div>';
                html += '</div>';

                $body.html(html);
            } else {
                var msg = (r.data && r.data.message) ? r.data.message : 'Unknown error';
                $body.html('<p class="aih-error">Error: ' + escapeHtml(msg) + '</p>');
            }
        }).fail(function(xhr) {
            $body.html('<p class="aih-error">Request failed: ' + escapeHtml(xhr.status + ' ' + xhr.statusText) + '</p>');
        });
    });

    // Close modal and restore focus
    $('.aih-modal-close, .aih-modal-backdrop').on('click', function() {
        $('#aih-order-modal').hide();
        if (lastFocusedElement) lastFocusedElement.focus();
    });

    $(document).on('keyup', function(e) {
        if (e.key === 'Escape' && $('#aih-order-modal').is(':visible')) {
            $('#aih-order-modal').hide();
            if (lastFocusedElement) lastFocusedElement.focus();
        }
    });

    $('.aih-bid-btn').on('click', function() {
        var $btn = $(this);
        var $card = $btn.closest('.aih-card');
        var $input = $card.find('.aih-bid-input');
        var $msg = $card.find('.aih-bid-message');
        var id = $btn.data('id');
        var amount = parseInt($input.val());

        if (!amount) { $msg.addClass('error').text('Enter a bid amount').show(); return; }

        var formatted = '$' + amount.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        window.aihConfirmBid(formatted, function() {
            $btn.prop('disabled', true).text('...');
            $msg.hide();

            $.post(aihAjax.ajaxurl, {action:'aih_place_bid', nonce:aihAjax.nonce, art_piece_id:id, bid_amount:amount}, function(r) {
                if (r.success) {
                    if (navigator.vibrate) navigator.vibrate(100);
                    $msg.removeClass('error').addClass('success').text('Bid placed!').show();
                    $btn.prop('disabled', true);
                    setTimeout(function() { location.reload(); }, 1500);
                } else {
                    $msg.removeClass('success').addClass('error').text(r.data.message || 'Failed').show();
                    $btn.prop('disabled', false).text('Bid');
                }
            }).fail(function() {
                $msg.removeClass('success').addClass('error').text('Connection error. Please try again.').show();
                $btn.prop('disabled', false).text('Bid');
            });
        });
    });

    // Countdown: update badges when auctions end
    var serverTime = parseInt($('#aih-mybids-wrapper').data('server-time')) || new Date().getTime();
    var timeOffset = serverTime - new Date().getTime();

    function updateMyBidsCountdowns() {
        // Update visible countdown timers
        $('.aih-time-remaining').each(function() {
            var $el = $(this);
            var endTime = $el.attr('data-end');
            if (!endTime) return;

            var end = new Date(endTime.replace(/-/g, '/')).getTime();
            var now = new Date().getTime() + timeOffset;
            var diff = end - now;

            if (diff <= 0) {
                $el.find('.aih-time-value').text('Ended');
                $el.addClass('ended');
                return;
            }

            var days = Math.floor(diff / (1000 * 60 * 60 * 24));
            var hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            var minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
            var seconds = Math.floor((diff % (1000 * 60)) / 1000);

            var timeStr = '';
            if (days > 0) {
                timeStr = days + 'd ' + hours + 'h';
            } else if (hours > 0) {
                timeStr = hours + 'h ' + minutes + 'm';
            } else {
                timeStr = minutes + 'm ' + seconds + 's';
            }

            $el.find('.aih-time-value').text(timeStr);

            if (diff < 3600000) {
                $el.addClass('urgent');
            }
        });

        // Update badges and hide forms when auctions end
        $('.aih-card[data-end]').each(function() {
            var $card = $(this);
            if ($card.hasClass('ended') || $card.hasClass('won') || $card.hasClass('paid')) return;

            var endTime = $card.attr('data-end');
            var end = new Date(endTime.replace(/-/g, '/')).getTime();
            var now = new Date().getTime() + timeOffset;
            var diff = end - now;

            if (diff <= 0) {
                var wasWinning = $card.hasClass('winning');
                var newStatus = wasWinning ? 'won' : 'ended';
                var newText = wasWinning ? 'Won' : 'Ended';

                $card.removeClass('winning outbid').addClass(newStatus);

                var $badge = $card.find('.aih-badge');
                if ($badge.length) {
                    $badge.attr('class', 'aih-badge aih-badge-' + newStatus).text(newText);
                }

                // Hide bid form and countdown
                $card.find('.aih-bid-form').hide();
                $card.find('.aih-time-remaining').addClass('ended').find('.aih-time-value').text('Ended');
            }
        });
    }

    updateMyBidsCountdowns();
    setInterval(updateMyBidsCountdowns, 1000);

    // === Live bid status polling ===
    var pollTimer = null;
    var POLL_INTERVAL = 5000;

    function hasActiveAuctions() {
        var hasActive = false;
        $('.aih-card').each(function() {
            var $card = $(this);
            if (!$card.hasClass('ended') && !$card.hasClass('won') && !$card.hasClass('paid')) {
                hasActive = true;
                return false;
            }
        });
        return hasActive;
    }

    function pollStatus() {
        if (!aihAjax.isLoggedIn || !hasActiveAuctions()) return;

        var ids = [];
        $('.aih-card').each(function() {
            var id = $(this).data('id');
            if (id) ids.push(id);
        });
        if (ids.length === 0) return;

        $.post(aihAjax.ajaxurl, {
            action: 'aih_poll_status',
            nonce: aihAjax.nonce,
            art_piece_ids: ids
        }, function(r) {
            if (!r.success || !r.data || !r.data.items) return;
            var items = r.data.items;

            $.each(items, function(id, info) {
                if (info.status === 'ended') return; // countdown handles ended transitions
                var $card = $('.aih-card[data-id="' + id + '"]');
                if (!$card.length) return;
                // Skip cards already transitioned to ended/won/paid
                if ($card.hasClass('ended') || $card.hasClass('won') || $card.hasClass('paid')) return;

                var wasWinning = $card.hasClass('winning');
                var wasOutbid = $card.hasClass('outbid');

                if (info.is_winning && !wasWinning) {
                    // Became winning
                    $card.removeClass('outbid').addClass('winning');
                    var $badge = $card.find('.aih-badge');
                    $badge.attr('class', 'aih-badge aih-badge-winning').text('Winning');
                    // Hide bid form when winning
                    $card.find('.aih-bid-form').hide();
                } else if (!info.is_winning && wasWinning) {
                    // Got outbid
                    $card.removeClass('winning').addClass('outbid');
                    var $badge = $card.find('.aih-badge');
                    $badge.attr('class', 'aih-badge aih-badge-outbid').text('Outbid');
                    // Show bid form when outbid
                    var $form = $card.find('.aih-bid-form');
                    if ($form.length) {
                        $form.show();
                    } else {
                        // Create bid form if it doesn't exist
                        $card.find('.aih-card-footer').append(
                            '<div class="aih-bid-form">' +
                            '<input type="text" inputmode="numeric" pattern="[0-9]*" class="aih-bid-input" data-min="' + info.min_bid + '" placeholder="$">' +
                            '<button type="button" class="aih-bid-btn" data-id="' + id + '">Bid</button>' +
                            '</div>'
                        );
                    }
                }

                // Update min bid on input
                var $input = $card.find('.aih-bid-input');
                if ($input.length) {
                    $input.attr('data-min', info.min_bid).data('min', info.min_bid);
                }
            });

            // Update cart count
            var $cartCount = $('.aih-cart-count');
            if (r.data.cart_count > 0) {
                if ($cartCount.length) {
                    $cartCount.text(r.data.cart_count);
                } else {
                    var checkoutUrl = '<?php echo esc_url($checkout_url); ?>';
                    if (checkoutUrl) {
                        $('.aih-header-actions .aih-theme-toggle').after(
                            '<a href="' + checkoutUrl + '" class="aih-cart-link"><span>🛒</span><span class="aih-cart-count">' + r.data.cart_count + '</span></a>'
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

    setTimeout(function() {
        startPolling();
    }, POLL_INTERVAL);

    document.addEventListener('visibilitychange', function() {
        if (document.hidden) {
            stopPolling();
        } else {
            pollStatus();
            startPolling();
        }
    });
});
</script>


