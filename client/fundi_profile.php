<?php
include "../includes/db.php";
include "../includes/session.php";

if(!isset($_GET['id'])){
    die("No fundi selected");
}

$id = (int) $_GET['id'];

$sql = "SELECT fundis.id AS fundi_id,
                             users.id AS user_id,
                             users.fullname,
                             users.phone,
                             users.email,
                             users.status,
                             COALESCE(fundis.service_category, 'General Service') AS service_category,
                             COALESCE(fundis.location, 'Not set') AS location,
                             COALESCE(fundis.verification_status, users.status) AS verification_status
                FROM users
                LEFT JOIN fundis ON fundis.user_id = users.id
                WHERE (fundis.id = $id OR users.id = $id)
                    AND TRIM(LOWER(users.role)) = 'fundi'
                    AND TRIM(LOWER(users.status)) = 'approved'
                LIMIT 1";

$result = mysqli_query($conn, $sql);

if(mysqli_num_rows($result) == 0){
    die("Fundi not found");
}

$row = mysqli_fetch_assoc($result);

// If this approved fundi has no profile row yet, create one now so booking FK works.
if (empty($row['fundi_id'])) {
    $newUserId = (int) $row['user_id'];
    $createStmt = $conn->prepare(
        "INSERT INTO fundis (user_id, service_category, location, verification_status)
         VALUES (?, 'General Service', 'Not set', 'approved')"
    );

    if ($createStmt) {
        $createStmt->bind_param("i", $newUserId);
        $createStmt->execute();
        $createStmt->close();
    }

    $reloadSql = "SELECT fundis.id AS fundi_id,
                         users.id AS user_id,
                         users.fullname,
                         users.phone,
                         users.email,
                         users.status,
                         COALESCE(fundis.service_category, 'General Service') AS service_category,
                         COALESCE(fundis.location, 'Not set') AS location,
                         COALESCE(fundis.verification_status, users.status) AS verification_status
                  FROM users
                  LEFT JOIN fundis ON fundis.user_id = users.id
                  WHERE users.id = $newUserId
                  LIMIT 1";

    $reloadResult = mysqli_query($conn, $reloadSql);
    if ($reloadResult && mysqli_num_rows($reloadResult) > 0) {
        $row = mysqli_fetch_assoc($reloadResult);
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Fundi Profile</title>

<style>
body{
    font-family: Arial;
    background:#f7f1ea;
    padding:20px;
}

.profile{
    width:400px;
    margin:auto;
    background:#fff;
    padding:20px;
    border-radius:15px;
    box-shadow:0 5px 15px rgba(0,0,0,0.1);
}

h2{
    color:#5c4b43;
}

.btn{
    display:inline-block;
    padding:10px 15px;
    background:#e89cae;
    color:#fff;
    text-decoration:none;
    border-radius:8px;
    margin-top:10px;
}

.badge{
    display:inline-block;
    margin-left:8px;
    padding:4px 10px;
    border-radius:999px;
    font-size:12px;
    font-weight:bold;
    vertical-align:middle;
}

.badge.verified{
    background:#e8f7ee;
    color:#1f7a3f;
    border:1px solid #bfe5cc;
}
</style>

</head>

<body>

<div class="profile">

<h2>
    <?php echo htmlspecialchars((string) $row['fullname']); ?>
    <?php if (in_array(strtolower((string) ($row['verification_status'] ?? '')), ['approved', 'verified'], true)): ?>
        <span class="badge verified">Admin Verified</span>
    <?php endif; ?>
</h2>

<p><b>Service:</b> <?php echo $row['service_category']; ?></p>
<p><b>Location:</b> <?php echo $row['location']; ?></p>
<p><b>Phone:</b> <?php echo $row['phone']; ?></p>
<p><b>Email:</b> <?php echo $row['email']; ?></p>
<p><b>Verification:</b> <?php echo htmlspecialchars((string) $row['verification_status']); ?></p>

<a class="btn" href="book_fundi.php?fundi_id=<?php echo (int) $row['fundi_id']; ?>">
Book Now
</a>

</div>

</body>
</html>