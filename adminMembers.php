<?php

include 'dbConnect.php';
include 'adminSessionHandler.php'; 

// Fetch memberships for dropdowns
$memberships = $conn->query("SELECT membershipID, membershipType FROM Memberships");

// Fetch members with membership info
$sqlFetchMembers = "SELECT m.memberID, m.firstName, m.lastName, m.email, ms.membershipType, m.membershipID FROM Members m LEFT JOIN Memberships ms ON m.membershipID = ms.membershipID";
$result = $conn->query($sqlFetchMembers);

// add member submission
if (isset($_POST['submitMember'])) {
    $firstName = trim($_POST['firstName']);
    $lastName = trim($_POST['lastName']);
    $email = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $membershipID = (isset($_POST['membershipID']) && $_POST['membershipID'] !== '') ? intval($_POST['membershipID']) : null;

    $stmt = $conn->prepare("INSERT INTO Members (firstName, lastName, email, memberPassword, membershipID) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssi", $firstName, $lastName, $email, $password, $membershipID);
    
    if ($stmt->execute()) {
        // Redirect to refresh the page and display the updated table
        header("Location: adminMembers.php");
        exit();
    } else {
        echo "<script>alert('Error: " . $conn->error . "');</script>";
    }
    $stmt->close();
}

// Handle editing/updating member details
if (isset($_POST['updateMemberSubmit'])) {
    $memberIdToUpdate = $_POST['memberID'];
    $newFirstName = $_POST['editFirstName'];
    $newLastName = $_POST['editLastName'];
    $newEmail = $_POST['editEmail'];
    $newMembershipID = isset($_POST['editMembershipID']) ? intval($_POST['editMembershipID']) : null;

    $sqlUpdateMember = "UPDATE Members SET firstName = ?, lastName = ?, email = ?, membershipID = ? WHERE memberID = ?";
    $stmt = $conn->prepare($sqlUpdateMember);
    $stmt->bind_param("sssii", $newFirstName, $newLastName, $newEmail, $newMembershipID, $memberIdToUpdate);

    if ($stmt->execute()) {
        echo "Activity added successfully!";
    } else {
       // echo "<script>alert('Error updating activity: " . $stmt->error . "');</script>";
    }
    $stmt->close();
}

// Handle deleting a member
if (isset($_POST['deleteMemberSubmit'])) {
    $memberIdToDelete = $_POST['memberId'];

    // Delete related rows in activitybookings
    $sqlDeleteBookings = "DELETE FROM activitybookings WHERE memberID = ?";
    $stmt = $conn->prepare($sqlDeleteBookings);
    $stmt->bind_param("i", $memberIdToDelete);

    if ($stmt->execute()) {
        $sqlDeleteMember = "DELETE FROM Members WHERE memberID = ?";
        $stmt = $conn->prepare($sqlDeleteMember);
        $stmt->bind_param("i", $memberIdToDelete);

        if ($stmt->execute()) {
            // Redirect to refresh the page and display the updated table
            header("Location: adminMembers.php");
            exit();
        } else {
            echo "<script>alert('Error deleting member: " . $stmt->error . "');</script>";
        }
    } else {
        echo "<script>alert('Error deleting related bookings: " . $stmt->error . "');</script>";
    }

    $stmt->close();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Members - Hustle Core</title>
    <link rel="stylesheet" href="HustleCoreStyles.css">

</head>


<body class="adminBody">
    <!-- Top bar -->
    <header class="adminHeader">
        <a href="adminDashboard.php"> <img class="adminGymLogo" src="images/logo.png" alt="gym logo" > </img> </a>
        
        <div class="adminUser">
            <span><?php echo htmlspecialchars($adminName); ?></span>
            <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                <button type="submit" name="logout">Log out</button>
            </form>
        </div>
    </header>

    <!-- Main section -->
    <main class="adminMain">
    
        <!-- Sidebar -->
        <div class="adminSideBar">
            <h4>Admin Panel</h4>
            <p class="adminOtherPage"><a href="adminDashboard.php">Dashboard</a></p>
            <p class="adminOtherPage"><a href="adminManageActivities.php">Manage Classes & Sessions</a></p>
            <p class="adminCurrentPage"><a href="adminMembers.php">Members</a></p>
        </div>

        <!-- Content Area -->
        <div class="adminContent">
            <div class="adminBreadcrumb">&gt; Members</div>
            <h2>Members</h2>

            <button id="addMemberBtn">Add Member</button>

        <!-- Add Member Modal -->
        <div id="addMemberModal" class="adminModal">
            <div class="adminModalContent">
                <span id="addMemberCloseBtn" class="adminCloseBtn">&times;</span>
                <h3>Add Member</h3>
                <form action="adminMembers.php" method="POST">

                    <div class="adminModalLabelGrp">
                        <label for="firstName">First Name:</label>
                        <input type="text" name="firstName" required>
                    </div>
                    <div class="adminModalLabelGrp">
                        <label for="lastName">Last Name:</label>
                        <input type="text" name="lastName" required>
                    </div>
                    <div class="adminModalLabelGrp">
                        <label for="email">E-mail:</label>
                        <input type="text" name="email" required>
                    </div>
                    <div class="adminModalLabelGrp">
                        <label for="password">Password:</label>
                        <input type="text" name="password" required>
                    </div>
                    <div class="adminModalLabelGrp">
                        <label for="membershipID">Membership:</label>
                        <select name="membershipID">
                                <option value="">-- Select Membership --</option>
                                <?php while ($row = $memberships->fetch_assoc()): ?>
                                    <option value="<?= $row['membershipID'] ?>">
                                        <?= htmlspecialchars($row['membershipType']) ?>
                                    </option>
                                <?php endwhile; ?>
                        </select>
                    </div>
                    <button class="adminSubmitMemBtn" type="submit" name="submitMember">Add Member</button>
                
                </form>
            </div>
        </div>


            <!-- Members Table -->
            <table border="1" class="membersTable">
                <thead>
                    <tr>
                        <th>Member ID</th>
                        <th>First Name</th>
                        <th>Last Name</th>
                        <th>Email</th>
                        <th>Membership</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['memberID']) ?></td>
                                <td><?= htmlspecialchars($row['firstName']) ?></td>
                                <td><?= htmlspecialchars($row['lastName']) ?></td>
                                <td><?= htmlspecialchars($row['email']) ?></td>
                                <td><?= htmlspecialchars($row['membershipType'] ?? 'None') ?></td>
                                <td>
                                    <!-- Edit Button -->
                                    <button 
                                        type="button" class="adminEditBtn" 
                                        data-member-id="<?= htmlspecialchars($row['memberID']) ?>" 
                                        data-first-name="<?= htmlspecialchars($row['firstName']) ?>" 
                                        data-last-name="<?= htmlspecialchars($row['lastName']) ?>" 
                                        data-email="<?= htmlspecialchars($row['email']) ?>" 
                                        data-membership-id="<?= htmlspecialchars($row['membershipID']) ?>">
                                        Edit
                                    </button>

                                    <!-- Delete Button -->
                                    <button 
                                        type="button" class="adminDeleteBtn" 
                                        data-member-id="<?= htmlspecialchars($row['memberID']) ?>">
                                        Delete
                                    </button>

                                    <!-- View Classes Button -->
                                    <form method="get" action="adminViewMemberClasses.php" style="display:inline">
                                        <input type="hidden" name="memberID" value="<?= $row['memberID'] ?>">
                                        <button type="submit" class="viewClassesBtn">View Enrolled Classes</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6">No members found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            
            <!-- edit member modal -->
            <div id="editMemberModal" class="adminModal">
                <div class="adminModalEditContent">
                    <span id="editMemberCloseBtn">&times;</span>
                    <h3>Edit Member</h3>
                    <form action="adminMembers.php" method="POST">
                        <input type="hidden" name="memberID" id="editMemberID">
                        <div class="adminModalLabelGrp">
                            <label for="editFirstName">First Name:</label>
                            <input type="text" name="editFirstName" id="editFirstName" required></div>

                        <div class="adminModalLabelGrp">
                            <label for="editLastName">Last Name:</label>
                            <input type="text" name="editLastName" id="editLastName" required></div>

                        <div class="adminModalLabelGrp">
                            <label for="editEmail">E-mail:</label>
                            <input type="text" name="editEmail" id="editEmail" required></div>

                        <div class="adminModalLabelGrp">
                            <label for="editMembershipID">Membership:</label>
                            <select name="editMembershipID" id="editMembershipID" required>
                                <option value="">-- Select Membership --</option>
                                <?php while ($row = $memberships->fetch_assoc()): ?>
                                    <option value="<?= $row['membershipID'] ?>">
                                        <?= htmlspecialchars($row['membershipType']) ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <button class="adminSubmitMemEditBtn" type="submit" name="updateMemberSubmit">Update Member</button>
                    </form>
                </div>
            </div>

            <!-- delete member modal -->
            <div id="deleteMemberModal" class="adminModal">
                <div class="adminModalDeleteContent">
                <span class="adminCloseBtn" id="deleteMemberCloseBtn">&times;</span>
                    <h3>Confirm Delete</h3>
                    <p>Are you sure you want to delete this member?</p>
                    <form method="post" action="adminMembers.php">
                        <input type="hidden" name="memberId" id="deleteMemberId">
                        <button type="submit" name="deleteMemberSubmit" class="adminSubmitDeleteBtn">Yes, Delete</button>
                    </form>   
                </div>
            </div>            

        </div>
    </main>
    <script src="adminScript.js"></script>
</body>
</html>