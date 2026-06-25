<?php
include "../includes/db.php";
include "../includes/session.php";

// Ensure approved fundi users always have a discoverable fundi profile row.
$conn->query(
    "INSERT INTO fundis (user_id, service_category, location, verification_status)
         SELECT users.id, 'General Service', 'Not set', 'verified'
     FROM users
         WHERE LOWER(users.role) LIKE '%fundi%'
             AND LOWER(users.status) LIKE '%approved%'
       AND NOT EXISTS (
           SELECT 1 FROM fundis WHERE fundis.user_id = users.id
       )"
);

// Keep profile verification aligned with approved users.
$conn->query(
    "UPDATE fundis
     JOIN users ON users.id = fundis.user_id
    SET fundis.verification_status = 'verified'
    WHERE LOWER(users.role) LIKE '%fundi%'
      AND LOWER(users.status) LIKE '%approved%'"
);

$search = "";
if(isset($_GET['search'])){
    $search = $_GET['search'];
}

$searchTerm = trim($search);

$sql = "SELECT
            users.id AS user_id,
            MIN(fundis.id) AS fundi_id,
            users.fullname,
            MAX(LOWER(COALESCE(fundis.verification_status, users.status))) AS verification_status,
            MAX(COALESCE(fundis.service_category, 'General Service')) AS service_category,
            MAX(COALESCE(fundis.location, 'Not set')) AS location
        FROM users
        LEFT JOIN fundis ON fundis.user_id = users.id
        WHERE LOWER(users.role) LIKE '%fundi%'
          AND LOWER(users.status) LIKE '%approved%'";

if ($searchTerm !== "") {
    $escapedSearch = mysqli_real_escape_string($conn, $searchTerm);
    $sql .= " AND (
        users.fullname LIKE '%$escapedSearch%'
        OR COALESCE(fundis.service_category, '') LIKE '%$escapedSearch%'
        OR COALESCE(fundis.location, '') LIKE '%$escapedSearch%'
    )";
}

$sql .= " GROUP BY users.id, users.fullname";
$sql .= " ORDER BY users.fullname ASC";

$result = mysqli_query($conn, $sql);

if ($result === false) {
    echo "<p>Could not load fundis right now. Please try again.</p>";
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Find Fundis</title>

<style>
body{
    font-family:Arial;
    background:#f7f1ea;
    margin:0;
    padding:20px;
}

.container{
    width:90%;
    margin:auto;
}

.topbar{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:20px;
}

input{
    padding:10px;
    width:300px;
    border-radius:8px;
    border:1px solid #ccc;
}

.btn{
    padding:10px 15px;
    background:#e89cae;
    color:white;
    text-decoration:none;
    border-radius:8px;
    border:none;
}

.card{
    background:white;
    padding:15px;
    margin:15px 0;
    border-radius:12px;
    box-shadow:0 4px 10px rgba(0,0,0,0.1);
}

.badge{
    display:inline-block;
    font-size:12px;
    font-weight:bold;
    padding:4px 9px;
    border-radius:999px;
    margin-left:8px;
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

<div class="container">

<!-- TOP BAR -->
<div class="topbar">

    <!-- BACK BUTTON -->
    <a class="btn" href="client_dashboard.php">⬅️ Back to Dashboard</a>

    <!-- SEARCH FORM -->
    <form method="GET">
        <input type="text" name="search" placeholder="Search fundi (electrician, plumber...)" value="<?php echo $search; ?>">
        <button class="btn" type="submit">Search</button>
    </form>

</div>

<h2>Available Fundis</h2>

<?php if(mysqli_num_rows($result) > 0){

    while($row = mysqli_fetch_assoc($result)){
?>

<div class="card">
    <h3>
        <?php echo htmlspecialchars((string) $row['fullname']); ?>
        <?php if (in_array(strtolower((string) ($row['verification_status'] ?? '')), ['approved', 'verified'], true)): ?>
            <span class="badge verified">Admin Verified</span>
        <?php endif; ?>
    </h3>
    <p><b>Service:</b> <?php echo htmlspecialchars((string) $row['service_category']); ?></p>
    <p><b>Location:</b> <?php echo htmlspecialchars((string) $row['location']); ?></p>

    <a class="btn" href="fundi_profile.php?id=<?php echo (int) ($row['fundi_id'] ?: $row['user_id']); ?>">
        👤 View Profile
    </a>

</div>

<?php
    }

}else{
    echo "<p>No fundis found.</p>";
}
?>

</div>

</body>
</html>