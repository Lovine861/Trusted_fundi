<?php
require_once __DIR__ . "/../includes/session.php";
require_once __DIR__ . "/../includes/db.php";

$currentRole = strtolower(trim((string) ($_SESSION['role'] ?? '')));
if ($currentRole !== 'fundi') {
	header("Location: ../login.php");
	exit();
}

$fundiUserId = (int) $_SESSION['user_id'];
$fundiId = 0;
$errorMessage = "";

$profileStmt = $conn->prepare("SELECT id FROM fundis WHERE user_id = ? LIMIT 1");
if ($profileStmt) {
	$profileStmt->bind_param("i", $fundiUserId);
	$profileStmt->execute();
	$profileResult = $profileStmt->get_result();
	$profileRow = $profileResult ? $profileResult->fetch_assoc() : null;
	if ($profileRow) {
		$fundiId = (int) $profileRow['id'];
	}
	$profileStmt->close();
}

if ($fundiId <= 0) {
	$errorMessage = "Fundi profile not found. Ratings cannot be loaded.";
}


$stmt = $conn->prepare(
	"SELECT id, review, rating, created_at
	 FROM reviews
	 WHERE fundi_id = ?
	 ORDER BY created_at DESC"
);

$result = false;
if ($stmt && $errorMessage === "") {
	$stmt->bind_param("i", $fundiId);
	if ($stmt->execute()) {
		$result = $stmt->get_result();
	} else {
		$errorMessage = "Could not load ratings right now.";
	}
} else {
	// This catches schema mismatch or missing table issues without crashing the page.
	$errorMessage = "Ratings table is not ready yet. Please initialize the database and try again.";
}
?>

<!DOCTYPE html>
<html>
<head>
	<title>My Ratings</title>
	<style>
		body {
			margin: 0;
			font-family: Arial, sans-serif;
			background: #f4efe7;
			padding: 20px;
			color: #2a2a2a;
		}

		.shell {
			max-width: 860px;
			margin: 0 auto;
			background: #fff;
			border-radius: 12px;
			border: 1px solid #e6e0d8;
			box-shadow: 0 8px 22px rgba(0, 0, 0, 0.08);
			padding: 18px;
		}

		.top {
			display: flex;
			justify-content: space-between;
			align-items: center;
			margin-bottom: 12px;
		}

		.top a {
			text-decoration: none;
			color: #146c63;
		}

		.error {
			margin: 10px 0;
			background: #fbe8e8;
			color: #8f2b2b;
			border-radius: 8px;
			padding: 10px;
		}

		.rating-card {
			border: 1px solid #ece7df;
			border-radius: 10px;
			padding: 12px;
			margin-bottom: 10px;
			background: #fcfbf8;
		}

		.meta {
			color: #666;
			font-size: 13px;
			margin-bottom: 6px;
		}
	</style>
</head>
<body>
	<div class="shell">
		<div class="top">
			<h2>My Ratings</h2>
			<a href="fundi_dashboard.php">⬅️ Back to Dashboard</a>
		</div>

		<?php if ($errorMessage !== ""): ?>
			<div class="error"><?php echo htmlspecialchars($errorMessage); ?></div>
		<?php elseif ($result && mysqli_num_rows($result) > 0): ?>
			<?php while ($row = mysqli_fetch_assoc($result)): ?>
				<div class="rating-card">
					<div class="meta">
						Rating: <?php echo htmlspecialchars((string) ($row['rating'] ?? 'N/A')); ?>
						| Date: <?php echo htmlspecialchars((string) ($row['created_at'] ?? '')); ?>
					</div>
					<div><?php echo htmlspecialchars((string) ($row['review'] ?? '')); ?></div>
				</div>
			<?php endwhile; ?>
		<?php else: ?>
			<p>No ratings yet.</p>
		<?php endif; ?>
	</div>
</body>
</html>

<?php
if ($stmt) {
	$stmt->close();
}
?>