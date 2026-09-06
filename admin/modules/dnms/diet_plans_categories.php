<?php
require_once __DIR__ . '/../../../config/config.php';
requireAuth();
checkRole(['admin', 'super admin']);

$page_title = 'Diet Plans & Categories - PMS';

// Handle form submissions
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'create_plan':
                    $stmt = $db->prepare("INSERT INTO diet_plans (plan_name, category, calorie_range_low, calorie_range_high, allowed_food_groups, macro_carbs, macro_protein, macro_fats, restrictions) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([
                        $_POST['plan_name'],
                        $_POST['category'],
                        $_POST['calorie_range_low'],
                        $_POST['calorie_range_high'],
                        $_POST['allowed_food_groups'],
                        $_POST['macro_carbs'] ?? null,
                        $_POST['macro_protein'] ?? null,
                        $_POST['macro_fats'] ?? null,
                        $_POST['restrictions'] ?? null
                    ]);
                    $message = 'Diet plan created successfully';
                    $messageType = 'success';
                    break;
                
                case 'update_plan':
                    $stmt = $db->prepare("UPDATE diet_plans SET plan_name = ?, category = ?, calorie_range_low = ?, calorie_range_high = ?, allowed_food_groups = ?, macro_carbs = ?, macro_protein = ?, macro_fats = ?, restrictions = ? WHERE id = ?");
                    $stmt->execute([
                        $_POST['plan_name'],
                        $_POST['category'],
                        $_POST['calorie_range_low'],
                        $_POST['calorie_range_high'],
                        $_POST['allowed_food_groups'],
                        $_POST['macro_carbs'] ?? null,
                        $_POST['macro_protein'] ?? null,
                        $_POST['macro_fats'] ?? null,
                        $_POST['restrictions'] ?? null,
                        $_POST['plan_id']
                    ]);
                    $message = 'Diet plan updated successfully';
                    $messageType = 'success';
                    break;
                
                case 'delete_plan':
                    $stmt = $db->prepare("DELETE FROM diet_plans WHERE id = ?");
                    $stmt->execute([$_POST['plan_id']]);
                    $message = 'Diet plan deleted successfully';
                    $messageType = 'success';
                    break;
            }
        }
    } catch (PDOException $e) {
        $message = 'Error: ' . $e->getMessage();
        $messageType = 'error';
    }
}

// Fetch data with filters
$search = $_GET['search'] ?? '';
$categoryFilter = $_GET['category'] ?? '';

$dietPlans = [];
$categories = [];

try {
    $checkTable = $db->query("SHOW TABLES LIKE 'diet_plans'");
    
    if ($checkTable && $checkTable->rowCount() > 0) {
        // Get unique categories
        $stmt = $db->query("SELECT DISTINCT category FROM diet_plans WHERE category IS NOT NULL AND category != '' ORDER BY category");
        $categories = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Get patient counts
        $checkAssignments = $db->query("SHOW TABLES LIKE 'patient_diet_assignments'");
        $patientCounts = [];
        if ($checkAssignments && $checkAssignments->rowCount() > 0) {
            $stmt = $db->query("
                SELECT diet_plan_id, COUNT(*) as patient_count
                FROM patient_diet_assignments
                WHERE status = 'active'
                GROUP BY diet_plan_id
            ");
            $counts = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($counts as $count) {
                $patientCounts[$count['diet_plan_id']] = $count['patient_count'];
            }
        }
        
        // Build query with filters
        $sql = "SELECT * FROM diet_plans WHERE 1=1";
        $params = [];
        
        if ($search) {
            $sql .= " AND (plan_name LIKE ? OR category LIKE ?)";
            $searchParam = "%$search%";
            $params[] = $searchParam;
            $params[] = $searchParam;
        }
        
        if ($categoryFilter) {
            $sql .= " AND category = ?";
            $params[] = $categoryFilter;
        }
        
        $sql .= " ORDER BY category, plan_name";
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $dietPlans = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Add patient counts
        foreach ($dietPlans as &$plan) {
            $plan['patient_count'] = $patientCounts[$plan['id']] ?? 0;
        }
    }
} catch (PDOException $e) {
    error_log('DNMS Diet Plans error: ' . $e->getMessage());
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
                    <li><span class="text-gray-700 dark:text-gray-200">Diet Plans & Categories</span></li>
                </ol>
            </nav>
            <h1 class="text-2xl font-semibold tracking-tight text-gray-900 dark:text-white">
                Diet Plans & Categories
            </h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Manage categorized diet plans with macro-nutrient targets, food groups, and restrictions.
            </p>
        </div>
        <button onclick="openPlanModal()" class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 font-medium flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
            Create New Plan
        </button>
    </div>

    <?php if ($message): ?>
        <div class="rounded-md p-4 <?php echo $messageType === 'success' ? 'bg-green-50 dark:bg-green-900/20 text-green-800 dark:text-green-200' : 'bg-red-50 dark:bg-red-900/20 text-red-800 dark:text-red-200'; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <!-- Filters -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
        <form method="GET" class="flex flex-wrap gap-4">
            <div class="flex-1 min-w-[200px]">
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                       placeholder="Search plans..."
                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
            </div>
            <div>
                <select name="category" onchange="this.form.submit()" 
                        class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo $categoryFilter === $cat ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>
    </div>

    <!-- Diet Plans Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php if (empty($dietPlans)): ?>
            <div class="col-span-full bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-8 text-center">
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    No diet plans found. <button onclick="openPlanModal()" class="text-primary-600 hover:text-primary-700">Create your first plan</button>
                </p>
            </div>
        <?php else: ?>
            <?php 
            $currentCategory = '';
            foreach ($dietPlans as $plan): 
                if ($currentCategory !== $plan['category']):
                    $currentCategory = $plan['category'];
            ?>
                <div class="col-span-full">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">
                        <?php echo htmlspecialchars($currentCategory); ?>
                    </h2>
                </div>
            <?php endif; ?>
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 hover:shadow-md transition-shadow">
                    <div class="flex items-start justify-between mb-4">
                        <div class="flex-1">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                <?php echo htmlspecialchars($plan['plan_name'] ?? 'N/A'); ?>
                            </h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                <?php echo htmlspecialchars($plan['category'] ?? 'N/A'); ?>
                            </p>
                        </div>
                        <span class="px-2 py-1 text-xs font-medium bg-primary-100 dark:bg-primary-900/30 text-primary-800 dark:text-primary-400 rounded-full">
                            <?php echo number_format($plan['patient_count'] ?? 0); ?> patients
                        </span>
                    </div>
                    
                    <div class="space-y-3 text-sm">
                        <div>
                            <span class="font-medium text-gray-700 dark:text-gray-300">Calorie Range:</span>
                            <span class="text-gray-600 dark:text-gray-400">
                                <?php echo number_format($plan['calorie_range_low'] ?? 0); ?> - <?php echo number_format($plan['calorie_range_high'] ?? 0); ?> kcal
                            </span>
                        </div>
                        
                        <div>
                            <span class="font-medium text-gray-700 dark:text-gray-300">Allowed Food Groups:</span>
                            <div class="text-gray-600 dark:text-gray-400 mt-1">
                                <?php echo htmlspecialchars($plan['allowed_food_groups'] ?? 'N/A'); ?>
                            </div>
                        </div>
                        
                        <?php if ($plan['macro_carbs'] || $plan['macro_protein'] || $plan['macro_fats']): ?>
                            <div>
                                <span class="font-medium text-gray-700 dark:text-gray-300">Macro Targets:</span>
                                <div class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                                    Carbs: <?php echo $plan['macro_carbs'] ?? 'N/A'; ?>g | 
                                    Protein: <?php echo $plan['macro_protein'] ?? 'N/A'; ?>g | 
                                    Fats: <?php echo $plan['macro_fats'] ?? 'N/A'; ?>g
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($plan['restrictions']): ?>
                            <div>
                                <span class="font-medium text-gray-700 dark:text-gray-300">Restrictions:</span>
                                <div class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                                    <?php echo htmlspecialchars($plan['restrictions']); ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="flex gap-2 mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                        <button onclick="editPlan(<?php echo htmlspecialchars($plan['id']); ?>)" 
                                class="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-2 text-sm text-primary-600 hover:text-primary-700 font-medium" title="Edit"><?php echo icon_edit('w-4 h-4'); ?>Edit</button>
                        <form method="POST" class="inline" onsubmit="return confirm('Delete this diet plan?')">
                            <input type="hidden" name="action" value="delete_plan">
                            <input type="hidden" name="plan_id" value="<?php echo htmlspecialchars($plan['id']); ?>">
                            <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-2 text-sm text-red-600 hover:text-red-700 font-medium" title="Delete"><?php echo icon_delete('w-4 h-4'); ?>Delete</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Diet Plan Modal -->
<div id="planModal" class="hidden fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="fixed inset-0 bg-black bg-opacity-50" onclick="closePlanModal()"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-2xl w-full p-6 max-h-[90vh] overflow-y-auto">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4" id="planModalTitle">Create New Diet Plan</h3>
            <form method="POST" id="planForm">
                <input type="hidden" name="action" id="planAction" value="create_plan">
                <input type="hidden" name="plan_id" id="planId">
                
                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Plan Name *</label>
                            <input type="text" name="plan_name" id="planName" required
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Category *</label>
                            <select name="category" id="planCategory" required
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                                <option value="">Select Category</option>
                                <option value="Diabetic">Diabetic</option>
                                <option value="Cardiac">Cardiac</option>
                                <option value="Renal">Renal</option>
                                <option value="General">General</option>
                                <option value="Low Sodium">Low Sodium</option>
                                <option value="Soft/Liquid">Soft/Liquid</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Calorie Range Low *</label>
                            <input type="number" name="calorie_range_low" id="planCalLow" required min="0"
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Calorie Range High *</label>
                            <input type="number" name="calorie_range_high" id="planCalHigh" required min="0"
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Allowed Food Groups *</label>
                        <textarea name="allowed_food_groups" id="planFoodGroups" required rows="3"
                                  placeholder="e.g., Vegetables, Lean Proteins, Whole Grains, Fruits"
                                  class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"></textarea>
                    </div>
                    
                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Carbs (g)</label>
                            <input type="number" name="macro_carbs" id="planCarbs" min="0" step="0.1"
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Protein (g)</label>
                            <input type="number" name="macro_protein" id="planProtein" min="0" step="0.1"
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Fats (g)</label>
                            <input type="number" name="macro_fats" id="planFats" min="0" step="0.1"
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Restrictions</label>
                        <textarea name="restrictions" id="planRestrictions" rows="3"
                                  placeholder="e.g., No added sugar, Low sodium, No dairy"
                                  class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"></textarea>
                    </div>
                </div>
                
                <div class="flex gap-3 justify-end mt-6">
                    <button type="button" onclick="closePlanModal()" 
                            class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700">
                        Save Plan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let plansData = <?php echo json_encode($dietPlans); ?>;

function openPlanModal() {
    document.getElementById('planModal').classList.remove('hidden');
    document.getElementById('planModalTitle').textContent = 'Create New Diet Plan';
    document.getElementById('planAction').value = 'create_plan';
    document.getElementById('planId').value = '';
    document.getElementById('planForm').reset();
}

function closePlanModal() {
    document.getElementById('planModal').classList.add('hidden');
}

function editPlan(planId) {
    const plan = plansData.find(p => p.id == planId);
    if (!plan) return;
    
    document.getElementById('planModal').classList.remove('hidden');
    document.getElementById('planModalTitle').textContent = 'Edit Diet Plan';
    document.getElementById('planAction').value = 'update_plan';
    document.getElementById('planId').value = plan.id;
    document.getElementById('planName').value = plan.plan_name || '';
    document.getElementById('planCategory').value = plan.category || '';
    document.getElementById('planCalLow').value = plan.calorie_range_low || '';
    document.getElementById('planCalHigh').value = plan.calorie_range_high || '';
    document.getElementById('planFoodGroups').value = plan.allowed_food_groups || '';
    document.getElementById('planCarbs').value = plan.macro_carbs || '';
    document.getElementById('planProtein').value = plan.macro_protein || '';
    document.getElementById('planFats').value = plan.macro_fats || '';
    document.getElementById('planRestrictions').value = plan.restrictions || '';
}
</script>

<?php include __DIR__ . '/../../../includes/footer.php'; ?>

