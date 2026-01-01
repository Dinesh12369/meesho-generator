<?php
require_once 'config.php';

$generation_id = $_GET['id'] ?? null;

if (!$generation_id) {
    header('Location: index.php');
    exit;
}

$generations = get_user_generations();
$generation = $generations[$generation_id] ?? null;

if (!$generation) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Generation | Ecom Master</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen py-8 px-4">
        <div class="container max-w-7xl mx-auto">
            <!-- Header -->
            <div class="bg-white border-2 border-gray-200 rounded-2xl p-6 shadow-xl mb-8">
                <div class="flex items-center justify-between flex-wrap gap-4">
                    <div class="flex items-center gap-4">
                        <a href="index.php" class="p-2 hover:bg-gray-100 rounded-lg transition-colors">
                            <i data-lucide="arrow-left" class="h-6 w-6 text-gray-700"></i>
                        </a>
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900"><?php echo htmlspecialchars($generation['category_name']); ?></h1>
                            <p class="text-sm text-gray-500 mt-1">
                                Generated on <?php echo date('M d, Y H:i', strtotime($generation['timestamp'])); ?>
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="px-4 py-2 bg-purple-100 text-purple-700 rounded-xl font-bold">
                            <?php echo count($generation['generated_images']); ?> Images
                        </div>
                        <a href="download.php?id=<?php echo $generation_id; ?>" 
                           class="px-6 py-3 bg-gradient-to-r from-purple-600 to-blue-600 text-white rounded-xl hover:from-purple-700 hover:to-blue-700 transition-all font-bold flex items-center gap-2 shadow-lg">
                            <i data-lucide="download" class="h-5 w-5"></i>
                            Download All
                        </a>
                    </div>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-gradient-to-br from-purple-500 to-blue-500 rounded-2xl p-6 text-white shadow-xl">
                    <div class="flex items-center gap-3 mb-2">
                        <i data-lucide="images" class="h-8 w-8"></i>
                        <h3 class="text-lg font-bold">Total Variants</h3>
                    </div>
                    <p class="text-4xl font-bold"><?php echo $generation['num_variants']; ?></p>
                </div>
                
                <div class="bg-gradient-to-br from-green-500 to-emerald-500 rounded-2xl p-6 text-white shadow-xl">
                    <div class="flex items-center gap-3 mb-2">
                        <i data-lucide="hard-drive" class="h-8 w-8"></i>
                        <h3 class="text-lg font-bold">Avg Size</h3>
                    </div>
                    <p class="text-4xl font-bold">
                        <?php 
                        $avg_size = array_sum(array_column($generation['generated_images'], 'size_kb')) / count($generation['generated_images']);
                        echo round($avg_size, 1); 
                        ?> KB
                    </p>
                </div>
                
                <div class="bg-gradient-to-br from-orange-500 to-red-500 rounded-2xl p-6 text-white shadow-xl">
                    <div class="flex items-center gap-3 mb-2">
                        <i data-lucide="tag" class="h-8 w-8"></i>
                        <h3 class="text-lg font-bold">Categories</h3>
                    </div>
                    <p class="text-xl font-bold truncate">
                        <?php echo implode(', ', array_slice($generation['category_names'], 0, 2)); ?>
                        <?php if (count($generation['category_names']) > 2): ?>
                            +<?php echo count($generation['category_names']) - 2; ?>
                        <?php endif; ?>
                    </p>
                </div>
            </div>

            <!-- Image Grid -->
            <div class="bg-white border-2 border-gray-200 rounded-2xl p-8 shadow-xl">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-2xl font-bold text-gray-900 flex items-center gap-3">
                        <i data-lucide="grid-3x3" class="h-6 w-6 text-purple-600"></i>
                        Generated Images
                    </h2>
                    <div class="flex items-center gap-2">
                        <button onclick="selectAll()" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 rounded-lg font-semibold text-sm transition-colors">
                            Select All
                        </button>
                        <button onclick="deselectAll()" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 rounded-lg font-semibold text-sm transition-colors">
                            Deselect All
                        </button>
                    </div>
                </div>
                
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
                    <?php foreach ($generation['generated_images'] as $img): ?>
                    <div class="group relative border-2 border-gray-200 rounded-xl overflow-hidden hover:border-purple-400 transition-all hover:shadow-lg">
                        <div class="aspect-square">
                            <img src="<?php echo htmlspecialchars($img['path']); ?>" 
                                 alt="Variant <?php echo $img['variant_number']; ?>"
                                 class="w-full h-full object-cover">
                        </div>
                        <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-40 transition-all flex items-center justify-center opacity-0 group-hover:opacity-100">
                            <div class="flex gap-2">
                                <a href="<?php echo htmlspecialchars($img['path']); ?>" 
                                   download="<?php echo $img['filename']; ?>"
                                   class="p-3 bg-white rounded-full hover:bg-gray-100 transition-colors">
                                    <i data-lucide="download" class="h-5 w-5 text-gray-700"></i>
                                </a>
                                <button onclick="viewImage('<?php echo htmlspecialchars($img['path']); ?>')"
                                        class="p-3 bg-white rounded-full hover:bg-gray-100 transition-colors">
                                    <i data-lucide="eye" class="h-5 w-5 text-gray-700"></i>
                                </button>
                            </div>
                        </div>
                        <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black to-transparent p-3">
                            <div class="flex items-center justify-between text-white text-xs">
                                <span class="font-bold">#<?php echo $img['variant_number']; ?></span>
                                <span><?php echo $img['size_kb']; ?> KB</span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Image Viewer Modal -->
    <div id="imageModal" class="hidden fixed inset-0 bg-black bg-opacity-90 z-50 flex items-center justify-center p-4" onclick="closeModal()">
        <div class="relative max-w-5xl w-full" onclick="event.stopPropagation()">
            <button onclick="closeModal()" class="absolute top-4 right-4 p-2 bg-white rounded-full hover:bg-gray-100">
                <i data-lucide="x" class="h-6 w-6 text-gray-700"></i>
            </button>
            <img id="modalImage" src="" alt="Full view" class="w-full h-auto rounded-xl shadow-2xl">
        </div>
    </div>

    <script>
        lucide.createIcons();

        function selectAll() {
            // Implementation for select all
            alert('Select all feature - coming soon!');
        }

        function deselectAll() {
            // Implementation for deselect all
            alert('Deselect all feature - coming soon!');
        }

        function viewImage(imagePath) {
            document.getElementById('modalImage').src = imagePath;
            document.getElementById('imageModal').classList.remove('hidden');
            lucide.createIcons();
        }

        function closeModal() {
            document.getElementById('imageModal').classList.add('hidden');
        }

        // Close modal on Escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                closeModal();
            }
        });
    </script>
</body>
</html>
