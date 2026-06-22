 <?php
include "session.php";
include "db.php";

// allow only admin
if (!is_admin_session()) {
    header("Location: login.php");
    exit();
}

$sql = "SELECT * FROM users";
$result = mysqli_query($conn, $sql);

if (!$result) {
    die("Query failed: " . mysqli_error($conn));
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>View Users</title>
    <style>
        body {
            font-family: Arial, sans-serif;
        }

        table {
            border-collapse: collapse;
            width: 90%;
            margin: 20px auto;
        }

        th, td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: center;
        }

        th {
            background-color: #333;
            color: white;
        }

        .pending { color: orange; }
        .approved { color: green; }
        .rejected { color: red; }
    </style>
</head>
<body>

<h2 style="text-align:center;">Admin - View Users</h2>

<table>
    <tr>
        <th>ID</th>
        <th>Full Name</th>
        <th>Email</th>
        <th>Role</th>
        <th>Status</th>
        <th>Action</th>
    </tr>

    <?php if (mysqli_num_rows($result) > 0) { ?>
        <?php while($row = mysqli_fetch_assoc($result)) { ?>
            <tr>
                <td><?php echo $row['id']; ?></td>
                <td><?php echo $row['fullname']; ?></td>
                <td><?php echo $row['email']; ?></td>
                <td><?php echo $row['role']; ?></td>
                <td class="<?php echo $row['status']; ?>">
                    <?php echo $row['status']; ?>
                </td>
                <td>
                    <form method="POST" action="update_user_status.php" style="display:inline;">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
                        <input type="hidden" name="id" value="<?php echo (int) $row['id']; ?>">
                        <input type="hidden" name="status" value="approved">
                        <button type="submit">Approve</button>
                    </form>
                    <form method="POST" action="update_user_status.php" style="display:inline; margin-left:8px;">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
                        <input type="hidden" name="id" value="<?php echo (int) $row['id']; ?>">
                        <input type="hidden" name="status" value="rejected">
                        <button type="submit">Reject</button>
                    </form>
                </td>
            </tr>
        <?php } ?>
    <?php } else { ?>
        <tr>
            <td colspan="6">No users found</td>
        </tr>
    <?php } ?>

</table>

</body>
</html>