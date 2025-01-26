<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bootstrap 5 Tabs</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-4">
    <!-- Tabs Navigation -->
    <ul class="nav nav-tabs" id="myTab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="status-tab" data-bs-toggle="tab" data-bs-target="#status" type="button" role="tab" aria-controls="status" aria-selected="true">Update Status</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="reassign-tab" data-bs-toggle="tab" data-bs-target="#reassign" type="button" role="tab" aria-controls="reassign" aria-selected="false">Reassign Course</button>
        </li>
    </ul>

    <!-- Tabs Content -->
    <div class="tab-content mt-3" id="myTabContent">
        <!-- Status Update Tab -->
        <div class="tab-pane fade show active" id="status" role="tabpanel">
            <form method="POST" class="mt-3">
                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                
                <div class="mb-3">
                    <label class="form-label">Current Status</label>
                    <select name="status" class="form-select" required>
                        <option value="start" <?= $current_assignment['status'] === 'start' ? 'selected' : '' ?>>Start</option>
                        <option value="in-progress" <?= $current_assignment['status'] === 'in-progress' ? 'selected' : '' ?>>In Progress</option>
                        <option value="ended" <?= $current_assignment['status'] === 'ended' ? 'selected' : '' ?>>Ended</option>
                    </select>
                </div>

                <button type="submit" name="update_status" class="btn btn-primary">
                    Update Status
                </button>
            </form>
        </div>

        <!-- Reassign Course Tab -->
        <div class="tab-pane fade" id="reassign" role="tabpanel">
            <div class="mt-3 <?= $has_active_courses ? 'disabled-form' : '' ?>">
                <?php if ($has_active_courses): ?>
                    <div class="alert alert-warning">
                        Cannot reassign - student has active courses
                    </div>
                <?php endif; ?>

                <form method="POST" <?= $has_active_courses ? 'onsubmit="return false;"' : '' ?>>
                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                    
                    <div class="mb-3">
                        <label class="form-label">Select New Course</label>
                        <select name="new_course_id" class="form-select" required 
                            <?= $has_active_courses ? 'disabled' : '' ?>>
                            <?php foreach ($available_courses as $course): ?>
                                <option value="<?= $course['course_id'] ?>">
                                    <?= htmlspecialchars($course['course_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <button type="submit" name="reassign_course" class="btn btn-primary"
                        <?= $has_active_courses ? 'disabled' : '' ?>>
                        Reassign Course
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Tabs Content -->
    <div class="tab-content mt-3" id="myTabContent">
        <div class="tab-pane fade show active" id="home" role="tabpanel" aria-labelledby="home-tab">
            <h3>HOME</h3>
            <p>Some content.</p>
        </div>
        <div class="tab-pane fade" id="menu1" role="tabpanel" aria-labelledby="menu1-tab">
            <h3>Menu 1</h3>
            <p>Some content in menu 1.</p>
        </div>
        <div class="tab-pane fade" id="menu2" role="tabpanel" aria-labelledby="menu2-tab">
            <h3>Menu 2</h3>
            <p>Some content in menu 2.</p>
        </div>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
