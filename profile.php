<?php
// profile.php - Customer / Garage Profile & Avatar Photo Management
require_once __DIR__ . '/includes/functions.php';

requireAuth();
$user = currentUser();
$pdo = getDbConnection();

$userData = $user;
if ($pdo) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$user['id']]);
        $dbUser = $stmt->fetch();
        if ($dbUser) $userData = $dbUser;
    } catch (Exception $e) {}
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $address = sanitize($_POST['address'] ?? '');
    $city = sanitize($_POST['city'] ?? '');
    $removeAvatar = isset($_POST['remove_avatar']) && $_POST['remove_avatar'] === '1';

    $avatarUrl = $userData['avatar_url'] ?? '';

    // Handle Avatar Photo Removal
    if ($removeAvatar) {
        if (!empty($avatarUrl) && strpos($avatarUrl, 'assets/uploads/avatars/') !== false) {
            $oldPath = __DIR__ . '/' . $avatarUrl;
            if (file_exists($oldPath) && is_file($oldPath)) {
                @unlink($oldPath);
            }
        }
        $avatarUrl = null;
    }

    // Handle Avatar Photo Upload
    if (isset($_FILES['avatar_file']) && $_FILES['avatar_file']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['avatar_file'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowedExts = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        
        if (in_array($ext, $allowedExts)) {
            $uploadDir = __DIR__ . '/assets/uploads/avatars';
            if (!is_dir($uploadDir)) {
                @mkdir($uploadDir, 0777, true);
            }
            $filename = 'avatar_' . $user['id'] . '_' . time() . '.' . $ext;
            $targetPath = $uploadDir . '/' . $filename;

            if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                // Delete old uploaded avatar if exists
                if (!empty($avatarUrl) && strpos($avatarUrl, 'assets/uploads/avatars/') !== false) {
                    $oldPath = __DIR__ . '/' . $avatarUrl;
                    if (file_exists($oldPath) && is_file($oldPath)) {
                        @unlink($oldPath);
                    }
                }
                $avatarUrl = 'assets/uploads/avatars/' . $filename;
            }
        }
    }

    if ($pdo) {
        try {
            $up = $pdo->prepare("UPDATE users SET name = ?, phone = ?, address = ?, city = ?, avatar_url = ? WHERE id = ?");
            $up->execute([$name, $phone, $address, $city, $avatarUrl, $user['id']]);
            $_SESSION['user_name'] = $name;
            $_SESSION['user_avatar'] = $avatarUrl;
            setFlash('success', 'Profile and photo updated successfully!');
            header('Location: profile.php');
            exit;
        } catch (Exception $e) {
            setFlash('error', 'Update error: ' . $e->getMessage());
        }
    } else {
        $_SESSION['user_name'] = $name;
        $_SESSION['user_avatar'] = $avatarUrl;
        setFlash('success', 'Profile updated (Demo mode).');
        header('Location: profile.php');
        exit;
    }
}

$flash = getFlash();
$currentAvatar = getUserAvatarUrl($userData);
$hasCustomAvatar = !empty($userData['avatar_url']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Profile &amp; Settings | JKtech.LK</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <style>
    .profile-card {
      max-width: 680px;
      margin: 48px auto 80px;
      background: #0c1322;
      border: 1px solid rgba(255, 255, 255, 0.09);
      border-radius: 20px;
      padding: 36px;
      box-shadow: 0 24px 60px rgba(0, 0, 0, 0.65);
      position: relative;
      overflow: hidden;
    }

    .profile-card::before {
      content: "";
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 3px;
      background: linear-gradient(90deg, #00e5ff 0%, #ff4d2d 100%);
    }

    .profile-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 28px;
      padding-bottom: 20px;
      border-bottom: 1px solid rgba(255, 255, 255, 0.08);
      flex-wrap: wrap;
      gap: 14px;
    }

    .profile-avatar-section {
      display: flex;
      align-items: center;
      gap: 24px;
      background: #080d18;
      border: 1px solid rgba(255, 255, 255, 0.07);
      border-radius: 16px;
      padding: 20px 24px;
      margin-bottom: 28px;
    }

    .avatar-preview-wrap {
      position: relative;
      width: 84px;
      height: 84px;
      flex-shrink: 0;
    }

    .profile-big-avatar {
      width: 84px;
      height: 84px;
      border-radius: 50%;
      object-fit: cover;
      border: 2.5px solid #00e5ff;
      box-shadow: 0 0 20px rgba(0, 229, 255, 0.35);
      background: #0f172a;
    }

    .avatar-upload-btn-label {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      background: rgba(0, 229, 255, 0.12);
      border: 1px solid rgba(0, 229, 255, 0.4);
      color: #00e5ff;
      font-size: 13px;
      font-weight: 700;
      padding: 8px 16px;
      border-radius: 8px;
      cursor: pointer;
      transition: all 0.2s ease;
    }

    .avatar-upload-btn-label:hover {
      background: #00e5ff;
      color: #080c14;
    }

    .avatar-remove-btn {
      background: transparent;
      border: 1px solid rgba(239, 68, 68, 0.3);
      color: #f87171;
      font-size: 12px;
      font-weight: 600;
      padding: 7px 14px;
      border-radius: 8px;
      cursor: pointer;
      transition: all 0.2s ease;
      margin-left: 8px;
    }

    .avatar-remove-btn:hover {
      background: rgba(239, 68, 68, 0.15);
      color: #ef4444;
    }

    .profile-field {
      margin-bottom: 20px;
    }

    .profile-label {
      display: block;
      font-size: 12.5px;
      font-weight: 700;
      color: #cbd5e1;
      margin-bottom: 7px;
      letter-spacing: 0.2px;
    }

    .profile-input {
      width: 100%;
      height: 46px;
      background: #080d18;
      border: 1px solid rgba(255, 255, 255, 0.12);
      border-radius: 10px;
      padding: 0 16px;
      color: #ffffff;
      font-size: 14px;
      font-family: var(--font-body);
      outline: none;
      transition: all 0.2s ease;
    }

    .profile-input:focus {
      border-color: #00e5ff;
      background: #0a101f;
      box-shadow: 0 0 0 3px rgba(0, 229, 255, 0.12);
    }

    .profile-row-2col {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 16px;
    }

    .btn-save-profile {
      background: linear-gradient(90deg, #ff4d2d 0%, #f97316 100%);
      color: #ffffff;
      border: none;
      border-radius: 10px;
      font-family: var(--font-display);
      font-size: 14px;
      font-weight: 800;
      padding: 13px 28px;
      cursor: pointer;
      box-shadow: 0 6px 20px rgba(255, 77, 45, 0.35);
      transition: all 0.25s ease;
    }

    .btn-save-profile:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 26px rgba(255, 77, 45, 0.55);
    }

    @media (max-width: 580px) {
      .profile-card {
        padding: 24px 18px;
        margin: 24px auto 50px;
      }
      .profile-avatar-section {
        flex-direction: column;
        text-align: center;
        gap: 16px;
      }
      .profile-row-2col {
        grid-template-columns: 1fr;
        gap: 0;
      }
    }
  </style>
</head>
<body>

  <?php include __DIR__ . '/includes/navbar.php'; ?>

  <div class="container">
    <div class="profile-card">
      <div class="profile-header">
        <div>
          <h1 style="font-family: var(--font-display); font-size: 22px; font-weight: 900; color: #fff; margin: 0 0 4px;">MY PROFILE</h1>
          <p style="font-size: 13px; color: #94a3b8; margin: 0;">Manage your user account photo and dispatch details</p>
        </div>
        <div style="display: flex; gap: 10px; align-items: center;">
          <a href="orders.php" class="btn btn-outline btn-sm">My Orders &rarr;</a>
        </div>
      </div>

      <?php if ($flash): ?>
        <div class="alert alert-<?= htmlspecialchars($flash['type']) ?>" style="margin-bottom: 22px;">
          <span><?= htmlspecialchars($flash['message']) ?></span>
        </div>
      <?php endif; ?>

      <form method="POST" action="profile.php" enctype="multipart/form-data">
        <input type="hidden" name="remove_avatar" id="removeAvatarInput" value="0">

        <!-- Avatar Photo Upload Box -->
        <div class="profile-avatar-section">
          <div class="avatar-preview-wrap">
            <img src="<?= htmlspecialchars($currentAvatar) ?>" alt="<?= htmlspecialchars($userData['name']) ?>" class="profile-big-avatar" id="avatarPreviewImg">
          </div>
          <div>
            <div style="font-weight: 800; font-size: 15px; color: #fff; margin-bottom: 4px;">Profile Photo</div>
            <p style="font-size: 12.5px; color: #94a3b8; margin: 0 0 12px; line-height: 1.4;">
              Displayed on the top header bar next to your username. JPG, PNG or WEBP up to 5MB.
            </p>
            <div style="display: flex; align-items: center; flex-wrap: wrap; gap: 8px;">
              <label class="avatar-upload-btn-label">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path>
                  <circle cx="12" cy="13" r="4"></circle>
                </svg>
                <span>Choose Photo</span>
                <input type="file" name="avatar_file" id="avatarFileInput" accept="image/*" style="display: none;" onchange="previewSelectedAvatar(this)">
              </label>

              <?php if ($hasCustomAvatar): ?>
                <button type="button" class="avatar-remove-btn" onclick="triggerRemoveAvatar()">Remove Photo</button>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <div class="profile-field">
          <label class="profile-label" for="prof_name">Full Name / Garage Name</label>
          <input type="text" name="name" id="prof_name" class="profile-input" value="<?= htmlspecialchars($userData['name'] ?? '') ?>" required>
        </div>

        <div class="profile-row-2col">
          <div class="profile-field">
            <label class="profile-label">Email Address (Account ID)</label>
            <input type="email" class="profile-input" value="<?= htmlspecialchars($userData['email'] ?? '') ?>" readonly style="opacity: 0.6; cursor: not-allowed;">
          </div>
          <div class="profile-field">
            <label class="profile-label" for="prof_phone">Contact Phone Number</label>
            <input type="text" name="phone" id="prof_phone" class="profile-input" value="<?= htmlspecialchars($userData['phone'] ?? '') ?>" placeholder="077 123 4567">
          </div>
        </div>

        <div class="profile-field">
          <label class="profile-label" for="prof_address">Default Delivery / Workshop Address</label>
          <input type="text" name="address" id="prof_address" class="profile-input" value="<?= htmlspecialchars($userData['address'] ?? '') ?>" placeholder="e.g. 45 Temple Road">
        </div>

        <div class="profile-field">
          <label class="profile-label" for="prof_city">City</label>
          <input type="text" name="city" id="prof_city" class="profile-input" value="<?= htmlspecialchars($userData['city'] ?? 'Colombo') ?>">
        </div>

        <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid rgba(255, 255, 255, 0.08); flex-wrap: wrap; gap: 14px;">
          <button type="submit" class="btn-save-profile">Save Profile Changes</button>
          <a href="logout.php" style="color: #ef4444; font-weight: 700; font-size: 13.5px; text-decoration: none;">Sign Out</a>
        </div>
      </form>
    </div>
  </div>

  <?php include __DIR__ . '/includes/footer.php'; ?>

  <script>
    function previewSelectedAvatar(input) {
      if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
          document.getElementById('avatarPreviewImg').src = e.target.result;
          document.getElementById('removeAvatarInput').value = '0';
        };
        reader.readAsDataURL(input.files[0]);
      }
    }

    function triggerRemoveAvatar() {
      if (confirm('Are you sure you want to remove your profile photo and use default initials?')) {
        document.getElementById('removeAvatarInput').value = '1';
        document.querySelector('form').submit();
      }
    }
  </script>
</body>
</html>
