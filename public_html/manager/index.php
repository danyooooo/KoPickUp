<?php
require __DIR__ . '/../db.php';
require_once __DIR__ . '/ef.php';

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['manager', 'admin'])) {
    die('Access Denied');
}

$message = '';
$error_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_fees'])) {
    $initial_fee = $_POST['initial_fee'];
    $recurring_days = $_POST['recurring_days'];
    $recurring_amount = $_POST['recurring_amount'];

    try {
        $pdo->beginTransaction();
        $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'late_fee_initial_day_fee'")->execute([$initial_fee]);
        $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'late_fee_recurring_days'")->execute([$recurring_days]);
        $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'late_fee_recurring_amount'")->execute([$recurring_amount]);
        $pdo->commit();
        $message = "Late fee settings updated successfully!";
    } catch (Exception $e) {
        $pdo->rollBack();
        $error_message = "Failed to update settings: " . $e->getMessage();
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['register_parcel'])) {
    $recipient_name = strtoupper(trim($_POST['recipient_name']));
    $tracking_number = trim($_POST['tracking_number']);
    $courier = trim($_POST['courier']);
    
    $weight = trim($_POST['weight']) ?: null;
    $dimensions = trim($_POST['dimensions']) ?: null;
    $destination = trim($_POST['destination']) ?: null;

    $settings = $pdo->query("SELECT * FROM settings")->fetchAll(PDO::FETCH_KEY_PAIR);
    $initial_fee = (float)($settings['late_fee_initial_day_fee'] ?? 1.00);
    
    $user_id = null;
    $user_email = null;

    $stmt = $pdo->prepare("SELECT id, email FROM users WHERE fullname = ?");
    $stmt->execute([$recipient_name]);
    $user = $stmt->fetch();

    if ($user) {
        $user_id = $user['id'];
        $user_email = $user['email'];
    }

    $stmt = $pdo->prepare(
        "INSERT INTO parcels (user_id, recipient_name, tracking_number, courier, weight, dimensions, destination, status, registered_at, late_fee) 
         VALUES (?, ?, ?, ?, ?, ?, ?, 'Registered', NOW(), ?)"
    );

    $stmt->execute([$user_id, $recipient_name, $tracking_number, $courier, $weight, $dimensions, $destination, $initial_fee]);
    
    $message = "Parcel registered successfully for " . htmlspecialchars($recipient_name);

    if ($user_email) {
        sendParcelNotification($pdo, $user_email, $recipient_name, $tracking_number);
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['add_holiday'])) {
        $holiday_date = $_POST['holiday_date'];
        $description = trim($_POST['description']);
        if (!empty($holiday_date)) {
            try {
                $stmt = $pdo->prepare("INSERT INTO holidays (holiday_date, description) VALUES (?, ?)");
                $stmt->execute([$holiday_date, $description]);
                $message = "Holiday added successfully.";
            } catch (Exception $e) {
                $error_message = "Could not add holiday. The date might already exist.";
            }
        }
    }
    
    if (isset($_POST['delete_holiday'])) {
        $holiday_id = $_POST['holiday_id'];
        $stmt = $pdo->prepare("DELETE FROM holidays WHERE id = ?");
        $stmt->execute([$holiday_id]);
        $message = "Holiday removed successfully.";
    }
}

$holidays = $pdo->query("SELECT * FROM holidays WHERE holiday_date >= CURDATE() ORDER BY holiday_date ASC")->fetchAll();

$filter_date = $_GET['filter_date'] ?? null;

$base_query = "SELECT p.*, u.fullname 
               FROM parcels p 
               LEFT JOIN users u ON p.user_id = u.id";
$params = [];

if ($filter_date) {
    $base_query .= " WHERE DATE(p.registered_at) = ?";
    $params[] = $filter_date;
}

$base_query .= " ORDER BY COALESCE(u.fullname, p.recipient_name) ASC, p.registered_at DESC";

$stmt = $pdo->prepare($base_query);
$stmt->execute($params);
$parcels = $stmt->fetchAll();

$total_customers = $pdo->query("SELECT COUNT(id) FROM users WHERE role = 'user'")->fetchColumn();
$total_parcels = $pdo->query("SELECT COUNT(id) FROM parcels")->fetchColumn();
$parcels_collected = $pdo->query("SELECT COUNT(id) FROM parcels WHERE status = 'Collected'")->fetchColumn();
$active_parcels = $total_parcels - $parcels_collected;

$settings = $pdo->query("SELECT * FROM settings")->fetchAll(PDO::FETCH_KEY_PAIR);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
    <title>Manager Panel | KoPickUp</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        h2 {
            text-align: center;
        }
        
        .stats-grid,
        .manager-grid {
            display: flex;
            justify-content: center;
            gap: 30px;
            margin-bottom: 40px;
            flex-wrap: wrap;
            text-align: center;
        }

        .stats-grid .stat-card {
            background: rgba(255, 255, 255, 0.7);
            padding: 20px 30px;
            border-radius: 16px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            border: 1px solid rgba(0,0,0,0.05);
            min-width: 180px;
        }
        .stats-grid .stat-card h3 {
            font-size: 16px;
            color: #6c757d;
            margin-bottom: 8px;
            font-weight: 500;
            text-transform: uppercase;
        }
        .stats-grid .stat-card p {
            font-size: 32px;
            font-weight: 700;
            color: #334155;
            margin: 0;
        }

        .manager-grid .section-card {
            flex: 1;
            min-width: 350px;
            max-width: 500px;
            text-align: center;
        }

        .alert-success, .alert-error {
            padding: 15px 25px;
            border-radius: 12px;
            margin: 20px auto;
            font-weight: 600;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            width: fit-content;
        }

        .alert-success { background-color: #d4edda; color: #155724; }
        .alert-error { background-color: #f8d7da; color: #721c24; }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 20px;
        }
        .card-header h3 { margin: 0; }

        .button-group button,
        .button-group a {
            background: linear-gradient(135deg, #aeeaff, #aeaeea);
            color: #334155;
            padding: 12px 28px;
            border-radius: 14px;
            border: none;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.25s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: inline-block;
            text-decoration: none;
        }
        .button-group button:hover a:hover {
            transform: translateY(-2px);
        }
        .button-group button:active a:active {
            transform: scale(0.98) translateY(0);
        }

        .button-group button a, .action-btn {
            padding: 12px 24px;
            white-space: nowrap;
        }

        .section-card form button {
            width: 100%;
            margin-top: 20px;
        }

        form input,
        form select {
            padding: 12px 55px;
        }

        .action-btn {
            background: #4CAF50;
            color: white;
            border: none;
            padding: 6px 12px;
            font-size: 14px;
            font-weight: 500;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.2s ease;
            white-space: nowrap;
            line-height: 1.4;
            display: inline-block;
            vertical-align: middle;
            box-sizing: border-box;
            text-decoration: none;
        }

        .action-btn:hover {
            background: #45a049;
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.15);
        }

        .action-btn:active {
            transform: translateY(0);
            box-shadow: 0 1px 2px rgba(0,0,0,0.15);
        }

        .action-btn:focus {
            outline: 2px solid #8BC34A;
            outline-offset: 1px;
        }

        .action-btn:disabled {
            background: #cccccc;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .autocomplete-container {
            position: relative;
        }

        #suggestions-box {
            position: absolute;
            border: 1px solid #ddd;
            border-top: none;
            z-index: 100;
            top: 100%;
            left: 0;
            right: 0;
            background-color: white;
            border-radius: 0 0 8px 8px;
            box-shadow: 0 8px 16px rgba(0,0,0,0.1);
            max-height: 200px;
            overflow-y: auto;
            display: none;
        }
        .suggestion-item {
            padding: 12px 15px;
            cursor: pointer;
            text-align: left;
            color: #333;
        }
        .suggestion-item:hover {
            background-color: #f1f3f5;
        }

        .filter-card-buttons {
            margin-top: 20px;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .filter-card-buttons button {
            width: 100%;
        }

        .filter-card-buttons a {
            width: 100%;
            text-align: center;
            background: #6c757d;
            padding: 8px;
            font-size: 14px;
            font-weight: 600;
        }
    </style>
</head>
<body>
<div class="container">
    <header>
        <div class="logo">
            <h1><a href="/">KoPickUp</a></h1>
        </div>
        <nav class="nav-links">
            <a href="/">HOME</a>
            <a href="/shop">SHOP</a>
            <a href="/about">ABOUT</a>
            <?php if ($_SESSION['role'] === 'admin'): ?>
                <a href="/admin">ADMIN PANEL</a>
            <?php endif; ?>
            <a href="#" onclick="openProfilePopup(); return false;">PROFILE</a>
        </nav>
        <div class="header-buttons">
            <a href="/logout" class="signup">Logout</a>
        </div>
    </header>

    <main>
        <h2>Manager Dashboard</h2>
        
        <div class="stats-grid">
            <div class="stat-card"><h3>Total Customers</h3><p><?php echo $total_customers; ?></p></div>
            <div class="stat-card"><h3>Total Parcels</h3><p><?php echo $total_parcels; ?></p></div>
            <div class="stat-card"><h3>Parcels Collected</h3><p><?php echo $parcels_collected; ?></p></div>
            <div class="stat-card"><h3>Active Parcels</h3><p><?php echo $active_parcels; ?></p></div>
        </div>

        <?php if ($message): ?><div class="alert-success"><?php echo $message; ?></div><?php endif; ?>
        <?php if ($error_message): ?><div class="alert-error"><?php echo $error_message; ?></div><?php endif; ?>

        <div class="manager-grid">
            <div class="section-card">
                <h3>Late Fee Settings</h3>
                <form action="" method="POST">
                    <div class="form-grid">
                        <div><label>Initial Day Fee (RM)</label><input type="number" step="0.01" name="initial_fee" value="<?php echo htmlspecialchars($settings['late_fee_initial_day_fee']); ?>" required></div>
                        <div><label>Recurring Fee Interval (Days)</label><input type="number" name="recurring_days" value="<?php echo htmlspecialchars($settings['late_fee_recurring_days']); ?>" required></div>
                        <div><label>Recurring Fee Amount (RM)</label><input type="number" step="0.01" name="recurring_amount" value="<?php echo htmlspecialchars($settings['late_fee_recurring_amount']); ?>" required></div>
                    </div>
                    <button type="submit" name="update_fees">Save Fee Settings</button>
                </form>
            </div>

            <div class="section-card">
                <h3>Register Incoming Parcel</h3>
                <form action="" method="POST">
                    <div class="form-grid">
                        <div class="autocomplete-container">
                            <input type="text" id="recipientNameInput" name="recipient_name" placeholder="Recipient's Full Name*" onkeyup="fetchUserSuggestions()" autocomplete="off" required>
                            <div id="suggestions-box"></div>
                        </div>
                        <input type="text" name="tracking_number" placeholder="Tracking Number*" required>
                        <input type="text" name="courier" placeholder="Courier*" required>
                        <input type="text" name="weight" placeholder="Weight (Optional)">
                        <input type="text" name="dimensions" placeholder="Dimensions (Optional)">
                        <input type="text" name="destination" placeholder="Destination (Optional)">
                    </div>
                    <button type="submit" name="register_parcel">Register Parcel & Notify Customer</button>
                </form>
            </div>

            <div class="section-card">
                <h3>Filter Parcels</h3>
                <p>Show only parcels registered on a specific date.</p>
                <form action="" method="GET">
                    <div class="form-grid" style="grid-template-columns: 1fr;">
                        <div>
                            <!--<label>Registration Date</label>-->
                            <input type="date" name="filter_date" value="<?php echo htmlspecialchars($filter_date ?? ''); ?>" required>
                        </div>
                    </div>
                    <div class="filter-card-buttons">
                        <button type="submit" name="apply_filter">Apply Filter</button>
                        <a href="/manager/" class="action-btn">Clear Filter</a>
                    </div>
                </form>
            </div>

            <div class="section-card">
                <h3>Holiday Management</h3>
                <p>Add dates when the shop will be closed. Late fees will not be calculated on these days.</p>
                
                <form action="" method="POST" style="margin-bottom: 20px;">
                    <div class="form-grid">
                        <input type="date" name="holiday_date" required>
                        <input type="text" name="description" placeholder="Description (Optional)">
                    </div>
                    <button type="submit" name="add_holiday">Add Holiday</button>
                </form>

                <div class="table-responsive" style="max-height: 200px; text-align: left;">
                    <h4>Upcoming Holidays:</h4>
                    <table>
                        <tbody>
                            <?php if (empty($holidays)): ?>
                                <tr><td>No upcoming holidays scheduled.</td></tr>
                            <?php else: ?>
                                <?php foreach ($holidays as $holiday): ?>
                                    <tr>
                                        <td><?php echo date('M j, Y', strtotime($holiday['holiday_date'])); ?></td>
                                        <td><?php echo htmlspecialchars($holiday['description']); ?></td>
                                        <td style="text-align: right;">
                                            <form action="" method="POST" style="margin:0;">
                                                <input type="hidden" name="holiday_id" value="<?php echo $holiday['id']; ?>">
                                                <button type="submit" name="delete_holiday" class="action-btn" style="background: #dc3545;">Delete</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="section-card">
            <div class="card-header">
                <h3>Parcel Management</h3>
                <div class="button-group">
                    <button id="scan-qr-btn">Scan QR Code</button>
                    <a href="export.php?format=pdf">Export PDF</a>
                    <a href="export.php?format=excel">Export Excel</a>
                    <!--<a href="export.php?format=csv">Export CSV</a>-->
                </div>
            </div>

            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Recipient Name</th>
                            <th>Tracking #</th>
                            <th>Courier</th>
                            <th>Status</th>
                            <th>Date Registered</th>
                            <th>Date Collected</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($parcels as $parcel): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($parcel['fullname'] ?? $parcel['recipient_name']); ?></td>
                                <td><?php echo htmlspecialchars($parcel['tracking_number']); ?></td>
                                <td><?php echo htmlspecialchars($parcel['courier']); ?></td>
                                <td>
                                    <span class="status-badge <?php echo strtolower(str_replace(' ', '-', $parcel['status'])); ?>">
                                        <?php echo htmlspecialchars($parcel['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M j, Y H:i', strtotime($parcel['registered_at'])); ?></td>
                                <td>
                                    <?php 
                                        if ($parcel['collected_at']) {
                                            echo date('M j, Y H:i', strtotime($parcel['collected_at']));
                                        } else {
                                            echo '<span style="color: #999;">Not collected</span>';
                                        }
                                    ?>
                                </td>
                                <td>
                                    <?php if ($parcel['status'] != 'Collected'): ?>
                                    <a href="/collect?tracking_number=<?php echo $parcel['tracking_number']; ?>" class="action-btn">Mark as Collected</a>
                                    <?php else: ?>
                                    <span>-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <footer class="site-footer">
        <p style="text-align: center;">&copy; <?php echo date('Y'); ?> <a href="/" style="text-decoration: none;">KoPickUp</a>. All Rights Reserved. Made by <a href="https://danidev.co.za" style="text-decoration: none;">dani dev</a>.</p>
    </footer>
</div>

<div id="qrScannerModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); z-index:1000; align-items:center; justify-content:center; flex-direction:column;">
    <div id="qr-reader" style="width: 90%; max-width: 500px; background: white; border-radius: 15px; overflow: hidden;"></div>
    <button id="closeScannerBtn" style="margin-top:20px; padding: 10px 25px; border-radius:10px; border:none; background: #fff; color: #333; font-weight:bold; cursor:pointer;">Close Scanner</button>
</div>

<script>
const scanBtn = document.getElementById('scan-qr-btn');
const closeScannerBtn = document.getElementById('closeScannerBtn');
const qrModal = document.getElementById('qrScannerModal');
const qrReaderElement = document.getElementById('qr-reader');

let html5QrCode;

function onScanSuccess(decodedText, decodedResult) {
    console.log(`Code matched = ${decodedText}`, decodedResult);
    
    html5QrCode.stop().then(() => {
        qrModal.style.display = 'none';
        window.location.href = decodedText;
    }).catch(err => console.error("Failed to stop scanner", err));
}

function onScanFailure(error) {
    console.warn(`Code scan error = ${error}`);
}

scanBtn.addEventListener('click', () => {
    qrModal.style.display = 'flex';
    html5QrCode = new Html5Qrcode("qr-reader");
    html5QrCode.start(
        { facingMode: "environment" },
        {
            fps: 10,
            qrbox: { width: 250, height: 250 }
        },
        onScanSuccess,
        onScanFailure
    ).catch(err => {
        alert("Unable to start QR Code scanner. Please ensure you have a camera and have granted permission.");
        console.error("QR Scanner Error:", err);
        qrModal.style.display = 'none';
    });
});

closeScannerBtn.addEventListener('click', () => {
    if (html5QrCode && html5QrCode.isScanning) {
        html5QrCode.stop().then(() => {
            qrModal.style.display = 'none';
        });
    } else {
        qrModal.style.display = 'none';
    }
});

const recipientInput = document.getElementById('recipientNameInput');
const suggestionsBox = document.getElementById('suggestions-box');

function fetchUserSuggestions() {
    const query = recipientInput.value;

    if (query.length < 2) {
        suggestionsBox.style.display = 'none';
        return;
    }

    fetch(`user_suggestions.php?q=${encodeURIComponent(query)}`)
        .then(response => response.json())
        .then(users => {
            if (users.length > 0) {
                let suggestionsHTML = '';
                users.forEach(user => {
                    suggestionsHTML += `<div class="suggestion-item" onclick="selectSuggestion('${user.fullname.replace(/'/g, "\\'")}')">${user.fullname}</div>`;
                });
                suggestionsBox.innerHTML = suggestionsHTML;
                suggestionsBox.style.display = 'block';
            } else {
                suggestionsBox.style.display = 'none';
            }
        })
        .catch(error => {
            console.error('Error fetching user suggestions:', error);
            suggestionsBox.style.display = 'none';
        });
}

function selectSuggestion(name) {
    recipientInput.value = name;
    suggestionsBox.style.display = 'none';
}

document.addEventListener('click', function(event) {
    if (!recipientInput.contains(event.target)) {
        suggestionsBox.style.display = 'none';
    }
});
</script>
<?php include __DIR__ . '/../includes/profile_popup.php'; ?>
</body>
</html>