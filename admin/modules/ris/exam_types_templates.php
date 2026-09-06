<?php
require_once __DIR__ . '/../../../config/config.php';
requireAuth();
checkRole(['admin', 'super admin']);

$page_title = 'Exam Types & Templates - RIS';

// Handle form submissions
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'save_exam':
                    if ($_POST['exam_id']) {
                        $stmt = $db->prepare("UPDATE exam_types SET exam_code = ?, exam_name = ?, modality_type = ?, body_part = ?, duration_minutes = ?, prep_instructions = ?, report_template = ? WHERE id = ?");
                        $stmt->execute([
                            $_POST['exam_code'],
                            $_POST['exam_name'],
                            $_POST['modality_type'],
                            $_POST['body_part'],
                            $_POST['duration_minutes'],
                            $_POST['prep_instructions'],
                            $_POST['report_template'],
                            $_POST['exam_id']
                        ]);
                        $message = 'Exam template updated successfully';
                    } else {
                        $stmt = $db->prepare("INSERT INTO exam_types (exam_code, exam_name, modality_type, body_part, duration_minutes, prep_instructions, report_template) VALUES (?, ?, ?, ?, ?, ?, ?)");
                        $stmt->execute([
                            $_POST['exam_code'],
                            $_POST['exam_name'],
                            $_POST['modality_type'],
                            $_POST['body_part'],
                            $_POST['duration_minutes'],
                            $_POST['prep_instructions'],
                            $_POST['report_template']
                        ]);
                        $message = 'Exam template created successfully';
                    }
                    $messageType = 'success';
                    break;
                
                case 'delete_exam':
                    $stmt = $db->prepare("DELETE FROM exam_types WHERE id = ?");
                    $stmt->execute([$_POST['exam_id']]);
                    $message = 'Exam template deleted successfully';
                    $messageType = 'success';
                    break;
            }
        }
    } catch (PDOException $e) {
        $message = 'Error: ' . $e->getMessage();
        $messageType = 'error';
    }
}

// Fetch data
$search = $_GET['search'] ?? '';
$exams = [];

try {
    $checkTable = $db->query("SHOW TABLES LIKE 'exam_types'");
    
    if ($checkTable && $checkTable->rowCount() > 0) {
        $sql = "SELECT * FROM exam_types WHERE 1=1";
        $params = [];
        
        if ($search) {
            $sql .= " AND (exam_code LIKE ? OR exam_name LIKE ? OR body_part LIKE ?)";
            $searchParam = "%$search%";
            $params[] = $searchParam;
            $params[] = $searchParam;
            $params[] = $searchParam;
        }
        
        $sql .= " ORDER BY exam_name";
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $exams = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    error_log('RIS Exam Types error: ' . $e->getMessage());
}

include __DIR__ . '/../../../includes/header.php';
?>

<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <nav class="flex text-sm text-gray-500 dark:text-gray-400 mb-1" aria-label="Breadcrumb">
                <ol class="inline-flex items-center space-x-1 md:space-x-2">
                    <li><a href="<?php echo BASE_URL; ?>/admin/admin-dashboard.php" class="hover:text-primary-600">Dashboard</a></li>
                    <li><span class="mx-2">/</span></li>
                    <li><span class="text-gray-700 dark:text-gray-200">Exam Types & Templates</span></li>
                </ol>
            </nav>
            <h1 class="text-2xl font-semibold tracking-tight text-gray-900 dark:text-white">
                Exam Types & Templates
            </h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Manage radiology exam templates with report templates and prep instructions.
            </p>
        </div>
        <button onclick="openExamModal()" class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 font-medium flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
            Create Template
        </button>
    </div>

    <?php if ($message): ?>
        <div class="rounded-md p-4 <?php echo $messageType === 'success' ? 'bg-green-50 dark:bg-green-900/20 text-green-800 dark:text-green-200' : 'bg-red-50 dark:bg-red-900/20 text-red-800 dark:text-red-200'; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <!-- Search -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
        <form method="GET" class="flex gap-4">
            <div class="flex-1">
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                       placeholder="Search by exam code, name, or body part..."
                       class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
            </div>
            <button type="submit" class="px-6 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 font-medium flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><path d="M21 21l-4.35-4.35"></path></svg>
                Search
            </button>
            <?php if ($search): ?>
                <a href="?" class="px-6 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 font-medium flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                    Clear
                </a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Exam Types Table -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Exam Code</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Exam Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Modality Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Body Part</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Duration</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    <?php if (empty($exams)): ?>
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                                No exam templates found. <button onclick="openExamModal()" class="text-primary-600 hover:text-primary-700">Create your first template</button>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($exams as $exam): ?>
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">
                                    <?php echo htmlspecialchars($exam['exam_code'] ?? 'N/A'); ?>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">
                                    <?php echo htmlspecialchars($exam['exam_name'] ?? 'N/A'); ?>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                                    <?php echo htmlspecialchars($exam['modality_type'] ?? 'N/A'); ?>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                                    <?php echo htmlspecialchars($exam['body_part'] ?? 'N/A'); ?>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                                    <?php echo htmlspecialchars($exam['duration_minutes'] ?? 0); ?> min
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex gap-2 items-center">
                                        <button onclick="viewTemplate(<?php echo htmlspecialchars($exam['id']); ?>)" 
                                                class="inline-flex items-center gap-1.5 text-primary-600 hover:text-primary-700 text-sm font-medium" title="View Template"><?php echo icon_view('w-4 h-4'); ?>View Template</button>
                                        <button onclick="editExam(<?php echo htmlspecialchars($exam['id']); ?>)" 
                                                class="inline-flex items-center gap-1.5 text-primary-600 hover:text-primary-700 text-sm font-medium" title="Edit"><?php echo icon_edit('w-4 h-4'); ?>Edit</button>
                                        <form method="POST" class="inline" onsubmit="return confirm('Delete this exam template?')">
                                            <input type="hidden" name="action" value="delete_exam">
                                            <input type="hidden" name="exam_id" value="<?php echo htmlspecialchars($exam['id']); ?>">
                                            <button type="submit" class="inline-flex items-center gap-1.5 text-red-600 hover:text-red-700 text-sm font-medium" title="Delete"><?php echo icon_delete('w-4 h-4'); ?>Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Exam Modal -->
<div id="examModal" class="hidden fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="fixed inset-0 bg-black bg-opacity-50" onclick="closeExamModal()"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-4xl w-full p-6 max-h-[90vh] overflow-y-auto">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4" id="examModalTitle">Create Exam Template</h3>
            <form method="POST" id="examForm">
                <input type="hidden" name="action" value="save_exam">
                <input type="hidden" name="exam_id" id="examId">
                
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Exam Code *</label>
                        <input type="text" name="exam_code" id="examCode" required
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Modality Type *</label>
                        <select name="modality_type" id="examModality" required
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                            <option value="">Select Modality</option>
                            <option value="CT">CT</option>
                            <option value="MRI">MRI</option>
                            <option value="X-Ray">X-Ray</option>
                            <option value="Ultrasound">Ultrasound</option>
                            <option value="Mammography">Mammography</option>
                        </select>
                    </div>
                    
                    <div class="col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Exam Name *</label>
                        <input type="text" name="exam_name" id="examName" required
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Body Part *</label>
                        <input type="text" name="body_part" id="examBodyPart" required
                               placeholder="e.g., Chest, Abdomen, Head"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Duration (Minutes) *</label>
                        <input type="number" name="duration_minutes" id="examDuration" required min="1"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    </div>
                    
                    <div class="col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Prep Instructions</label>
                        <textarea name="prep_instructions" id="examPrep" rows="3"
                                  placeholder="e.g., NPO 8 hours, Remove jewelry, etc."
                                  class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"></textarea>
                    </div>
                    
                    <div class="col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Report Template *</label>
                        <textarea name="report_template" id="examTemplate" rows="10" required
                                  placeholder="Enter report template with placeholders..."
                                  class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white font-mono text-sm"></textarea>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            Use placeholders like [PATIENT_NAME], [EXAM_DATE], [FINDINGS], etc.
                        </p>
                    </div>
                </div>
                
                <div class="flex gap-3 justify-end">
                    <button type="button" onclick="closeExamModal()" 
                            class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700">
                        Save Template
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View Template Modal -->
<div id="viewTemplateModal" class="hidden fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="fixed inset-0 bg-black bg-opacity-50" onclick="closeViewTemplateModal()"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-3xl w-full p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Report Template</h3>
            <div id="templateContent" class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4 font-mono text-sm whitespace-pre-wrap text-gray-900 dark:text-gray-100"></div>
            <div class="flex justify-end mt-4">
                <button onclick="closeViewTemplateModal()" 
                        class="px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>

<script>
let examData = <?php echo json_encode($exams); ?>;

function openExamModal() {
    document.getElementById('examModal').classList.remove('hidden');
    document.getElementById('examModalTitle').textContent = 'Create Exam Template';
    document.getElementById('examId').value = '';
    document.getElementById('examForm').reset();
}

function closeExamModal() {
    document.getElementById('examModal').classList.add('hidden');
}

function editExam(examId) {
    const exam = examData.find(e => e.id == examId);
    if (!exam) return;
    
    document.getElementById('examModal').classList.remove('hidden');
    document.getElementById('examModalTitle').textContent = 'Edit Exam Template';
    document.getElementById('examId').value = exam.id;
    document.getElementById('examCode').value = exam.exam_code || '';
    document.getElementById('examName').value = exam.exam_name || '';
    document.getElementById('examModality').value = exam.modality_type || '';
    document.getElementById('examBodyPart').value = exam.body_part || '';
    document.getElementById('examDuration').value = exam.duration_minutes || '';
    document.getElementById('examPrep').value = exam.prep_instructions || '';
    document.getElementById('examTemplate').value = exam.report_template || '';
}

function viewTemplate(examId) {
    const exam = examData.find(e => e.id == examId);
    if (!exam) return;
    
    document.getElementById('templateContent').textContent = exam.report_template || 'No template available';
    document.getElementById('viewTemplateModal').classList.remove('hidden');
}

function closeViewTemplateModal() {
    document.getElementById('viewTemplateModal').classList.add('hidden');
}
</script>

<?php include __DIR__ . '/../../../includes/footer.php'; ?>

