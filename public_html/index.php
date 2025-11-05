<?php
require 'db.php';

$is_logged_in = isset($_SESSION['user_id']);
$user_role = $is_logged_in ? $_SESSION['role'] : 'guest';
$parcels_to_collect = [];
$parcels_history = [];
$user_stats = [
    'parcels_to_collect' => 0,
    'late_fees_due' => 0,
    'late_fees_paid' => 0,
];

if ($is_logged_in) {
    $user_id = $_SESSION['user_id'];
    
    $settings_stmt = $pdo->query("SELECT * FROM settings");
    $settings = $settings_stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    $holidays_stmt = $pdo->query("SELECT holiday_date FROM holidays");
    $holiday_dates = $holidays_stmt->fetchAll(PDO::FETCH_COLUMN);

    $initial_fee = (float)($settings['late_fee_initial_day_fee'] ?? 1.00);
    $recurring_days_interval = (int)($settings['late_fee_recurring_days'] ?? 3);
    $recurring_amount = (float)($settings['late_fee_recurring_amount'] ?? 5.00);

    $fee_stmt = $pdo->prepare("SELECT id, registered_at, late_fee FROM parcels WHERE user_id = ? AND status != 'Collected'");
    $fee_stmt->execute([$_SESSION['user_id']]);
    $uncollected_parcels = $fee_stmt->fetchAll();

    $pdo->beginTransaction();
    try {
        foreach ($uncollected_parcels as $parcel) {
            $date_registered = new DateTime($parcel['registered_at']);
            $today = new DateTime();
            
            $business_days_held = 0;
            $period = new DatePeriod($date_registered, new DateInterval('P1D'), $today);
            
            foreach($period as $day) {
                $day_of_week = $day->format('N');
                $current_date_str = $day->format('Y-m-d');
                
                if ($day_of_week < 6 && !in_array($current_date_str, $holiday_dates)) {
                    $business_days_held++;
                }
            }

            $calculated_fee = 0;

            if ($business_days_held >= 1) {
                $calculated_fee += $initial_fee;
            }

            if ($business_days_held >= $recurring_days_interval) {
                $recurring_periods = floor(($business_days_held - 1) / $recurring_days_interval);
                $calculated_fee += $recurring_periods * $recurring_amount;
            }
            
            if ($calculated_fee > $parcel['late_fee']) {
                $update_stmt = $pdo->prepare("UPDATE parcels SET late_fee = ?, status = 'Late Collection' WHERE id = ?");
                $update_stmt->execute([$calculated_fee, $parcel['id']]);
            }
        }
        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Late fee calculation failed: " . $e->getMessage());
    }

    $stmt = $pdo->prepare("SELECT id, tracking_number, courier, status, registered_at, collected_at, late_fee FROM parcels WHERE user_id = ? ORDER BY registered_at DESC");
    $stmt->execute([$user_id]);
    $all_parcels = $stmt->fetchAll();

    foreach ($all_parcels as $parcel) {
        if ($parcel['status'] !== 'Collected') {
            $parcels_to_collect[] = $parcel;
            $user_stats['late_fees_due'] += $parcel['late_fee'];
        } else {
            $parcels_history[] = $parcel;
        }
    }

    $user_stats['parcels_to_collect'] = count($parcels_to_collect);

    $fees_paid_stmt = $pdo->prepare("SELECT SUM(late_fee) FROM parcels WHERE user_id = ? AND status = 'Collected'");
    $fees_paid_stmt->execute([$user_id]);
    $user_stats['late_fees_paid'] = $fees_paid_stmt->fetchColumn() ?: 0;
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>KoPickUp | KopPSP</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            text-align: center;
        }

        .stat-card {
            background: linear-gradient(135deg, rgba(230, 245, 255, 0.5), rgba(240, 240, 255, 0.5));
            padding: 25px;
            border-radius: 16px;
            border: 1px solid rgba(0, 0, 0, 0.03);
        }

        .stat-card h3 {
            font-size: 16px;
            font-weight: 600;
            color: #64748b;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stat-card p {
            font-size: 36px;
            font-weight: 700;
            color: #334155;
            line-height: 1.2;
        }

        h3 {
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <div class="logo">
                <h1><a href="/" style="text-decoration:none; color:inherit;">KoPickUp</a></h1>
            </div>
            <nav class="nav-links">
                <a href="/">HOME</a>
                <a href="/shop">SHOP</a>
                <a href="/about">ABOUT</a>
                <?php if ($user_role === 'admin'): ?>
                    <a href="/admin">ADMIN PANEL</a>
                    <a href="/manager">MANAGER PANEL</a>
                <?php elseif ($user_role === 'manager'): ?>
                    <a href="/manager">MANAGER PANEL</a>
                <?php endif; ?>
                 <?php if ($is_logged_in): ?>
                    <a href="#" onclick="openProfilePopup(); return false;">PROFILE</a>
                <?php endif; ?>
            </nav>
            <div class="header-buttons">
                <?php if ($is_logged_in): ?>
                    <a href="/logout" class="signup">Logout</a>
                <?php else: ?>
                    <a href="/login" class="login">Login</a>
                    <a href="/signup" class="signup">Sign Up</a>
                <?php endif; ?>
            </div>
        </header>

        <div class="hero-banner">
            <img src="/img2edited.jpg" alt="Banner" />
        </div>

        <main>
            <?php if (!$is_logged_in): ?>
            <section class="hero">
                <div class="hero-text">
                    <h2>Welcome to KoPickUp</h2>
                    <p>Your university's central hub for parcel collection. Please log in to see your parcels, or use the tracking system below.</p>
                </div>
                
                <form action="/track" method="GET" class="tracking-input">
                    <input type="text" name="tracking_number" placeholder="Enter tracking number" required />
                    <button type="submit" class="track-btn">Track</button>
                </form>
            </section>
            <?php endif; ?>

            <?php if ($is_logged_in): ?>
            <div class="section-card">
                <h3>Your Dashboard</h3>
                <div class="stats-grid">
                    <div class="stat-card">
                        <h3>Parcels to Collect</h3>
                        <p><?php echo $user_stats['parcels_to_collect']; ?></p>
                    </div>
                    <div class="stat-card">
                        <h3>Late Fees Due</h3>
                        <p>RM <?php echo number_format($user_stats['late_fees_due'], 2); ?></p>
                    </div>
                    <div class="stat-card">
                        <h3>Total Fees Paid</h3>
                        <p>RM <?php echo number_format($user_stats['late_fees_paid'], 2); ?></p>
                    </div>
                </div>
            </div>
            
            <section class="section-card">
                <h2>Parcels to Collect</h2>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Tracking #</th>
                                <th>Courier</th>
                                <th>Status</th>
                                <th>Late Fee</th>
                                <th>Date Registered</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($parcels_to_collect)): ?>
                                <tr><td colspan="6">You have no parcels to collect.</td></tr>
                            <?php else: ?>
                                <?php foreach ($parcels_to_collect as $parcel): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($parcel['tracking_number']); ?></td>
                                        <td><?php echo htmlspecialchars($parcel['courier']); ?></td>
                                        <td>
                                            <span class="status-badge <?php echo strtolower(str_replace(' ', '-', $parcel['status'])); ?>">
                                                <?php echo htmlspecialchars($parcel['status']); ?>
                                            </span>
                                        </td>
                                        <td>RM <?php echo number_format($parcel['late_fee'], 2); ?></td>
                                        <td><?php echo date('F j, Y', strtotime($parcel['registered_at'])); ?></td>
                                        <td>
                                            <button class="collect-btn" onclick="showQrCode('<?php echo $parcel['tracking_number']; ?>')">Show QR</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="section-card">
                <h2>Collection History</h2>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Tracking #</th>
                                <th>Courier</th>
                                <th>Status</th>
                                <th>Collected Date</th>
                            </tr>
                        </thead>
                        <tbody>
                             <?php if (empty($parcels_history)): ?>
                                <tr><td colspan="4">You have no collected parcels yet.</td></tr>
                            <?php else: ?>
                                <?php foreach ($parcels_history as $parcel): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($parcel['tracking_number']); ?></td>
                                        <td><?php echo htmlspecialchars($parcel['courier']); ?></td>
                                        <td>
                                            <span class="status-badge status-collected">
                                                <?php echo htmlspecialchars($parcel['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('F j, Y', strtotime($parcel['collected_at'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
            <?php endif; ?>
        </main>
    </div>
    
    <section>
        <div class="section-card">
            <h2>About</h2>
            <div class="about">
                <!--<div class="about-img">Logo</div>-->
                <p style="font-size: 20px">KoPickUp is a project in collaboration with Koperasi Politeknik Seberang Perai. Our platform helps students easily track and collect their parcels at the Koperasi, making the process faster and more convenient.</p>
            </div>
        </div>
    </section>

    <section>
        <div class="section-card">
            <h2>E-Commerce</h2>
            <div class="coming-soon">
                <div>Coming Soon</div>
            </div>
        </div>
    </section>

    <footer class="site-footer">
        <p style="text-align: center;">&copy; <?php echo date('Y'); ?> <a href="/" style="text-decoration: none;">KoPickUp</a>. All Rights Reserved. Made by <a href="https://danidev.co.za" style="text-decoration: none;">dani dev</a>.</p>
    </footer>

    <div id="qrModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.7); z-index:200; align-items:center; justify-content:center;">
        <div style="background:white; padding:40px; border-radius:15px; text-align:center;">
            <h3>Scan for Collection</h3>
            <div id="qrcode-container"></div>
            <p id="qr-tracking-number" style="margin-top:15px; font-weight:bold;"></p>
            <button onclick="document.getElementById('qrModal').style.display='none'" style="margin-top:20px; padding:10px 20px;">Close</button>
        </div>
    </div>

    <script src="https://cdn.rawgit.com/davidshimjs/qrcodejs/gh-pages/qrcode.min.js"></script>
    <script>
        function showQrCode(trackingNumber) {
            const modal = document.getElementById('qrModal');
            const qrContainer = document.getElementById('qrcode-container');
            qrContainer.innerHTML = '';
            
            const baseUrl = window.location.origin;
            const collectUrl = `${baseUrl}/collect?tracking_number=${trackingNumber}`;

            new QRCode(qrContainer, {
                text: collectUrl,
                width: 200,
                height: 200,
            });
            
            document.getElementById('qr-tracking-number').innerText = 'Tracking #: ' + trackingNumber;
            modal.style.display = 'flex';
        }
    </script>

    <?php include __DIR__ . '/includes/profile_popup.php'; ?>
</body>
</html>
