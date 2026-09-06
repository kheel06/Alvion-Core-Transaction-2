<?php
require_once __DIR__ . '/../../../config/config.php';
requireAuth();
checkRole(['admin', 'super admin']);

$page_title = 'Meal Schedules - DNMS';

// Handle form submissions
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'create_template':
                    $stmt = $db->prepare("INSERT INTO meal_templates (meal_name, meal_type, scheduled_time, food_items, portion_sizes, calories, protein, carbs, fats) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([
                        $_POST['meal_name'],
                        $_POST['meal_type'],
                        $_POST['scheduled_time'],
                        $_POST['food_items'],
                        $_POST['portion_sizes'],
                        $_POST['calories'] ?? 0,
                        $_POST['protein'] ?? 0,
                        $_POST['carbs'] ?? 0,
                        $_POST['fats'] ?? 0
                    ]);
                    $message = 'Meal template created successfully';
                    $messageType = 'success';
                    break;
                
                case 'update_template':
                    $stmt = $db->prepare("UPDATE meal_templates SET meal_name = ?, meal_type = ?, scheduled_time = ?, food_items = ?, portion_sizes = ?, calories = ?, protein = ?, carbs = ?, fats = ? WHERE id = ?");
                    $stmt->execute([
                        $_POST['meal_name'],
                        $_POST['meal_type'],
                        $_POST['scheduled_time'],
                        $_POST['food_items'],
                        $_POST['portion_sizes'],
                        $_POST['calories'] ?? 0,
                        $_POST['protein'] ?? 0,
                        $_POST['carbs'] ?? 0,
                        $_POST['fats'] ?? 0,
                        $_POST['template_id']
                    ]);
                    $message = 'Meal template updated successfully';
                    $messageType = 'success';
                    break;
                
                case 'copy_to_week':
                    // Copy selected meals to all days of the week
                    $selectedMeals = $_POST['selected_meals'] ?? [];
                    if (!empty($selectedMeals)) {
                        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                        foreach ($days as $day) {
                            foreach ($selectedMeals as $mealId) {
                                $stmt = $db->prepare("INSERT INTO meal_templates (meal_name, meal_type, scheduled_time, food_items, portion_sizes, calories, protein, carbs, fats, day_of_week) 
                                                      SELECT meal_name, meal_type, scheduled_time, food_items, portion_sizes, calories, protein, carbs, fats, ? 
                                                      FROM meal_templates WHERE id = ?");
                                $stmt->execute([$day, $mealId]);
                            }
                        }
                        $message = 'Meals copied to all days of the week';
                        $messageType = 'success';
                    }
                    break;
            }
        }
    } catch (PDOException $e) {
        $message = 'Error: ' . $e->getMessage();
        $messageType = 'error';
    }
}

// Fetch meal templates grouped by meal type
$mealTemplates = [];
$mealTypes = ['Breakfast', 'Lunch', 'Dinner', 'Snack'];

try {
    $checkTable = $db->query("SHOW TABLES LIKE 'meal_templates'");
    
    if ($checkTable && $checkTable->rowCount() > 0) {
        $stmt = $db->query("SELECT * FROM meal_templates ORDER BY 
                            CASE meal_type 
                                WHEN 'Breakfast' THEN 1 
                                WHEN 'Lunch' THEN 2 
                                WHEN 'Dinner' THEN 3 
                                ELSE 4 
                            END, scheduled_time");
        $allMeals = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Group by meal type
        foreach ($allMeals as $meal) {
            $type = $meal['meal_type'] ?? 'Other';
            if (!isset($mealTemplates[$type])) {
                $mealTemplates[$type] = [];
            }
            $mealTemplates[$type][] = $meal;
        }
    }
} catch (PDOException $e) {
    error_log('DNMS Meal Schedules error: ' . $e->getMessage());
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
                    <li><span class="text-gray-700 dark:text-gray-200">Meal Schedules</span></li>
                </ol>
            </nav>
            <h1 class="text-2xl font-semibold tracking-tight text-gray-900 dark:text-white">
                Meal Schedules
            </h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Manage daily meal templates with timeline view, nutritional information, and meal swapping options.
            </p>
        </div>
        <button onclick="openMealModal()" class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 font-medium flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
            Create Meal Template
        </button>
    </div>

    <?php if ($message): ?>
        <div class="rounded-md p-4 <?php echo $messageType === 'success' ? 'bg-green-50 dark:bg-green-900/20 text-green-800 dark:text-green-200' : 'bg-red-50 dark:bg-red-900/20 text-red-800 dark:text-red-200'; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <!-- Daily Timeline View -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Daily Meal Timeline</h2>
        
        <div class="space-y-6">
            <?php foreach ($mealTypes as $type): 
                $timeRange = [
                    'Breakfast' => '7:00 AM - 9:00 AM',
                    'Lunch' => '12:00 PM - 2:00 PM',
                    'Dinner' => '6:00 PM - 8:00 PM',
                    'Snack' => '10:00 AM / 3:00 PM'
                ];
                $meals = $mealTemplates[$type] ?? [];
            ?>
                <div class="border-l-4 border-primary-500 pl-4">
                    <div class="flex items-center justify-between mb-3">
                        <div>
                            <h3 class="text-base font-semibold text-gray-900 dark:text-white">
                                <?php echo htmlspecialchars($type); ?>
                            </h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                <?php echo $timeRange[$type] ?? 'Flexible'; ?>
                            </p>
                        </div>
                        <span class="px-2 py-1 text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-full">
                            <?php echo count($meals); ?> templates
                        </span>
                    </div>
                    
                    <?php if (empty($meals)): ?>
                        <p class="text-sm text-gray-500 dark:text-gray-400 italic">No meal templates for <?php echo strtolower($type); ?></p>
                    <?php else: ?>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mt-3">
                            <?php foreach ($meals as $meal): ?>
                                <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4 border border-gray-200 dark:border-gray-600">
                                    <div class="flex items-start justify-between mb-2">
                                        <div class="flex-1">
                                            <h4 class="font-medium text-gray-900 dark:text-white">
                                                <?php echo htmlspecialchars($meal['meal_name'] ?? 'N/A'); ?>
                                            </h4>
                                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                                <?php echo htmlspecialchars($meal['scheduled_time'] ?? 'N/A'); ?>
                                            </p>
                                        </div>
                                        <input type="checkbox" class="meal-checkbox" value="<?php echo htmlspecialchars($meal['id']); ?>" 
                                               onchange="updateSelectedMeals()">
                                    </div>
                                    
                                    <div class="text-xs text-gray-600 dark:text-gray-400 mb-2">
                                        <div class="font-medium mb-1">Food Items:</div>
                                        <div><?php echo htmlspecialchars($meal['food_items'] ?? 'N/A'); ?></div>
                                    </div>
                                    
                                    <div class="text-xs text-gray-600 dark:text-gray-400 mb-2">
                                        <div class="font-medium mb-1">Portion Sizes:</div>
                                        <div><?php echo htmlspecialchars($meal['portion_sizes'] ?? 'N/A'); ?></div>
                                    </div>
                                    
                                    <div class="grid grid-cols-4 gap-2 text-xs pt-2 border-t border-gray-200 dark:border-gray-600">
                                        <div>
                                            <div class="text-gray-500 dark:text-gray-400">Cal</div>
                                            <div class="font-medium text-gray-900 dark:text-white"><?php echo number_format($meal['calories'] ?? 0); ?></div>
                                        </div>
                                        <div>
                                            <div class="text-gray-500 dark:text-gray-400">Protein</div>
                                            <div class="font-medium text-gray-900 dark:text-white"><?php echo number_format($meal['protein'] ?? 0, 1); ?>g</div>
                                        </div>
                                        <div>
                                            <div class="text-gray-500 dark:text-gray-400">Carbs</div>
                                            <div class="font-medium text-gray-900 dark:text-white"><?php echo number_format($meal['carbs'] ?? 0, 1); ?>g</div>
                                        </div>
                                        <div>
                                            <div class="text-gray-500 dark:text-gray-400">Fats</div>
                                            <div class="font-medium text-gray-900 dark:text-white"><?php echo number_format($meal['fats'] ?? 0, 1); ?>g</div>
                                        </div>
                                    </div>
                                    
                                    <div class="flex gap-2 mt-3 pt-3 border-t border-gray-200 dark:border-gray-600">
                                        <button onclick="editMeal(<?php echo htmlspecialchars($meal['id']); ?>)" 
                                                class="flex-1 inline-flex items-center justify-center gap-1 px-2 py-1 text-xs text-primary-600 hover:text-primary-700 font-medium" title="Edit"><?php echo icon_edit('w-3.5 h-3.5'); ?>Edit</button>
                                        <button onclick="findSimilarMeals(<?php echo htmlspecialchars($meal['id']); ?>)" 
                                                class="flex-1 px-2 py-1 text-xs text-blue-600 hover:text-blue-700 font-medium">Swap</button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Copy to Week Button -->
        <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
            <form method="POST" id="copyToWeekForm" onsubmit="return confirm('Copy selected meals to all days of the week?')">
                <input type="hidden" name="action" value="copy_to_week">
                <input type="hidden" name="selected_meals" id="selectedMealsInput">
                <button type="submit" id="copyToWeekBtn" disabled
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="14" height="14" x="8" y="8" rx="2" ry="2"></rect><path d="M4 16c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2h8c1.1 0 2 .9 2 2"></path></svg>
                    Copy Selected to Week
                </button>
            </form>
        </div>
    </div>
</div>

<!-- Meal Template Modal -->
<div id="mealModal" class="hidden fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="fixed inset-0 bg-black bg-opacity-50" onclick="closeMealModal()"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-2xl w-full p-6 max-h-[90vh] overflow-y-auto">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4" id="mealModalTitle">Create Meal Template</h3>
            <form method="POST" id="mealForm">
                <input type="hidden" name="action" id="mealAction" value="create_template">
                <input type="hidden" name="template_id" id="templateId">
                
                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Meal Name *</label>
                            <input type="text" name="meal_name" id="mealName" required
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Meal Type *</label>
                            <select name="meal_type" id="mealType" required
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                                <option value="">Select Type</option>
                                <option value="Breakfast">Breakfast</option>
                                <option value="Lunch">Lunch</option>
                                <option value="Dinner">Dinner</option>
                                <option value="Snack">Snack</option>
                            </select>
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Scheduled Time *</label>
                        <input type="text" name="scheduled_time" id="mealTime" required
                               placeholder="e.g., 7:00 AM - 9:00 AM"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Food Items *</label>
                        <textarea name="food_items" id="mealFoodItems" required rows="3"
                                  placeholder="e.g., Grilled chicken breast, Steamed broccoli, Brown rice"
                                  class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"></textarea>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Portion Sizes *</label>
                        <textarea name="portion_sizes" id="mealPortions" required rows="2"
                                  placeholder="e.g., 150g chicken, 100g broccoli, 80g rice"
                                  class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"></textarea>
                    </div>
                    
                    <div class="grid grid-cols-4 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Calories</label>
                            <input type="number" name="calories" id="mealCalories" min="0" step="1"
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Protein (g)</label>
                            <input type="number" name="protein" id="mealProtein" min="0" step="0.1"
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Carbs (g)</label>
                            <input type="number" name="carbs" id="mealCarbs" min="0" step="0.1"
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Fats (g)</label>
                            <input type="number" name="fats" id="mealFats" min="0" step="0.1"
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                        </div>
                    </div>
                </div>
                
                <div class="flex gap-3 justify-end mt-6">
                    <button type="button" onclick="closeMealModal()" 
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

<!-- Similar Meals Modal -->
<div id="similarMealsModal" class="hidden fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="fixed inset-0 bg-black bg-opacity-50" onclick="closeSimilarMealsModal()"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-3xl w-full p-6 max-h-[90vh] overflow-y-auto">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Similar Meals (Similar Calories/Protein)</h3>
            <div id="similarMealsList" class="space-y-3">
                <!-- Similar meals will be loaded here -->
            </div>
            <div class="flex justify-end mt-6">
                <button onclick="closeSimilarMealsModal()" 
                        class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>

<script>
let mealsData = <?php echo json_encode(array_merge(...array_values($mealTemplates))); ?>;

function openMealModal() {
    document.getElementById('mealModal').classList.remove('hidden');
    document.getElementById('mealModalTitle').textContent = 'Create Meal Template';
    document.getElementById('mealAction').value = 'create_template';
    document.getElementById('templateId').value = '';
    document.getElementById('mealForm').reset();
}

function closeMealModal() {
    document.getElementById('mealModal').classList.add('hidden');
}

function editMeal(templateId) {
    const meal = mealsData.find(m => m.id == templateId);
    if (!meal) return;
    
    document.getElementById('mealModal').classList.remove('hidden');
    document.getElementById('mealModalTitle').textContent = 'Edit Meal Template';
    document.getElementById('mealAction').value = 'update_template';
    document.getElementById('templateId').value = meal.id;
    document.getElementById('mealName').value = meal.meal_name || '';
    document.getElementById('mealType').value = meal.meal_type || '';
    document.getElementById('mealTime').value = meal.scheduled_time || '';
    document.getElementById('mealFoodItems').value = meal.food_items || '';
    document.getElementById('mealPortions').value = meal.portion_sizes || '';
    document.getElementById('mealCalories').value = meal.calories || '';
    document.getElementById('mealProtein').value = meal.protein || '';
    document.getElementById('mealCarbs').value = meal.carbs || '';
    document.getElementById('mealFats').value = meal.fats || '';
}

function updateSelectedMeals() {
    const checkboxes = document.querySelectorAll('.meal-checkbox:checked');
    const selectedIds = Array.from(checkboxes).map(cb => cb.value);
    document.getElementById('selectedMealsInput').value = JSON.stringify(selectedIds);
    document.getElementById('copyToWeekBtn').disabled = selectedIds.length === 0;
}

function findSimilarMeals(mealId) {
    const meal = mealsData.find(m => m.id == mealId);
    if (!meal) return;
    
    const calories = parseFloat(meal.calories) || 0;
    const protein = parseFloat(meal.protein) || 0;
    
    // Find meals with similar calories (±50) and protein (±10g)
    const similar = mealsData.filter(m => {
        if (m.id == mealId) return false;
        const mCal = parseFloat(m.calories) || 0;
        const mProt = parseFloat(m.protein) || 0;
        return Math.abs(mCal - calories) <= 50 && Math.abs(mProt - protein) <= 10;
    });
    
    const listEl = document.getElementById('similarMealsList');
    if (similar.length === 0) {
        listEl.innerHTML = '<p class="text-gray-500 dark:text-gray-400">No similar meals found.</p>';
    } else {
        listEl.innerHTML = similar.map(m => `
            <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4 border border-gray-200 dark:border-gray-600">
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <h4 class="font-medium text-gray-900 dark:text-white">${m.meal_name || 'N/A'}</h4>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">${m.meal_type || 'N/A'} - ${m.scheduled_time || 'N/A'}</p>
                        <p class="text-xs text-gray-600 dark:text-gray-400 mt-2">${m.food_items || 'N/A'}</p>
                    </div>
                    <div class="text-xs text-right ml-4">
                        <div>Cal: ${parseFloat(m.calories || 0).toFixed(0)}</div>
                        <div>Prot: ${parseFloat(m.protein || 0).toFixed(1)}g</div>
                    </div>
                </div>
            </div>
        `).join('');
    }
    
    document.getElementById('similarMealsModal').classList.remove('hidden');
}

function closeSimilarMealsModal() {
    document.getElementById('similarMealsModal').classList.add('hidden');
}
</script>

<?php include __DIR__ . '/../../../includes/footer.php'; ?>

