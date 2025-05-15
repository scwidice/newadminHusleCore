<?php
include 'dbConnect.php';
include 'adminSessionHandler.php';

if (!isset($_GET['activityID'])) {
    die("Activity ID not provided.");
}

$activityID = intval($_GET['activityID']);

// get activity name
$activityQuery = $conn->prepare("SELECT activityName FROM Activities WHERE activityID = ?");
$activityQuery->bind_param("i", $activityID);
$activityQuery->execute();
$activityResult = $activityQuery->get_result();
$activityName = $activityResult->fetch_assoc()['activityName'] ?? 'Unknown';

// Get members
$sqlEnrolledMembers = "
    SELECT ab.bookingID, ab.bookingStatus, m.firstName, m.lastName
    FROM ActivityBookings ab
    JOIN Members m ON ab.memberID = m.memberID
    WHERE ab.activityID = ?
";
$stmt = $conn->prepare($sqlEnrolledMembers);
$stmt->bind_param("i", $activityID);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Attendance - Hustle Core</title>
        <link rel="stylesheet" href="HustleCoreStyles.css">
    </head>

<body class="adminBody">
    <!-- Top bar -->
    <header class="adminHeader">
        <a href="adminDashboard.php"> <img class="adminGymLogo" src="images/logo.png" alt="gym logo" > </im> </a>
        <div class="adminUser">
            <span><?php echo htmlspecialchars($adminName); ?></span>
            <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                <button type="submit" name="logout">Log out</button>
            </form>
        </div>
    </header>
    
    <main class="adminMain">
        <!-- Sidebar -->
        <div class="adminSideBar">
            <h4>Admin Panel</h4>
            <p class="adminOtherPage"><a href="adminDashboard.php">Dashboard</a></p>
            <p class="adminCurrentPage"><a href="adminManageActivities.php">Manage Classes & Sessions</a></p>
            <p class="adminOtherPage"><a href="adminMembers.php">Members</a></p>
        </div>  

        <!-- Content Area -->
        <div class="adminContent">
            <div class="adminBreadcrumb">&gt; <a href="adminManageActivities.php">Manage Classes & Sessions</a>&gt; Attendance</a></div>
            <h2 class="adminHeading2">Attendance for: <?= htmlspecialchars($activityName) ?></h2>
        
            <!--Search Form for member name or ID -->
            <form method="get" action="" >
                <input type="hidden" name="activityID" value="<?= $activityID ?>">
                <label for="searchMember">Search member name / ID to enroll:</label>
                <input type="text" name="searchMember" placeholder="Enter name or ID..." value="<?= htmlspecialchars($_GET['searchMember'] ?? '') ?>">
                <button type="submit" class="searchMemberEnrollBtn">Search</button>
            </form>

            <?php
            if (isset($_GET['enrollSuccess'])): ?>
                <p class="success">Member successfully enrolled.</p>
            <?php elseif (isset($_GET['alreadyEnrolled'])): ?>
                <p class="error">Member is already enrolled in this activity.</p>
            <?php elseif (isset($_GET['notFound'])): ?>
                <p class="error">Member not found.</p>
            <?php endif; 

            
            if (!empty($_GET['searchMember'])) {
                $searchTerm = $conn->real_escape_string($_GET['searchMember']);

                // Check if the search term is numeric (ID) or text (name)
                if (is_numeric($searchTerm)) {
                    $searchQuery = $conn->prepare("SELECT memberID, firstName, lastName FROM Members WHERE memberID = ?");
                    $searchQuery->bind_param("i", $searchTerm);
                } else {
                    $searchTerm = "%" . $searchTerm . "%";
                    $searchQuery = $conn->prepare("SELECT memberID, firstName, lastName FROM Members WHERE CONCAT(firstName, ' ', lastName) LIKE ?");
                    $searchQuery->bind_param("s", $searchTerm);
                }

                $searchQuery->execute();
                $searchResult = $searchQuery->get_result();

                if ($searchResult->num_rows > 0) {
                    echo "\n<h3>Search Results:</h3><ul>";
                    while ($member = $searchResult->fetch_assoc()) {
                        echo "<li>" . htmlspecialchars($member['firstName'] . ' ' . $member['lastName']) . " (ID: " . htmlspecialchars($member['memberID']) . ")";
                        echo "<form method='post' action='adminEnrollMemberHandler.php' style='display:inline'>";
                        echo "<input type='hidden' name='memberID' value='" . $member['memberID'] . "'>";
                        echo "<input type='hidden' name='activityID' value='" . $activityID . "'>     ";
                        echo "     <button type='submit' class='adminEnrollBtn'>Enroll to Class</button>";
                        echo "</form></li>";
                    }
                    echo "</ul>";
                } else {
                    echo "<p>No members found matching: " . htmlspecialchars($_GET['searchMember']) . "</p>";
                }
            }
            ?>

            <table border="1" class="attendanceActTable">
                <thead>
                    <tr>
                        <th>Member Name</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['firstName'] . ' ' . $row['lastName']) ?></td>
                            <td><?= htmlspecialchars($row['bookingStatus']) ?></td>
                            <td>
                                <form method="post" action="adminAttendanceStatus.php" class="attendanceFrm">
                                    <input type="hidden" name="bookingID" value="<?= $row['bookingID'] ?>">
                                    <button name="status" value="logged in" class="attendanceBtn" 
                                        <?= $row['bookingStatus'] === 'logged in' || $row['bookingStatus'] === 'logged out' || $row['bookingStatus'] === 'cancelled' ? 'disabled' : '' ?>>Log In</button>
                                </form>
                                <form method="post" action="adminAttendanceStatus.php" class="attendanceFrm">
                                    <input type="hidden" name="bookingID" value="<?= $row['bookingID'] ?>">
                                    <button name="status" value="logged out" class="attendanceBtn" 
                                        <?= $row['bookingStatus'] === 'logged out' || $row['bookingStatus'] === 'cancelled' ? 'disabled' : '' ?>>Log Out</button>
                                </form>
                                <form method="post" action="adminAttendanceStatus.php" class="attendanceFrm">
                                    <input type="hidden" name="bookingID" value="<?= $row['bookingID'] ?>">
                                    <button name="status" value="cancelled" class="attendanceBtn" 
                                        <?= $row['bookingStatus'] === 'cancelled' || $row['bookingStatus'] === 'logged out' ? 'disabled' : '' ?>>Cancel</button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

    </main>
</body>
</html>
