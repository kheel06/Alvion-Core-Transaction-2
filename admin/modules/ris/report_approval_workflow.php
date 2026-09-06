<?php
require_once __DIR__ . '/../../../config/config.php';
requireAuth();
checkRole(['admin', 'super admin']);

$page_title = 'Report Approval Workflow - RIS';

// Handle actions
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'approve':
                    $stmt = $db->prepare("UPDATE radiology_reports SET status = 'approved', approved_at = NOW(), approved_by = ? WHERE id = ?");
                    $stmt->execute([$_SESSION['user_id'], $_POST['report_id']]);
                    $message = 'Report approved successfully';
                    $messageType = 'success';
                    break;
                
                case 'send_back':
                    $stmt = $db->prepare("UPDATE radiology_reports SET status = 'draft', sent_back_at = NOW(), sent_back_by = ? WHERE id = ?");
                    $stmt->execute([$_SESSION['user_id'], $_POST['report_id']]);
                    $message = 'Report sent back for revision';
                    $messageType = 'success';
                    break;
            }
        }
    } catch (PDOException $e) {
        $message = 'Error: ' . $e->getMessage();
        $messageType = 'error';
    }
}

// Get active tab
$activeTab = $_GET['tab'] ?? 'draft';

// Fetch reports
$draftReports = [];
$awaitingReview = [];
$approvedReports = [];

try {
    $checkTable = $db->query("SHOW TABLES LIKE 'radiology_reports'");
    
    if ($checkTable && $checkTable->rowCount() > 0) {
        // Draft Reports
        $stmt = $db->query("SELECT * FROM radiology_reports WHERE status = 'draft' ORDER BY created_at DESC");
        $draftReports = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Awaiting Review
        $stmt = $db->query("SELECT * FROM radiology_reports WHERE status = 'awaiting_review' ORDER BY created_at DESC");
        $awaitingReview = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Approved
        $stmt = $db->query("SELECT * FROM radiology_reports WHERE status = 'approved' ORDER BY approved_at DESC LIMIT 50");
        $approvedReports = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    error_log('RIS Report Approval error: ' . $e->getMessage());
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
                    <li><span class="text-gray-700 dark:text-gray-200">Report Approval Workflow</span></li>
                </ol>
            </nav>
            <h1 class="text-2xl font-semibold tracking-tight text-gray-900 dark:text-white">
                Report Approval Workflow
            </h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Manage radiology report approval workflow with draft, review, and approval stages.
            </p>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="rounded-md p-4 <?php echo $messageType === 'success' ? 'bg-green-50 dark:bg-green-900/20 text-green-800 dark:text-green-200' : 'bg-red-50 dark:bg-red-900/20 text-red-800 dark:text-red-200'; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <!-- Tabs -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="border-b border-gray-200 dark:border-gray-700">
            <nav class="flex -mb-px">
                <a href="?tab=draft" 
                   class="px-6 py-4 text-sm font-medium border-b-2 <?php echo $activeTab === 'draft' ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?>">
                    Draft Reports (<?php echo count($draftReports); ?>)
                </a>
                <a href="?tab=awaiting" 
                   class="px-6 py-4 text-sm font-medium border-b-2 <?php echo $activeTab === 'awaiting' ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?>">
                    Awaiting Review (<?php echo count($awaitingReview); ?>)
                </a>
                <a href="?tab=approved" 
                   class="px-6 py-4 text-sm font-medium border-b-2 <?php echo $activeTab === 'approved' ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?>">
                    Approved (<?php echo count($approvedReports); ?>)
                </a>
            </nav>
        </div>

        <!-- Tab Content -->
        <div class="p-6">
            <?php
            $reports = [];
            if ($activeTab === 'draft') {
                $reports = $draftReports;
            } elseif ($activeTab === 'awaiting') {
                $reports = $awaitingReview;
            } else {
                $reports = $approvedReports;
            }
            ?>
            
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Patient ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Exam Type</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Radiologist</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Findings Summary</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Status Timestamp</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        <?php if (empty($reports)): ?>
                            <tr>
                                <td colspan="6" class="px-6 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                                    No reports found
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($reports as $report): ?>
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                    <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">
                                        <?php echo htmlspecialchars($report['patient_id'] ?? 'N/A'); ?>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                                        <?php echo htmlspecialchars($report['exam_type'] ?? 'N/A'); ?>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                                        <?php echo htmlspecialchars($report['radiologist'] ?? 'N/A'); ?>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400 max-w-xs truncate">
                                        <?php echo htmlspecialchars($report['findings_summary'] ?? 'N/A'); ?>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                                        <?php 
                                        if ($activeTab === 'approved' && $report['approved_at']) {
                                            echo date('M d, Y H:i', strtotime($report['approved_at']));
                                        } elseif ($activeTab === 'awaiting' && $report['created_at']) {
                                            echo date('M d, Y H:i', strtotime($report['created_at']));
                                        } else {
                                            echo date('M d, Y H:i', strtotime($report['created_at'] ?? 'now'));
                                        }
                                        ?>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex gap-2">
                                            <?php if ($activeTab === 'awaiting'): ?>
                                                <button onclick="reviewReport(<?php echo htmlspecialchars($report['id']); ?>)" 
                                                        class="text-primary-600 hover:text-primary-700 text-sm font-medium flex items-center gap-1">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                                    Review
                                                </button>
                                                <form method="POST" class="inline" onsubmit="return confirm('Send this report back for revision?')">
                                                    <input type="hidden" name="action" value="send_back">
                                                    <input type="hidden" name="report_id" value="<?php echo htmlspecialchars($report['id']); ?>">
                                                    <button type="submit" class="text-amber-600 hover:text-amber-700 text-sm font-medium flex items-center gap-1">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 4 23 10 17 10"></polyline><polyline points="1 20 1 14 7 14"></polyline><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path></svg>
                                                        Send Back
                                                    </button>
                                                </form>
                                                <form method="POST" class="inline" onsubmit="return confirm('Approve this report?')">
                                                    <input type="hidden" name="action" value="approve">
                                                    <input type="hidden" name="report_id" value="<?php echo htmlspecialchars($report['id']); ?>">
                                                    <button type="submit" class="text-green-600 hover:text-green-700 text-sm font-medium flex items-center gap-1">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                                        Approve
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <button onclick="viewReport(<?php echo htmlspecialchars($report['id']); ?>)" 
                                                        class="inline-flex items-center gap-1.5 text-primary-600 hover:text-primary-700 text-sm font-medium" title="View"><?php echo icon_view('w-4 h-4'); ?>View</button>
                                            <?php endif; ?>
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
</div>

<!-- Review Modal -->
<div id="reviewModal" class="hidden fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="fixed inset-0 bg-black bg-opacity-50" onclick="closeReviewModal()"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-3xl w-full p-6 max-h-[90vh] overflow-y-auto">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Review Report</h3>
            <div id="reviewContent" class="space-y-4"></div>
            <div class="flex gap-3 justify-end mt-6">
                <button onclick="closeReviewModal()" 
                        class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>

<script>
let reportsData = <?php echo json_encode($reports); ?>;

function reviewReport(reportId) {
    const report = reportsData.find(r => r.id == reportId);
    if (!report) return;
    
    document.getElementById('reviewContent').innerHTML = `
        <div class="space-y-4">
            <div><strong>Patient ID:</strong> ${report.patient_id || 'N/A'}</div>
            <div><strong>Exam Type:</strong> ${report.exam_type || 'N/A'}</div>
            <div><strong>Radiologist:</strong> ${report.radiologist || 'N/A'}</div>
            <div><strong>Findings Summary:</strong><br>${report.findings_summary || 'N/A'}</div>
            <div><strong>Full Report:</strong><br><div class="bg-gray-50 dark:bg-gray-900 rounded p-4 mt-2">${report.full_report || 'N/A'}</div></div>
        </div>
    `;
    document.getElementById('reviewModal').classList.remove('hidden');
}

function closeReviewModal() {
    document.getElementById('reviewModal').classList.add('hidden');
}

function viewReport(reportId) {
    reviewReport(reportId);
}
</script>

<?php include __DIR__ . '/../../../includes/footer.php'; ?>

