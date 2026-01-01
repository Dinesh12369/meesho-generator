<?php
require_once 'config.php';

// Get user's generations
$generations = get_user_generations();
$remaining_credits = get_remaining_credits();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Image Generator | Ecom Master</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        @keyframes slide-up {
            from {
                transform: translateY(100px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        .animate-slide-up {
            animation: slide-up 0.3s ease-out;
        }
        #categoryList::-webkit-scrollbar {
            width: 8px;
        }
        #categoryList::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }
        #categoryList::-webkit-scrollbar-thumb {
            background: #9333ea;
            border-radius: 10px;
        }
        #categoryList::-webkit-scrollbar-thumb:hover {
            background: #7e22ce;
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen py-8 px-4 bg-gradient-to-br from-gray-50 to-blue-50">
        <div class="container max-w-7xl mx-auto">
            <!-- Credits Banner -->
            <div class="mb-6 bg-gradient-to-r from-yellow-400 via-orange-500 to-red-500 border-2 border-orange-400 rounded-2xl p-6 shadow-2xl">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <div class="bg-white p-3 rounded-xl">
                            <i data-lucide="zap" class="h-8 w-8 text-orange-600"></i>
                        </div>
                        <div>
                            <h2 class="text-2xl font-bold text-white mb-1">Demo Credits</h2>
                            <p class="text-white/90 text-sm">Try our image generator for free!</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="bg-white/20 backdrop-blur-sm rounded-xl px-6 py-3 border-2 border-white/30">
                            <p class="text-white/90 text-sm font-semibold mb-1">Remaining Credits</p>
                            <div class="flex items-center gap-2">
                                <span class="text-5xl font-bold text-white" id="creditsDisplay"><?php echo $remaining_credits; ?></span>
                                <span class="text-white/80 text-lg">/ <?php echo DEMO_CREDITS; ?></span>
                            </div>
                        </div>
                    </div>
                </div>
                <?php if ($remaining_credits <= 0): ?>
                <div class="mt-4 p-4 bg-white/90 rounded-xl border-2 border-white">
                    <p class="text-red-600 font-bold flex items-center gap-2">
                        <i data-lucide="alert-circle" class="h-5 w-5"></i>
                        No credits remaining! Contact us to get more credits.
                    </p>
                </div>
                <?php endif; ?>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Left Panel - Form -->
                <div class="lg:col-span-2">
                    <div class="bg-white border-2 border-gray-200 rounded-2xl p-8 shadow-xl">
                        <!-- Header -->
                        <div class="mb-8">
                            <div class="flex items-center gap-3 mb-3">
                                <div class="bg-gradient-to-r from-purple-600 to-blue-600 p-3 rounded-xl">
                                    <i data-lucide="image" class="h-6 w-6 text-white"></i>
                                </div>
                                <h1 class="text-3xl font-bold text-gray-900">Image Generator</h1>
                            </div>
                            <p class="text-gray-600">Create optimized product images with automatic gradient borders and promotional tags</p>
                        </div>

                        <!-- Form -->
                        <form id="generateForm" class="space-y-6">
                            <!-- Image Upload -->
                            <div class="bg-gradient-to-br from-blue-50 to-purple-50 border-2 border-blue-200 rounded-xl p-6">
                                <label class="block text-sm font-semibold mb-3 text-gray-900 flex items-center gap-2">
                                    <i data-lucide="upload" class="h-5 w-5 text-blue-600"></i>
                                    Upload Product Image
                                </label>
                                <div class="relative">
                                    <input type="file" id="imageFile" accept="image/*" class="hidden">
                                    <button type="button" id="uploadBtn"
                                        class="w-full px-6 py-4 border-3 border-dashed border-blue-300 rounded-xl bg-white hover:bg-blue-50 transition-all text-gray-700 font-medium flex items-center justify-center gap-3">
                                        <i data-lucide="upload-cloud" class="h-6 w-6 text-blue-600"></i>
                                        Click to upload image
                                    </button>
                                </div>
                                <div id="imagePreview" class="hidden mt-4 bg-white border-2 border-blue-200 rounded-xl p-4">
                                    <div class="flex items-center gap-4">
                                        <img id="previewImg" src="" alt="Preview" class="w-24 h-24 object-cover rounded-lg border-2 border-gray-200">
                                        <div class="flex-1">
                                            <p id="imageName" class="text-sm font-semibold text-gray-900"></p>
                                            <p id="imageSize" class="text-xs text-gray-500 mt-1"></p>
                                            <div class="mt-2 flex items-center gap-2">
                                                <span class="px-2 py-1 bg-green-100 text-green-700 text-xs font-medium rounded-full">✓ Ready</span>
                                            </div>
                                        </div>
                                        <button type="button" id="removeImageBtn"
                                            class="text-red-500 hover:text-red-700 hover:bg-red-50 p-2 rounded-lg transition-colors">
                                            <i data-lucide="trash-2" class="h-5 w-5"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Category Selection -->
                            <div>
                                <label class="block text-sm font-semibold mb-3 text-gray-900 flex items-center gap-2">
                                    <i data-lucide="tag" class="h-5 w-5 text-purple-600"></i>
                                    Select Categories <span class="text-gray-400 text-xs font-normal">(Max <?php echo MAX_CATEGORIES; ?>)</span>
                                </label>
                                <div class="relative">
                                    <button type="button" id="categoryDropdownBtn"
                                        class="w-full flex items-center justify-between border-2 border-gray-300 bg-white hover:border-purple-400 px-4 py-3 rounded-xl transition-all min-h-[50px]">
                                        <div id="selectedCategoriesDisplay" class="flex flex-wrap gap-2">
                                            <span class="text-gray-400">Select categories...</span>
                                        </div>
                                        <i data-lucide="chevron-down" class="h-5 w-5 text-gray-500 flex-shrink-0"></i>
                                    </button>
                                    <div id="categoryDropdown" class="hidden absolute z-20 w-full mt-2 bg-white border-2 border-gray-300 rounded-xl shadow-2xl max-h-96 overflow-hidden">
                                        <div class="p-3 sticky top-0 bg-white border-b-2 border-gray-200 z-10">
                                            <div class="relative">
                                                <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400"></i>
                                                <input type="text" id="categorySearch" placeholder="Search categories (saree, dress, kurti...)"
                                                    class="w-full pl-10 pr-3 py-2 border-2 border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                                            </div>
                                        </div>
                                        <div id="categoryList" class="py-2 overflow-y-auto max-h-80">
                                            <div class="px-4 py-8 text-sm text-gray-500 text-center">
                                                <i data-lucide="search" class="h-8 w-8 mx-auto mb-3 text-gray-300"></i>
                                                <p class="font-medium">Type to search categories...</p>
                                                <p class="text-xs mt-2 text-gray-400">Try: "saree", "dress", "kurti", etc.</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div id="selectedCategoriesList" class="mt-3 flex flex-wrap gap-2"></div>
                                <input type="hidden" id="selectedCategoryIds" name="category_ids" value="">
                            </div>

                            <!-- Number of Variants -->
                            <div>
                                <label class="block text-sm font-semibold mb-3 text-gray-900 flex items-center gap-2">
                                    <i data-lucide="copy" class="h-5 w-5 text-blue-600"></i>
                                    Number of Variants
                                </label>
                                <input type="number" id="numVariants" min="1" max="<?php echo MAX_VARIANTS_PER_GENERATION; ?>" value="50"
                                    class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-purple-500 text-lg font-semibold">
                                <p class="text-xs text-gray-500 mt-2 flex items-center gap-1">
                                    <i data-lucide="info" class="h-3 w-3"></i>
                                    Recommended: 50+ variants for better shipping rates
                                </p>
                            </div>

                            <!-- Generate Button -->
                            <div class="pt-6 border-t-2 border-gray-200">
                                <button type="submit" id="generateBtn"
                                    class="w-full bg-gradient-to-r from-purple-600 to-blue-600 text-white px-8 py-4 rounded-xl hover:from-purple-700 hover:to-blue-700 transition-all inline-flex items-center justify-center gap-3 font-bold text-lg shadow-xl hover:shadow-2xl transform hover:-translate-y-0.5 disabled:opacity-50 disabled:cursor-not-allowed"
                                    <?php echo $remaining_credits <= 0 ? 'disabled' : ''; ?>>
                                    <i data-lucide="sparkles" class="h-6 w-6"></i>
                                    <?php echo $remaining_credits > 0 ? 'Generate Images' : 'No Credits Available'; ?>
                                </button>
                                <p class="text-xs text-gray-500 text-center mt-3 font-medium">
                                    This will generate <span id="totalVariantsFooter" class="font-bold text-purple-600">50</span> optimized image variants
                                </p>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Right Panel - Settings -->
                <div class="space-y-6">
                    <!-- Auto Gradient Info -->
                    <div class="bg-gradient-to-br from-pink-500 via-purple-500 to-blue-500 border-2 border-purple-400 rounded-2xl p-6 shadow-xl">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="bg-white p-2 rounded-lg">
                                <i data-lucide="sparkles" class="h-5 w-5 text-purple-600"></i>
                            </div>
                            <h3 class="text-lg font-bold text-white">Auto Gradient Borders</h3>
                        </div>
                        <div class="bg-white/20 backdrop-blur-sm rounded-lg p-4 mb-4">
                            <p class="text-sm text-white font-medium mb-3">✨ Each image gets:</p>
                            <ul class="space-y-2 text-sm text-white">
                                <li class="flex items-center gap-2">
                                    <i data-lucide="check" class="h-4 w-4 flex-shrink-0"></i>
                                    <span>Random gradient combination</span>
                                </li>
                                <li class="flex items-center gap-2">
                                    <i data-lucide="check" class="h-4 w-4 flex-shrink-0"></i>
                                    <span>Random border width (5-30px)</span>
                                </li>
                                <li class="flex items-center gap-2">
                                    <i data-lucide="check" class="h-4 w-4 flex-shrink-0"></i>
                                    <span>Random direction</span>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <!-- Image Compression -->
                    <div class="bg-white border-2 border-gray-200 rounded-2xl p-6 shadow-xl">
                        <div class="flex items-center gap-3 mb-5">
                            <div class="bg-gradient-to-r from-orange-500 to-red-500 p-2 rounded-lg">
                                <i data-lucide="hard-drive" class="h-5 w-5 text-white"></i>
                            </div>
                            <h3 class="text-lg font-bold text-gray-900">Compression</h3>
                        </div>
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-sm font-semibold text-gray-700">Target Size:</span>
                            <span class="text-lg font-bold text-orange-600"><span id="targetSizeDisplay"><?php echo DEFAULT_IMAGE_SIZE_KB; ?></span> KB</span>
                        </div>
                        <input type="range" id="targetSize" min="<?php echo MIN_IMAGE_SIZE_KB; ?>" max="<?php echo MAX_IMAGE_SIZE_KB; ?>" value="<?php echo DEFAULT_IMAGE_SIZE_KB; ?>" step="50"
                            class="w-full h-3 bg-gray-200 rounded-lg appearance-none cursor-pointer accent-orange-600">
                        <div class="flex justify-between text-xs text-gray-500 mt-2">
                            <span><?php echo MIN_IMAGE_SIZE_KB; ?> KB</span>
                            <span class="font-semibold text-orange-600">Optimal</span>
                            <span><?php echo MAX_IMAGE_SIZE_KB; ?> KB</span>
                        </div>
                    </div>

                    <!-- Discount Tags -->
                    <div class="bg-gradient-to-br from-green-500 to-emerald-600 border-2 border-green-400 rounded-2xl p-6 shadow-xl">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="bg-white p-2 rounded-lg">
                                <i data-lucide="percent" class="h-5 w-5 text-green-600"></i>
                            </div>
                            <h3 class="text-lg font-bold text-white">Discount Tags</h3>
                        </div>
                        <label class="flex items-start gap-3 cursor-pointer group">
                            <input type="checkbox" id="addDiscountTag" checked
                                class="w-6 h-6 text-green-600 border-2 border-white rounded focus:ring-2 focus:ring-white mt-0.5">
                            <div class="text-white">
                                <span class="text-sm font-bold block mb-1">Add Sale Tags</span>
                                <p class="text-xs text-green-100">Randomly apply promotional badges</p>
                            </div>
                        </label>
                    </div>

                    <!-- Quick Stats -->
                    <div class="bg-gradient-to-br from-gray-800 to-gray-900 border-2 border-gray-700 rounded-2xl p-6 shadow-xl">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="bg-gradient-to-r from-purple-500 to-blue-500 p-2 rounded-lg">
                                <i data-lucide="bar-chart-3" class="h-5 w-5 text-white"></i>
                            </div>
                            <h3 class="text-lg font-bold text-white">Quick Stats</h3>
                        </div>
                        <div class="space-y-3">
                            <div class="flex items-center justify-between p-3 bg-white/10 rounded-lg">
                                <span class="text-sm text-gray-300">Total Variants</span>
                                <span class="text-lg font-bold text-white" id="statsVariants">50</span>
                            </div>
                            <div class="flex items-center justify-between p-3 bg-white/10 rounded-lg">
                                <span class="text-sm text-gray-300">Per Image</span>
                                <span class="text-sm font-bold text-green-400" id="statsSize">~<?php echo DEFAULT_IMAGE_SIZE_KB; ?> KB</span>
                            </div>
                            <div class="flex items-center justify-between p-3 bg-white/10 rounded-lg">
                                <span class="text-sm text-gray-300">Credits Left</span>
                                <span class="text-lg font-bold text-yellow-400" id="statsCredits"><?php echo $remaining_credits; ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Generations -->
            <?php if (!empty($generations)): ?>
            <div class="mt-8 bg-white border-2 border-gray-200 rounded-2xl p-8 shadow-xl">
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center gap-3">
                        <div class="bg-gradient-to-r from-purple-600 to-blue-600 p-2 rounded-lg">
                            <i data-lucide="history" class="h-6 w-6 text-white"></i>
                        </div>
                        <h2 class="text-2xl font-bold text-gray-900">Your Generations</h2>
                    </div>
                    <span class="px-4 py-2 bg-purple-100 text-purple-700 rounded-xl font-bold">
                        <?php echo count($generations); ?> Total
                    </span>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                    <?php foreach (array_slice($generations, 0, 6) as $gen_id => $gen): ?>
                    <div class="group border-2 border-gray-200 rounded-xl p-5 hover:shadow-xl hover:border-purple-300 transition-all bg-gradient-to-br from-white to-gray-50">
                        <div class="flex items-start justify-between mb-4">
                            <div class="flex-1 min-w-0">
                                <h3 class="font-bold text-gray-900 truncate flex items-center gap-2">
                                    <i data-lucide="image" class="h-4 w-4 text-purple-600"></i>
                                    <?php echo htmlspecialchars($gen['category_name'] ?? 'Product Images'); ?>
                                </h3>
                                <p class="text-xs text-gray-500 mt-1"><?php echo date('M d, Y H:i', strtotime($gen['timestamp'])); ?></p>
                            </div>
                            <span class="px-2 py-1 bg-green-100 text-green-700 text-xs font-bold rounded-full">NEW</span>
                        </div>
                        <div class="space-y-2 mb-4">
                            <div class="flex items-center gap-2 text-sm">
                                <i data-lucide="images" class="h-4 w-4 text-blue-600"></i>
                                <span class="text-gray-700 font-semibold"><?php echo $gen['num_variants']; ?> variants</span>
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <a href="viewer.php?id=<?php echo $gen_id; ?>"
                                class="flex-1 px-4 py-2.5 bg-gradient-to-r from-purple-600 to-blue-600 text-white rounded-lg hover:from-purple-700 hover:to-blue-700 transition-all text-center text-sm font-bold flex items-center justify-center gap-2 shadow-md">
                                <i data-lucide="eye" class="h-4 w-4"></i>
                                View
                            </a>
                            <button onclick="downloadGeneration('<?php echo $gen_id; ?>')"
                                class="px-3 py-2.5 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors shadow-md" title="Download All">
                                <i data-lucide="download" class="h-4 w-4 text-gray-700"></i>
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Progress Modal -->
    <div id="progressModal" class="hidden fixed inset-0 bg-black bg-opacity-80 z-50 flex items-center justify-center p-4 backdrop-blur-sm">
        <div class="bg-white rounded-2xl max-w-md w-full p-8 shadow-2xl">
            <div class="text-center">
                <div class="mb-6">
                    <div class="relative inline-block">
                        <div class="w-24 h-24 border-4 border-gray-200 border-t-purple-600 rounded-full animate-spin"></div>
                        <div class="absolute inset-0 flex items-center justify-center">
                            <i data-lucide="sparkles" class="h-10 w-10 text-purple-600 animate-pulse"></i>
                        </div>
                    </div>
                </div>
                <h3 class="text-2xl font-bold mb-2 text-gray-900">Generating Images...</h3>
                <p class="text-sm text-gray-600 mb-8">Please wait while we create your variants</p>
                <div class="space-y-5">
                    <div>
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-sm font-semibold text-gray-700">Images Generated</span>
                            <span class="text-sm font-bold text-purple-600">
                                <span id="imagesGenerated">0</span> / <span id="totalImages">0</span>
                            </span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-4 overflow-hidden shadow-inner">
                            <div id="imagesProgressBar" class="bg-gradient-to-r from-purple-600 to-blue-600 h-4 rounded-full transition-all duration-500 shadow-md" style="width: 0%"></div>
                        </div>
                    </div>
                    <div class="mt-6 p-4 bg-blue-50 border-2 border-blue-200 rounded-xl">
                        <div class="flex items-start gap-3">
                            <i data-lucide="loader" class="h-5 w-5 text-blue-600 flex-shrink-0 mt-0.5 animate-spin"></i>
                            <div class="text-left">
                                <p id="currentStatus" class="text-sm font-semibold text-blue-900">Starting generation...</p>
                                <p id="currentSubStatus" class="text-xs text-blue-700 mt-1"></p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="mt-6 p-4 bg-yellow-50 border-2 border-yellow-300 rounded-xl">
                    <p class="text-xs text-yellow-800 font-medium flex items-center gap-2">
                        <i data-lucide="alert-triangle" class="h-4 w-4"></i>
                        Please don't close this window.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script src="script.js"></script>
    <script>
        lucide.createIcons();
    </script>
</body>
</html>