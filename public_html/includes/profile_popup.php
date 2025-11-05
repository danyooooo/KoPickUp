<?php
$popup_user_data = null;
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT fullname, email, role FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $popup_user_data = $stmt->fetch();
}
?>

<div id="profileOverlay" class="profile-overlay">
    <div class="profile-popup">
        <div class="profile-image"></div>
        <div class="profile-info">
            <h2 id="profileName"></h2>
            <p id="profileEmail"></p>
            <p id="profileRole"></p>
        </div>
        <a href="/profile/" class="profile-edit-btn">Edit Profile</a>
        <button class="profile-close-btn" onclick="closeProfilePopup()">Close</button>
    </div>
</div>

<style>
.profile-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.6);
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 1001;
}
.profile-popup {
    background: #fff;
    padding: 30px;
    border-radius: 20px;
    box-shadow: 0 5px 25px rgba(0,0,0,0.2);
    width: 90%;
    max-width: 360px;
    text-align: center;
}
.profile-image {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    background: #e9ecef;
    margin: 0 auto 20px;
    border: 4px solid #fff;
    box-shadow: 0 0 10px rgba(0,0,0,0.1);
}
.profile-info h2 { margin: 0 0 5px; font-size: 24px; color: #333; }
.profile-info p { margin: 4px 0; color: #6c757d; font-size: 16px; }
#profileRole { font-weight: bold; text-transform: capitalize; }
.profile-close-btn {
    margin-top: 25px;
    padding: 12px 30px;
    border-radius: 10px;
    border: none;
    background: #f1f3f5;
    color: #333;
    font-weight: bold;
    cursor: pointer;
    transition: background 0.2s;
}
.profile-close-btn:hover { background: #e9ecef; }
.profile-edit-btn {
    display: block;
    width: 100%;
    padding: 12px;
    margin-top: 15px;
    border-radius: 10px;
    background: #007bff;
    color: white;
    font-weight: bold;
    text-decoration: none;
    transition: background 0.2s;
}
.profile-edit-btn:hover {
    background: #0056b3;
}
</style>

<script>
const profileOverlay = document.getElementById('profileOverlay');

function openProfilePopup() {
    const user = <?php echo json_encode($popup_user_data); ?>;

    if (user) {
        document.getElementById('profileName').textContent = user.fullname;
        document.getElementById('profileEmail').textContent = user.email;
        document.getElementById('profileRole').textContent = 'Role: ' + user.role;
        profileOverlay.style.display = 'flex';
    } else {
        window.location.href = '/login';
    }
}

function closeProfilePopup() {
    profileOverlay.style.display = 'none';
}

profileOverlay.addEventListener('click', function(event) {
    if (event.target === profileOverlay) {
        closeProfilePopup();
    }
});
</script>