<?php
/**
 * Checkout Page - Elegant Design
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
                <p>Please sign in to complete your purchase</p>
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

// Handle PushPay redirect - check for payment token
$payment_result = null;
if (!empty($_GET['paymentToken'])) {
    $pushpay = AIH_Pushpay_API::get_instance();
    $payment_data = $pushpay->get_payment_by_token(sanitize_text_field($_GET['paymentToken']));
    if (!is_wp_error($payment_data)) {
        $payment_result = 'success';
    } else {
        $payment_result = 'error';
    }
} elseif (isset($_GET['sr']) && !isset($_GET['paymentToken'])) {
    // Source reference present but no payment token = payment was cancelled/failed
    $payment_result = 'cancelled';
}

// Get won items
$checkout = AIH_Checkout::get_instance();
$won_items = $checkout->get_won_items($bidder_id);
$orders = $checkout->get_bidder_orders($bidder_id);
$art_images = new AIH_Art_Images();

$subtotal = 0;
foreach ($won_items as $item) {
    // Support both winning_bid and winning_amount property names
    $winning_amount = isset($item->winning_bid) ? $item->winning_bid : (isset($item->winning_amount) ? $item->winning_amount : 0);
    $subtotal += $winning_amount;
}
$tax_rate = floatval(get_option('aih_tax_rate', 0));
$tax = $subtotal * ($tax_rate / 100);
$total = $subtotal + $tax;
?>

<div class="aih-page aih-checkout-page">
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
        <?php if ($payment_result === 'success'): ?>
        <div class="aih-payment-banner aih-payment-success">
            <span class="aih-payment-icon">&#10003;</span>
            <div>
                <strong>Payment Successful</strong>
                <p>Thank you! Your payment has been received. You can view your order details below.</p>
            </div>
        </div>
        <?php elseif ($payment_result === 'cancelled'): ?>
        <div class="aih-payment-banner aih-payment-cancelled">
            <span class="aih-payment-icon">!</span>
            <div>
                <strong>Payment Not Completed</strong>
                <p>It looks like the payment was not completed. You can try again below.</p>
            </div>
        </div>
        <?php elseif ($payment_result === 'error'): ?>
        <div class="aih-payment-banner aih-payment-error">
            <span class="aih-payment-icon">&#10007;</span>
            <div>
                <strong>Payment Issue</strong>
                <p>There was a problem verifying your payment. Please contact support if you were charged.</p>
            </div>
        </div>
        <?php endif; ?>

        <div class="aih-gallery-header">
            <div class="aih-gallery-title">
                <h1>Checkout</h1>
                <p class="aih-subtitle"><?php echo count($won_items); ?> items won</p>
            </div>
        </div>

        <?php if (empty($won_items)): ?>
        <div class="aih-empty-state">
            <div class="aih-ornament">✦</div>
            <h2>No Items to Checkout</h2>
            <p>You haven't won any auctions yet.</p>
            <a href="<?php echo esc_url($gallery_url); ?>" class="aih-btn aih-btn--inline">Browse Gallery</a>
        </div>
        <?php else: ?>
        <div class="aih-checkout-layout">
            <div class="aih-checkout-items">
                <h2 class="aih-section-heading">Won Items</h2>
                <?php foreach ($won_items as $item):
                    // Support both id and art_piece_id property names
                    $art_piece_id = isset($item->art_piece_id) ? $item->art_piece_id : (isset($item->id) ? $item->id : 0);
                    $images = $art_images->get_images($art_piece_id);
                    $image_url = !empty($images) ? $images[0]->watermarked_url : (isset($item->watermarked_url) ? $item->watermarked_url : (isset($item->image_url) ? $item->image_url : ''));
                    // Support both winning_bid and winning_amount property names
                    $winning_amount = isset($item->winning_bid) ? $item->winning_bid : (isset($item->winning_amount) ? $item->winning_amount : 0);
                ?>
                <div class="aih-checkout-item">
                    <div class="aih-checkout-item-image">
                        <?php if ($image_url): ?>
                        <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr(isset($item->title) ? $item->title : ''); ?>">
                        <span class="aih-art-id-badge"><?php echo esc_html(isset($item->art_id) ? $item->art_id : ''); ?></span>
                        <?php else: ?>
                        <div class="aih-checkout-placeholder">
                            <span><?php echo esc_html(isset($item->art_id) ? $item->art_id : ''); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="aih-checkout-item-details">
                        <h4><?php echo esc_html(isset($item->title) ? $item->title : ''); ?></h4>
                        <p><?php echo esc_html(isset($item->artist) ? $item->artist : ''); ?></p>
                    </div>
                    <div class="aih-checkout-item-price">
                        <span>Winning Bid</span>
                        <strong>$<?php echo number_format($winning_amount); ?></strong>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <div class="aih-checkout-summary">
                <h3>Order Summary</h3>
                <div class="aih-summary-row">
                    <span>Subtotal</span>
                    <span>$<?php echo number_format($subtotal); ?></span>
                </div>
                <?php if ($tax > 0): ?>
                <div class="aih-summary-row">
                    <span>Tax (<?php echo esc_html($tax_rate); ?>%)</span>
                    <span>$<?php echo number_format($tax, 2); ?></span>
                </div>
                <?php endif; ?>
                <div class="aih-summary-row aih-summary-total">
                    <span>Total</span>
                    <span>$<?php echo number_format($total, 2); ?></span>
                </div>
                <button type="button" id="aih-create-order" class="aih-btn" style="margin-top: 24px;">
                    Proceed to Payment
                </button>
                <div id="aih-checkout-msg" class="aih-message" style="display:none; margin-top: 12px;"></div>
                <p class="aih-checkout-note">You'll be redirected to our secure payment portal.</p>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($orders)): ?>
        <div class="aih-previous-orders">
            <h2 class="aih-section-heading" style="margin-top: 48px;">Previous Orders</h2>
            <div class="aih-orders-grid">
                <?php foreach ($orders as $order): ?>
                <div class="aih-order-card aih-order-clickable" data-order="<?php echo esc_attr($order->order_number); ?>">
                    <div class="aih-order-header">
                        <strong><?php echo esc_html($order->order_number); ?></strong>
                        <span class="aih-order-status aih-status-<?php echo esc_attr($order->payment_status); ?>">
                            <?php echo esc_html(ucfirst($order->payment_status)); ?>
                        </span>
                    </div>
                    <div class="aih-order-details">
                        <p><?php echo intval($order->item_count); ?> items • $<?php echo number_format($order->total); ?></p>
                        <p class="aih-order-date"><?php echo esc_html(date('M j, Y', strtotime($order->created_at))); ?></p>
                    </div>
                    <div class="aih-order-view-link">
                        <span>View Details →</span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

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
    </main>

    <footer class="aih-footer">
        <p>&copy; <?php echo date('Y'); ?> Art in Heaven. All rights reserved.</p>
    </footer>
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

    $('#aih-create-order').on('click', function() {
        var $btn = $(this).prop('disabled', true).addClass('loading');
        $.post(aihAjax.ajaxurl, {action:'aih_create_order', nonce:aihAjax.nonce}, function(r) {
            if (r.success && r.data.pushpay_url) {
                window.location.href = r.data.pushpay_url;
            } else {
                $('#aih-checkout-msg').addClass('error').text(r.data.message || 'Error creating order. Payment URL could not be generated.').show();
                $btn.prop('disabled', false).removeClass('loading');
            }
        }).fail(function() {
            $('#aih-checkout-msg').addClass('error').text('Connection error. Please try again.').show();
            $btn.prop('disabled', false).removeClass('loading');
        });
    });

    // Order details modal with caching
    var orderCache = {};
    $('.aih-order-clickable').on('click', function() {
        lastFocusedElement = this;
        var orderNumber = $(this).data('order');
        var $modal = $('#aih-order-modal');
        var $body = $('#aih-modal-body');

        $modal.show();
        $modal.find('.aih-modal-close').focus();
        $('#aih-modal-title').text('Order ' + orderNumber);

        // Use cached data if available
        if (orderCache[orderNumber]) {
            $body.html(orderCache[orderNumber]);
            return;
        }

        $body.html('<div class="aih-loading">Loading order details...</div>');

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
                orderCache[orderNumber] = html;
            } else {
                var msg = (r.data && r.data.message) ? r.data.message : 'Unknown error';
                $body.html('<p class="aih-error">Error: ' + escapeHtml(msg) + '</p>');
            }
        }).fail(function(xhr) {
            $body.html('<p class="aih-error">Request failed: ' + escapeHtml(xhr.status + ' ' + xhr.statusText) + '</p>');
        });
    });

    // Close modal and restore focus
    var lastFocusedElement;
    $('.aih-modal-close, .aih-modal-backdrop').on('click', function() {
        $('#aih-order-modal').hide();
        if (lastFocusedElement) lastFocusedElement.focus();
    });

    // Close on escape key
    $(document).on('keydown', function(e) {
        if (e.key === 'Escape') {
            $('#aih-order-modal').hide();
            if (lastFocusedElement) lastFocusedElement.focus();
        }
    });
});
</script>


