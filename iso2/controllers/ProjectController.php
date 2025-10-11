<?php
require_once __DIR__ . '/../models/Project.php';
require_once __DIR__ . '/../includes/permissions.php';
class ProjectController {
    public function index() {
        requirePermission(PERMISSION_PROJECT_VIEW);
        $projectModel = new Project();
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    $status = isset($_GET['status']) ? $_GET['status'] : '';
        $projects = $projectModel->getAllWithUser($_SESSION['user_id'], $search, $status);
    include __DIR__ . '/../views/projects/index.php';
    }
    public function create() {
        requirePermission(PERMISSION_PROJECT_CREATE);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->store();
            return;
        }
    include __DIR__ . '/../views/projects/create.php';
    }
    private function store() {
    $name = isset($_POST['name']) ? $_POST['name'] : '';
    $description = isset($_POST['description']) ? $_POST['description'] : '';
    $status = isset($_POST['status']) ? $_POST['status'] : '';
    $start_date = isset($_POST['start_date']) ? $_POST['start_date'] : null;
    $end_date = isset($_POST['end_date']) ? $_POST['end_date'] : null;
    $budget = isset($_POST['budget']) ? $_POST['budget'] : null;
        $errors = [];
        if (empty($name)) {
            $errors[] = "Project name is required";
        }
        if (empty($errors)) {
            $projectModel = new Project();
            $projectId = $projectModel->create([
                'name' => $name,
                'description' => $description,
                'status' => $status,
                'start_date' => $start_date,
                'end_date' => $end_date,
                'budget' => $budget,
                'user_id' => $_SESSION['user_id'],
                'created_at' => date('Y-m-d H:i:s')
            ]);
            if ($projectId) {
                header('Location: projects.php?success=Project created successfully');
                exit;
            } else {
                $errors[] = "Failed to create project";
            }
        }
    include __DIR__ . '/../views/projects/create.php';
    }
    public function edit($id) {
        requirePermission(PERMISSION_PROJECT_EDIT);
        $projectModel = new Project();
        $project = $projectModel->find($id);
        if ($project['user_id'] != $_SESSION['user_id'] && !hasPermission(PERMISSION_PROJECT_MANAGE)) {
            die('Access Denied');
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->update($id);
            return;
        }
    include __DIR__ . '/../views/projects/edit.php';
    }
    private function update($id) {
    $name = isset($_POST['name']) ? $_POST['name'] : '';
    $description = isset($_POST['description']) ? $_POST['description'] : '';
    $status = isset($_POST['status']) ? $_POST['status'] : '';
    $start_date = isset($_POST['start_date']) ? $_POST['start_date'] : null;
    $end_date = isset($_POST['end_date']) ? $_POST['end_date'] : null;
    $budget = isset($_POST['budget']) ? $_POST['budget'] : null;
        $projectModel = new Project();
        $updated = $projectModel->update($id, [
            'name' => $name,
            'description' => $description,
            'status' => $status,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'budget' => $budget,
            'updated_at' => date('Y-m-d H:i:s')
        ]);
        if ($updated) {
            header('Location: projects.php?success=Project updated successfully');
            exit;
        } else {
            $errors[] = "Failed to update project";
            include __DIR__ . '/../views/projects/edit.php';
        }
    }
    public function delete($id) {
        requirePermission(PERMISSION_PROJECT_DELETE);
        $projectModel = new Project();
        $project = $projectModel->find($id);
        if ($project['user_id'] != $_SESSION['user_id'] && !hasPermission(PERMISSION_PROJECT_MANAGE)) {
            die('Access Denied');
        }
        $deleted = $projectModel->delete($id);
        if ($deleted) {
            header('Location: projects.php?success=Project deleted successfully');
        } else {
            header('Location: projects.php?error=Failed to delete project');
        }
        exit;
    }
}
